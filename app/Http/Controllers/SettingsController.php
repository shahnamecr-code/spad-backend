<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Setting;
use App\Support\SpadLocation;
use App\Services\MarketPriceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    public function marketPrices(MarketPriceService $marketPriceService)
    {
        return response()->json($marketPriceService->getTickerData());
    }

    public function getByGroup($group)
    {
        $settings=Setting::$settings;
        if(isset($settings[$group]) && isset($settings[$group]['subs'])){
            $res=Setting::where('group',$group)->whereIn('code',array_column($settings[$group]['subs'],'code'))->get()->pluck('user_show','code');
            return ($res && count($res)>0)?$res:false;
        }
        throw new ModelNotFoundException();
    }

    public function buy_script_form()
    {
        if (checkHasLicense()){
            return redirect()->to(env("CLIENT_URL"));
        }
        $error=null;
        if (Session::get('error')){
            $error=Session::get('error');
            Session::forget('error');
        }
        return view('buyScript',compact(['error']));

    }

    public function buy_script(Request $request)
    {
        if (checkHasLicense()){
            return redirect()->to(env("CLIENT_URL"));
        }
        $form_data = $request->all();
        $api = "rtl8354729b1c3e74c37a4071c5a136f8"; //'SandBox-API'
        $order_id = $form_data['id_order']??'';//"SandBox-Order"
        $username = $form_data['username']??'';//"SandBox-User"
        $domain = env('CLIENT_URL');
        $productId = 242876;
        $result = checkBuyScript($api, $username, $order_id, $domain, $productId);
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
                $error = 'کد سفارش مربوطه به این نام کاربری نمیباشد.';
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
        if (is_null($error)) {
            $random_hash = "7f515218479cdb03a6c9bd0c035cbc2f1";
            $ciphering = "AES-128-CTR";
            $options = 0;
            $encryption_iv = '1825562541011121';
            $encryption_key = "42c5bd69a4e320p1a0b74946e6e05e507";

            $data = [
                'api' => $api,
                'order_id' => $order_id,
                'username' => $username,
                'domain' => $domain,
                'productId' => $productId,
                'hash' => $random_hash,
                'time'=>Carbon::now()->timestamp
            ];

            $encryption = openssl_encrypt(json_encode($data), $ciphering,
                $encryption_key, $options, $encryption_iv);
            $license = \App\Models\Setting::updateOrCreate([
                'group' => 'default',
                'code' => 'license',
            ], [
                'longtext_value' => \Illuminate\Support\Facades\Crypt::encrypt($encryption)
            ]);
            if ($license) {
                return redirect()->to(env('CLIENT_URL'));
            }
        }else{
            Session::put('error',$error);
            return redirect()->back();
        }
    }

    public function globalSetting(Request $request)
    {
        $global_settings=Setting::query()->whereIn('code',['general_site_name','panel_logo','general_logo'])->get()->pluck('user_show','code');
        return ($global_settings && count($global_settings)>0)?$global_settings:[];
    }

    public function serverSideSettings()
    {
        $city = SpadLocation::city();
        $output=[
            'city'=>[
                'id' => $city->id,
                'name' => $city->name,
                'province_id' => $city->province_id,
            ],
        ];
        $res=Setting::whereIn('code',['general_site_name','general_favicon'])->get()->pluck('user_show','code')->toArray();
        $settings=($res && count($res)>0)?$res:[];
        return array_merge($output,$settings);
    }
}
