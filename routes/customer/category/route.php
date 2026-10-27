<?php

//Route::get('/categories',[\App\Http\Controllers\Customer\CategoriesController::class,'index']);
//Route::get('/categories/{category}/products',[\App\Http\Controllers\Customer\CategoriesController::class,'products']);

Route::get('categories/v1',[\App\Http\Controllers\AdvertisementController::class,'showAllCategoriesWithChildren']);
Route::get('categories/v2',[\App\Http\Controllers\AdvertisementController::class,'getAllCategories']);
Route::get('categories/v3',[\App\Http\Controllers\AdvertisementController::class,'getAllCategoriesV3']);

Route::get('categories/layout/v1/{category}',[\App\Http\Controllers\AdvertisementController::class,'getCategoryLayoutV1']);
Route::get('categories/layout/{category}',[\App\Http\Controllers\AdvertisementController::class,'getCategoryLayout']);

Route::middleware('auth:sanctum')->group(function (){
//    Route::post('advertisement/register',[\App\Http\Controllers\AdvertisementController::class,'registerAdvertisement']);
    Route::post('advertisement/register',[\App\Http\Controllers\AdvertisementController::class,'registerCustomerAdvertisement'])->middleware('buy_script');
});
