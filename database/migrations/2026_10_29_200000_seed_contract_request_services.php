<?php

use App\Models\InquiryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedContractRequestServices extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inquiry_services')) {
            throw new \RuntimeException('Inquiry services must be installed before contract services.');
        }

        $common = [
            ['key' => 'full_name', 'label' => 'نام و نام خانوادگی متقاضی', 'type' => 'text', 'required' => true, 'placeholder' => 'نام کامل متقاضی'],
            ['key' => 'mobile', 'label' => 'شماره موبایل برای هماهنگی', 'type' => 'mobile', 'required' => true, 'placeholder' => '09xxxxxxxxx'],
            ['key' => 'transaction_type', 'label' => 'نوع معامله', 'type' => 'select', 'required' => true, 'options' => ['خرید و فروش', 'رهن و اجاره', 'پیش‌فروش', 'سایر']],
            ['key' => 'property_address', 'label' => 'آدرس ملک', 'type' => 'textarea', 'required' => true, 'placeholder' => 'شهر، محله، خیابان و مشخصات ملک'],
            ['key' => 'property_postal_code', 'label' => 'کد پستی ملک (اختیاری)', 'type' => 'text', 'required' => false, 'placeholder' => 'کد پستی ده‌رقمی در صورت دسترسی'],
            ['key' => 'counterparty_name', 'label' => 'نام طرف دیگر معامله (اختیاری)', 'type' => 'text', 'required' => false, 'placeholder' => 'نام خریدار، فروشنده، موجر یا مستأجر'],
            ['key' => 'notes', 'label' => 'توضیحات و نکات تکمیلی', 'type' => 'textarea', 'required' => false, 'placeholder' => 'هر توضیحی برای بررسی بهتر درخواست'],
        ];

        DB::transaction(function () use ($common) {
            $records = [
                [
                    'slug' => 'online-agreement-request',
                    'title' => 'درخواست قولنامه آنلاین',
                    'short_description' => 'فرم اطلاعات معامله را تکمیل کنید تا درخواست تنظیم قرارداد برای بررسی کارشناسی ثبت شود.',
                    'description' => 'این خدمت برای دریافت و بررسی درخواست تنظیم قولنامه است. متن نهایی، اعتبار و امضای قرارداد تنها پس از احراز مدارک و تأیید افراد و مراجع مجاز انجام می‌شود.',
                    'sort_order' => 30,
                ],
                [
                    'slug' => 'tracking-code-request',
                    'title' => 'درخواست کد رهگیری',
                    'short_description' => 'اطلاعات معامله را ثبت کنید تا شرایط و مدارک لازم برای دریافت کد رهگیری بررسی شود.',
                    'description' => 'ثبت این فرم به‌معنای صدور قطعی یا فوری کد رهگیری نیست. صدور منوط به انجام مراحل قانونی و ثبت نزد مرجع یا سامانه مجاز است.',
                    'sort_order' => 31,
                ],
            ];
            foreach ($records as $record) {
                InquiryService::firstOrCreate(
                    ['slug' => $record['slug']],
                    [
                        'title' => $record['title'],
                        'category' => 'قولنامه و کد رهگیری',
                        'short_description' => $record['short_description'],
                        'description' => $record['description'],
                        'price' => 0,
                        'partner_payout' => 0,
                        'execution_type' => 'manual',
                        'partner_handler_type' => 'notary_office',
                        'estimated_minutes' => null,
                        'form_schema' => $common,
                        'enabled' => true,
                        'featured' => false,
                        'sort_order' => $record['sort_order'],
                    ]
                );
            }
        });
    }

    public function down()
    {
        // Deliberately preserve user requests, admin edits and configured prices.
        // Service disabling or removal is handled in the existing admin panel.
    }
}
