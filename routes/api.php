<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

require __DIR__ . "/customer/route.php";

Route::group(['prefix' => 'admin'], function () {
    Route::post('login', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'login']);
    Route::post('registerShop', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'register']);
    Route::post('verifyEmail', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'verify_email']);
    Route::put('verifyEmail', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'send_again_verify_email']);
    Route::post('verifyMobile', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'verify_mobile']);
    Route::put('verifyMobile', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'send_again_verify_mobile']);
    Route::post('completeRegister', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'completeRegister']);
    Route::post('contactInfo', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'contactInfo']);
    Route::post('businessInfo', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'businessInfo']);
    Route::post('uploadDocument', [\App\Http\Controllers\Admin\Auth\RegisterShopController::class, 'uploadDocument']);

    Route::group(['middleware' => ['auth:sanctum,admin']], function () {
        Route::get('user', function () {
            $user = auth('admin')->user();
            if ($user->shop_id != null) {
                if ($result = $user->shop->getStepByStatus()) {
                    return $result;
                }
            }
            return new \App\Http\Resources\AdminResource($user);
        });
        Route::post('logout', [\App\Http\Controllers\Admin\Auth\LoginController::class, 'logout']);
        require __DIR__ . "/admin-route.php";
        require __DIR__ . "/admin/route.php";

//        Route::post('file', [\App\Http\Controllers\Admin\FilesController::class, 'get']);
//        Route::post('file/create', [\App\Http\Controllers\Admin\FilesController::class, 'create']);
//        Route::post('file/upload', [\App\Http\Controllers\Admin\FilesController::class, 'upload']);

    });

});

Route::get('has_license',function (){
    return response()->json([
        'license'=>checkHasLicense()
    ]);
});

