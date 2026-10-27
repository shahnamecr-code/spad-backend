<?php

namespace App\Http\Middleware;

use Closure;

class CheckIpMiddleware
{

    public $whiteIps = ['127.0.0.1','84.17.47.124','151.246.94.204'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->ip(), $this->whiteIps)) {

            /*
                 You can redirect to any error page.
            */
            return response()->json(['your ip address is not valid.']);
        }

        return $next($request);
    }
}
