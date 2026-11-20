<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

function marketRequest($app, string $path, string $method = 'GET'): Illuminate\Http\Request
{
    $request = Illuminate\Http\Request::create($path, $method, [], [], [], ['REMOTE_ADDR' => '192.0.2.23']);
    $route = $app['router']->getRoutes()->match($request);
    $request->setRouteResolver(static function () use ($route) {
        return $route;
    });
    return $request;
}

function requireMarketPolicy($condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$default = Illuminate\Support\Facades\RateLimiter::limiter('api');
$dedicated = Illuminate\Support\Facades\RateLimiter::limiter('spad-property-market');
requireMarketPolicy(is_callable($default) && is_callable($dedicated), 'Missing limiter');
$summary = marketRequest($app, '/api/property-market/summary');
$children = marketRequest($app, '/api/property-market/places/1/children');
foreach ([$summary, $children] as $request) {
    $middleware = $request->route()->middleware();
    requireMarketPolicy(in_array('throttle:spad-property-market', $middleware, true), 'Missing market middleware');
    requireMarketPolicy(!in_array('throttle:40,1', $middleware, true), 'Inherited cross-service numeric limiter');
    requireMarketPolicy($default($request) instanceof Illuminate\Cache\RateLimiting\Unlimited, 'Market inherited unrelated request allowance');
}

$summaryLimit = $dedicated($summary);
$childrenLimit = $dedicated($children);
requireMarketPolicy($summaryLimit->maxAttempts === 180 && $childrenLimit->maxAttempts === 180, 'Market limiter ceiling changed');
requireMarketPolicy($summaryLimit->key !== $childrenLimit->key, 'Summary and children share one allowance');
$otherPlace = marketRequest($app, '/api/property-market/places/2/children');
requireMarketPolicy($dedicated($otherPlace)->key === $childrenLimit->key, 'Changing place IDs creates new allowances');
$limited = call_user_func($summaryLimit->responseCallback, $summary, ['Retry-After' => '29']);
requireMarketPolicy($limited->getStatusCode() === 429 && $limited->headers->get('Retry-After') === '29', 'Missing 429 retry signal');
requireMarketPolicy($limited->getData()->code === 'property_market_busy', 'Missing localized market error');

$ordinary = marketRequest($app, '/api/app/config');
requireMarketPolicy($default($ordinary)->maxAttempts === 60 && $default($ordinary)->key !== $summaryLimit->key, 'Regular API allowance changed');
$postal = marketRequest($app, '/api/property-valuation/postal-lookup', 'POST');
requireMarketPolicy(in_array('auth:sanctum', $postal->route()->middleware(), true), 'Missing valuation authentication');
requireMarketPolicy(in_array('throttle:spad-property-postal-lookup', $postal->route()->middleware(), true), 'Missing postal allowance');

echo "SPAD_PUBLIC_MARKET_POLICY=VERIFIED\n";
