<?php

Route::get('sale-products',[\App\Http\Controllers\Customer\ProductsController::class,'sale_products']);
Route::get('product/{product}',[\App\Http\Controllers\Customer\ProductsController::class,'get_product_single_page']);
