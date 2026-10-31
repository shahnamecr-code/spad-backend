<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait BuildingUnitActions
{
    private function unitData(Request $request)
    {
        $d=$request->validate([
            'floor_number'=>'required|integer|min:-10|max:250',
            'unit_number'=>'required|string|max:30',
            'postal_code'=>['nullable','regex:/^[0-9]{10}$/'],
            'owner_name'=>'required|string|min:3|max:180',
            'owner_phone'=>['required','string','max:20','regex:/^[0-9+ -]{7,20}$/'],
            'is_rented'=>'required|boolean',
            'tenant_first_name'=>'required_if:is_rented,1|nullable|string|max:100',
            'tenant_last_name'=>'required_if:is_rented,1|nullable|string|max:100',
            'tenant_phone'=>['required_if:is_rented,1','nullable','string','max:20','regex:/^[0-9+ -]{7,20}$/'],
            'tenant_move_out_date'=>'required_if:is_rented,1|nullable|date_format:Y-m-d',
            'water_bill_id'=>['nullable','regex:/^[0-9]{5,40}$/'],
            'water_subscription_no'=>'nullable|string|max:50',
            'electricity_bill_id'=>['nullable','regex:/^[0-9]{5,40}$/'],
            'electricity_subscription_no'=>'nullable|string|max:50',
            'gas_bill_id'=>['nullable','regex:/^[0-9]{5,40}$/'],
            'gas_subscription_no'=>'nullable|string|max:50',
            'landline_number'=>['nullable','regex:/^[0-9]{8,15}$/'],
            'landline_bill_id'=>['nullable','regex:/^[0-9]{5,40}$/'],
            'municipal_renovation_code'=>'nullable|string|max:50',
            'municipal_property_code'=>'nullable|string|max:50',
            'deed_main_no'=>'nullable|string|max:50',
            'deed_sub_no'=>'nullable|string|max:50',
            'deed_registration_section'=>'nullable|string|max:50',
            'deed_area_sqm'=>'nullable|numeric|min:0.01|max:9999999999',
            'deed_unique_id'=>'nullable|string|max:60',
            'tax_file_reference'=>'nullable|string|max:80'
        ]);
        $d['label']='طبقه '.$d['floor_number'].' واحد '.$d['unit_number'];
        $d['resident_name']=$d['is_rented']
            ? trim(($d['tenant_first_name']??'').' '.($d['tenant_last_name']??'')) : $d['owner_name'];
        if(!$d['is_rented'])foreach(['tenant_first_name','tenant_last_name','tenant_phone','tenant_move_out_date'] as $key)$d[$key]=null;
        return $d;
    }
    private function listBuildingUnits($id)
    {
        return DB::table('spad_building_units')->where('building_id',$id)
            ->orderBy('floor_number')->orderBy('unit_number')->limit(500)->get();
    }
    private function saveBuildingUnit($building, Request $request, $unitId=null)
    {
        $d=$this->unitData($request);
        $q=DB::table('spad_building_units')->where('building_id',$building->id);
        if($unitId)abort_unless((clone $q)->where('id',$unitId)->exists(),404);
        else abort_if((clone $q)->count()>=$building->unit_count,422,'ظرفیت واحدهای ساختمان تکمیل شده است.');
        abort_if((clone $q)->where('label',$d['label'])
            ->when($unitId,function($query)use($unitId){$query->where('id','!=',$unitId);})
            ->exists(),422,'طبقه و شماره واحد تکراری است.');
        if($unitId){
            (clone $q)->where('id',$unitId)->update(array_merge($d,['updated_at'=>now()]));
            return response()->json(['message'=>'مشخصات آپارتمان ذخیره شد.']);
        }
        $id=DB::table('spad_building_units')->insertGetId(array_merge($d,[
            'building_id'=>$building->id,'status'=>'vacant','created_at'=>now(),'updated_at'=>now()
        ]));
        return response()->json(['id'=>$id,'message'=>'آپارتمان اضافه شد.'],201);
    }
    private function deleteBuildingUnit($building,$unitId)
    {
        return DB::transaction(function()use($building,$unitId){
            $q=DB::table('spad_building_units')->where('building_id',$building->id)->where('id',$unitId);
            abort_unless((clone $q)->lockForUpdate()->first(),404);
            abort_if(DB::table('spad_building_charges')->where('unit_id',$unitId)->exists() ||
                DB::table('spad_building_tickets')->where('unit_id',$unitId)->exists(),422,
                'واحد دارای سوابق مالی یا درخواست است؛ برای حفظ سوابق امکان حذف وجود ندارد.');
            $q->delete();
            return response()->json(['message'=>'واحد حذف شد.']);
        });
    }
}
