<?php

Route::prefix('product')->group(function (){
    Route::get('/', [\App\Http\Controllers\Admin\ProductsController::class, 'getShopProducts']);
    Route::get('buy-products', [\App\Http\Controllers\Admin\ProductsController::class, 'getShopProductsByInventories']);
    Route::get('{product}/product-variants',[\App\Http\Controllers\Admin\ProductsController::class, 'getProductVariants']);
    Route::get('{product}', [\App\Http\Controllers\Admin\ProductsController::class, 'get_info']);
    Route::get('{product}/attributes', [\App\Http\Controllers\Admin\ProductsController::class, 'show']);
    Route::get('{product}/units', [\App\Http\Controllers\Admin\ProductsController::class, 'get_product_units']);
    Route::post('{product}/update', [\App\Http\Controllers\Admin\ProductsController::class, 'update']);
    Route::get('{product}/get_variants', [\App\Http\Controllers\Admin\ProductsController::class, 'get_variants']);
    Route::post('{product}/save_variants', [\App\Http\Controllers\Admin\ProductsController::class, 'save_variants']);
    Route::post('{product}/add_product_to_inventory', [\App\Http\Controllers\Admin\ProductsController::class, 'add_product_to_inventory']);
    Route::post('{product}/edit_product_in_inventory/{item}', [\App\Http\Controllers\Admin\ProductsController::class, 'edit_product_in_inventory']);
    Route::get('inventory/{item}', [\App\Http\Controllers\Admin\ProductsController::class, 'get_product_inventory']);
    Route::post('/', [\App\Http\Controllers\Admin\ProductsController::class, 'store']);
    //        template actions
    Route::post('change-status/{product?}',[\App\Http\Controllers\Admin\ProductsController::class,'updateStatus']);

    Route::post('{product}/approve',[\App\Http\Controllers\Admin\ProductsController::class,'approve']);
    Route::post('{product}/reject',[\App\Http\Controllers\Admin\ProductsController::class,'reject']);

    Route::post('{product}/combination', [\App\Http\Controllers\Admin\ProductsController::class, 'create_combination']);

    Route::post('/delete/{product?}',[\App\Http\Controllers\Admin\ProductsController::class,'delete']);

});

