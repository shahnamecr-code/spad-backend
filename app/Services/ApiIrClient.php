<?php
namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Fixed, server-side adapter for api.ir. Never accept a destination URL or
 * credential from visitor input. No automatic retries on billable endpoints.
 */
class ApiIrClient
{
    private const ENDPOINT = 'https://s.api.ir/api/sw1/';

    public static function catalog(): array
    {
        return [
            'property-apiir-water-bill' => ['operation' => 'WatterBill', 'field' => 'billID', 'title' => 'استعلام قبض آب'],
            'property-apiir-power-bill' => ['operation' => 'PowerBill', 'field' => 'billID', 'title' => 'استعلام قبض برق'],
            'property-apiir-gas-bill' => ['operation' => 'GasBill', 'field' => 'billID', 'title' => 'استعلام قبض گاز'],
            'property-apiir-active-loans' => ['operation' => 'ActiveLoans', 'field' => 'nationalCode', 'title' => 'استعلام تسهیلات فعال بانکی', 'restricted' => true],
            'property-apiir-unpaid-cheque-pro' => ['operation' => 'UnpaidChequePro', 'field' => 'nationalCode', 'title' => 'استعلام چک برگشتی Pro', 'restricted' => true],
            'property-apiir-postal-code-location' => ['operation' => 'PostalCodeLocation', 'field' => 'postalCode', 'title' => 'دریافت لوکیشن با کدپستی'],
            'property-apiir-postal-code-pro' => ['operation' => 'PostalCodePro', 'field' => 'postalCode', 'title' => 'استعلام کدپستی Pro'],
            // API.ir official OpenAPI operations; all disabled until admin licenses/prices them.
            'property-apiir-cheque-color' => ['operation'=>'ChequeColor', 'field'=>'nationalCode',
                'title'=>'استعلام رنگ چک صیادی', 'restricted'=>true, 'isCompany'=>false],
            'property-apiir-cheque-info' => ['operation'=>'ChequeInfo', 'field'=>'chequeID',
                'title'=>'استعلام مشخصات چک صیادی', 'restricted'=>true],
            'property-apiir-sana' => ['operation'=>'Sana', 'field'=>'nationalCode',
                'title'=>'استعلام سامانه ثنا', 'restricted'=>true, 'isCompany'=>false],
            'property-apiir-iban-match' => ['operation'=>'IbanMatch', 'field'=>'nationalCode',
                'title'=>'تطبیق کد ملی با شبا', 'restricted'=>true, 'hasIban'=>true, 'birthDate'=>true],
            'property-apiir-iban-match-pro' => ['operation'=>'IbanMatchPro', 'field'=>'nationalCode',
                'title'=>'تطبیق کد ملی با شبا پرو (سیاح)', 'restricted'=>true, 'hasIban'=>true],
        ];
    }

    public static function supports(string $slug): bool
    {
        return isset(self::catalog()[$slug]);
    }

    private function connection()
    {
        if (!Schema::hasTable('spad_web_service_connections')) {
            throw new RuntimeException('اتصال api.ir هنوز در سرور نصب نشده است.', 503);
        }
        $row = DB::table('spad_web_service_connections')->where('code', 'api-ir')->first();
        if (!$row || !$row->enabled || $row->mode !== 'configured' || !$row->secrets_encrypted) {
            throw new RuntimeException('اتصال api.ir هنوز در پنل مدیر فعال نشده است.', 503);
        }
        return $row;
    }

    public function assertReady(): void
    {
        $this->connection();
    }

