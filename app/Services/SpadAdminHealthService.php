<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SpadAdminHealthService
{
 const TABLE='spad_admin_server_health_samples';
 const INTERVAL=5;
 public static function available()
 {
  return Schema::hasTable(self::TABLE);
 }
 public static function sample($force=false)
 {
  if(!self::available())return false;
  $time=now()->startOfMinute();
  $time->minute(intdiv((int)$time->minute,self::INTERVAL)*self::INTERVAL)->second(0);
  $bucket=$time->toDateTimeString();
  if(!$force && DB::table(self::TABLE)->where('sampled_at',$bucket)->exists())return true;
  // The public-site probe is performed from the hosting server, not from a browser.
  // HTTPS origin is fixed to prevent an admin setting becoming an SSRF vector.
  $dbOk=false;$webOk=false;$dbMs=null;$appMs=null;$note=null;
  $start=microtime(true);
  try {
   DB::select('SELECT 1');
   $dbMs=(int)round((microtime(true)-$start)*1000);
   $dbOk=true;
  }catch(\Throwable $e){$note='database_unavailable';}
  $start=microtime(true);
  try {
   $res=Http::withOptions(['allow_redirects'=>false])->connectTimeout(2)->timeout(4)
      ->withHeaders(['User-Agent'=>'SPAD-Internal-Availability-Check/1.0'])
      ->get('https://www.spad.estate/');
   $appMs=(int)round((microtime(true)-$start)*1000);
   $webOk=$res->status()>=200 && $res->status()<400;
   if(!$webOk)$note='public_http_'.(int)$res->status();
  } catch(\Throwable $e) {
   $appMs=(int)round((microtime(true)-$start)*1000);
   $note='public_check_failed';
  }
  $values=[
   'database_ok'=>$dbOk,'application_ok'=>$webOk,
   'db_latency_ms'=>$dbMs,'app_latency_ms'=>$appMs,
   'note'=>$note,'updated_at'=>now()
  ];
  // Do not overwrite an existing sample on a page refresh; one result per
  // five-minute bucket. Historical gaps remain explicitly unknown, not downtime.
  DB::table(self::TABLE)->updateOrInsert(
   ['sampled_at'=>$bucket],
   $values+['created_at'=>now()]
  );
  return true;
 }
 public static function report($hours=24)
 {
  if(!self::available())return ['ready'=>false,'samples'=>[],'message'=>'سرویس پایش نصب نشده است.'];
  try {
   self::sample();
  }catch(\Throwable $e) {
   // A read-only report can still render cached samples if fresh probe fails.
  }
  $hours=$hours===168?168:24;
  $since=now()->subHours($hours)->startOfMinute();
  $points=DB::table(self::TABLE)->where('sampled_at','>=',$since)
    ->orderBy('sampled_at')->get([
      'sampled_at','database_ok','application_ok','db_latency_ms','app_latency_ms','note'
    ]);
  $samples=$points->map(function($row){
   return [
    'at'=>$row->sampled_at,
    'app_ok'=>(bool)$row->application_ok,
    'db_ok'=>(bool)$row->database_ok,
    'app_ms'=>$row->app_latency_ms,
    'db_ms'=>$row->db_latency_ms,
    'note'=>$row->note,
   ];
  })->values()->all();
  $count=count($samples);
  $success=count(array_filter($samples,function($item){return $item['app_ok'] && $item['db_ok'];}));
  $failures=array_values(array_filter($samples,function($item){return !$item['app_ok']||!$item['db_ok'];}));
  $latencies=array_values(array_filter(array_map(function($item){return $item['app_ok'] ? $item['app_ms'] : null;},$samples),function($v){return $v!==null;}));
  sort($latencies);
  $latencyCount=count($latencies);
  $avg=$latencyCount?(int)round(array_sum($latencies)/$latencyCount):null;
  $p95=$latencyCount?$latencies[max(0,(int)ceil($latencyCount*0.95)-1)]:null;
  $expected=(int)($hours*60/self::INTERVAL);
  // Elapsed since last recorded observation, not an uptime claim.
  $latestAge=$count?abs((int)now()->diffInSeconds(\Carbon\Carbon::parse($samples[$count-1]['at']))):null;
  // Samples are observations, not proof of 24h continuous uptime.
  return [
   'ready'=>true,'source'=>'server_probe','probe_target'=>'https://www.spad.estate/',
   'interval_minutes'=>self::INTERVAL,'sample_count'=>$count,'samples'=>$samples,
   'success_count'=>$success,'failed_count'=>count($failures),
   'coverage_percent'=>round(min(100,$count/max(1,$expected)*100),1),
   'window_hours'=>$hours,'expected_samples'=>$expected,
   'average_response_ms'=>$avg,'p95_response_ms'=>$p95,
   'latest_age_seconds'=>$latestAge,
   'incidents'=>array_slice(array_reverse($failures),0,12),
   'observed_success_percent'=>$count>=12?round($success/$count*100,1):null,
   'last_at'=>$count?$samples[$count-1]['at']:null,
   'message'=>$count<12?'پایش تازه آغاز شده و هنوز نمونه کافی برای درصد موفقیت وجود ندارد.':
      'این درصد فقط نسبت نمونه‌های موفق ثبت‌شده است، نه درصد قطعی آپتایم شبانه‌روزی.',
  ];
 }
}
