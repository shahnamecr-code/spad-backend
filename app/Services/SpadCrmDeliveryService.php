<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SpadCrmDeliveryService
{
    public static function readiness()
    {
        $sms=app(SpadSmsService::class);
        return [
            'sms'=>$sms->eventEnabled('manual_customer_sms'),
            'voice'=>(bool)(config('services.spad_crm.voice_enabled') && config('services.spad_crm.voice_key')),
            'sms_provider'=>'IranPayamak',
            'voice_provider'=>'خودگو',
        ];
    }
    public static function send($channel,$phone,$message,$reference=null)
    {
        $ready=self::readiness();
        if(empty($ready[$channel])) throw new RuntimeException('provider_not_configured');
        if($channel==='sms'){
            $ok=app(SpadSmsService::class)->sendManual($phone,$message,
                $reference ? 'crm-manual:'.$reference : null);
            if(!$ok) throw new RuntimeException('sms_provider_rejected');
            return ['reference'=>null,'provider'=>'IranPayamak'];
        }
        if($channel==='voice'){
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
