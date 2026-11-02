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
