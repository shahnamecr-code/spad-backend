<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::get(
    'buy_script',
    [\App\Http\Controllers\SettingsController::class, 'buy_script_form']
)->name('buy_script_form');

Route::post(
    'buy_script',
    [\App\Http\Controllers\SettingsController::class, 'buy_script']
)->name('buy_script');

/*
|--------------------------------------------------------------------------
| Storage Files
|--------------------------------------------------------------------------
*/

Route::get('storage/{file_name}', function ($file_name) {
    $path = storage_path('app/public/' . $file_name);

    return response()->file($path);
})->where('file_name', '.*');

/*
|--------------------------------------------------------------------------
| Admin Panel
|--------------------------------------------------------------------------
|
| Main admin panel.
| Protected with admin authentication.
|
*/

Route::get('spad-admin/{path?}', function () {

    $settings = \App\Models\Setting::query()
        ->whereIn('code', [
            'general_site_name',
            'general_favicon',
        ])
        ->get()
        ->pluck('user_show', 'code')
        ->toArray();

    return view('welcome', compact('settings'));

})
    ->where('path', '.*')
    ->middleware('auth:sanctum,admin');

/*
|--------------------------------------------------------------------------
| Admin Login
|--------------------------------------------------------------------------
|
| Fix:
| Opening /spad-login directly previously rendered the React shell
| without a matching child route, which resulted in a blank white page.
|
| Now:
|
| /spad-login
|       ↓
| /spad-login/login
|
*/

Route::get('spad-login/{path?}', function ($path = null) {

    /*
     * If user opens:
     *
     * https://panel.spad.estate/spad-login
     *
     * redirect to:
     *
     * https://panel.spad.estate/spad-login/login
     */
    if (empty($path)) {
        return redirect('/spad-login/login');
    }

    $settings = \App\Models\Setting::query()
        ->whereIn('code', [
            'general_site_name',
            'general_favicon',
        ])
        ->get()
        ->pluck('user_show', 'code')
        ->toArray();

    return view('welcome', compact('settings'));

})
    ->where('path', '.*')
    ->name('login');

/*
|--------------------------------------------------------------------------
| Frontend Redirect
|--------------------------------------------------------------------------
|
| Any other route on panel domain redirects to frontend website.
|
*/

// The panel bare domain must open its own login, not the public website.
// No privileged data is served here; admin API authentication is unchanged.
Route::get('/', function () {
    return redirect('/spad-login/login');
});

Route::get('{path?}', function () {

    return \Illuminate\Support\Facades\Redirect::to(
        env('CLIENT_URL')
    );

})
    ->where('path', '.*')
    ->name('main');