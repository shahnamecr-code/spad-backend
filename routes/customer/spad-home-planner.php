<?php

use App\Http\Controllers\SpadHomePlannerController as Planner;
use Illuminate\Support\Facades\Route;

Route::prefix('home-planner')->middleware('auth:sanctum')->group(function () {
    Route::get('groups', [Planner::class, 'groups']);
    Route::post('groups', [Planner::class, 'createGroup'])->middleware('throttle:10,60,planner-groups');
    Route::post('invitations/accept', [Planner::class, 'accept'])->middleware('throttle:12,1,planner-accept');
    Route::prefix('groups/{group}')->where(['group' => '[0-9]+'])->group(function () {
        Route::get('/', [Planner::class, 'dashboard']);
        Route::patch('/', [Planner::class, 'updateGroup'])->middleware('throttle:20,1,planner-group-edit');
        Route::post('invitations', [Planner::class, 'invite'])->middleware('throttle:12,60,planner-invite');
        Route::delete('invitations/{invite}', [Planner::class, 'revoke'])->whereNumber('invite');
        Route::patch('members/{member}', [Planner::class, 'updateMember'])->whereNumber('member');
        Route::delete('members/{member}', [Planner::class, 'removeMember'])->whereNumber('member');
        Route::post('tasks', [Planner::class, 'saveTask'])->middleware('throttle:30,1,planner-tasks');
        Route::patch('tasks/{task}', [Planner::class, 'saveTask'])->whereNumber('task')->middleware('throttle:30,1,planner-tasks');
        Route::delete('tasks/{task}', [Planner::class, 'archiveTask'])->whereNumber('task');
        Route::put('tasks/{task}/occurrences/{date}/completion', [Planner::class, 'complete'])
            ->whereNumber('task')->where('date', '\d{4}-\d{2}-\d{2}')->middleware('throttle:45,1,planner-completion');
    });
});
