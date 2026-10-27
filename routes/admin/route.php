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
include 'inquiry.php';

include 'virtual-tour.php';
