<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SpadSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpadSmsCampaignController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless($request->user() &&
            $request->user()->roles()->where('name','Super Admin')->exists(),
            403,'مدیریت پیامک فقط برای مدیر اصلی مجاز است.');
        abort_unless(Schema::hasTable('spad_customer_sms_outbox')
            && Schema::hasTable('spad_crm_contacts')
            && Schema::hasColumn('spad_crm_contacts','budget_toman'),
            503,'مرکز مشتریان و صف پیامک هنوز نصب نشده است.');
    }

    public function index(Request $request)
    {
        $this->guard($request);
        $q=DB::table('spad_crm_contacts')->whereNull('deleted_at')->whereNull('opted_out_at')
            ->where('sms_consent',true)->whereNotNull('consent_recorded_at');
        $search=trim((string)$request->query('q',''));
        if($search!=='' && mb_strlen($search)<=70){
            $q->where(function($w)use($search){
                $w->where('full_name','like','%'.$search.'%')
                  ->orWhere('mobile','like','%'.$search.'%');
            });
        }
        $contacts=$q->select('id','full_name','mobile','budget_toman')
            ->orderByDesc('id')->limit(150)->get()->map(function($c){
                $c->mobile_masked=substr($c->mobile,0,4).'***'.substr($c->mobile,-4);
                unset($c->mobile);
                return $c;
            });
        $logs=DB::table('spad_customer_sms_outbox as o')
            ->leftJoin('spad_crm_contacts as c','c.id','=','o.contact_id')
            ->select('o.id','o.kind','o.status','o.error_code','o.created_at','c.full_name')
            ->orderByDesc('o.id')->limit(45)->get();
        return response()->json([
            'enabled'=>app(SpadSmsService::class)->eventEnabled('manual_customer_sms'),
            'property_match_enabled'=>app(SpadSmsService::class)->eventEnabled('property_match'),
            'contacts'=>$contacts,
            'logs'=>$logs,
            'queued'=>DB::table('spad_customer_sms_outbox')->where('status','pending')->count()
        ])->header('Cache-Control','private, no-store');
    }

    public function dispatch(Request $request)
    {
        $this->guard($request);
        $data=$request->validate([
            'contact_ids'=>'required|array|min:1|max:20',
            'contact_ids.*'=>'required|integer|min:1|distinct',
            'text'=>'required|string|min:10|max:400',
            'request_id'=>'required|uuid'
        ]);
        abort_unless(app(SpadSmsService::class)->eventEnabled('manual_customer_sms'),
            422,'ابتدا ارسال دستی را در تنظیمات ایران پیامک فعال کنید.');
        $hour=(int)now()->timezone('Asia/Tehran')->format('G');
        abort_if($hour<9||$hour>=19,422,'ارسال فقط در ساعت ۹ تا ۱۹ به وقت ایران مجاز است.');
        $contacts=DB::table('spad_crm_contacts')->whereIn('id',$data['contact_ids'])
            ->whereNull('deleted_at')->whereNull('opted_out_at')
            ->where('sms_consent',true)->whereNotNull('consent_recorded_at')->get();
        abort_unless($contacts->count()===count($data['contact_ids']),422,
            'برخی از مشتریان بدون رضایت یا غیرفعال هستند.');
        abort_if(DB::table('spad_customer_sms_outbox')
            ->whereDate('created_at',now()->toDateString())->count()+count($data['contact_ids'])>100,
            429,'سقف روزانه ۱۰۰ پیامک تکمیل شده است.');
        $rows=[];
        foreach($contacts as $c){
            abort_unless(preg_match('/^09[0-9]{9}$/D',(string)$c->mobile),422,'شماره مشتری معتبر نیست.');
            $rows[]=[
                'contact_id'=>$c->id,'admin_id'=>$request->user()->id,
                'kind'=>'manual','message'=>trim($data['text']),
                'dedup_hash'=>hash('sha256','manual:'.$data['request_id'].':'.$c->id),
                'status'=>'pending','created_at'=>now(),'updated_at'=>now()
            ];
        }
        $inserted=DB::table('spad_customer_sms_outbox')->insertOrIgnore($rows);
        return response()->json([
            'queued'=>$inserted,
            'message'=>'پیامک‌ها در صف قرار گرفتند؛ نتیجه ارسال را در گزارش بررسی کنید.'
        ],202);
    }
}