    public function authorize(string $slug, array $inputs, $user): void
    {
        $service = self::catalog()[$slug] ?? null;
        if (!$service || !$user) {
            throw new RuntimeException('این خدمت در دسترس نیست.', 422);
        }
        $field = $service['field'];
        $value = (string) ($inputs[$field] ?? '');
        if ($field === 'postalCode' && !preg_match('/^[0-9]{10}$/D', $value)) {
            throw new RuntimeException('کدپستی باید ده رقم باشد.', 422);
        }
        if ($field === 'billID' && !preg_match('/^[0-9]{6,24}$/D', $value)) {
            throw new RuntimeException('شناسه قبض معتبر وارد کنید.', 422);
        }
        if ($slug === 'property-apiir-cheque-info') {
            abort_unless(preg_match('/^[0-9]{15,16}$/D', $value), 422,
                'شناسه چک صیادی باید ۱۵ یا ۱۶ رقم باشد.');
            abort_unless(($inputs['consent'] ?? '') === 'بله، مجوز بررسی چک را دارم', 422,
                'تأیید داشتن مجوز بررسی این چک ضروری است.');
            $this->assertVerifiedAccount($user);
            return;
        }
        if ($field !== 'nationalCode') return;
        if (!preg_match('/^[0-9]{10}$/D', $value) || ($inputs['consent'] ?? '') !== 'بله، اطلاعات خودم است') {
            throw new RuntimeException('کد ملی معتبر و رضایت صریح صاحب اطلاعات الزامی است.', 422);
        }
        if (!empty($service['hasIban']) &&
            !preg_match('/^IR[0-9]{24}$/D', strtoupper((string)($inputs['iban']??''))))
            throw new RuntimeException('شماره شبا باید با IR شروع شود و ۲۴ رقم داشته باشد.', 422);
        if (!empty($service['birthDate']) &&
            !preg_match('/^(13|14)[0-9]{2}\\/(0?[1-9]|1[0-2])\\/(0?[1-9]|[12][0-9]|3[01])$/D',
                (string)($inputs['birthDate']??'')))
            throw new RuntimeException('تاریخ تولد شمسی باید مانند ۱۳۷۵/۱/۲ باشد.', 422);
        if (!Schema::hasTable('spad_user_verifications')) {
            throw new RuntimeException('سامانه احراز هویت در دسترس نیست.', 503);
        }
        $verification = DB::table('spad_user_verifications')
            ->where('user_id', $user->id)->first();
        if (!$verification || $verification->status !== 'approved' || (int) $verification->level < 1 ||
            !$verification->phone_verified || $verification->shahkar_status !== 'matched' ||
            $verification->verification_provider !== 'api_ir_shahkarpro' ||
            $verification->phone !== (string)$user->mobile ||
            !$verification->national_code_hash ||
            !hash_equals((string) $verification->national_code_hash, hash_hmac('sha256', $value, config('app.key')))) {
            throw new RuntimeException('استعلام اطلاعات بانکی فقط برای صاحب حساب با احراز هویت تأییدشده اسپاد مجاز است.', 403);
        }
    }

    private function assertVerifiedAccount($user): void
    {
        if (!Schema::hasTable('spad_user_verifications'))
            throw new RuntimeException('احراز هویت اسپاد در دسترس نیست.', 503);
        $v=DB::table('spad_user_verifications')->where('user_id',$user->id)->first();
        if (!$v || $v->status!=='approved' || (int)$v->level<1 ||
            !$v->phone_verified || $v->shahkar_status!=='matched' ||
            $v->verification_provider!=='api_ir_shahkarpro' ||
            $v->phone!==(string)$user->mobile)
            throw new RuntimeException('برای این استعلام ابتدا احراز هویت پایه را تکمیل کنید.', 403);
    }

