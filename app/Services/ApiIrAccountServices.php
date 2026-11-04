<?php
namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ApiIrAccountServices
{
    private const BASE = 'https://s.api.ir/api/sw1/';
    private function connection()
    {
        if (!Schema::hasTable('spad_web_service_connections')) return null;
        return DB::table('spad_web_service_connections')->where('code', 'api-ir')->first();
    }
    public function settings(): array
    {
        $row = $this->connection();
        $public = $row ? json_decode((string) $row->config_public, true) : [];
        $public = is_array($public) ? $public : [];
        return [
            'has_key' => (bool) ($row && $row->secrets_encrypted),
            'master_enabled' => (bool) ($row && $row->enabled && $row->mode === 'configured'),
            'sms_otp' => ($public['sms_otp_enabled'] ?? '0') === '1',
            'person_info' => ($public['person_info_enabled'] ?? '0') === '1',
            'shahkar' => ($public['shahkar_apiir_enabled'] ?? '0') === '1',
            'otp_template' => (int) ($public['otp_template'] ?? '1'),
        ];
    }
    public function enabled(string $feature): bool
    {
        $settings = $this->settings();
        return (bool) ($settings['master_enabled'] && $settings['has_key'] && ($settings[$feature] ?? false));
    }
    private function call(string $operation, array $payload)
    {
        $row = $this->connection();
        if (!$row || !$row->enabled || $row->mode !== 'configured' || !$row->secrets_encrypted)
            throw new RuntimeException('اتصال api.ir فعال نیست.');
        try { $token = Crypt::decryptString($row->secrets_encrypted); }
        catch (\Throwable $e) { throw new RuntimeException('کلید api.ir قابل خواندن نیست.'); }
        if (!is_string($token) || trim($token) === '') throw new RuntimeException('کلید api.ir تنظیم نشده است.');
        try {
            $response = Http::timeout(30)->withToken($token)->acceptJson()->post(self::BASE . $operation, $payload);
            if (!$response->successful()) throw new RuntimeException('ارائه‌دهنده پاسخ موفق نداد.');
            $json = $response->json();
            if (!is_array($json) || ($json['success'] ?? false) !== true || !array_key_exists('data', $json))
                throw new RuntimeException('نتیجه معتبر از api.ir دریافت نشد.');
            return $json['data'];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('ارتباط با ارائه‌دهنده برقرار نشد.');
        }
    }
    public function sendOtp(string $mobile, string $code): void
    {
        if (!$this->enabled('sms_otp')) throw new RuntimeException('OTP api.ir فعال نیست.');
        if (!preg_match('/^09[0-9]{9}$/D', $mobile) || !preg_match('/^[0-9]{6}$/D', $code))
            throw new RuntimeException('اطلاعات OTP نامعتبر است.');
        $template = $this->settings()['otp_template'];
        if ($template < 0 || $template > 4) throw new RuntimeException('قالب پیامک نامعتبر است.');
        if ($this->call('SmsOTP', ['code'=>$code, 'mobile'=>$mobile, 'template'=>$template]) !== true)
            throw new RuntimeException('ارسال پیامک تأیید نشد.');
    }
    /**
     * National code and mobile belong to the authenticated user's KYC draft.
     * Official Shahkar data is a boolean, not an object with a match property.
     * Reject all malformed responses rather than treating them as success.
     */
    public function shahkar(string $national, string $mobile): bool
    {
        if (!$this->enabled('shahkar')) throw new RuntimeException('سرویس شاهکار api.ir فعال نیست.');
        if (!preg_match('/^[0-9]{10}$/D', $national) ||
            !preg_match('/^09[0-9]{9}$/D', $mobile)) {
            throw new RuntimeException('کد ملی یا شماره موبایل نامعتبر است.');
        }
        $data = $this->call('Shahkar', [
            'nationalCode' => $national, 'mobile' => $mobile, 'isCompany' => false,
        ]);
        if (!is_bool($data)) throw new RuntimeException('پاسخ شاهکار ساختار معتبر ندارد.');
        return $data;
    }

    public function personInfo(string $national, string $birthDate): array
    {
        if (!$this->enabled('person_info')) throw new RuntimeException('استعلام هویتی فعال نیست.');
        if (!preg_match('/^[0-9]{10}$/D', $national) ||
            !preg_match('/^(13|14)[0-9]{2}\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])$/D', $birthDate))
            throw new RuntimeException('اطلاعات هویتی نامعتبر است.');
        $data = $this->call('PersonInfo', ['nationalCode'=>$national, 'birthDate'=>$birthDate]);
        if (!is_array($data) || !array_key_exists('nationalCode',$data) ||
            !array_key_exists('firstName',$data) || !array_key_exists('lastName',$data) ||
            !array_key_exists('alive',$data))
            throw new RuntimeException('ساختار پاسخ هویتی معتبر نیست.');
        return $data;
    }
}
