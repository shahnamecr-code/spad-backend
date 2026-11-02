<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SpadCrmDeliveryService {
 public static function readiness(){
  return [
   'sms'=>(bool)(config('services.spad_crm.sms_enabled') && config('services.spad_crm.sms_key') && config('services.spad_crm.sms_line')),
   'voice'=>(bool)(config('services.spad_crm.voice_enabled') && config('services.spad_crm.voice_key')),
   'sms_provider'=>'SMS.ir',
   'voice_provider'=>'خودگو',
  ];
 }
 public static function send($channel,$phone,$message){
  $ready=self::readiness();
  if(empty($ready[$channel]))throw new RuntimeException('provider_not_configured');
  if($channel==='sms'){
   // Single recipient only. Provider accepts an order, not proof of delivery.
   $response=Http::withHeaders(['X-API-KEY'=>config('services.spad_crm.sms_key')])
    ->connectTimeout(4)->timeout(12)
    ->post('https://api.sms.ir/v1/send/bulk',[
     'lineNumber'=>config('services.spad_crm.sms_line'),
     'messageText'=>$message,'mobiles'=>[$phone]
    ]);
   $json=$response->json();
   if(!$response->successful()||!is_array($json)|| (int)($json['status']??0)!==1)
    throw new RuntimeException('sms_provider_rejected');
   $ids=$json['data']['messageIds']??[];
   $ref=$json['data']['packId']??($ids[0]??null);
   return ['reference'=>$ref ? (string)$ref : null,'provider'=>'SMS.ir'];
  }
  if($channel==='voice'){
   // Documented one-to-one TTS telephone call. No voice impersonation.
   $response=Http::asForm()->connectTimeout(4)->timeout(15)->post(
    'https://khodgoo.com/api/calls',[
     'api_key'=>config('services.spad_crm.voice_key'),'to'=>$phone,'message'=>$message
    ]);
   $json=$response->json();
   if(!$response->successful()||!is_array($json)||($json['success']??false)!==true)
    throw new RuntimeException('voice_provider_rejected');
   return ['reference'=>isset($json['call_id'])?(string)$json['call_id']:null,'provider'=>'خودگو'];
  }
  throw new RuntimeException('unsupported_delivery_channel');
 }
}
