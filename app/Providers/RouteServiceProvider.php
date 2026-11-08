<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            // Sanctum resolves the user after the global API throttle on many
            // installations. For the authenticated seller submit endpoint,
            // never place all customers behind a shared reverse-proxy IP bucket.
            if ($request->is('api/property-acceptance/submit') && $request->bearerToken()) {
                $credentialKey = hash_hmac('sha256', $request->bearerToken(), config('app.key'));
                return [
                    Limit::perMinute(240)->by('spad-acceptance-ip:' . $request->ip()),
                    Limit::perMinute(60)->by('spad-acceptance-api:' . $credentialKey),
                ];
            }

            // Postal lookup precedes Sanctum's user resolution in the global
            // API middleware. Do not share a 60/minute counter across all
            // customers behind one gateway or NAT address.
            if ($request->is('api/property-valuation/postal-lookup') && $request->bearerToken()) {
                $credentialKey = hash_hmac('sha256', $request->bearerToken(), config('app.key'));
                return [
                    Limit::perMinute(180)->by('spad-postal-ip:' . $request->ip()),
                    Limit::perMinute(40)->by('spad-postal-api:' . $credentialKey),
                ];
            }
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Cached postal lookups should remain easy to access. Billable new
        // lookups are separately budgeted inside PropertyValuationController.
        RateLimiter::for('spad-property-postal-lookup', function (Request $request) {
            $user = $request->user();
            $credential = $request->bearerToken();
            $identity = $user
                ? 'user:' . $user->id
                : ($credential ? 'token:' . hash_hmac('sha256', $credential, config('app.key')) : 'ip:' . $request->ip());
            return Limit::perMinute(30)->by('spad-postal-route:' . $identity);
        });

        // A separate, strictly limited authenticated submit counter; retries
        // remain controlled without blocking unrelated users behind one IP.
        RateLimiter::for('spad-property-acceptance-submit', function (Request $request) {
            $bearer = $request->bearerToken();
            $identity = $bearer
                ? 'token:' . hash_hmac('sha256', $bearer, config('app.key'))
                : 'ip:' . $request->ip();
            return Limit::perMinute(10)->by('spad-acceptance-submit:' . $identity);
        });

    }
}
