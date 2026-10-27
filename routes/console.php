<?php

use App\Models\Admin;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('priority',function (){
    $ads=\App\Models\Advertisement::all();
    foreach ($ads as $ad){
        $ad->priority=$ad->created_at;
        $ad->save();
    }
});

Artisan::command('check_license',function (){
    $this->comment('checking license ...');
    $api = 'rtl8354729b1c3e74c37a4071c5a136f8'; // API اختصاصی فروشنده
    $username = 'rjalili'; //نام کاربری خریدار
    $order_id = '19376503474'; // شماره سفارش
    $domain = 'https://melkido.com'; //دامنه
    $productId ='242876'; // شناسه محصول
    $result = checkBuyScript($api,$username,$order_id,$domain,$productId);
    switch ($result) {
        case '1':
            $error = NULL;
            break;
        case '-1':
            $error = 'API اشتباه است';
            break;
        case '-2':
            $error = 'نام کاربری اشتباه است';
            break;
        case '-3':
            $error = 'کد سفارش اشتباه است';
            break;
        case '-4':
            $error = 'کد سفارش قبلاً ثبت شده است';
            break;
        case '-5':
            $error = 'کد سفارش مربوطه به این نام کاربری نمیباشد .';
            break;
        case '-6':
            $error = 'اطلاعات وارد شده  در فرمت صحیح نمیباشند!';
            break;
        case '-7':
            $error = 'کد سفارش مربوط به این محصول نیست';
            break;
        case '-8':
            $error = 'کد سفارش مربوطه به این نام کاربری نمیباشد.';
            break;
        default:
            $error = 'خطای غیرمنتظره رخ داده است';
            break;
    }
    $this->comment($result);
});

Artisan::command('restore',function (){
    \Illuminate\Support\Facades\DB::table('users')
        ->whereNotNull('deleted_at')
        ->update([
            'deleted_at'=>null
        ]);
    \Illuminate\Support\Facades\DB::table('crm_requests')
        ->whereNotNull('deleted_at')
        ->update([
            'deleted_at'=>null
        ]);
    \Illuminate\Support\Facades\DB::table('crm_request_properties')
        ->whereNotNull('deleted_at')
        ->update([
            'deleted_at'=>null
        ]);
});

Artisan::command('setPass {email?}', function () {
    $pass=$this->ask('set password');
    $email = $this->argument('email') ?: $this->ask('admin email');
    $admin = Admin::query()->where('email', $email)->first();
    if ($admin){
        if ($admin->update([
            'password'=>$pass,
            'deleted_at'=>null
        ])){
            $this->comment('password changed successfully');
        }else{
            $this->comment('error occurred. try later.');
        }
    }else{
        $this->warn('admin not found');
    }
})->purpose('set main user password');
