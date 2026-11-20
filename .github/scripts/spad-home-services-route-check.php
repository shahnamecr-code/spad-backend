<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

$routes = $app['router']->getRoutes();
$policies = [
    ['GET', '/api/online-renovation/scans', 'scan', true],
    ['GET', '/api/home-doctor/categories', 'doctor', false],
    ['POST', '/api/home-doctor/cases', 'doctor', true],
    ['GET', '/api/neighbors/posts', 'neighbors', false],
    ['POST', '/api/neighbors/posts', 'neighbors', true],
    ['GET', '/api/home-league', 'league', false],
    ['POST', '/api/home-league/sample/entries/1/vote', 'league', true],
];
foreach ($policies as [$method, $uri, $service, $authenticated]) {
    $request = Illuminate\Http\Request::create($uri, $method);
    $route = $routes->match($request);
    $middleware = $route->gatherMiddleware();
    $labels = array_values(array_filter($middleware, static function ($name) {
        return strpos($name, 'auth:') === 0;
    }));
    if (!in_array(App\Http\Middleware\SpadHomeService::class . ':' . $service, $middleware, true) ||
        ($authenticated ? !in_array('auth:sanctum', $labels, true) : $labels !== [])) {
        throw new RuntimeException('Unexpected access rules for ' . $method . ' ' . $uri);
    }
}
$admin = $routes->match(Illuminate\Http\Request::create('/api/admin/home-services', 'GET'));
if (!in_array(App\Http\Middleware\SpadHomeServiceAdministrator::class, $admin->gatherMiddleware(), true)) {
    throw new RuntimeException('House service settings lack the established administration permission');
}
echo "SPAD_HOME_SERVICE_ROUTE_POLICIES=VERIFIED\n";
