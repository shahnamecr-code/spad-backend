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
            'shahkar_pro' => ($public['shahkar_pro_enabled'] ?? '0') === '1',
            'video_live' => ($public['video_live_enabled'] ?? '0') === '1',
            'shahkar' => ($public['shahkar_apiir_enabled'] ?? '0') === '1',
            'otp_template' => (int) ($public['otp_template'] ?? '1'),
        ];
    }
    public function enabled(string $feature): bool
    {
        $settings = $this->settings();
        return (bool) ($settings['master_enabled'] && $settings['has_key'] && ($settings[$feature] ?? false));
    }
    private function call(string $operation, array $payload, int $timeout = 30)
    {
        $row = $this->connection();
        if (!$row || !$row->enabled || $row->mode !== 'configured' || !$row->secrets_encrypted)
            throw new RuntimeException('اتصال api.ir فعال نیست.');
        try { $token = Crypt::decryptString($row->secrets_encrypted); }
        catch (\Throwable $e) { throw new RuntimeException('کلید api.ir قابل خواندن نیست.'); }
        if (!is_string($token) || trim($token) === '') throw new RuntimeException('کلید api.ir تنظیم نشده است.');
        try {
            $response = Http::timeout($timeout)->withToken($token)->acceptJson()->post(self::BASE . $operation, $payload);
            if (!$response->successful()) {
                $status = $response->status();
                if ($status === 429) throw new RuntimeException('سقف درخواست سرویس api.ir تکمیل شده است (HTTP 429). لطفاً پس از پایان محدودیت دوباره تلاش کنید.', 429);
                if ($status === 401 || $status === 403) throw new RuntimeException('کلید یا مجوز این سرویس در api.ir معتبر نیست (HTTP '.$status.').', $status);
                if ($status === 402) throw new RuntimeException('اعتبار حساب api.ir برای این سرویس کافی نیست (HTTP 402).', 402);
                if ($status >= 500) throw new RuntimeException('سرویس api.ir موقتاً در دسترس نیست (HTTP '.$status.').', 503);
                throw new RuntimeException('ارائه‌دهنده پاسخ موفق نداد (HTTP '.$status.').', 502);
            }
            $json = $response->json();
            if (!is_array($json))
                throw new RuntimeException('ساختار پاسخ api.ir نامعتبر است.', 502);
            if (($json['success'] ?? false) !== true) {
                $providerCode = (int) ($json['code'] ?? 0);
                if ($providerCode === 429)
                    throw new RuntimeException('سقف درخواست‌های api.ir تکمیل شده است.', 429);
                if ($providerCode === 401 || $providerCode === 403)
                    throw new RuntimeException('مجوز یا کلید این سرویس معتبر نیست.', $providerCode);
                if ($providerCode === 402)
                    throw new RuntimeException('اعتبار حساب api.ir کافی نیست.', 402);
                throw new RuntimeException('ارائه‌دهنده نتیجه موفق برنگرداند؛ مجوز و اعتبار را بررسی کنید.', 502);
            }
            if (!array_key_exists('data', $json))
                throw new RuntimeException('داده پاسخ api.ir ناقص است.', 502);
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

    /**
     * Official API.ir ShahkarPro: checks whether the registered SIM holder
     * matches the national code. This does NOT verify name or birth date.
     * A missing/invalid response is never considered a positive match.
     */
    public function shahkarPro(string $national, string $mobile): bool
    {
        if (!$this->enabled('shahkar_pro'))
            throw new RuntimeException('سرویس شاهکار پرو API.ir فعال نیست.');
        if (!preg_match('/^[0-9]{10}$/D', $national) ||
            !preg_match('/^09[0-9]{9}$/D', $mobile))
            throw new RuntimeException('کد ملی یا شماره همراه معتبر نیست.');

        $result = $this->call('ShahkarPro', [
            'nationalCode' => $national,
            'mobile' => $mobile,
            'isCompany' => false,
        ]);
        if (!is_bool($result))
            throw new RuntimeException('پاسخ سرویس شاهکار پرو معتبر نیست.', 502);
        return $result;
    }

    /**
     * Official API.ir SDK: VideoVerifySpeechText returns the phrase to read.
     * The phrase is not user-submitted; server binds it to the authenticated
     * user's short-lived challenge before one billable VideoVerify call.
     */
    public function videoVerifySpeechText(): string
    {
        if (!$this->enabled('video_live'))
            throw new RuntimeException('سرویس VideoVerify در تنظیمات API.ir فعال نیست.',503);
        $result=$this->call('VideoVerifySpeechText',[],30);
        if (!is_string($result) || trim($result)==='' || mb_strlen($result)>300)
            throw new RuntimeException('متن گفتار احراز ویدئویی از API.ir معتبر نیست.',502);
        return trim($result);
    }

    /**
     * Official video biometric verification includes face match, liveness,
     * spoken phrase match and the aggregate isPassed result.
     * Base64 video and card serial must never be persisted or logged.
     */
    public function videoVerify(string $national,string $birthDate,
        string $serial,string $videoBase64,string $speechText): bool
    {
        if (!$this->enabled('video_live'))
            throw new RuntimeException('سرویس VideoVerify در تنظیمات API.ir فعال نیست.',503);
        if (!preg_match('/^[0-9]{10}$/D',$national) ||
            !preg_match('/^(13|14)[0-9]{2}\/(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])$/D',$birthDate) ||
            !preg_match('/^[A-Za-z0-9]{6,20}$/D',$serial) ||
            !is_string($speechText) || trim($speechText)==='' || mb_strlen($speechText)>300 ||
            strlen($videoBase64)>6991000 || base64_decode($videoBase64,true)===false)
            throw new RuntimeException('اطلاعات احراز هویت ویدئویی یا حجم ویدئو معتبر نیست.',422);
        $matching=90;$liveness=80;$speech=50;
        // Never use VideoLive: the documented provider operation is VideoVerify.
        $data=$this->call('VideoVerify',[
            'nationalCode'=>$national,
            'birthDate'=>$birthDate,
            'serialNumber'=>$serial,
            'videoBase64'=>$videoBase64,
            'speechText'=>$speechText,
            'matchingThreshold'=>$matching,
            'livenessThreshold'=>$liveness,
            'speechThreshold'=>$speech,
        ],120);
        if (!is_array($data) ||
            !is_bool($data['isMatch']??null) ||
            !is_bool($data['isLiveness']??null) ||
            !is_bool($data['isSpeechMatched']??null) ||
            !is_bool($data['isPassed']??null) ||
            !is_numeric($data['matchingScore']??null) ||
            !is_numeric($data['livenessScore']??null) ||
            !is_numeric($data['speechScore']??null))
            throw new RuntimeException('پاسخ بایومتریک VideoVerify ساختار معتبر ندارد.',502);
        return $data['isPassed']===true &&
            $data['isMatch']===true && $data['isLiveness']===true &&
            $data['isSpeechMatched']===true &&
            (float)$data['matchingScore'] >= $matching &&
            (float)$data['livenessScore'] >= $liveness &&
            (float)$data['speechScore'] >= $speech;
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
