<?php

Route::prefix('wrappers')->group(function (){
    Route::get('',[\App\Http\Controllers\Admin\AdvertisementController::class,'index']);
    Route::post('add',[\App\Http\Controllers\Admin\AdvertisementController::class,'addPropertyWrapper']);
    Route::get('show/{wrapper}',[\App\Http\Controllers\Admin\AdvertisementController::class,'getPropertyWrapper']);
    Route::post('edit/{wrapper}',[\App\Http\Controllers\Admin\AdvertisementController::class,'updatePropertyWrapper']);
});

Route::prefix('properties')->group(function (){
    Route::get('',[\App\Http\Controllers\Admin\AdvertisementController::class,'properties_select']);
    Route::post('add',[\App\Http\Controllers\Admin\AdvertisementController::class,'addProperty']);
    Route::get('edit/{property}',[\App\Http\Controllers\Admin\AdvertisementController::class,'getEditPropertyForm']);
    Route::post('edit/{property}',[\App\Http\Controllers\Admin\AdvertisementController::class,'editProperty']);
});

Route::prefix('cities')->group(function (){
    Route::get('',[\App\Http\Controllers\Admin\AdvertisementController::class,'searchCities']);
});

Route::prefix('transactions')->group(function (){
    Route::get('',[\App\Http\Controllers\Admin\AdvertisementController::class,'TransactionsList']);
    Route::post('add',[\App\Http\Controllers\Admin\AdvertisementController::class,'addTransaction']);
    Route::get('show/{transaction}',[\App\Http\Controllers\Admin\AdvertisementController::class,'getTransactionEditForm']);
    Route::post('edit/{transaction}',[\App\Http\Controllers\Admin\AdvertisementController::class,'updateTransaction']);
});

Route::prefix('land-types')->group(function (){
    Route::get('',[\App\Http\Controllers\Admin\AdvertisementController::class,'landTypesList']);
    Route::post('add',[\App\Http\Controllers\Admin\AdvertisementController::class,'addLandType']);
    Route::get('show/{landType}',[\App\Http\Controllers\Admin\AdvertisementController::class,'getLandTypeEditForm']);
    Route::post('edit/{landType}',[\App\Http\Controllers\Admin\AdvertisementController::class,'updateLandType']);
});

Route::prefix('skills')->group(function (){
    Route::get('/',function (){
        $res=\App\Models\LandTypeTransaction::all();
        return \App\Http\Resources\AdminSkillResource::collection($res);
    });
});

Route::prefix('advertisements')->group(function (){
    Route::get('all-reports',[\App\Http\Controllers\Admin\AdvertisementController::class,'advertisementsHAsReporting']);
    Route::get('list',[\App\Http\Controllers\Admin\AdvertisementController::class,'advertisementsList']);
    Route::get('register',[\App\Http\Controllers\Admin\AdvertisementController::class,'registerAdvertisementForm']);
    Route::post('register',[\App\Http\Controllers\Admin\AdvertisementController::class,'registerAdvertisement']);
    Route::get('showCategories',[\App\Http\Controllers\Admin\AdvertisementController::class,'getCategories']);
    Route::post('reject',[\App\Http\Controllers\Admin\AdvertisementController::class,'rejectAdvertisements'])->middleware('permission:reject.advertisement');
    Route::post('approve',[\App\Http\Controllers\Admin\AdvertisementController::class,'approveAdvertisements'])->middleware('permission:reject.advertisement');
    Route::post('priority',[\App\Http\Controllers\Admin\AdvertisementController::class,'priorityAdvertisements'])->middleware('permission:reject.advertisement');
    Route::post('delete/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'deleteAdvertisement'])->middleware('permission:reject.advertisement');
    Route::get('/transform/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'showAdvertisementExperts']);
    Route::get('/preview/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'showAdvertisementPreview']);
    Route::get('/reports/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'advertisementReports']);
    Route::get('/find/{id}',[\App\Http\Controllers\Admin\AdvertisementController::class,'findAdById']);
    Route::get('/edit/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'editAdvertisementInfo']);
    Route::post('/edit/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'editAdvertisement']);
    Route::post('/transform/{advertisement}',[\App\Http\Controllers\Admin\AdvertisementController::class,'transformAdvertisement']);
    Route::get('/allManagerExperts',[\App\Http\Controllers\Admin\AdvertisementController::class,'allManagerExperts']);
    Route::get('/find/{id}',[\App\Http\Controllers\Admin\AdvertisementController::class,'findAdById']);
});

Route::prefix('advertisement-category')->group(function (){
    Route::get('getAllCategoriesWithChildren',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'getCategoriesList']);
    Route::get('list',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'list']);
    Route::get('getEditFormData/{category}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'getEditFormData']);
    Route::post('create',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'store']);
//    Route::get('allCategories',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'searchCategories']);
    Route::post('edit/{category}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'update']);
    Route::get('layout/edit/{layout}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'getLayoutEditForm']);
    Route::post('layout/edit/{layout}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'updateLayout']);
    Route::get('layout/{category}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'layout']);
    Route::post('layout/{category}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'addLayout']);
    Route::get('allCategories',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'allCategories']);
    Route::post('import-layout/{category}',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'importCategoryLayout']);
    Route::get('panelAdvertisementList/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'panelAdvertisementList']);
    Route::post('panelAdvertisementList/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'savePanelAdvertisementList']);
    Route::get('filters/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'showCategoryFilter']);
    Route::post('filters/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'saveCategoryFilter']);
    Route::get('displayCategoryFilters',[\App\Http\Controllers\Admin\AdvertisementController::class,'displayCategoryFilters']);
    Route::get('single-layout/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'displaySingleLayout']);
    Route::post('single-layout/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'saveSingleLayout']);
    Route::get('main-filters/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'showCategoryMainFilter']);
    Route::post('main-filters/{category}',[\App\Http\Controllers\Admin\AdvertisementController::class,'saveCategoryMainFilter']);
    Route::get('showAllCategoriesWithParent',[\App\Http\Controllers\Admin\AdvertisementCategoryController::class,'showAllCategoriesWithParent']);

});

Route::get('/managers/search_experts',[\App\Http\Controllers\Admin\AdvertisementController::class,'searchExperts']);



