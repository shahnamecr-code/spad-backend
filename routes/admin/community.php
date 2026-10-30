<?php
use App\Http\Controllers\SpadCommunityController as Community;
use Illuminate\Support\Facades\Route;

Route::get('community/settings',[Community::class,'adminSettings']);
Route::post('community/settings',[Community::class,'updateSettings']);
Route::get('community/buildings',[Community::class,'adminBuildings']);
Route::patch('community/buildings/{id}',[Community::class,'adminBuildingStatus'])->whereNumber('id');
Route::get('community/builders',[Community::class,'adminBuilders']);
Route::patch('community/builders/{id}',[Community::class,'adminBuilderStatus'])->whereNumber('id');
Route::get('community/auctions',[Community::class,'adminAuctions']);
Route::patch('community/auctions/{id}',[Community::class,'adminAuctionStatus'])->whereNumber('id');
Route::get('community/auctions/{id}/bids',[Community::class,'adminAuctionBids'])->whereNumber('id');
