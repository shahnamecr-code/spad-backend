<?php

use App\Http\Controllers\Admin\SpadHomePlannerController as Planner;
use Illuminate\Support\Facades\Route;

Route::get('home-planner/overview', [Planner::class, 'overview']);
