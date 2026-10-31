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

Route::post('community/builders',[Community::class,'adminCreateBuilder']);
Route::put('community/builders/{id}',[Community::class,'adminEditBuilder'])->whereNumber('id');
Route::delete('community/builders/{id}',[Community::class,'adminDeleteBuilder'])->whereNumber('id');
Route::get('community/builders/{id}/projects',[Community::class,'adminBuilderProjects'])->whereNumber('id');
Route::post('community/builders/{id}/projects',[Community::class,'adminAddBuilderProject'])->whereNumber('id');
Route::delete('community/builders/{id}/projects/{projectId}',[Community::class,'adminDeleteBuilderProject'])->whereNumber('id')->whereNumber('projectId');
Route::post('community/auctions',[Community::class,'adminCreateAuction']);
