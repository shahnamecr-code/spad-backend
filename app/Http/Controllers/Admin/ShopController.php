<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopRequest;
use App\Http\Resources\AdminShopResource;
use App\Models\Admin;
use App\Models\Shop;
use App\Models\ShopActual;
use App\Models\ShopLegal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ShopController extends Controller {
    public function index() {
        return AdminShopResource::collection(Shop::query()->with('admin')
            ->paginate(request()->whenHas('per_page', function ($data) {
                return (int)$data;
            })));
    }

    public function store(ShopRequest $request) {

        \DB::transaction(function () use ($request) {
            $shop = Shop::forceCreate([
                'type' => $request->get('type'),
                'name' => $request->get('name'),
                'state_id' => $request->get('state_id'),
                'city_id' => $request->get('city_id'),
                'address' => $request->get('address'),
                'post_code' => $request->get('post_code'),
                'lat' => $request->get('lat_and_long')[0],
                'lng' => $request->get('lat_and_long')[1],
                'phone' => $request->get('phone'),
                'shaba_number' => $request->get('shaba_number'),
                'status' => $request->get('shop_status') ? Shop::APPROVED : Shop::PROCESSING,
            ]);
            if ($request->get('type') == 1) {
                $data = new ShopActual([
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                    'birthday' => $request->get('birthday'),
                    'identity_card_number' => $request->get('identity_card_number'),
                    'national_identity_number' => $request->get('national_identity_number'),
                    'gender' => $request->get('gender'),
                ]);
                $shop->shopActual()->save($data);
            } elseif ($request->get('type') == 2) {
                $data = new ShopLegal([
                    'company_name' => $request->get('company_name'),
                    'company_type' => $request->get('company_type'),
                    'register_number' => $request->get('register_number'),
                    'identity_number' => $request->get('identity_number'),
                    'economic_number' => $request->get('economic_number')
                ]);
                $shop->shopLegal()->save($data);
            }
            Admin::forceCreate([
                'first_name' => $request->get('user_first_name'),
                'last_name' => $request->get('user_last_name'),
                'email' => $request->get('email'),
                'username' => $request->get('email'),
                'phone' => $request->get('phone'),
                'mobile' => $request->get('mobile'),
                'password' => Hash::make($request->get('password')),
                'shop_id' => $shop->id,
                'owner' => true,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'address' => $request->get('address'),
                'status' => $request->get('admin_status') ? Admin::APPROVED : Admin::NOT_APPROVED
            ]);
        });

        return new JsonResponse(['status' => true]);
    }

    public function show(Shop $shop) {
        return new AdminShopResource($shop);
    }

    public function update(ShopRequest $request, Shop $shop) {
        \DB::transaction(function () use ($request, $shop) {
            $shop->type = $request->get('type');
            $shop->update([
                'name' => $request->get('name'),
                'state_id' => $request->get('state_id'),
                'city_id' => $request->get('city_id'),
                'address' => $request->get('address'),
                'post_code' => $request->get('post_code'),
                'lat' => $request->get('lat_and_long')[0],
                'lng' => $request->get('lat_and_long')[1],
                'phone' => $request->get('phone'),
                'shaba_number' => $request->get('shaba_number'),
                'status' => $request->get('shop_status') ? Shop::APPROVED : Shop::PROCESSING,
            ]);
            $isTypeChanged = $shop->wasChanged('type');
            if ($isTypeChanged) {
                if ($shop->type == 1) {
                    $shop->shopLegal()->delete();
                } elseif ($shop->type == 2) {
                    $shop->shopActual()->delete();
                }
            }

            if ($request->get('type') == 1) {
                $shop->shopActual()->{$isTypeChanged ? 'create' : 'update'}([
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                    'birthday' => $request->get('birthday'),
                    'identity_card_number' => $request->get('identity_card_number'),
                    'national_identity_number' => $request->get('national_identity_number'),
                    'gender' => $request->get('gender'),
                ]);
            } elseif ($request->get('type') == 2) {
                $shop->shopLegal()->{$isTypeChanged ? 'create' : 'update'}([
                    'company_name' => $request->get('company_name'),
                    'company_type' => $request->get('company_type'),
                    'register_number' => $request->get('register_number'),
                    'identity_number' => $request->get('identity_number'),
                    'economic_number' => $request->get('economic_number')
                ]);
            }

            $adminData = [
                'first_name' => $request->get('user_first_name'),
                'last_name' => $request->get('user_last_name'),
                'email' => $request->get('email'),
                'username' => $request->get('email'),
                'phone' => $request->get('phone'),
                'mobile' => $request->get('mobile'),
                'shop_id' => $shop->id,
                'owner' => true,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
                'address' => $request->get('address'),
            ];
            if ($request->has('password')) {
                $adminData['password'] = Hash::make($request->get('password'));
            }
            $shop->admin()->update($adminData);
        });
    }

    public function changeShopStatus(Request $request, Shop $shop) {
        $this->validate($request, [
            'type' => ['required'],
        ]);

        if ($request->get('type') == 'deny_shop') {
            $shop->status = Shop::NOT_APPROVED;
            $request->whenHas('reason', function ($value) use ($shop) {
                $shop->update(['reason' => $value]);
            });

        }

    }

    public function shopList() {
        $user = auth('admin')->user();
        if ($user->hasRole('Super Admin')) {
            return Shop::select(['name as label', 'id as value'])->get();
        }
        return $user->shops()->select(['name as label', 'id as value'])->get();
    }

    public function getShopsForSelect() {
        return Shop::select(['id as value', 'name as label'])->get();
    }
}
