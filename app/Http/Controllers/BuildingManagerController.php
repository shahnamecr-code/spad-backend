<?php
namespace App\Http\Controllers;
use App\Models\PartnerPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingManagerController extends Controller
{
    use BuildingUnitActions;

    private function panel(Request $r,$panelId)
    {
        $u=$r->user();abort_unless($u,401);
        $panel=PartnerPanel::where('id',$panelId)->where('user_id',$u->id)
            ->where('type',PartnerPanel::TYPE_BUILDING_MANAGER)
            ->where('status',PartnerPanel::STATUS_ACTIVE)->first();
        abort_unless($panel,403,'پنل مدیر ساختمان برای شما فعال نشده است.');
        return $panel;
    }
    private function building(Request $r,$panelId,$id)
    {
        $p=$this->panel($r,$panelId);
        $b=DB::table('spad_buildings')->where('id',$id)
            ->where('partner_panel_id',$p->id)->where('owner_user_id',$p->user_id)->first();
        abort_unless($b,404);return $b;
    }
    private function approved($b)
    {
        abort_unless($b->status==='approved',422,'ساختمان هنوز توسط مدیریت اسپاد تأیید نشده است.');
    }
    public function index(Request $r,$panelId)
    {
        $p=$this->panel($r,$panelId);
        return response()->json(['data'=>DB::table('spad_buildings')->where('partner_panel_id',$p->id)
            ->where('owner_user_id',$p->user_id)->orderByDesc('id')->limit(100)->get()]);
    }
    public function create(Request $r,$panelId)
    {
        $p=$this->panel($r,$panelId);
        $d=$r->validate(['name'=>'required|string|min:3|max:180','city'=>'required|string|max:110',
            'address'=>'required|string|min:10|max:600','unit_count'=>'required|integer|min:1|max:500',
            'default_charge_toman'=>'nullable|integer|min:0|max:999999999999']);
        $id=DB::table('spad_buildings')->insertGetId(array_merge($d,[
            'default_charge_toman'=>$d['default_charge_toman']??0,'owner_user_id'=>$p->user_id,
            'partner_panel_id'=>$p->id,'status'=>'pending','created_at'=>now(),'updated_at'=>now()
        ]));
        return response()->json(['id'=>$id,'message'=>'ساختمان برای تأیید مدیریت اسپاد ثبت شد.'],201);
    }
    public function detail(Request $r,$panelId,$id)
    {
        $b=$this->building($r,$panelId,$id);
        $read=function($table,$max=200)use($id){return DB::table($table)->where('building_id',$id)->orderByDesc('id')->limit($max)->get();};
        return response()->json(['data'=>[
            'building'=>$b,'units'=>$this->listBuildingUnits($id),
            'charges'=>$read('spad_building_charges',400),
            'expenses'=>$read('spad_building_expenses'),
            'bills'=>$read('spad_building_bills')
        ]]);
    }
    public function addUnit(Request $r,$panelId,$id)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        return $this->saveBuildingUnit($b,$r);
    }
    public function updateUnit(Request $r,$panelId,$id,$unitId)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        return $this->saveBuildingUnit($b,$r,$unitId);
    }
    public function deleteUnit(Request $r,$panelId,$id,$unitId)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        return $this->deleteBuildingUnit($b,$unitId);
    }
    public function charge(Request $r,$panelId,$id)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        $d=$r->validate(['unit_id'=>'required|integer','title'=>'required|string|max:170',
            'amount_toman'=>'required|integer|min:1|max:999999999999','due_date'=>'nullable|date_format:Y-m-d']);
        abort_unless(DB::table('spad_building_units')->where('building_id',$id)->where('id',$d['unit_id'])->exists(),422);
        DB::table('spad_building_charges')->insert(array_merge($d,[
            'building_id'=>$id,'status'=>'unpaid','recorded_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()
        ]));
        return response()->json(['message'=>'شارژ ثبت شد؛ پرداخت آنلاین خودکار نیست.'],201);
    }
    public function chargeStatus(Request $r,$panelId,$id,$chargeId)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        $d=$r->validate(['status'=>'required|in:paid,unpaid']);
        $q=DB::table('spad_building_charges')->where('building_id',$id)->where('id',$chargeId);
        abort_unless((clone $q)->exists(),404);
        $q->update(['status'=>$d['status'],'paid_at'=>$d['status']==='paid'?now():null,
            'recorded_by'=>$r->user()->id,'updated_at'=>now()]);
        return response()->json(['message'=>'وضعیت پرداخت دستی ذخیره شد؛ استعلام بانکی انجام نشده است.']);
    }
    public function expense(Request $r,$panelId,$id)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        $d=$r->validate(['title'=>'required|string|max:170','amount_toman'=>'required|integer|min:1|max:999999999999',
            'expense_date'=>'required|date_format:Y-m-d','description'=>'nullable|string|max:2000']);
        DB::table('spad_building_expenses')->insert(array_merge($d,[
            'building_id'=>$id,'created_at'=>now(),'updated_at'=>now()
        ]));
        return response()->json(['message'=>'هزینه تعمیرات یا نگهداری ثبت شد.'],201);
    }
    public function bill(Request $r,$panelId,$id)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        $d=$r->validate([
            'kind'=>'required|in:water,electricity,gas,phone,municipal,tax,other',
            'title'=>'required|string|max:180',
            'bill_reference'=>'nullable|string|max:100',
            'payment_identifier'=>'nullable|string|max:60',
            'tax_category'=>'required_if:kind,tax|nullable|in:transfer,rental,other',
            'unit_id'=>'nullable|integer',
            'amount_toman'=>'required|integer|min:1|max:999999999999',
            'due_date'=>'nullable|date_format:Y-m-d'
        ]);
        if(!empty($d['unit_id'])){
            abort_unless(DB::table('spad_building_units')->where('building_id',$id)
                ->where('id',$d['unit_id'])->exists(),422,'واحد متعلق به ساختمان نیست.');
        } else { $d['unit_id']=null; }
        if($d['kind']==='tax')abort_unless($d['unit_id'],422,'برای حفظ حریم خصوصی، قبض مالیاتی باید به یک واحد مشخص مرتبط شود.');
        if($d['kind']!=='tax')$d['tax_category']=null;
        DB::table('spad_building_bills')->insert(array_merge($d,[
            'building_id'=>$id,'status'=>'unpaid','recorded_by'=>$r->user()->id,
            'created_at'=>now(),'updated_at'=>now()
        ]));
        return response()->json(['message'=>'قبض ثبت شد. پرداخت خودکار فعال نیست.'],201);
    }
    public function billStatus(Request $r,$panelId,$id,$billId)
    {
        $b=$this->building($r,$panelId,$id);$this->approved($b);
        $d=$r->validate(['status'=>'required|in:paid,unpaid']);
        $q=DB::table('spad_building_bills')->where('building_id',$id)->where('id',$billId);
        abort_unless((clone $q)->exists(),404);
        $q->update(['status'=>$d['status'],'paid_at'=>$d['status']==='paid'?now():null,'updated_at'=>now()]);
        return response()->json(['message'=>'وضعیت دستی قبض ثبت شد؛ پرداخت بانکی تأیید نشده است.']);
    }
}
