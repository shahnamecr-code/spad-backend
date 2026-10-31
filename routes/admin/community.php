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
Route::get('community/auctions/eligible-advertisements',[Community::class,'adminEligibleAuctionAdvertisements']);
Route::patch('community/auctions/{id}',[Community::class,'adminAuctionStatus'])->whereNumber('id');
Route::get('community/auctions/{id}/bids',[Community::class,'adminAuctionBids'])->whereNumber('id');

Route::post('community/builders/upload-image',[Community::class,'adminUploadBuilderImage']);
Route::post('community/builders',[Community::class,'adminCreateBuilder']);
Route::put('community/builders/{id}',[Community::class,'adminEditBuilder'])->whereNumber('id');
Route::delete('community/builders/{id}',[Community::class,'adminDeleteBuilder'])->whereNumber('id');
Route::get('community/builders/{id}/projects',[Community::class,'adminBuilderProjects'])->whereNumber('id');
Route::post('community/builders/{id}/projects',[Community::class,'adminAddBuilderProject'])->whereNumber('id');
Route::delete('community/builders/{id}/projects/{projectId}',[Community::class,'adminDeleteBuilderProject'])->whereNumber('id')->whereNumber('projectId');
Route::post('community/auctions',[Community::class,'adminCreateAuction']);
Route::get('community/auctions/{id}/participations',[\App\Http\Controllers\AuctionParticipationController::class,'adminList'])->whereNumber('id');
Route::post('community/auctions/{id}/participations/{participantId}/refund',[\App\Http\Controllers\AuctionParticipationController::class,'adminRefund'])->whereNumber('id')->whereNumber('participantId');

Route::post('community/buildings',[ \App\Http\Controllers\BuildingAdminController::class,'create']);
Route::put('community/buildings/{id}',[ \App\Http\Controllers\BuildingAdminController::class,'update'])->whereNumber('id');
Route::delete('community/buildings/{id}',[ \App\Http\Controllers\BuildingAdminController::class,'delete'])->whereNumber('id');
Route::get('community/buildings/{id}/units',[ \App\Http\Controllers\BuildingAdminController::class,'units'])->whereNumber('id');
Route::post('community/buildings/{id}/units',[ \App\Http\Controllers\BuildingAdminController::class,'addUnit'])->whereNumber('id');
Route::put('community/buildings/{id}/units/{unitId}',[ \App\Http\Controllers\BuildingAdminController::class,'updateUnit'])->whereNumber('id')->whereNumber('unitId');
Route::delete('community/buildings/{id}/units/{unitId}',[ \App\Http\Controllers\BuildingAdminController::class,'deleteUnit'])->whereNumber('id')->whereNumber('unitId');
