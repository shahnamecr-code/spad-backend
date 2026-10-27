<?php

namespace Spad\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class CustomerController extends Controller
{
    public function checkExistCustomer(Request $request)
    {
        $data=$this->validate($request,[
            'mobile'=>'required',
        ],[
            'mobile.required'=>'وارد کردن شماره تلفن الزامی است'
        ]);
        $data['mobile']=fix_mobile($data['mobile']);

        if ($customer=User::firstWhere('mobile',$data['mobile'])){
            return response()->json([
                'status'=>true,
                'customer'=>$customer->only('id','first_name','last_name','mobile')
            ]);
        }else{
            return response()->json([
                'status'=>false
            ]);
        }

    }

    public function addCustomer(Request $request)
    {
        $data=$this->validate($request,[
            'first_name' => 'required',
            'last_name' => 'required',
//            'email' => ['nullable', 'email'],
            'mobile' => ['required',Rule::unique('users')],
//            'password' => ['required']
        ]);

        $data['mobile'] = fix_mobile($data['mobile']);
        $data['password']=Hash::make(Str::random(8));

        if ($user = User::create($data)) {
            return response()->json([
                'status' => true,
                'message' => 'مشتری افزوده شد',
                'customer'=>$user->only('id','first_name','last_name','mobile')
            ]);
        }
    }


}
