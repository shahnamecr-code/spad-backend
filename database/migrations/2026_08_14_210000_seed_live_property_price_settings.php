<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedLivePropertyPriceSettings extends Migration
{
    public function up()
    {
        $defaults = [
            ['code' => 'live_property_price_enabled', 'text_value' => '1'],
            ['code' => 'live_property_price_header_enabled', 'text_value' => '1'],
            ['code' => 'live_property_price_header_label', 'text_value' => 'قیمت لحظه‌ای ملک'],
            ['code' => 'live_property_price_badge', 'text_value' => 'SPAD MARKET INTELLIGENCE'],
            ['code' => 'live_property_price_title', 'text_value' => 'قیمت لحظه‌ای ملک در سراسر ایران'],
            [
                'code' => 'live_property_price_description',
                'longtext_value' => 'شهر موردنظر را انتخاب کنید تا قیمت تحلیلی همه مناطق آن شهر بر اساس فایل‌های فعال SPAD نمایش داده شود.',
            ],
            ['code' => 'live_property_price_min_samples', 'text_value' => '3'],
            ['code' => 'live_property_price_max_age_days', 'text_value' => '0'],
            [
                'code' => 'live_property_price_disclaimer',
                'longtext_value' => 'قیمت‌های این صفحه برآورد آماری از آگهی‌های فعال SPAD هستند و جایگزین کارشناسی رسمی ملک نیستند.',
            ],
        ];

        foreach ($defaults as $default) {
            $code = $default['code'];
            unset($default['code']);

            $exists = DB::table('settings')
                ->where('group', 'live_property_price')
                ->where('code', $code)
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert(array_merge([
                    'group' => 'live_property_price',
                    'code' => $code,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $default));
            }
        }
    }

    public function down()
    {
        DB::table('settings')
            ->where('group', 'live_property_price')
            ->delete();
    }
}
