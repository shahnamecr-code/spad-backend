<?php
use App\Http\Controllers\Admin\SpadCrmProController as CRM;
Route::prefix('crm-pro')->middleware('throttle:60,1')->group(function(){
 Route::get('/overview',[CRM::class,'overview']);
 Route::post('/import-customers',[CRM::class,'importCustomers'])->middleware('throttle:2,1');
 Route::get('/contacts',[CRM::class,'index']);
 Route::post('/contacts',[CRM::class,'store']);
 Route::get('/contacts/{id}',[CRM::class,'show'])->whereNumber('id');
 Route::put('/contacts/{id}',[CRM::class,'update'])->whereNumber('id');
 Route::post('/contacts/{id}/opt-out',[CRM::class,'optOut'])->whereNumber('id');
 Route::post('/contacts/{id}/notes',[CRM::class,'note'])->whereNumber('id');
 Route::post('/contacts/{id}/followups',[CRM::class,'addTask'])->whereNumber('id');
 Route::post('/contacts/{id}/followups/{taskId}/complete',[CRM::class,'finishTask'])->whereNumber('id')->whereNumber('taskId');
 Route::post('/contacts/{id}/outbound',[CRM::class,'outbound'])->middleware('throttle:5,1')->whereNumber('id');
});
