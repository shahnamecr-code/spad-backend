<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CustomerAddressController extends Controller {

    public function index(User $user) {
        $address = Address::query()->where('user_id', $user->id)
            ->paginate(request()->whenHas('per_page', function ($data) {
                return (int)$data;
            }));
        return new JsonResponse(['user' => $user, 'addresses' => $address]);
    }


    public function store(CustomerAddressRequest $request, User $user) {
        $data = $request->validated();
        $address = new Address($data);
        $user->addresses()->save($address);


        return response()->json(['status' => true, "message" => "با موفقیت افزوده شد."]);
    }


    public function show(Address $address) {
        return new JsonResponse($address);
    }


    public function update(CustomerAddressRequest $request, Address $address) {
        $data = $request->validated();
        $address->update($data);
        return response()->json(['status' => true, 'message' => "با موفقیت به روز شد."]);
    }


    public function destroy(Address $address) {
        //
    }
}
