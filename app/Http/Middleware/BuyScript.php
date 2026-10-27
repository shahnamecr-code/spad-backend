<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class BuyScript
{

    public function handle(Request $request, Closure $next)
    {
        if (checkHasLicense()){
            return $next($request);
        }
        if ($request->acceptsJson()){
            return response()->json([
                'message'=>'جهت استفاده از این قسمت لایسنس خود را وارد نمایید',
                'url'=>route('buy_script_form'),
            ],402);
        }
        return Redirect::route('buy_script');
    }
}
