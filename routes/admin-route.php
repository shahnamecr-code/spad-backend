<?php

Route::prefix('admin')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\AdminController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\AdminController::class, 'store']);
    Route::get('{admin}', [\App\Http\Controllers\Admin\AdminController::class, 'show']);
    Route::put('{admin}', [\App\Http\Controllers\Admin\AdminController::class, 'update']);
    Route::delete('{admin}', [\App\Http\Controllers\Admin\AdminController::class, 'destroy']);
});

Route::get('rolesAndPermissions', function () {
    return \Spatie\Permission\Models\Role::with(['permissions' => function ($q) {
        $q->orderBy('name');
    }])->whereHas('permissions')->get();
});

Route::prefix('operator')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\OperatorController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\OperatorController::class, 'store']);
    Route::get('{admin}', [\App\Http\Controllers\Admin\OperatorController::class, 'show']);
    Route::put('{admin}', [\App\Http\Controllers\Admin\OperatorController::class, 'update']);
    Route::delete('{admin}', [\App\Http\Controllers\Admin\OperatorController::class, 'destroy']);

    Route::get('getPermissionable/{admin}', [\App\Http\Controllers\Admin\OperatorController::class, 'getPermissionable']);
    Route::post('permissions/{admin}', [\App\Http\Controllers\Admin\OperatorController::class, 'storePermission']);
});

Route::get('getShops', [\App\Http\Controllers\Admin\ShopController::class, 'getShopsForSelect']);
Route::prefix('shop')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\ShopController::class, 'index']);
    Route::get('list', [\App\Http\Controllers\Admin\ShopController::class, 'shopList']);
    Route::post('', [\App\Http\Controllers\Admin\ShopController::class, 'store']);
    Route::get('{shop}', [\App\Http\Controllers\Admin\ShopController::class, 'show']);
    Route::put('{shop}', [\App\Http\Controllers\Admin\ShopController::class, 'update']);
    Route::delete('{shop}', [\App\Http\Controllers\Admin\ShopController::class, 'destroy']);
    Route::post('changeStatus/{shop}', [\App\Http\Controllers\Admin\ShopController::class, 'changeShopStatus']);
});

/*Route::get('getInventory', [\App\Http\Controllers\Admin\InventoryController::class, 'getInventoryForSelect']);
Route::prefix('inventory')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\InventoryController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\InventoryController::class, 'store']);
    Route::get('{inventory}', [\App\Http\Controllers\Admin\InventoryController::class, 'show']);
    Route::put('{inventory}', [\App\Http\Controllers\Admin\InventoryController::class, 'update']);
    Route::delete('{inventory}', [\App\Http\Controllers\Admin\InventoryController::class, 'destroy']);
});

*/


Route::prefix('address')->group(function () {
    Route::get('{user}', [\App\Http\Controllers\Admin\CustomerAddressController::class, 'index']);
    Route::post('{user}', [\App\Http\Controllers\Admin\CustomerAddressController::class, 'store']);
    Route::get('{address}', [\App\Http\Controllers\Admin\CustomerAddressController::class, 'show']);
    Route::put('{address}', [\App\Http\Controllers\Admin\CustomerAddressController::class, 'update']);
    Route::delete('/{address}', [\App\Http\Controllers\Admin\CustomerAddressController::class, 'destroy']);
});

/*Route::get('getCustomerGroup', [\App\Http\Controllers\Admin\CustomerGroupController::class, 'getCustomerGroupForSelect']);
Route::prefix('customer-group')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\CustomerGroupController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\CustomerGroupController::class, 'store']);
    Route::get('{customerGroup}', [\App\Http\Controllers\Admin\CustomerGroupController::class, 'show']);
    Route::put('{customerGroup}', [\App\Http\Controllers\Admin\CustomerGroupController::class, 'update']);
    Route::delete('{customerGroup}', [\App\Http\Controllers\Admin\CustomerGroupController::class, 'destroy']);
});

Route::prefix('accounting')->group(function () {
    Route::get('factor/list/{type}', [\App\Http\Controllers\Admin\Accounting\FactorController::class, 'index']);
    Route::get('factor/{factor}', [\App\Http\Controllers\Admin\Accounting\BuyFactorController::class, 'show']);
    Route::get('productInfo/{product}', [\App\Http\Controllers\Admin\Accounting\BuyFactorController::class, 'getProductInfo']);
    Route::get('productSaleInfo/{product}', [\App\Http\Controllers\Admin\Accounting\BuyFactorController::class, 'getProductVariantInfo']);
    Route::post('factor/buy', [\App\Http\Controllers\Admin\Accounting\BuyFactorController::class, 'store']);
    Route::put('factor/buy/{factor}', [\App\Http\Controllers\Admin\Accounting\BuyFactorController::class, 'update']);
    Route::delete('factor/buy/{factor}', [\App\Http\Controllers\Admin\Accounting\BuyFactorController::class, 'delete']);
    Route::post('factor/sell', [\App\Http\Controllers\Admin\Accounting\SellFactorController::class, 'store']);
    Route::put('factor/sell/{factor}', [\App\Http\Controllers\Admin\Accounting\SellFactorController::class, 'update']);
    Route::delete('factor/sell/{factor}', [\App\Http\Controllers\Admin\Accounting\SellFactorController::class, 'delete']);
});

Route::prefix('promo-code')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\PromoCodeController::class, 'index']);
    Route::post('', [\App\Http\Controllers\Admin\PromoCodeController::class, 'store']);
    Route::get('{promoCode}', [\App\Http\Controllers\Admin\PromoCodeController::class, 'show']);
    Route::put('{promoCode}', [\App\Http\Controllers\Admin\PromoCodeController::class, 'update']);
    Route::delete('{promoCode}', [\App\Http\Controllers\Admin\PromoCodeController::class, 'destroy']);
});*/

Route::prefix('orders')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\OrderController::class, 'index']);
    Route::get('{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show']);
    Route::post('{order}/change-status',[\App\Http\Controllers\Admin\OrderController::class,'change_status']);
});


