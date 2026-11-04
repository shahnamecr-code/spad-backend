<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedApiIrInquiries extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inquiry_services') || !Schema::hasTable('spad_web_service_connections')) {
            throw new RuntimeException('Install SPAD inquiry and web-service registry migrations first.');
        }
        $items = [
            ['water-bill', 'استعلام قبض آب', 'انشعابات و قبوض', 'billID', 'شناسه قبض آب', 'text', 'مبلغ و شناسه پرداخت قبض آب'],
            ['power-bill', 'استعلام قبض برق', 'انشعابات و قبوض', 'billID', 'شناسه قبض برق', 'text', 'مبلغ و شناسه پرداخت قبض برق'],
            ['gas-bill', 'استعلام قبض گاز', 'انشعابات و قبوض', 'billID', 'شناسه قبض گاز', 'text', 'مبلغ و شناسه پرداخت قبض گاز'],
            ['active-loans', 'استعلام تسهیلات فعال بانکی', 'بانک و اعتبار', 'nationalCode', 'کد ملی صاحب حساب', 'national_code', 'مشاهده تسهیلات فعال خودتان، مانده و معوقات'],
            ['unpaid-cheque-pro', 'استعلام چک برگشتی Pro', 'بانک و اعتبار', 'nationalCode', 'کد ملی صاحب حساب', 'national_code', 'تعداد و فهرست چک‌های برگشتی شخص احراز هویت‌شده'],
            ['postal-code-location', 'دریافت لوکیشن با کدپستی', 'کد پستی و نشانی', 'postalCode', 'کد پستی ده‌رقمی', 'text', 'موقعیت جغرافیایی، عرض و طول و مسیر نقشه'],
            ['postal-code-pro', 'استعلام کدپستی نسخه Pro', 'کد پستی و نشانی', 'postalCode', 'کد پستی ده‌رقمی', 'text', 'آدرس، پلاک، طبقه، نام ساختمان و مختصات در صورت وجود در پاسخ'],
        ];
        foreach ($items as $index => $item) {
            [$code, $title, $category, $key, $label, $type, $description] = $item;
            $slug = 'property-apiir-' . $code;
            if (DB::table('inquiry_services')->where('slug', $slug)->exists()) continue;
            $form = [['key' => $key, 'label' => $label, 'type' => $type, 'required' => true,
                'placeholder' => $key === 'billID' ? 'شناسه درج‌شده روی قبض' : 'ده رقم بدون فاصله']];
            if ($key === 'nationalCode') {
                $form[] = ['key' => 'consent', 'label' => 'تأیید مالکیت و رضایت',
                    'type' => 'select', 'required' => true,
                    'options' => ['بله، اطلاعات خودم است']];
            }
            DB::table('inquiry_services')->insert([
                'slug' => $slug, 'title' => $title, 'category' => $category,
                'accent' => '#087bd7', 'short_description' => $description,
                'description' => 'استعلام خودکار از api.ir؛ فعال‌سازی منوط به ثبت کلید، تعرفه و مجوز در پنل مدیر اسپاد است.',
                'price' => 0, 'execution_type' => 'api', 'estimated_minutes' => 2,
                'form_schema' => json_encode($form, JSON_UNESCAPED_UNICODE),
                'api_config_encrypted' => null, 'enabled' => false, 'featured' => false,
                'sort_order' => 630 + $index, 'created_at' => now(), 'updated_at' => now()
            ]);
        }
    }
    public function down()
    {
        // Never delete services with paid inquiries, results or administrator edits.
        if (Schema::hasTable('inquiry_services')) {
            DB::table('inquiry_services')->where('slug', 'like', 'property-apiir-%')
                ->update(['enabled' => false]);
        }
    }
}
