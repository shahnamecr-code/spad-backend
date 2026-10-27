<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperatorRequest;
use App\Http\Resources\InventoryResource;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperatorController extends Controller {

    public function index() {
        $per_page = request()->whenHas('per_page', function ($data) {
            return (int)$data;
        });
        $admins = Admin::query()->paginate($per_page);

        return response()->json($admins);
    }

    public function store(OperatorRequest $request) {
        $data = $request->validated();

        if ($data['profile'] && is_array($data['profile']) && count($data['profile'])>0  && isset($data['profile'][0]['id'])){
            $data['profile']=$data['profile'][0]['id'];
        }else{
            $data['profile']=null;
        }

        $roles=[$data['role']];
        $permissions=[];
        if (isset($data['permissionNames'])){
            $permissions=(array)$data['permissionNames'];
        }
        $data['status'] = $request->get('status');
        $admin=Admin::create($data);
        $admin->syncRoles($roles);
        $admin->syncPermissions($permissions);

        return response()->json(['status' => true, "message" => "با موفقیت افزوده شد."]);
    }

    public function show(Admin $admin) {
        $admin->load(['roles']);
        $admin->permissionNames=$admin->permissions->pluck('name');
        if ($admin->profile_file){
            $admin->profile=[$admin->profile_file->only(['id','url'])];
        }else{
            $admin->profile=null;
        }
        return new JsonResponse($admin);
    }

    public function update(OperatorRequest $request, Admin $admin) {
        $data = $request->validated();

        if ($data['profile'] && is_array($data['profile']) && count($data['profile'])>0){
            $data['profile']=$data['profile'][0]['id'];
        }else{
            $data['profile']=null;
        }

        $roles=[$data['role']];
        unset($data['role']);
        $permissions=[];
        if (isset($data['permissionNames'])){
            $permissions=(array)$data['permissionNames'];
            unset($data['permissionNames']);
        }
        $admin->update($data);
        $admin->syncRoles($roles);
        $admin->syncPermissions($permissions);
        return response()->json(['status' => true, 'message' => "با موفقیت به روز شد."]);
    }

    public function destroy(Admin $admin) {

    }

    public function getPermissionable(Admin $admin) {
        $result = [
            'categories' => $admin->allowed_categories->pluck('id'),
            'cities' => $admin->cities->map(function ($items){
                return $items->only(['id','name']);
            }),
            'regions' => $admin->regions->map(function ($item){
                return [
                    "label"=>"{$item->city->name}-{$item->name}",
                    "value"=>$item->id,
                ];
            }),
            'experts'=>$admin->experts()->role('expert')->select(['id','first_name','last_name'])->get()->each->append('display_name'),
        ];
        return new JsonResponse($result);
    }

    public function storePermission(Request $request, Admin $admin) {
        $categories=$request->input('categories',[]);
        $cities=$request->input('cities',[]);
        $regions=$request->input('regions',[]);
        $experts=$request->input('experts',[]);
        $expert_ids=array_column($experts,'id');
        $admin->allowed_categories()->sync($categories);
        $admin->cities()->sync($cities?array_column($cities,'id'):[]);
        $admin->regions()->sync($regions?array_column($regions,'value'):[]);
        DB::table('admins')
            ->where('manager_id',$admin->id)
            ->whereNotIn('id',$expert_ids)
            ->update(['manager_id'=>null]);
        DB::table('admins')
            ->whereIn('id',$expert_ids)
            ->update(['manager_id'=>$admin->id]);
        return response()->json(['status' => true, 'message' => "با موفقیت به روز شد."]);
    }
}
