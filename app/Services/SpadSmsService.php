<?php
namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SpadSmsService
{
    // A single catalogue drives the admin switches, pattern IDs and
    // event dispatch. All notification categories are opt-in.
    public const EVENTS = [
        'login_otp' => 'کد ورود و ثبت‌نام',
        'admin_signup_otp' => 'تأیید شماره در ثبت‌نام اپراتور',
        'kyc_otp' => 'کد تأیید هویت',
        'phone_change_otp' => 'تأیید تغییر شماره موبایل',
        'handover_otp' => 'کد تأیید تحویل یا تخلیه ملک',
        'password_reset_otp' => 'تأیید بازیابی رمز',
        'registration' => 'ثبت‌نام موفق',
        'password_changed' => 'تغییر رمز عبور',
        'kyc_approved' => 'تأیید احراز هویت',
        'kyc_rejected' => 'رد یا لغو احراز هویت',
        'auction_joined' => 'ثبت مشارکت در مزایده',
        'auction_refund' => 'بازگشت ودیعه مزایده',
        'auction_bid' => 'ثبت پیشنهاد در مزایده',
        'raffle_ticket' => 'خرید و ثبت بلیط قرعه‌کشی',
        'raffle_result' => 'نتیجه قرعه‌کشی',
        'property_deposit' => 'پرداخت بیعانه ملک',
        'property_acceptance_submitted' => 'ثبت درخواست پذیرش و فروش ملک',
        'property_acceptance_update' => 'تغییر وضعیت پذیرش ملک',
        'wallet_transaction' => 'تراکنش کیف پول',
        'inquiry_completed' => 'نتیجه استعلام',
        'handover_approved' => 'تأیید تحویل و تخلیه ملک',
        'manual_customer_sms' => 'ارسال دستی به مشتریان',
        'property_match' => 'معرفی خودکار ملک متناسب با بودجه',
        'admin_alert' => 'اعلان رویدادها برای مدیر',
        'inquiry_requested' => 'ثبت درخواست استعلام',
        'mortgage_requested' => 'درخواست وام مسکن',
        'contract_requested' => 'درخواست قولنامه و کد رهگیری',
        'valuation_requested' => 'نتیجه قیمت‌گذاری ملک',
    ];

    private $row;
    private ?array $settings = null;

    public function settings(): array
    {
        if ($this->settings !== null) return $this->settings;
        $this->row = Schema::hasTable('spad_sms_settings')
            ? DB::table('spad_sms_settings')->where('id', 1)->first() : null;
        $row = $this->row;
        $events = $row && $row->events_json ? json_decode($row->events_json, true) : [];
        if (!is_array($events)) $events = [];
        $resolved = [];
        foreach (self::EVENTS as $key => $label) {
            $item = is_array($events[$key] ?? null) ? $events[$key] : [];
            $resolved[$key] = [
                'enabled' => (bool) ($item['enabled'] ?? false),
                'pattern_code' => (string) ($item['pattern_code'] ?? ''),
                'label' => $label,
            ];
        }
        return $this->settings = [
            'enabled' => (bool) ($row->enabled ?? false),
            'provider' => (string) ($row->provider ?? ''),
            'sender' => (string) ($row->sender ?? ''),
            'admin_mobile' => (string) ($row->admin_mobile ?? ''),
            'admin_notify_enabled' => (bool) ($row->admin_notify_enabled ?? false),
            'daily_limit' => max(10, (int) ($row->daily_limit ?? 300)),
            'key_saved' => !empty($row->api_key_encrypted),
            'events' => $resolved,
        ];
    }

    public function configured(): bool
    {
        $s = $this->settings();
        return $s['enabled'] && $s['provider'] === 'iranpayamak' && $s['key_saved'] && $s['sender'] !== '';
    }

    private function apiKey(): string
    {
        if (!$this->row || empty($this->row->api_key_encrypted)) return '';
        try { return Crypt::decryptString($this->row->api_key_encrypted); }
        catch (\Throwable $e) { return ''; }
    }

    private function normalizeMobile(string $phone): string
    {
        $phone = strtr(trim($phone), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        if (strpos($phone, '+98') === 0) $phone = '0' . substr($phone, 3);
        if (strpos($phone, '98') === 0 && strlen($phone) === 12) $phone = '0' . substr($phone, 2);
        if (!preg_match('/^09\d{9}$/D', $phone)) {
            throw new RuntimeException('شماره گیرنده پیامک معتبر نیست.');
        }
        return $phone;
    }

    /**
     * OTP is synchronous and MUST fail closed. Never log codes, passwords,
     * the decrypted API key, or the content of a security message.
     */
    public function sendOtp(string $event, string $mobile, string $code): void
    {
        if (!in_array($event, ['login_otp','kyc_otp','phone_change_otp','password_reset_otp','handover_otp','admin_signup_otp'], true)
            || !preg_match('/^\d{4,8}$/D', $code)) {
            throw new RuntimeException('درخواست کد تأیید نامعتبر است.');
        }
        if (!$this->configured() || !$this->eventEnabled($event)) {
            throw new RuntimeException('سامانه پیامک تأیید فعال نشده است.');
        }
        $this->send($event, $mobile, ['code' => $code], null, true);
    }

    public function sendManual(string $mobile, string $message, ?string $reference = null): bool
    {
        if (!$this->eventEnabled('manual_customer_sms')) throw new RuntimeException('provider_not_configured');
        $message=trim($message);
        if (mb_strlen($message)<10 || mb_strlen($message)>400 ||
            preg_match('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', $message))
            throw new RuntimeException('sms_provider_rejected');
        return $this->send('manual_customer_sms',$mobile,[],$reference,true,$message);
    }

    public function eventEnabled(string $event): bool
    {
        $s = $this->settings();
        return $this->configured() && isset(self::EVENTS[$event]) &&
            ($s['events'][$event]['enabled'] ?? false);
    }

    /**
     * Informational messages must never roll back a purchase, login,
     * verification or wallet debit. Database audit is only metadata.
     */
    public function notify(string $event, string $mobile, array $params = [], ?string $reference = null): bool
    {
        $sent=false;
        try {
            if ($this->eventEnabled($event))
                $sent=$this->send($event,$mobile,$params,$reference,false);
        } catch (\Throwable $e) {
            // A notification is best effort, never reverse business operations.
        }
        $adminEvents=[
            'inquiry_requested','inquiry_completed','mortgage_requested',
            'contract_requested','valuation_requested','property_acceptance_submitted',
            'property_acceptance_update'
        ];
        $settings=$this->settings();
        if(in_array($event,$adminEvents,true) && !empty($settings['admin_notify_enabled'])
            && preg_match('/^09[0-9]{9}$/D',(string)$settings['admin_mobile'])
            && $this->eventEnabled('admin_alert') && $reference!==null) {
            try {
                $id=(string)($params['id']??'');
                $id=preg_match('/^[a-zA-Z0-9_-]{1,50}$/D',$id)?$id:'—';
                $this->send('admin_alert',(string)$settings['admin_mobile'],[
                    'event'=>self::EVENTS[$event]??'رویداد جدید','id'=>$id
                ],'manager:'.$event.':'.$reference,false);
            } catch (\Throwable $e) {
                // Admin SMS is independent of the recipient's operation.
            }
        }
        return $sent;
    }

    private function send(string $event, string $phone, array $params, ?string $reference, bool $critical, ?string $messageOverride = null): bool
    {
        if (!isset(self::EVENTS[$event])) throw new RuntimeException('نوع پیامک پشتیبانی نمی‌شود.');
        $mobile = $this->normalizeMobile($phone);
        $settings = $this->settings();
        $rule = $settings['events'][$event];
        $key = $this->apiKey();
        if ($key === '' || !$settings['enabled'] || !$rule['enabled']) {
            throw new RuntimeException('سامانه پیامکی این رویداد فعال نیست.');
        }
        if (!Schema::hasTable('spad_sms_delivery_logs')) {
            throw new RuntimeException('ثبت سوابق پیامک نصب نشده است.');
        }
        $today = DB::table('spad_sms_delivery_logs')->whereDate('created_at', now()->toDateString())->count();
        if ($today >= $settings['daily_limit']) throw new RuntimeException('سقف روزانه پیامک تکمیل شده است.');
        // Prevent duplicate notifications from payment callbacks/retries.
        $dedup = $reference ? hash('sha256', $event . '|' . $reference) : null;
        $masked = substr($mobile, 0, 4) . '***' . substr($mobile, -4);
        try {
            $id = DB::table('spad_sms_delivery_logs')->insertGetId([
                'event' => $event, 'mobile_masked' => $masked,
                'status' => 'sending', 'dedup_hash' => $dedup,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($dedup) return false;
            throw new RuntimeException('ثبت سوابق پیامک ممکن نیست.');
        }

        $pattern = $messageOverride === null ? trim((string) ($rule['pattern_code'] ?? '')) : '';
        $otp = ['login_otp','kyc_otp','phone_change_otp','password_reset_otp','handover_otp','admin_signup_otp'];
        if ($settings['sender'] === '' || (in_array($event, $otp, true) && $pattern === '')) {
            DB::table('spad_sms_delivery_logs')->where('id', $id)->update([
                'status'=>'failed','error_code'=>'CONFIG_MISSING','updated_at'=>now(),
            ]);
            throw new RuntimeException('خط فرستنده یا الگوی کد تأیید تنظیم نشده است.');
        }
        if ($pattern !== '') {
            $attributes = [];
            foreach ($params as $name => $value) {
                if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,39}$/D', (string) $name)) continue;
                $attributes[(string) $name] = mb_substr(str_replace(["\r","\n"], ' ', (string) $value),0,120);
            }
            $payload = [
                'code'=>$pattern,'attributes'=>(object)$attributes,
                'recipient'=>$mobile,'line_number'=>$settings['sender'],
                'number_format'=>'english',
            ];
            $endpoint='https://api.iranpayamak.com/ws/v1/sms/pattern';
        } else {
            $payload = [
                'text'=>$messageOverride !== null ? $messageOverride : $this->render($event,$params),
                'line_number'=>$settings['sender'],'recipients'=>[$mobile],
                'number_format'=>'english',
            ];
            $endpoint='https://api.iranpayamak.com/ws/v1/sms/simple';
        }

        try {
            $response = Http::withHeaders([
                'Api-Key'=>$key,'Accept'=>'application/json',
            ])->asJson()->timeout(9)->post($endpoint,$payload);
        } catch (\Throwable $e) {
            // A network timeout can happen after provider acceptance: no automatic retry.
            DB::table('spad_sms_delivery_logs')->where('id',$id)->update([
                'status'=>'unknown','error_code'=>'PROVIDER_OR_NETWORK','updated_at'=>now(),
            ]);
            if($critical) throw new RuntimeException('ارسال کد تأیید ناموفق بود؛ لطفاً بعداً تلاش کنید.');
            return false;
        }
        $decoded=$response->json();
        // A verified success body is required. Some HTTP gateways return
        // 200 instead of the documented 201 after accepting a pattern SMS.
        // Never infer delivery from HTTP 2xx alone.
        $ok = self::providerAccepted($response->status(), $decoded);
        if(!$ok) {
            DB::table('spad_sms_delivery_logs')->where('id',$id)->update([
                'status'=>'failed',
                'error_code'=>$response->successful()?'PROVIDER_REJECTED':'HTTP_'.$response->status(),
                'updated_at'=>now(),
            ]);
            if($critical) throw new RuntimeException('ارسال کد تأیید پذیرفته نشد؛ لطفاً بعداً تلاش کنید.');
            return false;
        }
        $providerId=is_scalar($decoded['data']??null)?(string)$decoded['data']:'';
        DB::table('spad_sms_delivery_logs')->where('id',$id)->update([
            'status'=>'sent',
            'provider_reference'=>preg_match('/^[A-Za-z0-9_-]{1,90}$/D',$providerId)?$providerId:null,
            'updated_at'=>now(),
        ]);
        return true;
    }

    /**
     * Accept only an explicit positive provider acknowledgement with an HTTP
     * success status. No OTP is accepted merely because transport returned 200.
     */
    private static function providerAccepted(int $httpStatus, $body): bool
    {
        if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($body)) return false;
        $status = $body['status'] ?? null;
        return is_string($status) && strtolower(trim($status)) === 'success';
    }

    private function render(string $event, array $params): string
    {
        $data = array_map('strval', $params);
        $code = $data['code'] ?? '';
        $id = $data['id'] ?? '';
        // No free-text admin templates or arbitrary user interpolation.
        $templates = [
            'login_otp' => "کد ورود اسپاد: {$code}\nاین کد را در اختیار دیگران قرار ندهید.",
            'admin_signup_otp' => "کد تأیید ثبت‌نام اپراتور اسپاد: {$code}",
            'kyc_otp' => "کد احراز هویت اسپاد: {$code}",
            'phone_change_otp' => "کد تأیید تغییر شماره اسپاد: {$code}",
            'password_reset_otp' => "کد بازیابی رمز اسپاد: {$code}",
            'handover_otp' => "کد تأیید تحویل ملک در اسپاد: {$code}",
            'registration' => 'ثبت‌نام شما در اسپاد با موفقیت انجام شد.',
            'password_changed' => 'رمز حساب اسپاد تغییر کرد. اگر شما نبوده‌اید با پشتیبانی تماس بگیرید.',
            'kyc_approved' => 'احراز هویت حساب اسپاد شما تأیید شد.',
            'kyc_rejected' => 'وضعیت احراز هویت اسپاد تغییر کرد. جزئیات را در حساب خود ببینید.',
            'auction_joined' => "مشارکت شما در مزایده شماره {$id} ثبت شد.",
            'auction_refund' => "ودیعه مزایده شماره {$id} به کیف پول شما بازگشت داده شد.",
            'auction_bid' => "پیشنهاد شما در مزایده شماره {$id} ثبت شد.",
            'raffle_ticket' => "ثبت بلیط قرعه‌کشی شماره {$id} در اسپاد موفق بود.",
            'raffle_result' => "نتیجه قرعه‌کشی شماره {$id} در حساب اسپاد قابل مشاهده است.",
            'property_deposit' => "پرداخت بیعانه ملک کد {$id} در اسپاد ثبت شد.",
            'property_acceptance_submitted' => "درخواست پذیرش ملک شما در اسپاد با کد پیگیری {$id} ثبت شد.",
            'property_acceptance_update' => "وضعیت درخواست پذیرش ملک با کد {$id} در اسپاد تغییر کرد. جزئیات را در حساب خود پیگیری کنید.",
            'wallet_transaction' => 'تراکنش کیف پول شما در اسپاد ثبت شد.',
            'inquiry_completed' => 'نتیجه استعلام شما در حساب اسپاد آماده است.',
            'handover_approved' => 'تأییدیه تحویل ملک شما در اسپاد ثبت شد.',
            'property_match' => "اسپاد: ملک جدید متناسب با بودجه شما با قیمت {$data['price']} تومان، کد {$id}\nhttps://spad.estate/property/{$id}\nبرای لغو پیامک با پشتیبانی اسپاد تماس بگیرید.",
            'admin_alert' => "اسپاد: رویداد {$data['event']} برای پرونده {$id} ثبت شد.",
            'inquiry_requested' => "درخواست استعلام شما در اسپاد ثبت شد. شناسه {$id}",
            'mortgage_requested' => "درخواست وام مسکن شما در اسپاد ثبت شد. شناسه {$id}",
            'contract_requested' => "درخواست قولنامه و کد رهگیری در اسپاد ثبت شد. شناسه {$id}",
            'valuation_requested' => "نتیجه قیمت‌گذاری ملک شما در حساب اسپاد آماده است.",
            'manual_customer_sms' => '',
        ];
        return $templates[$event];
    }
}
