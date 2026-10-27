<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePartnerSession
{
    public function handle(Request $request, Closure $next)
    {
        $accessToken = optional($request->user())->currentAccessToken();

        if (!$accessToken || ($accessToken->name ?? null) !== 'partner-token') {
            return response()->json([
                'status' => false,
                'message' => 'برای دسترسی به پنل همکاری باید از صفحه ورود همکاران وارد شوید.',
            ], 403);
        }

        return $next($request);
    }
}
