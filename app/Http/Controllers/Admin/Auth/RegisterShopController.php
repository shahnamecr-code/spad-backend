<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Shop;
use App\Models\ShopActual;
use App\Models\ShopLegal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegisterShopController extends Controller {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request) {
        $data = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email', 'unique:admins'],
            'password' => ['required', 'confirmed']
        ]);
        $data['username'] = $request->input('email');
        $data['status'] = Admin::APPROVED;
        $data['password'] = \Hash::make($request->get('password'));
        $shop = new Shop();
        $shop->status = Shop::REGISTER;
        $shop->save();
        $data['shop_id'] = $shop->id;
        $admin = Admin::create($data);

        sendEmailVerifyToken($admin);

        auth('admin')->login($admin);
        return new JsonResponse(['status' => true]);
    }

    public function verify_email(Request $request) {
        $data = $this->validate($request, [
            'verify_code' => ['required']
        ]);

        $token = Cache::get('user_email_verify_' . auth('admin')->id());
        if ($token && $token == $data['verify_code']) {
            auth('admin')->user()->markEmailAsVerified();
            return new JsonResponse(['status' => true]);
        } else {
            return new JsonResponse([
                'status' => false,
                'errors' => [
                    'verify_code' => ['کد وارد شده اشتباه می باشد.']
                ],
            ], 422);
        }
    }

    public function send_again_verify_email(Request $request) {
        $data = $this->validate($request, [
            'email' => ['required','email'],
        ]);
        if (!Cache::has('user_email_verify_' . auth('admin')->id())) {
            $user = auth('admin')->user();
            sendEmailVerifyToken($user);
            $data['username'] = $data['email'];
            $user->update($data);
            return new JsonResponse(['status' => true, 'message' => 'کد جدید با موفقیت ارسال شد.']);
        } else {
            return new JsonResponse(['status' => false, "message" => "لطفا چند دقیقه دیگر امتحان کنید."]);
        }

    }


    public function verify_mobile(Request $request) {
        $data = $this->validate($request, [
            'verify_code' => ['required']
        ]);

        $token = Cache::get('user_mobile_verify_' . auth('admin')->id());
        if ($token && $token == $data['verify_code']) {
            auth('admin')->user()->markMobileAsVerified();
            return new JsonResponse(['status' => true]);
        } else {
            return new JsonResponse([
                'status' => false,
                'errors' => [
                    'verify_code' => ['کد وارد شده اشتباه می باشد.']
                ],
            ], 422);
        }
    }


    public function send_again_verify_mobile(Request $request) {
        $data = $this->validate($request, [
            'mobile' => ['required'],
        ]);
        if (!Cache::has('user_mobile_verify_' . auth('admin')->id())) {
            $user = auth('admin')->user();
            sendMobileVerifyToken($user);
            $user->update($data);
            return new JsonResponse(['status' => true, 'message' => 'کد جدید با موفقیت ارسال شد.']);
        } else {
            return new JsonResponse(['status' => false, "message" => "لطفا چند دقیقه دیگر امتحان کنید."]);
        }

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function completeRegister(Request $request) {
        $request->validate(['type' => ['required', 'in:0,1']]);
        $type = $request->get('type');
        if ($type == '0') {
            $data = $request->validate([
                'first_name' => ['required'],
                'last_name' => ['required'],
                'birthday' => ['required'],
                'identity_card_number' => ['required'],
                'national_identity_number' => ['required', 'min:10'],
                'gender' => ['required', 'in:women,men'],
            ]);
        } elseif ($type == '1') {
            $data = $request->validate([
                'company_name' => ['required', 'string'],
                'company_type' => ['required', 'in:0,1,2,3,4,5'],
                'register_number' => ['required', 'min:10'],
                'identity_number' => ['required', 'min:10'],
                'economic_number' => ['required', 'min:10']
            ]);
        }

        $shop = Shop::find(auth('admin')->user()->shop_id);
        if ($shop->type == null) {
            $shop->status = Shop::COMPLETE_REGISTER;
            if ($type == 'actual') {
                $shop->type = 1;
                $actual = ShopActual::firstOrNew($data);
                $shop->shopActual()->save($actual);
            } elseif ($type == 'legal') {
                $shop->type = 2;
                $legal = ShopLegal::firstOrNew($data);
                $shop->shopLegal()->save($legal);
            }
            $shop->save();
        }

        return new JsonResponse(['status' => true]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function contactInfo(Request $request) {
        $data = $this->validate($request, [
            'address' => ['required', 'string', 'min:10'],
            'city_id' => ['required'],
            'state_id' => ['required'],
            'post_code' => ['required', 'string', 'min:10'],
            'phone' => ['required'],
            'mobile' => ['required'],
            'lat_and_long' => ['required', 'array']
        ], [], ['post_code' => "کد پستی"]);

        $data['lat'] = $data['lat_and_long'][0];
        $data['lng'] = $data['lat_and_long'][1];
        unset($data['lat_and_long']);
        $user = auth('admin')->user();
        $user->update($data);
        $shop = Shop::findOrFail($user->shop_id);
        $shop->status = Shop::CONTACT_INFO;
        $shop->update($data);
        sendMobileVerifyToken($user);
        return new JsonResponse(['status' => true]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function businessInfo(Request $request) {
        $data = $this->validate($request, [
            'name' => ['required', 'unique:shops'],
            'shaba_number' => ['required', 'min:24', 'max:24']
        ]);

        $shop = Shop::findOrFail(auth('admin')->user()->shop_id);
        $data['status'] = Shop::BUSINESS_INFO;
        $shop->update($data);

        return new JsonResponse(['status' => true]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function uploadDocument(Request $request) {
        $this->validate($request, [
            'maliyat' => ['required'],
            'frontIdCard' => ['required', 'image'],
            'backIdCard' => ['required', 'image'],
            'photoTax' => ['required_if:maliyat,true', 'image'],

        ]);
        $shop = Shop::findOrFail(auth('admin')->user()->shop_id);
        $shop->status = Shop::PROCESSING;
        $shop->media()->delete();
        $shop->addMedia($request->file('frontIdCard'))->toMediaCollection('frontIdCard');
        $shop->addMedia($request->file('backIdCard'))->toMediaCollection('backIdCard');
        if ($request->has('photoTax')) {
            $shop->addMedia($request->file('photoTax'))->toMediaCollection('photoTax');
        }
        $shop->save();
        return new JsonResponse(['status' => false]);
    }
}
