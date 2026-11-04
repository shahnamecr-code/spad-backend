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
        if ($field !== 'nationalCode') return;

        if (!preg_match('/^[0-9]{10}$/D', $value) || ($inputs['consent'] ?? '') !== 'بله، اطلاعات خودم است') {
            throw new RuntimeException('کد ملی معتبر و رضایت صریح صاحب اطلاعات الزامی است.', 422);
        }
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

    public function execute(string $slug, array $inputs, $user): array
    {
        $this->authorize($slug, $inputs, $user);
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
        $response = Http::timeout(30)->withToken($token)->acceptJson()
            ->post(self::ENDPOINT . $meta['operation'], [
                $meta['field'] => (string) $inputs[$meta['field']],
            ]);
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
        if (!is_array($data)) {
            throw new RuntimeException('پاسخ استعلام ساختار قابل نمایش ندارد.', 502);
        }
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
