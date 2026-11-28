<?php

include 'location.php';
include 'employ-request.php';
Route::middleware('buy_script')->group(function (){
    include 'expert-request.php';
    include 'advertisement.php';
    include 'meeting.php';
    include 'property-tools.php';
});
include 'customer.php';
include 'partner-panel.php';
include 'setting.php';
include 'blog/route.php';
include 'contact.php';
include 'dashboard.php';
include 'media.php';


include 'raffle.php';
include 'wallet.php';
require_once __DIR__ . '/ai-assistant.php';
include 'inquiry.php';

include 'virtual-tour.php';

include_once 'mortgage.php';
include_once __DIR__ . '/community.php';

include_once __DIR__ . '/identity.php';
include_once __DIR__ . '/web-services.php';
include_once __DIR__ . '/crm-pro.php';

// Super Admin-only management of owner acceptance requests.
include_once __DIR__ . '/property-acceptance.php';
require __DIR__ . '/spad-home-services.php';

Route::get('site-usage/users', [\App\Http\Controllers\Admin\PublicSiteUserStatsController::class, 'index'])
    ->middleware('throttle:30,1');

Route::prefix('site-usage/directory')->middleware('throttle:30,1,spad-user-directory')->group(function () {
    Route::get('overview', [\App\Http\Controllers\Admin\PublicSiteUserDirectoryController::class, 'overview']);
    Route::post('search', [\App\Http\Controllers\Admin\PublicSiteUserDirectoryController::class, 'index']);
    Route::get('{userId}', [\App\Http\Controllers\Admin\PublicSiteUserDirectoryController::class, 'show'])->whereNumber('userId');
});
