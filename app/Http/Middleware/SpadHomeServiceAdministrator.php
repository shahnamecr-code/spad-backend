<?php

namespace App\Http\Middleware;

use Closure;

class SpadHomeServiceAdministrator
{
    public function handle($request, Closure $next)
    {
        $admin = auth('admin')->user();
        abort_unless($admin && $admin->roles()->where('name', 'Super Admin')->exists(), 403);
        return $next($request);
    }
}
