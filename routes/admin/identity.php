<?php
use App\Http\Controllers\SpadIdentityController as Identity;
Route::get('identity/requests',[Identity::class,'adminList']);
Route::patch('identity/requests/{id}',[Identity::class,'adminReview'])->whereNumber('id');
Route::get('identity/requests/{id}/documents/{kind}',[Identity::class,'adminDocument'])->whereNumber('id')->where('kind','national_card|face_photo|supporting');
