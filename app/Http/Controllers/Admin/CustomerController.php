<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spad\Crm\Models\CrmRequest;

class CustomerController extends Controller {

    public function index(Request $request) {
        $users = User::query();

        $request->whenFilled('search',function ($data) use ($users){
            $users->where('first_name','like',"%$data%")->orWhere('last_name','like',"%$data%");
        });

        $paginate = $users->paginate($request->whenHas('per_page', function ($data) {
            return (int)$data;
        }));

        return $paginate;
    }

    public function store(CustomerRequest $request) {
        $data = $request->validated();
        $data['password'] = Hash::make(Str::random(8));
        $user = User::create($data);
        return response()->json(['status' => true, "message" => "با موفقیت افزوده شد."]);
    }

    public function show(User $user) {
        $userRes = $user->toArray();
        return new JsonResponse($userRes);
    }

    public function update(CustomerRequest $request, User $user) {
        $data = $request->validated();
        $user->update($data);

        return response()->json(['status' => true, 'message' => "با موفقیت به روز شد."]);
    }

    public function destroy(User $user) {
        $exception=DB::transaction(function ()use($user){
            $crm_requests=$user->crm_requests()->get();
            foreach ($crm_requests as $crm_request){
                $crm_request->delete();
            }
            $user->delete();
        });
        if(is_null($exception)){
            return response()->json([
                'status'=>true,
                'message'=>'کارشناس حذف شد.'
            ]);
        }else{
            return response()->json([
                'status'=>true,
                'message'=>'مشکل در حذف کارشناس'
            ]);
        }
    }
}
