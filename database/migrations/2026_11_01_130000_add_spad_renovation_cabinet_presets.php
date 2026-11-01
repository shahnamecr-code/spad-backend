<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additional cabinet colour and construction presets for the SPAD renovation studio.
 * Existing catalogue records and administrator changes are never overwritten.
 */
class AddSpadRenovationCabinetPresets extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_renovation_assets')) {
            throw new \RuntimeException('Run the base SPAD renovation catalogue migration first.');
        }

        $models = [
            ['cabinet-spad-highgloss-ivory', 'هایگلاس سفید صدفی', '#f7f6f0'],
            ['cabinet-spad-highgloss-black', 'هایگلاس مشکی آینه‌ای', '#25282d'],
            ['cabinet-spad-matte-navy', 'مدرن مات سرمه‌ای', '#263e5b'],
            ['cabinet-spad-matte-sage', 'مدرن مات سبز سدری', '#899a81'],
            ['cabinet-spad-matte-greige', 'مدرن گریژ روشن', '#c1b9ab'],
            ['cabinet-spad-wood-oak', 'چوب بلوط طبیعی', '#bf946a'],
            ['cabinet-spad-wood-walnut', 'چوب گردوی تیره', '#76533b'],
            ['cabinet-spad-membrane-cream', 'ممبران کلاسیک کرم', '#e2cfb2'],
            ['cabinet-spad-classic-white', 'کلاسیک سفید با قاب', '#ebe8e0'],
            ['cabinet-spad-modern-concrete', 'مدرن طرح بتن', '#92989a'],
            ['cabinet-spad-matte-charcoal', 'مینیمال ذغالی', '#494f55'],
            ['cabinet-spad-matte-sky', 'مدرن آبی مه‌آلود', '#9dbfcf'],
        ];

        foreach ($models as $position => $model) {
            if (DB::table('spad_renovation_assets')->where('code', $model[0])->exists()) {
                continue;
            }
            DB::table('spad_renovation_assets')->insert([
                'code' => $model[0],
                'category' => 'cabinet',
                'category_title' => 'کابینت',
                'title' => $model[1],
                'room_scope' => 'kitchen',
                'rate_code' => 'cabinet',
                'color_hex' => $model[2],
                'image_url' => null,
                'photo_source_url' => null,
                'photo_credit' => null,
                'unit_price_toman' => null,
                'enabled' => true,
                'sort_order' => 31 + $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Preserve cabinet presets that may be referenced by saved renovation projects.
    }
}