    public function execute(string $slug, array $inputs, $user): array
    {
        $this->authorize($slug, $inputs, $user);
        app(\App\Services\SpadBiometricAccess::class)->assertFor($user,'inquiries:'.$slug);
        $connection = $this->connection();
        try {
            $token = Crypt::decryptString($connection->secrets_encrypted);
        } catch (\Throwable $e) {
            throw new RuntimeException('کلید امن api.ir قابل خواندن نیست؛ با مدیر تماس بگیرید.', 503);
        }
        if (!is_string($token) || trim($token) === '') {
            throw new RuntimeException('کلید api.ir نامعتبر است.', 503);
        }
        $meta = self::catalog()[$slug];
        $payload=[$meta['field'] => (string)$inputs[$meta['field']]];
        if (array_key_exists('isCompany',$meta)) $payload['isCompany']=false;
        if (!empty($meta['hasIban'])) $payload['iban']=strtoupper((string)$inputs['iban']);
        if (!empty($meta['birthDate'])) $payload['birthDate']=(string)$inputs['birthDate'];
        // One billable call only: no fallback endpoint, redirect, retry or logging payloads.
        $response = Http::timeout(30)->withToken($token)->acceptJson()
            ->post(self::ENDPOINT . $meta['operation'], $payload);
        if (!$response->successful()) {
            $status = $response->status();
            if ($status === 429) throw new RuntimeException('سقف درخواست سرویس api.ir تکمیل شده است (HTTP 429). بدون ارسال درخواست تکراری، بعداً تلاش کنید.', 429);
            if ($status === 401 || $status === 403) throw new RuntimeException('مجوز یا کلید این استعلام در api.ir معتبر نیست (HTTP '.$status.').', $status);
            if ($status === 402) throw new RuntimeException('اعتبار حساب api.ir برای این استعلام کافی نیست (HTTP 402).', 402);
            if ($status >= 500) throw new RuntimeException('اختلال موقت سرویس api.ir (HTTP '.$status.').', 503);
            throw new RuntimeException('ارائه‌دهنده استعلام پاسخ معتبر نداد (HTTP '.$status.').', 502);
        }
        $json = $response->json();
        if (!is_array($json))
            throw new RuntimeException('پاسخ سرویس api.ir ساختار معتبری ندارد.', 502);
        if (($json['success'] ?? false) !== true) {
            // API.ir can return HTTP 200 with application-level failures.
            // Never log or surface raw response: banking responses contain PII.
            $providerCode = filter_var($json['code'] ?? null, FILTER_VALIDATE_INT);
            if ($providerCode === 401 || $providerCode === 403)
                throw new RuntimeException('مجوز سرویس UnpaidChequePro یا کلید API.ir تأیید نشده است.', 403);
            if ($providerCode === 402)
                throw new RuntimeException('اعتبار حساب API.ir کافی نیست.', 402);
            if ($providerCode === 429)
                throw new RuntimeException('محدودیت تعداد درخواست API.ir فعال است.', 429);
            throw new RuntimeException('API.ir نتیجه موفق برای این استعلام برنگرداند.', 502);
        }
        if (!array_key_exists('data', $json))
            throw new RuntimeException('پاسخ استعلام api.ir ناقص است.', 502);
        $data = $json['data'];
        // A negative match/absence of Sana membership is valid successful data.
        if (is_bool($data)) {
            if ($slug==='property-apiir-sana') return ['registered'=>$data];
            if ($slug==='property-apiir-iban-match' || $slug==='property-apiir-iban-match-pro')
                return ['isMatch'=>$data];
            throw new RuntimeException('نوع پاسخ این سرویس معتبر نیست.', 502);
        }
        if (!is_array($data))
            throw new RuntimeException('پاسخ استعلام ساختار قابل نمایش ندارد.', 502);
        if ($slug==='property-apiir-cheque-color' &&
            (!isset($data['chequeColorCode']) || !isset($data['chequeColor'])))
            throw new RuntimeException('پاسخ استعلام رنگ چک ناقص است.', 502);
        if ($slug==='property-apiir-cheque-info' &&
            !isset($data['chequeType']) && !isset($data['iban']))
            throw new RuntimeException('پاسخ مشخصات چک ناقص است.', 502);
        if ($slug === 'property-apiir-unpaid-cheque-pro') {
            // A zero count is a valid positive result, not an API failure.
            if (!isset($data['count']) || !is_numeric($data['count']) ||
                (int)$data['count'] < 0 ||
                (isset($data['chequeList']) && !is_array($data['chequeList'])))
                throw new RuntimeException('ساختار پاسخ چک برگشتی پرو ناقص است.', 502);
            if (!empty($data['nationalCode']) && !hash_equals((string)$inputs['nationalCode'], (string)$data['nationalCode']))
                throw new RuntimeException('کد ملی پاسخ با درخواست تطابق ندارد.', 502);
        }
        return $data;
    }
}
