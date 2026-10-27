<?php

Route::group(['prefix' => 'api', 'middleware' => 'api'], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::group(['middleware' => ['auth:sanctum,admin']], function () {
            Route::prefix('advertisement-category')->group(function (){
                Route::get('crm-layout/{category}',[\Spad\Crm\Controllers\AdvertisementCategoryController::class,'showCategoryCrmLayout']);
                Route::post('crm-layout/{category}',[\Spad\Crm\Controllers\AdvertisementCategoryController::class,'saveCategoryCrmLayout'])->middleware('buy_script');
                Route::get('show-crm-layout/{category:slug}',[\Spad\Crm\Controllers\AdvertisementCategoryController::class,'displayCategoryCrmLayout']);

            });
            Route::prefix('crm')->group(function (){
                Route::post('add-request/{category:slug}',[\Spad\Crm\Controllers\CrmController::class,'addCrmRequest'])->middleware('buy_script');
                Route::get('request-list',[\Spad\Crm\Controllers\CrmController::class,'crmAllCustomers']);
                Route::get('share-ads/{crm_request}',[\Spad\Crm\Controllers\CrmController::class,'shareAdsWithCustomer']);
                Route::get('preview/{crm_request}',[\Spad\Crm\Controllers\CrmController::class,'showCrmRequest']);
                Route::get('edit/{crm_request}',[\Spad\Crm\Controllers\CrmController::class,'getCrmRequestForm']);
                Route::post('edit/{crm_request}',[\Spad\Crm\Controllers\CrmController::class,'editCrmRequest'])->middleware('buy_script');
                Route::post('transfer/{crm_request}',[\Spad\Crm\Controllers\CrmController::class,'transferRequest'])->middleware('buy_script');
            });
            Route::prefix('customer')->group(function (){
                Route::post('check-exist',[\Spad\Crm\Controllers\CustomerController::class,'checkExistCustomer'])->middleware('buy_script');
                Route::post('add-crm-customer',[\Spad\Crm\Controllers\CustomerController::class,'addCustomer'])->middleware('buy_script');
            });
            Route::get('getAllAdmins',[\Spad\Crm\Controllers\CrmController::class,'allAdmins']);
        });
    });
    Route::prefix('category/{category:slug}')->group(function (){
        Route::get('crm-layout',[\Spad\Crm\Controllers\AdvertisementCategoryController::class,'displayCategoryCrmLayout']);
    });
    Route::prefix('crm')->group(function (){
        Route::get('{crm_request}/suggest',[\Spad\Crm\Controllers\CrmController::class,'suggest']);
        Route::get('layout/{category:slug}',[\Spad\Crm\Controllers\PublicCrmController::class,'displayCategoryCrmLayout']);
        Route::middleware('auth:sanctum')->group(function (){
            Route::post('request/{category:slug}/create',[\Spad\Crm\Controllers\PublicCrmController::class,'addCrmRequest'])->middleware('buy_script');
            Route::get('my-requests',[\Spad\Crm\Controllers\CrmController::class,'userRequests']);
        });
    });
});
