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
        'wallet_transaction' => 'تراکنش کیف پول',
        'inquiry_completed' => 'نتیجه استعلام',
        'handover_approved' => 'تأیید تحویل و تخلیه ملک',
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
                'body_id' => max(0, (int) ($item['body_id'] ?? 0)),
                'label' => $label,
            ];
        }
        return $this->settings = [
            'enabled' => (bool) ($row->enabled ?? false),
            'username' => (string) ($row->username ?? ''),
            'sender' => (string) ($row->sender ?? ''),
            'daily_limit' => max(10, (int) ($row->daily_limit ?? 300)),
            'key_saved' => !empty($row->api_key_encrypted),
            'events' => $resolved,
        ];
    }

    public function configured(): bool
    {
        $s = $this->settings();
        return $s['enabled'] && $s['username'] !== '' && $s['key_saved'];
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
        try {
            if (!$this->eventEnabled($event)) return false;
            return $this->send($event, $mobile, $params, $reference, false);
        } catch (\Throwable $e) {
            // Notices are strictly best-effort and must not alter successful
            // financial, authentication or identity transactions.
            return false;
        }
    }

    private function send(string $event, string $phone, array $params, ?string $reference, bool $critical): bool
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

        $bodyId = $rule['body_id'];
        if ($bodyId > 0) {
            // Values must follow the variable ordering of the registered
            // provider pattern. The common OTP pattern has only one value.
            $values = array_values(array_map(function ($v) {
                return mb_substr(str_replace([';', "\n", "\r"], ' ', (string) $v), 0, 120);
            }, $params));
            $payload = [
                'username' => $settings['username'], 'password' => $key,
                'text' => implode(';', $values), 'to' => $mobile, 'bodyId' => $bodyId,
            ];
            $endpoint = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
        } else {
            if ($settings['sender'] === '') {
                DB::table('spad_sms_delivery_logs')->where('id', $id)->update(['status'=>'failed','error_code'=>'SENDER_MISSING','updated_at'=>now()]);
                throw new RuntimeException('خط ارسال یا کد الگوی پیامک تنظیم نشده است.');
            }
            $body = $this->render($event, $params);
            $payload = [
                'username' => $settings['username'], 'password' => $key,
                'from' => $settings['sender'], 'to' => $mobile,
                'text' => $body, 'isflash' => false,
            ];
            $endpoint = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
        }

        try {
            $response = Http::asForm()->timeout(9)->post($endpoint, $payload);
            $decoded = $response->json();
            $ok = $response->successful() && is_array($decoded) &&
                ((int) ($decoded['RetStatus'] ?? 0) === 1 ||
                 ((int) ($decoded['Value'] ?? 0) > 1000 && !isset($decoded['RetStatus'])));
            $code = $response->successful() ? 'PROVIDER_REJECTED' : 'HTTP_' . $response->status();
            $referenceId = is_array($decoded) ? (string) ($decoded['Value'] ?? '') : '';
            if (!$ok) throw new RuntimeException($code);
            DB::table('spad_sms_delivery_logs')->where('id', $id)->update([
                'status'=>'sent',
                'provider_reference'=>preg_match('/^\d{1,90}$/D', $referenceId) ? $referenceId : null,
                'updated_at'=>now(),
            ]);
            return true;
        } catch (\Throwable $e) {
            DB::table('spad_sms_delivery_logs')->where('id', $id)->update([
                // Unknown provider/network outcome: do not retry automatically.
                'status'=>'unknown', 'error_code'=>'PROVIDER_OR_NETWORK', 'updated_at'=>now(),
            ]);
            if ($critical) throw new RuntimeException('ارسال کد تأیید ناموفق بود؛ لطفاً بعداً تلاش کنید.');
            return false;
        }
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
            'wallet_transaction' => 'تراکنش کیف پول شما در اسپاد ثبت شد.',
            'inquiry_completed' => 'نتیجه استعلام شما در حساب اسپاد آماده است.',
            'handover_approved' => 'تأییدیه تحویل ملک شما در اسپاد ثبت شد.',
        ];
        return $templates[$event];
    }
}
