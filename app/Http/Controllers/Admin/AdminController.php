<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller {

    public function index() {
        $admins = Admin::where("owner", true)
            ->paginate(request()->whenHas('per_page', function ($data) {
                return (int)$data;
            }));
        return new JsonResponse($admins);
    }

    public function store(AdminRequest $adminRequest) {
        Admin::create($adminRequest->validated());

        return response()->json(['status' => true, "msg" => "با موفقیت افزوده شد."]);
    }

    public function show(Admin $admin) {
        $admin->load(['roles', 'permissions']);
        return response()->json(['status' => true, 'admin' => $admin]);
    }

    public function update(AdminRequest $adminRequest, Admin $admin) {
        $admin->update($adminRequest->validated());

        return response()->json(['status' => true], "با موفقیت به روز شد.");
    }

    public function destroy(Admin $admin) {
        $exception=DB::transaction(function ()use($admin){
            DB::table('admin_city')->where('admin_id',$admin->id)->delete();
            DB::table('admin_region')->where('admin_id',$admin->id)->delete();
            DB::table('admin_category')->where('admin_id',$admin->id)->delete();
            DB::table('admin_skill')->where('admin_id',$admin->id)->delete();
            $admin->delete();
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
