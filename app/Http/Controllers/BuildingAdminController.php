<?php
namespace App\Http\Controllers;
use App\Models\PartnerPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingAdminController extends Controller
{
    use BuildingUnitActions;

    private function authorizeAdmin()
    {
        abort_unless(auth('admin')->check(),403,'دسترسی مدیر اسپاد لازم است.');
    }
    private function fields(Request $r)
    {
        $d=$r->validate([
            'name'=>'required|string|min:3|max:180','city'=>'required|string|max:110',
            'address'=>'required|string|min:10|max:600','unit_count'=>'required|integer|min:1|max:500',
            'default_charge_toman'=>'nullable|integer|min:0|max:999999999999',
            'status'=>'required|in:pending,approved,rejected,suspended',
            'partner_panel_id'=>'nullable|integer',
        ]);
        $panelId=$d['partner_panel_id']??null;
        unset($d['partner_panel_id']);
        if($panelId){
            $panel=PartnerPanel::where('id',$panelId)->where('type',PartnerPanel::TYPE_BUILDING_MANAGER)
                ->where('status',PartnerPanel::STATUS_ACTIVE)->first();
            abort_unless($panel,422,'پنل مدیر ساختمان فعال یافت نشد.');
            $d['owner_user_id']=$panel->user_id;
            $d['partner_panel_id']=$panel->id;
        } else {
            $d['partner_panel_id']=null;
        }
        $d['default_charge_toman']=$d['default_charge_toman']??0;
        return $d;
    }
    public function create(Request $r)
    {
        $this->authorizeAdmin();$d=$this->fields($r);
        $id=DB::table('spad_buildings')->insertGetId(array_merge($d,[
            'owner_user_id'=>$d['owner_user_id']??null,
            'created_at'=>now(),'updated_at'=>now()
        ]));
        return response()->json(['id'=>$id,'message'=>'ساختمان ایجاد شد.'],201);
    }
    public function update(Request $r,$id)
    {
        $this->authorizeAdmin();$d=$this->fields($r);
        $b=DB::table('spad_buildings')->where('id',$id)->first();abort_unless($b,404);
        abort_if(DB::table('spad_building_units')->where('building_id',$id)->count()>$d['unit_count'],422,
            'تعداد واحدهای ثبت‌شده بیشتر از ظرفیت جدید است.');
        if(!array_key_exists('owner_user_id',$d)){
            $d['owner_user_id']=$b->partner_panel_id?null:$b->owner_user_id;
        }
        DB::table('spad_buildings')->where('id',$id)->update(array_merge($d,['updated_at'=>now()]));
        return response()->json(['message'=>'ساختمان ویرایش شد.']);
    }
    public function delete($id)
    {
        $this->authorizeAdmin();
        return DB::transaction(function()use($id){
            $b=DB::table('spad_buildings')->where('id',$id)->lockForUpdate()->first();abort_unless($b,404);
            foreach(['spad_building_units','spad_building_charges','spad_building_expenses',
                'spad_building_notices','spad_building_tickets','spad_building_bills'] as $table)
                abort_if(DB::table($table)->where('building_id',$id)->exists(),422,
                    'برای حفظ سوابق ساکنان و پرداخت‌ها فقط ساختمان بدون اطلاعات مرتبط حذف می‌شود.');
            DB::table('spad_buildings')->where('id',$id)->delete();
            return response()->json(['message'=>'ساختمان حذف شد.']);
        });
    }
    private function building($id)
    {
        $this->authorizeAdmin();
        $b=DB::table('spad_buildings')->where('id',$id)->first();
        abort_unless($b,404);return $b;
    }
    public function units($id)
    {
        $this->building($id);
        return response()->json(['data'=>$this->listBuildingUnits($id)]);
    }
    public function addUnit(Request $r,$id)
    {
        return $this->saveBuildingUnit($this->building($id),$r);
    }
    public function updateUnit(Request $r,$id,$unitId)
    {
        return $this->saveBuildingUnit($this->building($id),$r,$unitId);
    }
    public function deleteUnit($id,$unitId)
    {
        return $this->deleteBuildingUnit($this->building($id),$unitId);
    }
}
