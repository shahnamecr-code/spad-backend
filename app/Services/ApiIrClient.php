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
            throw new RuntimeException('اتصال api.ir هنوز در سرور نصب نشده است.');
        }
        $row = DB::table('spad_web_service_connections')->where('code', 'api-ir')->first();
        if (!$row || !$row->enabled || $row->mode !== 'configured' || !$row->secrets_encrypted) {
            throw new RuntimeException('اتصال api.ir هنوز در پنل مدیر فعال نشده است.');
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
            throw new RuntimeException('این خدمت در دسترس نیست.');
        }
        $field = $service['field'];
        $value = (string) ($inputs[$field] ?? '');
        if ($field === 'postalCode' && !preg_match('/^[0-9]{10}$/D', $value)) {
            throw new RuntimeException('کدپستی باید ده رقم باشد.');
        }
        if ($field === 'billID' && !preg_match('/^[0-9]{6,24}$/D', $value)) {
            throw new RuntimeException('شناسه قبض معتبر وارد کنید.');
        }
        if ($field !== 'nationalCode') return;

        if (!preg_match('/^[0-9]{10}$/D', $value) || ($inputs['consent'] ?? '') !== 'بله، اطلاعات خودم است') {
            throw new RuntimeException('کد ملی معتبر و رضایت صریح صاحب اطلاعات الزامی است.');
        }
        if (!Schema::hasTable('spad_user_verifications')) {
            throw new RuntimeException('سامانه احراز هویت در دسترس نیست.');
        }
        $verification = DB::table('spad_user_verifications')
            ->where('user_id', $user->id)->first();
        if (!$verification || $verification->status !== 'approved' || (int) $verification->level < 1 ||
            !$verification->phone_verified || $verification->shahkar_status !== 'matched' ||
            $verification->verification_provider !== 'api_ir' ||
            $verification->phone !== (string)$user->mobile ||
            !$verification->national_code_hash ||
            !hash_equals((string) $verification->national_code_hash, hash_hmac('sha256', $value, config('app.key')))) {
            throw new RuntimeException('استعلام اطلاعات بانکی فقط برای صاحب حساب با احراز هویت تأییدشده اسپاد مجاز است.');
        }
    }

    public function execute(string $slug, array $inputs, $user): array
    {
        $this->authorize($slug, $inputs, $user);
        $connection = $this->connection();
        try {
            $token = Crypt::decryptString($connection->secrets_encrypted);
        } catch (\Throwable $e) {
            throw new RuntimeException('کلید امن api.ir قابل خواندن نیست؛ با مدیر تماس بگیرید.');
        }
        if (!is_string($token) || trim($token) === '') {
            throw new RuntimeException('کلید api.ir نامعتبر است.');
        }
        $meta = self::catalog()[$slug];
        $response = Http::timeout(30)->withToken($token)->acceptJson()
            ->post(self::ENDPOINT . $meta['operation'], [
                $meta['field'] => (string) $inputs[$meta['field']],
            ]);
        if (!$response->successful()) {
            throw new RuntimeException('ارائه‌دهنده استعلام پاسخ معتبر نداد (HTTP ' . $response->status() . ').');
        }
        $json = $response->json();
        if (!is_array($json) || ($json['success'] ?? false) !== true || !array_key_exists('data', $json)) {
            throw new RuntimeException('استعلام از api.ir موفق نبود؛ در پنل ارائه‌دهنده وضعیت دسترسی و اعتبار را بررسی کنید.');
        }
        $data = $json['data'];
        if (!is_array($data)) {
            throw new RuntimeException('پاسخ استعلام ساختار قابل نمایش ندارد.');
        }
        return $data;
    }
}
