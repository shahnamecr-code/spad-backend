<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPAD renovation studio (v2): live material preview on 360 tours and an
 * approximate pre-invoice. Adds catalog fields (surface, unit, render recipe,
 * waste), trade productivity on rates, per-scene room layouts and the saved
 * studio state on projects. Seeds the studio catalog and its trade rates.
 * Existing projects, assets and rates are kept; seeded rows are upserted by
 * code, and admin-edited prices of existing rows are never overwritten.
 */
class SpadRenovationStudio extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_renovation_assets') || !Schema::hasTable('spad_renovation_rates')
            || !Schema::hasTable('spad_renovation_projects')) {
            return;
        }

        Schema::table('spad_renovation_assets', function (Blueprint $t) {
            if (!Schema::hasColumn('spad_renovation_assets', 'surface')) $t->string('surface', 20)->nullable()->index();
            if (!Schema::hasColumn('spad_renovation_assets', 'unit')) $t->string('unit', 10)->nullable();
            if (!Schema::hasColumn('spad_renovation_assets', 'subtitle')) $t->string('subtitle', 160)->nullable();
            if (!Schema::hasColumn('spad_renovation_assets', 'render')) $t->text('render')->nullable();
            if (!Schema::hasColumn('spad_renovation_assets', 'waste_percent')) $t->unsignedTinyInteger('waste_percent')->default(0);
            if (!Schema::hasColumn('spad_renovation_assets', 'default_qty')) $t->decimal('default_qty', 8, 2)->nullable();
        });
        Schema::table('spad_renovation_rates', function (Blueprint $t) {
            if (!Schema::hasColumn('spad_renovation_rates', 'per_day')) $t->decimal('per_day', 8, 2)->nullable();
            if (!Schema::hasColumn('spad_renovation_rates', 'lead_days')) $t->unsignedSmallInteger('lead_days')->default(0);
            if (!Schema::hasColumn('spad_renovation_rates', 'group')) $t->string('group', 20)->default('labor');
        });
        Schema::table('spad_renovation_projects', function (Blueprint $t) {
            if (!Schema::hasColumn('spad_renovation_projects', 'studio_state')) $t->longText('studio_state')->nullable();
        });
        if (!Schema::hasTable('spad_renovation_layouts')) {
            Schema::create('spad_renovation_layouts', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('tour_id');
                $t->string('scene_key', 60);
                $t->string('source', 20);
                $t->longText('layout');
                $t->string('model', 60)->nullable();
                $t->timestamps();
                $t->unique(['tour_id', 'scene_key', 'source']);
            });
        }

        $now = now();
        // [code, title, unit, price per unit (toman), units per working day, lead days, group]
        $rates = [
            ['paint', 'رنگ‌آمیزی دیوار و سقف', 'm2', 350000, 40, 0, 'labor'],
            ['wallpaper', 'نصب کاغذدیواری', 'm2', 280000, 30, 0, 'labor'],
            ['wall-panel', 'نصب دیوارپوش', 'm2', 550000, 14, 3, 'labor'],
            ['tile-wall', 'کاشی‌کاری دیوار', 'm2', 1100000, 10, 0, 'labor'],
            ['parquet', 'نصب پارکت و لمینت', 'm2', 380000, 30, 2, 'labor'],
            ['tile-floor', 'سرامیک‌کاری کف', 'm2', 950000, 12, 0, 'labor'],
            ['ceiling', 'اجرای سقف کاذب', 'm2', 850000, 15, 0, 'labor'],
            ['cabinet-install', 'ساخت و نصب کابینت', 'm', 2500000, 3, 14, 'labor'],
            ['countertop', 'نصب صفحه کابینت', 'm', 900000, 6, 5, 'labor'],
            ['appliance', 'نصب لوازم آشپزخانه', 'item', 1200000, 4, 0, 'labor'],
            ['plumbing', 'لوله‌کشی و نصب شیرآلات', 'item', 1500000, 3, 0, 'labor'],
            ['lighting-install', 'برق‌کاری و نصب روشنایی', 'item', 600000, 8, 0, 'labor'],
            ['curtain-install', 'نصب پرده', 'm', 350000, 20, 4, 'labor'],
            ['door-install', 'نصب درب داخلی', 'item', 2500000, 3, 10, 'labor'],
            ['demo-floor', 'تخریب و جمع‌آوری کف‌پوش قدیمی', 'm2', 450000, 40, 0, 'demolition'],
            ['demo-wall', 'آماده‌سازی و سابیدن دیوار', 'm2', 120000, 60, 0, 'demolition'],
            ['demo-cabinet', 'باز کردن کابینت قدیمی', 'm', 1500000, 8, 0, 'demolition'],
            ['cleanup', 'حمل نخاله و نظافت پایان کار', 'job', 3500000, 1, 0, 'demolition'],
        ];
        foreach ($rates as [$code, $title, $unit, $price, $perDay, $lead, $group]) {
            $exists = DB::table('spad_renovation_rates')->where('code', $code)->first();
            $fields = ['per_day' => $perDay, 'lead_days' => $lead, 'group' => $group, 'updated_at' => $now];
            if ($exists) {
                DB::table('spad_renovation_rates')->where('code', $code)->update($fields);
            } else {
                DB::table('spad_renovation_rates')->insert(array_merge($fields, [
                    'code' => $code, 'title' => $title, 'unit' => $unit, 'price_toman' => $price,
                    'days' => max(1, (int) ceil(10 / max($perDay, 1))), 'enabled' => true, 'created_at' => $now,
                ]));
            }
        }

        $sort = 1000;
        foreach (self::catalog() as $c) {
            foreach ($c['items'] as $it) {
                $sort++;
                $code = 'st-' . $c['code'] . '-' . $it[0];
                $render = $it[4];
                $row = [
                    'category' => 'st-' . $c['code'],
                    'category_title' => $c['title'],
                    'title' => $it[1],
                    'subtitle' => $it[2],
                    'room_scope' => $c['scope'],
                    'rate_code' => $c['rate'],
                    'surface' => $c['surface'],
                    'unit' => $c['unit'],
                    'color_hex' => $render['color'] ?? ($render['colors'][0] ?? '#d9e2ea'),
                    'render' => json_encode($render, JSON_UNESCAPED_UNICODE),
                    'waste_percent' => $c['waste'],
                    'default_qty' => $c['qty'] ?? null,
                    'sort_order' => $sort,
                    'updated_at' => $now,
                ];
                $existing = DB::table('spad_renovation_assets')->where('code', $code)->first();
                if ($existing) {
                    // Keep the admin's price and on/off switch.
                    DB::table('spad_renovation_assets')->where('code', $code)->update($row);
                } else {
                    DB::table('spad_renovation_assets')->insert(array_merge($row, [
                        'code' => $code, 'unit_price_toman' => $it[3], 'enabled' => true, 'created_at' => $now,
                    ]));
                }
            }
        }
    }

    public function down()
    {
        // User projects and admin prices are kept on rollback.
    }

    /** Studio catalog: [key, title, spec, material price per unit, render recipe]. Prices are approximate (Shiraz, late 1405) and editable by the admin. */
    public static function catalog(): array
    {
        $paint = fn($c) => ['type' => 'paint', 'color' => $c];
        return [
            ['code' => 'paint', 'title' => 'رنگ دیوار', 'surface' => 'wall', 'unit' => 'm2', 'rate' => 'paint', 'waste' => 5, 'scope' => 'all', 'items' => [
                ['shell', 'سفید صدفی', 'اکریلیک مات، قابل شستشو', 280000, $paint('#efe9df')],
                ['sand', 'کرم شنی', 'اکریلیک مات، ضد لک', 300000, $paint('#d8c4a3')],
                ['linen', 'بژ کتان', 'اکریلیک مات', 300000, $paint('#e3d5bf')],
                ['greige', 'طوسی گرم', 'اکریلیک مات', 300000, $paint('#bdb6ad')],
                ['sage', 'سبز مریمی', 'نیم‌براق، قابل شستشو', 340000, $paint('#a9b9a0')],
                ['mist', 'آبی مه‌آلود', 'اکریلیک مات', 300000, $paint('#a7bccb')],
                ['terracotta', 'تراکوتا ملایم', 'اکریلیک مات', 320000, $paint('#c79a7e')],
                ['blush', 'صورتی پودری', 'اکریلیک مات', 320000, $paint('#e2c4bf')],
                ['olive', 'زیتونی', 'اکریلیک مات', 320000, $paint('#8d8a64')],
                ['charcoal', 'خاکستری ذغالی', 'اکریلیک مات، رنگ تأکیدی', 340000, $paint('#5d6166')],
                ['navy', 'سرمه‌ای عمیق', 'نیم‌براق، رنگ تأکیدی', 360000, $paint('#2f3e55')],
                ['white', 'سفید برفی', 'پلاستیک مات', 240000, $paint('#f6f5f1')],
            ]],
            ['code' => 'wallpaper', 'title' => 'کاغذدیواری', 'surface' => 'wall', 'unit' => 'm2', 'rate' => 'wallpaper', 'waste' => 15, 'scope' => 'all', 'items' => [
                ['damask', 'داماسک طلایی', 'کلاسیک، وینیل قابل شستشو', 950000, ['type' => 'pattern', 'motif' => 'damask', 'colors' => ['#e9dcc3', '#b89a62'], 'size' => 0.53]],
                ['geo-navy', 'هندسی سرمه‌ای', 'مدرن، وینیل', 880000, ['type' => 'pattern', 'motif' => 'geo', 'colors' => ['#2f3e55', '#c9b48a'], 'size' => 0.5]],
                ['leaf', 'برگ استوایی', 'طبیعت، مخصوص دیوار تأکیدی', 1050000, ['type' => 'pattern', 'motif' => 'leaf', 'colors' => ['#dfe6dc', '#5f7f63'], 'size' => 0.7]],
                ['stripe', 'راه‌راه طوسی', 'مینیمال', 720000, ['type' => 'pattern', 'motif' => 'stripes', 'colors' => ['#e5e2dc', '#c9c4bb'], 'size' => 0.25]],
                ['linen', 'بافت کتان کرم', 'طرح پارچه، ساده', 680000, ['type' => 'pattern', 'motif' => 'linen', 'colors' => ['#e7ddca', '#d3c6ae'], 'size' => 0.4]],
                ['marble', 'طرح مرمر', 'وینیل براق', 990000, ['type' => 'marble', 'colors' => ['#eeebe5', '#9c958b'], 'size' => 1.2]],
            ]],
            ['code' => 'panel', 'title' => 'دیوارپوش', 'surface' => 'wall', 'unit' => 'm2', 'rate' => 'wall-panel', 'waste' => 8, 'scope' => 'all', 'items' => [
                ['oak-slat', 'ترمووال بلوط روشن', 'چوب و MDF، شیار ۹ سانتی', 3200000, ['type' => 'slats', 'colors' => ['#c9a173', '#3a2a1c'], 'slat' => 0.09]],
                ['walnut-slat', 'ترمووال گردویی', 'چوب و MDF', 3600000, ['type' => 'slats', 'colors' => ['#8f6240', '#2e1f14'], 'slat' => 0.09]],
                ['black-slat', 'ترمووال مشکی', 'مدرن و مات', 3400000, ['type' => 'slats', 'colors' => ['#2c2c2e', '#111112'], 'slat' => 0.07]],
                ['pvc-marble', 'دیوارپوش PVC طرح مرمر', 'ضد آب، براق', 1650000, ['type' => 'marble', 'colors' => ['#f2efe9', '#8f887d'], 'size' => 1.22, 'joint' => 1.22]],
                ['stone', 'سنگ آنتیک', 'سنگ مصنوعی سه‌بعدی', 3900000, ['type' => 'stone', 'colors' => ['#bdb3a3', '#8d8274'], 'size' => 0.35]],
                ['brick', 'آجرنما', 'آجر قزاقی', 2400000, ['type' => 'brick', 'colors' => ['#b0694a', '#d9cbb7'], 'size' => 0.22]],
            ]],
            ['code' => 'wall-tile', 'title' => 'کاشی دیوار', 'surface' => 'wall', 'unit' => 'm2', 'rate' => 'tile-wall', 'waste' => 10, 'scope' => 'all', 'items' => [
                ['white-30x90', 'سفید براق ۳۰×۹۰', 'کاشی دیواری لعاب‌دار', 1150000, ['type' => 'tiles', 'colors' => ['#f3f3f1', '#d7d7d3'], 'size' => [0.9, 0.3], 'grout' => '#cfcfcb']],
                ['marble-60x120', 'مرمر کالاکاتا ۶۰×۱۲۰', 'پرسلان براق', 3100000, ['type' => 'marble', 'colors' => ['#f1eee8', '#9b8f7d'], 'size' => 1.2, 'joint' => 0.6]],
                ['subway', 'آجری سفید متروئی', '۷.۵×۱۵', 1450000, ['type' => 'tiles', 'colors' => ['#f5f4f0', '#e6e4dd'], 'size' => [0.15, 0.075], 'grout' => '#b9b6ae', 'offset' => true]],
                ['terrazzo', 'ترازو روشن', 'پرسلان مات ۶۰×۶۰', 1900000, ['type' => 'terrazzo', 'colors' => ['#e9e5dd', '#9a8f80'], 'size' => 0.6]],
                ['grey-matte', 'طوسی مات ۶۰×۱۲۰', 'طرح بتن', 1700000, ['type' => 'tiles', 'colors' => ['#a7a6a2', '#9c9b97'], 'size' => [0.6, 1.2], 'grout' => '#8f8e8a']],
            ]],
            ['code' => 'parquet', 'title' => 'پارکت و لمینت', 'surface' => 'floor', 'unit' => 'm2', 'rate' => 'parquet', 'waste' => 8, 'scope' => 'all', 'items' => [
                ['light-oak', 'بلوط روشن', 'لمینت ۸ میلی‌متری، ضد آب', 1950000, ['type' => 'planks', 'colors' => ['#d9b68a', '#c99f6c', '#e2c398'], 'plank' => [1.2, 0.19]]],
                ['natural-oak', 'بلوط طبیعی', 'لمینت ۱۰ میلی‌متری', 2350000, ['type' => 'planks', 'colors' => ['#c19162', '#b07f52', '#cb9d6f'], 'plank' => [1.38, 0.19]]],
                ['walnut', 'گردویی', 'لمینت ۸ میلی‌متری', 2100000, ['type' => 'planks', 'colors' => ['#7a5235', '#6a4529', '#84593a'], 'plank' => [1.2, 0.19]]],
                ['grey-oak', 'بلوط طوسی', 'لمینت ۸ میلی‌متری', 1950000, ['type' => 'planks', 'colors' => ['#a59e93', '#968f84', '#b0a99e'], 'plank' => [1.2, 0.19]]],
                ['herringbone', 'جناغی بلوط', 'پارکت مهندسی', 5400000, ['type' => 'herringbone', 'colors' => ['#c7996a', '#b88a5c', '#d4a777'], 'plank' => [0.6, 0.12]]],
                ['wenge', 'ونگه تیره', 'لمینت ۸ میلی‌متری', 2050000, ['type' => 'planks', 'colors' => ['#4a3428', '#3f2c22', '#55392b'], 'plank' => [1.2, 0.19]]],
            ]],
            ['code' => 'floor-tile', 'title' => 'سرامیک و سنگ کف', 'surface' => 'floor', 'unit' => 'm2', 'rate' => 'tile-floor', 'waste' => 10, 'scope' => 'all', 'items' => [
                ['statuario', 'مرمر سفید ۶۰×۱۲۰', 'پرسلان پولیش', 2900000, ['type' => 'marble', 'colors' => ['#f1efea', '#a49b8e'], 'size' => 1.2, 'joint' => 0.6]],
                ['travertine', 'تراورتن کرم', 'پرسلان مات ۶۰×۶۰', 2100000, ['type' => 'tiles', 'colors' => ['#d9c7a8', '#cdb994'], 'size' => [0.6, 0.6], 'grout' => '#c2ae8c', 'noise' => 0.5]],
                ['concrete', 'طرح بتن طوسی', 'پرسلان مات ۶۰×۱۲۰', 1850000, ['type' => 'tiles', 'colors' => ['#9e9d99', '#94938f'], 'size' => [0.6, 1.2], 'grout' => '#878682', 'noise' => 0.6]],
                ['nero', 'مرمر مشکی', 'پرسلان پولیش ۶۰×۱۲۰', 3400000, ['type' => 'marble', 'colors' => ['#262626', '#8a8a8a'], 'size' => 1.2, 'joint' => 0.6]],
                ['wood-look', 'سرامیک طرح چوب', '۲۰×۱۲۰ مات', 1650000, ['type' => 'planks', 'colors' => ['#b98d62', '#a77c53', '#c49a6e'], 'plank' => [1.2, 0.2]]],
                ['crema', 'سنگ مرمریت کرما', 'سنگ طبیعی صیقلی', 4800000, ['type' => 'marble', 'colors' => ['#e6d8bf', '#b49f7d'], 'size' => 0.8, 'joint' => 0.8]],
            ]],
            ['code' => 'ceiling', 'title' => 'سقف', 'surface' => 'ceiling', 'unit' => 'm2', 'rate' => 'ceiling', 'waste' => 5, 'scope' => 'all', 'items' => [
                ['white-paint', 'رنگ سفید سقف', 'پلاستیک مات، فقط رنگ‌آمیزی', 200000, ['type' => 'paint', 'color' => '#f7f6f2', 'rate' => 'paint']],
                ['warm-paint', 'رنگ سفید گرم سقف', 'پلاستیک مات، فقط رنگ‌آمیزی', 220000, ['type' => 'paint', 'color' => '#f1ebe0', 'rate' => 'paint']],
                ['gypsum', 'سقف کاذب کناف ساده', 'با نور مخفی دور تا دور', 950000, ['type' => 'paint', 'color' => '#f8f8f6']],
                ['wood-slat', 'سقف ترمووال چوبی', 'بلوط طبیعی', 2800000, ['type' => 'slats', 'colors' => ['#c39a6c', '#4a3524'], 'slat' => 0.1]],
                ['dark', 'سقف تیره مدرن', 'رنگ ذغالی مات', 260000, ['type' => 'paint', 'color' => '#4a4d52', 'rate' => 'paint']],
            ]],
            ['code' => 'cabinet', 'title' => 'کابینت', 'surface' => 'cabinet', 'unit' => 'm', 'rate' => 'cabinet-install', 'waste' => 0, 'scope' => 'kitchen', 'items' => [
                ['gloss-white', 'سفید هایگلاس', 'MDF هایگلاس، یراق آرام‌بند', 22000000, ['type' => 'recolor', 'color' => '#f2f1ec', 'finish' => 'gloss']],
                ['matte-grey', 'طوسی مات', 'MDF مات ضد خش', 20000000, ['type' => 'recolor', 'color' => '#8e9196', 'finish' => 'matte']],
                ['walnut', 'روکش گردو', 'روکش چوب طبیعی', 32000000, ['type' => 'recolor', 'color' => '#7b5436', 'finish' => 'wood']],
                ['navy', 'سرمه‌ای مات', 'MDF مات', 22000000, ['type' => 'recolor', 'color' => '#2f3e55', 'finish' => 'matte']],
                ['cream', 'ممبران کرم کلاسیک', 'ممبران قاب‌دار', 26000000, ['type' => 'recolor', 'color' => '#e8dcc5', 'finish' => 'matte']],
                ['oak', 'بلوط روشن', 'ملامینه طرح چوب', 18000000, ['type' => 'recolor', 'color' => '#c8a477', 'finish' => 'wood']],
                ['black', 'مشکی مات', 'MDF مات', 21000000, ['type' => 'recolor', 'color' => '#2b2c2e', 'finish' => 'matte']],
            ]],
            ['code' => 'countertop', 'title' => 'صفحه کابینت', 'surface' => 'object', 'unit' => 'm', 'rate' => 'countertop', 'waste' => 5, 'scope' => 'kitchen', 'qty' => 3, 'items' => [
                ['quartz-white', 'کوارتز سفید', 'ضخامت ۲ سانت', 14000000, ['type' => 'object', 'icon' => 'countertop', 'color' => '#efefec']],
                ['granite-black', 'گرانیت مشکی', 'سنگ طبیعی', 8000000, ['type' => 'object', 'icon' => 'countertop', 'color' => '#262626']],
                ['sintered', 'سینتر طرح مرمر', 'ضد لک و حرارت', 18000000, ['type' => 'object', 'icon' => 'countertop', 'color' => '#ece8e0']],
            ]],
            ['code' => 'hob', 'title' => 'گاز صفحه‌ای', 'surface' => 'object', 'unit' => 'item', 'rate' => 'appliance', 'waste' => 0, 'scope' => 'kitchen', 'items' => [
                ['glass-5', 'شیشه‌ای ۵ شعله مشکی', 'فندک اتوماتیک، ترموکوپل', 28000000, ['type' => 'object', 'icon' => 'hob', 'color' => '#1b1f22']],
                ['glass-4w', 'شیشه‌ای ۴ شعله سفید', 'فندک اتوماتیک', 24000000, ['type' => 'object', 'icon' => 'hob', 'color' => '#f1f1ef']],
                ['steel-4', 'استیل ۴ شعله', 'بدنه استیل ضد زنگ', 19000000, ['type' => 'object', 'icon' => 'hob', 'color' => '#b9bec2']],
                ['induction', 'القایی ۴ زون', 'برقی، تاچ', 55000000, ['type' => 'object', 'icon' => 'hob', 'color' => '#111214']],
            ]],
            ['code' => 'hood', 'title' => 'هود آشپزخانه', 'surface' => 'object', 'unit' => 'item', 'rate' => 'appliance', 'waste' => 0, 'scope' => 'kitchen', 'items' => [
                ['chimney', 'شومینه‌ای استیل ۹۰', 'مکش ۸۰۰ متر مکعب', 22000000, ['type' => 'object', 'icon' => 'hood', 'color' => '#b9bec2']],
                ['glass-t', 'شیشه‌ای T مشکی', 'تاچ، لامپ LED', 26000000, ['type' => 'object', 'icon' => 'hood', 'color' => '#202326']],
                ['slim', 'مخفی کشویی', 'زیر کابینتی', 16000000, ['type' => 'object', 'icon' => 'hood', 'color' => '#9aa0a5']],
                ['island', 'جزیره‌ای', 'آویز سقفی', 45000000, ['type' => 'object', 'icon' => 'hood', 'color' => '#c3c7ca']],
            ]],
            ['code' => 'oven', 'title' => 'فر و مایکروویو', 'surface' => 'object', 'unit' => 'item', 'rate' => 'appliance', 'waste' => 0, 'scope' => 'kitchen', 'items' => [
                ['oven-black', 'فر توکار مشکی ۶۰', 'برقی، ۱۰ برنامه', 38000000, ['type' => 'object', 'icon' => 'oven', 'color' => '#1d1f22']],
                ['oven-steel', 'فر توکار استیل', 'گازی برقی', 34000000, ['type' => 'object', 'icon' => 'oven', 'color' => '#b9bec2']],
                ['micro', 'مایکروویو توکار', '۲۵ لیتری', 26000000, ['type' => 'object', 'icon' => 'oven', 'color' => '#2a2c2f']],
            ]],
            ['code' => 'sink', 'title' => 'سینک و شیرآلات', 'surface' => 'object', 'unit' => 'item', 'rate' => 'plumbing', 'waste' => 0, 'scope' => 'kitchen', 'items' => [
                ['steel-single', 'سینک استیل تک‌لگن', 'زیرصفحه‌ای', 9000000, ['type' => 'object', 'icon' => 'sink', 'color' => '#b9bec2']],
                ['steel-double', 'سینک استیل دولگن', 'روکار', 12000000, ['type' => 'object', 'icon' => 'sink', 'color' => '#b9bec2']],
                ['granite', 'سینک گرانیتی مشکی', 'کامپوزیت', 15000000, ['type' => 'object', 'icon' => 'sink', 'color' => '#2b2b2b']],
                ['faucet-chrome', 'شیر ظرفشویی شلنگ‌دار', 'کروم', 7000000, ['type' => 'object', 'icon' => 'faucet', 'color' => '#c9ced2']],
                ['faucet-black', 'شیر ظرفشویی مشکی مات', 'شلنگ‌دار', 9000000, ['type' => 'object', 'icon' => 'faucet', 'color' => '#202020']],
            ]],
            ['code' => 'lighting', 'title' => 'روشنایی', 'surface' => 'object', 'unit' => 'item', 'rate' => 'lighting-install', 'waste' => 0, 'scope' => 'all', 'items' => [
                ['crystal', 'لوستر کریستالی', '۸ شاخه', 18000000, ['type' => 'object', 'icon' => 'chandelier', 'color' => '#e9d79e']],
                ['ring', 'آویز حلقه‌ای مدرن', 'LED، قطر ۸۰', 12000000, ['type' => 'object', 'icon' => 'pendant', 'color' => '#d6c49a']],
                ['spot', 'هالوژن توکار (۶ عدد)', 'LED آفتابی', 2700000, ['type' => 'object', 'icon' => 'spot', 'color' => '#f4f1e4']],
                ['linear', 'لاین نوری (هر متر)', 'پروفیل آلومینیومی', 950000, ['type' => 'object', 'icon' => 'linear', 'color' => '#f4f1e4', 'unit' => 'm']],
                ['sconce', 'دیوارکوب', 'برنجی', 3500000, ['type' => 'object', 'icon' => 'sconce', 'color' => '#c9a45b']],
            ]],
            ['code' => 'curtain', 'title' => 'پرده', 'surface' => 'object', 'unit' => 'm', 'rate' => 'curtain-install', 'waste' => 0, 'scope' => 'all', 'qty' => 3, 'items' => [
                ['linen', 'پرده کتان کرم', 'هر متر عرض، با ریل', 4500000, ['type' => 'object', 'icon' => 'curtain', 'color' => '#e6dccb']],
                ['blackout', 'پرده بلک‌اوت طوسی', 'هر متر عرض', 5500000, ['type' => 'object', 'icon' => 'curtain', 'color' => '#8f9196']],
                ['zebra', 'پرده زبرا', 'هر متر عرض', 3800000, ['type' => 'object', 'icon' => 'blind', 'color' => '#e9e6df']],
                ['sheer', 'پرده حریر سفید', 'هر متر عرض', 3000000, ['type' => 'object', 'icon' => 'curtain', 'color' => '#f7f7f5']],
            ]],
            ['code' => 'door', 'title' => 'درب داخلی', 'surface' => 'object', 'unit' => 'item', 'rate' => 'door-install', 'waste' => 0, 'scope' => 'all', 'items' => [
                ['white', 'درب سفید HDF', 'با چهارچوب و یراق', 24000000, ['type' => 'object', 'icon' => 'door', 'color' => '#f2f1ec']],
                ['oak', 'درب روکش بلوط', 'با چهارچوب و یراق', 32000000, ['type' => 'object', 'icon' => 'door', 'color' => '#c39a6c']],
                ['black', 'درب مشکی مینیمال', 'لولا مخفی', 29000000, ['type' => 'object', 'icon' => 'door', 'color' => '#2a2a2c']],
            ]],
            ['code' => 'bath', 'title' => 'سرویس بهداشتی', 'surface' => 'object', 'unit' => 'item', 'rate' => 'plumbing', 'waste' => 0, 'scope' => 'all', 'items' => [
                ['toilet', 'توالت فرنگی وال‌هنگ', 'با فلاش توکار', 32000000, ['type' => 'object', 'icon' => 'toilet', 'color' => '#f4f4f2']],
                ['vanity', 'روشویی کابینت‌دار ۶۰', 'با آینه', 28000000, ['type' => 'object', 'icon' => 'vanity', 'color' => '#e9e5dd']],
                ['shower', 'دوردوشی شیشه‌ای', 'سکوریت ۸ میل', 26000000, ['type' => 'object', 'icon' => 'shower', 'color' => '#cfe0e8']],
                ['faucets', 'ست شیرآلات', 'روشویی، دوش و حمام', 14000000, ['type' => 'object', 'icon' => 'faucet', 'color' => '#c9ced2']],
            ]],
            ['code' => 'furniture', 'title' => 'مبلمان و دکور', 'surface' => 'object', 'unit' => 'item', 'rate' => null, 'waste' => 0, 'scope' => 'all', 'items' => [
                ['sofa-l', 'مبل ال راحتی', 'پارچه کتان، ۶ نفره', 120000000, ['type' => 'object', 'icon' => 'sofa', 'color' => '#bfb3a1']],
                ['dining', 'میز ناهارخوری ۶ نفره', 'صفحه سنگ', 65000000, ['type' => 'object', 'icon' => 'table', 'color' => '#c9c3b8']],
                ['tv-unit', 'میز تلویزیون و دیوار TV', 'ترمووال و نور مخفی', 45000000, ['type' => 'object', 'icon' => 'tv', 'color' => '#7b5436']],
                ['rug', 'فرش مدرن ۳×۴', 'ماشینی ۱۲۰۰ شانه', 30000000, ['type' => 'object', 'icon' => 'rug', 'color' => '#b8a48a']],
                ['plants', 'گلدان و گیاه آپارتمانی', 'ست ۳ تایی', 6000000, ['type' => 'object', 'icon' => 'plant', 'color' => '#5f7f63']],
            ]],
        ];
    }
}
