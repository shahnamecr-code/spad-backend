<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SpadHomeService
{
    public function handle(Request $request, Closure $next, string $service)
    {
        $personalData = '~^api/(?:home-doctor/cases/[a-f0-9-]{36}(?:/media/[a-f0-9-]{36})?'
            . '|online-renovation/scans/[0-9]+(?:/photos/[0-9]+)?'
            . '|home-energy/(?:locations|accounts|bills)/[0-9]+(?:/receipt)?'
            . '|home-sunlight/projects/[0-9]+'
            . '|home-book/(?:homes|devices)/[0-9]+(?:/(?:documents|maintenance)/[0-9]+)?'
            . '|home-garden/plants/[a-f0-9-]{36}/photos/[a-f0-9-]{36}'
            . '|smart-home/connection)$~Di';
        if ($request->isMethod('DELETE') && preg_match($personalData, $request->path())) {
            return $next($request);
        }

        $settings = DB::table('spad_home_service_settings')->where('service', $service)->first();
        if (!$settings || !$settings->enabled) {
            return response()->json(['message' => 'این خدمت موقتاً در دسترس نیست.'], 503);
        }

        if (!$request->isMethodSafe()) {
            if (!$settings->writes_enabled) {
                return response()->json(['message' => 'ثبت و ویرایش در این خدمت موقتاً متوقف است.'], 503);
            }
            $user = $request->user('sanctum');
            $key = 'spad-home-service:' . $service . ':' . ($user ? 'user-' . $user->getAuthIdentifier() : $request->ip());
            if (RateLimiter::tooManyAttempts($key, (int) $settings->writes_per_minute)) {
                return response()->json(
                    ['message' => 'تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.'],
                    429,
                    ['Retry-After' => (string) RateLimiter::availableIn($key)]
                );
            }
            RateLimiter::hit($key, 60);
        }

        $started = microtime(true);
        $response = $next($request);
        // Count operations in the product without collecting media identifiers,
        // content, credentials, or any anonymous device fingerprint.
        if (!$request->isMethod('HEAD') && !preg_match('~/(?:photos|media)/[^/]+$~', $request->path())) {
            try {
                $user = $request->user('sanctum');
                $keys = ['analysis', 'vote', 'report', 'comments', 'entries', 'feedback'];
                $tail = basename($request->path());
                $event = $request->isMethodSafe() ? 'read' : (in_array($tail, $keys, true) ? $tail : 'write');
                DB::table('spad_home_service_events')->insert([
                    'service' => $service,
                    'event' => $event,
                    'actor_hash' => $user ? hash_hmac('sha256', $service . ':' . $user->getAuthIdentifier(), config('app.key')) : null,
                    'http_status' => $response->getStatusCode(),
                    'duration_ms' => min(4294967295, (int) round((microtime(true) - $started) * 1000)),
                    'created_at' => now(),
                ]);
            } catch (Throwable $error) {
                // Telemetry must never cause a completed customer action to be retried.
                logger()->warning('SPAD home service operational counter unavailable', ['service' => $service]);
            }
        }
        return $response;
    }
}
