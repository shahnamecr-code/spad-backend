<?php

use Illuminate\Http\Request;

Route::post('/sendLoginCode', [\App\Http\Controllers\UsersController::class,'sendLoginCode'])->middleware('throttle:15,1440');
Route::post('/login', [\App\Http\Controllers\UsersController::class,'login']);
Route::post('/loginWithPass', [\App\Http\Controllers\UsersController::class,'loginWithPhoneAndPass']);
Route::post('/register', [\App\Http\Controllers\UsersController::class,'register'])->middleware('throttle:6,1440');


Route::prefix('partner-auth')->group(function () {
    Route::post('/send-login-code', [\App\Http\Controllers\PartnerAuthController::class, 'sendLoginCode'])->middleware('throttle:15,1440');
    Route::post('/login', [\App\Http\Controllers\PartnerAuthController::class, 'login']);
    Route::post('/login-with-pass', [\App\Http\Controllers\PartnerAuthController::class, 'loginWithPassword']);
});

Route::get('about',[\App\Http\Controllers\PagesController::class,'about']);
Route::post('contact',[\App\Http\Controllers\PagesController::class,'create_contact']);

Route::get('/homeFilters',[\App\Http\Controllers\AdvertisementController::class,'mainPageFilters']);
Route::get('/homeFilters/v1',[\App\Http\Controllers\HomeStabilityController::class,'mainPageFiltersV1']);
Route::get('home-layout',[\App\Http\Controllers\HomeStabilityController::class,'homeLayout']);
Route::get('/live-property-price',[\App\Http\Controllers\LivePropertyPriceController::class,'index']);

Route::post('/employ-request',[\App\Http\Controllers\UsersController::class,'employ_request']);
Route::post('/expert-request',[\App\Http\Controllers\UsersController::class,'guest_expert_request']);

Route::prefix('location/')->group(function (){
    Route::get('shiraz',[\App\Http\Controllers\LocationController::class,'shiraz']);
    Route::get('provinces',[\App\Http\Controllers\LocationController::class,'provinces']);
    Route::get('provinces/v1',[\App\Http\Controllers\LocationController::class,'provincesV1']);
    Route::get('{province}/cities',[\App\Http\Controllers\LocationController::class,'getProvinceCities']);
    Route::get('{city}/regions',[\App\Http\Controllers\LocationController::class,'getCityRegions']);
    Route::get('paginate-cities',[\App\Http\Controllers\LocationController::class,'asyncPaginateCities']);
    Route::post('regions/v1',[\App\Http\Controllers\LocationController::class,'getRegionsByCityAndProvinces']);
    Route::post('regions/v2',[\App\Http\Controllers\LocationController::class,'getPaginatedRegionsByCityAndProvinces']);
});

Route::middleware('auth:sanctum')->group(function (){
    Route::get('/user',[\App\Http\Controllers\UsersController::class,'getUser']);
    Route::get('/my-meetings',[\App\Http\Controllers\MeetingController::class,'getMeetings']);
    Route::get('favorite_adds/v1',[\App\Http\Controllers\UsersController::class,'getUserFavoritesOnlyId']);
    Route::get('favorite_adds',[\App\Http\Controllers\UsersController::class,'getUserFavorites']);
    Route::post('add_to_favorites',[\App\Http\Controllers\UsersController::class,'addAdvertisementToFavorites']);
    Route::delete('empty_favorites',[\App\Http\Controllers\UsersController::class,'emptyAdvertisementsFavorite']);
    Route::delete('delete_ad_from_favorites/{advertisement}',[\App\Http\Controllers\UsersController::class,'deleteAdvertisementFromFavorite']);
    Route::post('/change-profile',[\App\Http\Controllers\UsersController::class,'changeProfile']);
    Route::get('/logout',[\App\Http\Controllers\UsersController::class,'logout']);

    Route::post('/users-expert-request',[\App\Http\Controllers\UsersController::class,'users_expert_request']);
    Route::post('/upload', [\App\Http\Controllers\UsersController::class, 'uploadAdImage']);
    Route::get('my-ads',[\App\Http\Controllers\UsersController::class,'myAdvertisements']);
    Route::get('my-ads/{advertisement}/edit',[\App\Http\Controllers\UsersController::class,'editMyAdvertisement']);
    Route::post('my-ads/{advertisement}/edit',[\App\Http\Controllers\UsersController::class,'updateMyAdvertisement']);
    Route::get('my-expert-requests',[\App\Http\Controllers\UsersController::class,'myExpertsRequests']);
    Route::post('change-password',[\App\Http\Controllers\UsersController::class,'changePassword']);
    Route::prefix('advertisements')->group(function (){
        Route::get('/preview/auth/{advertisement}',[\App\Http\Controllers\AdvertisementController::class,'showAdvertisementPreviewWhenLogin']);
    });
});

Route::prefix('advertisements')->group(function (){
//    Route::get('/',[\App\Http\Controllers\AdvertisementController::class,'showAllAdvertisements']);
//    Route::post('/register',[\App\Http\Controllers\AdvertisementController::class,'registerCustomerAdvertisement']);
    Route::get('/checkExist/{advertisement}',[\App\Http\Controllers\AdvertisementController::class,'checkExistAdvertisement']);
    Route::get('/preview/{advertisement}',[\App\Http\Controllers\AdvertisementController::class,'showAdvertisementPreview']);
    Route::get('list',[\App\Http\Controllers\AdvertisementController::class,'advertisementsList']);
    Route::get('latest',[\App\Http\Controllers\AdvertisementController::class,'latestAdvertisements']);
    Route::post('adsListById',[\App\Http\Controllers\AdvertisementController::class,'adsListById']);
    Route::middleware('auth:sanctum')->group(function (){
        Route::post('/point/{advertisement}',[\App\Http\Controllers\AdvertisementController::class,'pointToAdvertisement']);
        Route::post('/report/{advertisement}',[\App\Http\Controllers\AdvertisementController::class,'report']);
    });
});

Route::prefix('category')->group(function (){
    Route::get('filters',[\App\Http\Controllers\AdvertisementController::class,'displayCategoryFilters']);
});

include_once 'category/route.php';
include_once 'meeting/route.php';
//include_once 'product/route.php';
include_once 'setting/route.php';
include_once 'blog/route.php';
include_once 'experts/route.php';
//include_once 'profile/route.php';
//include_once 'location/route.php';

include_once 'raffle/route.php';
include_once 'wallet/route.php';
include_once 'inquiry/route.php';
include_once 'property-tools.php';
include_once 'partner-panel.php';

include_once 'virtual-tour.php';

require_once __DIR__ . '/mortgage.php';
