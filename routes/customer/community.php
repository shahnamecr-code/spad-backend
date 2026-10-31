<?php
use App\Http\Controllers\SpadCommunityController as Community;
use Illuminate\Support\Facades\Route;

Route::get('community/settings',[Community::class,'settings']);
Route::get('builders',[Community::class,'builders']);
Route::get('builders/{id}',[Community::class,'builder'])->whereNumber('id');
Route::get('property-auctions',[Community::class,'auctions']);
Route::get('property-auctions/{id}',[Community::class,'auction'])->whereNumber('id');

Route::middleware('auth:sanctum')->group(function(){
 Route::get('building-management/mine',[Community::class,'myBuildings']);
 Route::post('building-management',[Community::class,'createBuilding'])->middleware('throttle:5,60');
 Route::get('building-management/{id}',[Community::class,'buildingDetail'])->whereNumber('id');
 Route::post('building-management/{id}/units',[Community::class,'addUnit'])->whereNumber('id');
 Route::post('building-management/{id}/join',[Community::class,'claimUnit'])->whereNumber('id')->middleware('throttle:5,60');
 Route::patch('building-management/{id}/units/{unitId}',[Community::class,'reviewUnit'])->whereNumber('id')->whereNumber('unitId');
 Route::post('building-management/{id}/charges',[Community::class,'addCharge'])->whereNumber('id');
 Route::patch('building-management/{id}/charges/{chargeId}',[Community::class,'markCharge'])->whereNumber('id')->whereNumber('chargeId');
 Route::post('building-management/{id}/expenses',[Community::class,'addExpense'])->whereNumber('id');
 Route::post('building-management/{id}/notices',[Community::class,'addNotice'])->whereNumber('id');
 Route::post('building-management/{id}/tickets',[Community::class,'addTicket'])->whereNumber('id');
 Route::patch('building-management/{id}/tickets/{ticketId}',[Community::class,'replyTicket'])->whereNumber('id')->whereNumber('ticketId');
 Route::get('my-builder-profile',[Community::class,'myBuilder']);
 Route::post('my-builder-profile',[Community::class,'saveMyBuilder'])->middleware('throttle:8,60');
 Route::post('my-builder-profile/projects',[Community::class,'addMyProject'])->middleware('throttle:10,60');
 Route::delete('my-builder-profile/projects/{id}',[Community::class,'deleteMyProject'])->whereNumber('id');
 Route::get('my-property-auctions',[Community::class,'myAuctions']);
 Route::post('property-auctions',[Community::class,'proposeAuction'])->middleware('throttle:4,60');
 Route::post('property-auctions/{id}/bids',[Community::class,'bid'])->whereNumber('id')->middleware('throttle:8,1');
});

Route::middleware('auth:sanctum')->prefix('building-partner')->group(function(){
 Route::get('{panelId}/buildings',[\App\Http\Controllers\BuildingManagerController::class,'index'])->whereNumber('panelId');
 Route::post('{panelId}/buildings',[\App\Http\Controllers\BuildingManagerController::class,'create'])->whereNumber('panelId');
 Route::get('{panelId}/buildings/{id}',[\App\Http\Controllers\BuildingManagerController::class,'detail'])->whereNumber('panelId')->whereNumber('id');
 Route::post('{panelId}/buildings/{id}/units',[\App\Http\Controllers\BuildingManagerController::class,'addUnit'])->whereNumber('panelId')->whereNumber('id');
 Route::put('{panelId}/buildings/{id}/units/{unitId}',[\App\Http\Controllers\BuildingManagerController::class,'updateUnit'])->whereNumber('panelId')->whereNumber('id')->whereNumber('unitId');
 Route::delete('{panelId}/buildings/{id}/units/{unitId}',[\App\Http\Controllers\BuildingManagerController::class,'deleteUnit'])->whereNumber('panelId')->whereNumber('id')->whereNumber('unitId');
 Route::post('{panelId}/buildings/{id}/charges',[\App\Http\Controllers\BuildingManagerController::class,'charge'])->whereNumber('panelId')->whereNumber('id');
 Route::patch('{panelId}/buildings/{id}/charges/{chargeId}',[\App\Http\Controllers\BuildingManagerController::class,'chargeStatus'])->whereNumber('panelId')->whereNumber('id');
 Route::post('{panelId}/buildings/{id}/expenses',[\App\Http\Controllers\BuildingManagerController::class,'expense'])->whereNumber('panelId')->whereNumber('id');
 Route::post('{panelId}/buildings/{id}/bills',[\App\Http\Controllers\BuildingManagerController::class,'bill'])->whereNumber('panelId')->whereNumber('id');
 Route::patch('{panelId}/buildings/{id}/bills/{billId}',[\App\Http\Controllers\BuildingManagerController::class,'billStatus'])->whereNumber('panelId')->whereNumber('id');
});
