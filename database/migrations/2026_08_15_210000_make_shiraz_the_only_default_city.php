<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeShirazTheOnlyDefaultCity extends Migration
{
    public function up()
    {
        $shiraz = DB::table('cities')->where('name', 'شیراز')->first();

        if (!$shiraz) {
            return;
        }

        DB::table('cities')->update(['is_default' => false]);
        DB::table('cities')->where('id', $shiraz->id)->update(['is_default' => true]);

        // Keep the market page copy aligned with the Shiraz-only scope.
        DB::table('settings')
            ->where('group', 'live_property_price')
            ->where('code', 'live_property_price_title')
            ->update([
                'text_value' => 'قیمت لحظه‌ای ملک در شیراز',
                'updated_at' => now(),
            ]);

        DB::table('settings')
            ->where('group', 'live_property_price')
            ->where('code', 'live_property_price_description')
            ->update([
                'longtext_value' => 'قیمت تحلیلی خیابان‌های شیراز را بر اساس فایل‌های فعال SPAD به‌صورت یکجا بررسی و مقایسه کنید.',
                'updated_at' => now(),
            ]);

        DB::table('settings')
            ->where('group', 'live_property_price')
            ->where('code', 'live_property_price_disclaimer')
            ->update([
                'longtext_value' => 'قیمت‌های این صفحه برآورد آماری از آگهی‌های فعال SPAD در شیراز هستند و جایگزین کارشناسی رسمی ملک نیستند.',
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        // Intentionally left unchanged: SPAD remains Shiraz-only.
    }
}
