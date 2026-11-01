<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSpadRenovationDesignCatalog extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('spad_renovation_projects')) {
            throw new \RuntimeException('The SPAD renovation base migration is missing.');
        }
        if (!Schema::hasColumn('spad_renovation_projects','design_layers')) {
            Schema::table('spad_renovation_projects', function (Blueprint $table) {
                $table->json('design_layers')->nullable();
            });
        }
        if (!Schema::hasTable('spad_renovation_assets')) {
            Schema::create('spad_renovation_assets', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('code',50)->unique();
                $t->string('category',35)->index();
                $t->string('category_title',100);
                $t->string('title',150);
                $t->string('room_scope',30);
                $t->string('rate_code',45)->nullable();
                $t->string('color_hex',7)->default('#edf5fb');
                $t->string('image_url',500)->nullable();
                $t->string('photo_source_url',500)->nullable();
                $t->string('photo_credit',140)->nullable();
                $t->unsignedBigInteger('unit_price_toman')->nullable();
                $t->boolean('enabled')->default(true);
                $t->unsignedInteger('sort_order')->default(0);
                $t->timestamps();
            });
        }
        $records = json_decode(<<<'JSON'
[
  {
    "code": "cabinet-1",
    "category": "cabinet",
    "category_title": "کابینت",
    "room_scope": "kitchen",
    "title": "سفید براق مدرن",
    "color_hex": "#f4f4ef",
    "image_url": "https://images.unsplash.com/photo-1772016608838-d8d467da27e8?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-white-kitchen-with-black-countertop-and-stainless-steel-hood-FuWvMvU38ao",
    "photo_credit": "Smart Renovations",
    "rate_code": "cabinet",
    "sort_order": 1
  },
  {
    "code": "cabinet-2",
    "category": "cabinet",
    "category_title": "کابینت",
    "room_scope": "kitchen",
    "title": "گردویی گرم",
    "color_hex": "#ac8862",
    "image_url": "https://images.unsplash.com/photo-1721743170649-dcdcdc889b1a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-kitchen-with-wooden-cabinets-and-white-counter-tops-qj1UJRoWfSo",
    "photo_credit": "Alex Tyson",
    "rate_code": "cabinet",
    "sort_order": 2
  },
  {
    "code": "cabinet-3",
    "category": "cabinet",
    "category_title": "کابینت",
    "room_scope": "kitchen",
    "title": "خاکستری مات",
    "color_hex": "#73787b",
    "image_url": "https://images.unsplash.com/photo-1778272617899-038ae0330471?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-black-kitchen-counter-with-sink-and-faucet-RKi6pLKBayg",
    "photo_credit": "krishna kumar c",
    "rate_code": "cabinet",
    "sort_order": 3
  },
  {
    "code": "hob-1",
    "category": "hob",
    "category_title": "گاز صفحه‌ای",
    "room_scope": "kitchen",
    "title": "شیشه مشکی پنج شعله",
    "color_hex": "#181c20",
    "image_url": "https://images.unsplash.com/photo-1782898622718-8d8a5e8ef637?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-modern-kitchen-counter-with-a-stovetop-and-extractor-hood-d588UyS6P4Q",
    "photo_credit": "Rodrigo Rodrigues",
    "rate_code": "appliances",
    "sort_order": 4
  },
  {
    "code": "hob-2",
    "category": "hob",
    "category_title": "گاز صفحه‌ای",
    "room_scope": "kitchen",
    "title": "استیل چهار شعله",
    "color_hex": "#a7adb0",
    "image_url": "https://images.unsplash.com/photo-1782898622998-899bb7183a12?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-kitchen-with-wooden-cabinets-marble-backsplash-and-gas-stovetop-BAJkc_MBQD4",
    "photo_credit": "Rodrigo Rodrigues",
    "rate_code": "appliances",
    "sort_order": 5
  },
  {
    "code": "hob-3",
    "category": "hob",
    "category_title": "گاز صفحه‌ای",
    "room_scope": "kitchen",
    "title": "صفحه تیره مینیمال",
    "color_hex": "#343535",
    "image_url": "https://images.unsplash.com/photo-1778272617899-038ae0330471?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-black-kitchen-counter-with-sink-and-faucet-RKi6pLKBayg",
    "photo_credit": "krishna kumar c",
    "rate_code": "appliances",
    "sort_order": 6
  },
  {
    "code": "hood-1",
    "category": "hood",
    "category_title": "هود آشپزخانه",
    "room_scope": "kitchen",
    "title": "هود مورب مشکی",
    "color_hex": "#20282d",
    "image_url": "https://images.unsplash.com/photo-1772016608838-d8d467da27e8?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-white-kitchen-with-black-countertop-and-stainless-steel-hood-FuWvMvU38ao",
    "photo_credit": "Smart Renovations",
    "rate_code": "appliances",
    "sort_order": 7
  },
  {
    "code": "hood-2",
    "category": "hood",
    "category_title": "هود آشپزخانه",
    "room_scope": "kitchen",
    "title": "هود استیل مخفی",
    "color_hex": "#bec8cc",
    "image_url": "https://images.unsplash.com/photo-1782898622718-8d8a5e8ef637?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-modern-kitchen-counter-with-a-stovetop-and-extractor-hood-d588UyS6P4Q",
    "photo_credit": "Rodrigo Rodrigues",
    "rate_code": "appliances",
    "sort_order": 8
  },
  {
    "code": "hood-3",
    "category": "hood",
    "category_title": "هود آشپزخانه",
    "room_scope": "kitchen",
    "title": "هود شیشه‌ای مینیمال",
    "color_hex": "#6c7379",
    "image_url": "https://images.unsplash.com/photo-1782898622998-899bb7183a12?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-kitchen-with-wooden-cabinets-marble-backsplash-and-gas-stovetop-BAJkc_MBQD4",
    "photo_credit": "Rodrigo Rodrigues",
    "rate_code": "appliances",
    "sort_order": 9
  },
  {
    "code": "chandelier-1",
    "category": "chandelier",
    "category_title": "لوستر و آویز",
    "room_scope": "living",
    "title": "لوستر مدرن حلقه‌ای",
    "color_hex": "#e5cd93",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "lighting",
    "sort_order": 10
  },
  {
    "code": "chandelier-2",
    "category": "chandelier",
    "category_title": "لوستر و آویز",
    "room_scope": "living",
    "title": "آویز شیشه‌ای",
    "color_hex": "#e4edf2",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "lighting",
    "sort_order": 11
  },
  {
    "code": "chandelier-3",
    "category": "chandelier",
    "category_title": "لوستر و آویز",
    "room_scope": "living",
    "title": "لوستر طلایی کلاسیک",
    "color_hex": "#b79752",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "lighting",
    "sort_order": 12
  },
  {
    "code": "ceiling-1",
    "category": "ceiling",
    "category_title": "کناف سقف",
    "room_scope": "living",
    "title": "سقف ساده خطی",
    "color_hex": "#f4f5f2",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "ceiling",
    "sort_order": 13
  },
  {
    "code": "ceiling-2",
    "category": "ceiling",
    "category_title": "کناف سقف",
    "room_scope": "living",
    "title": "سقف دورباکس مدرن",
    "color_hex": "#e8e5de",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "ceiling",
    "sort_order": 14
  },
  {
    "code": "ceiling-3",
    "category": "ceiling",
    "category_title": "کناف سقف",
    "room_scope": "living",
    "title": "سقف دکوراتیو دو سطح",
    "color_hex": "#d9e8f2",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "ceiling",
    "sort_order": 15
  },
  {
    "code": "led-1",
    "category": "led",
    "category_title": "لاین نوری",
    "room_scope": "living",
    "title": "لاین نور گرم ۳۰۰۰K",
    "color_hex": "#f4d69b",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "lighting",
    "sort_order": 16
  },
  {
    "code": "led-2",
    "category": "led",
    "category_title": "لاین نوری",
    "room_scope": "living",
    "title": "لاین نوری سفید ۴۰۰۰K",
    "color_hex": "#e7f6ff",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "lighting",
    "sort_order": 17
  },
  {
    "code": "led-3",
    "category": "led",
    "category_title": "لاین نوری",
    "room_scope": "living",
    "title": "نور خطی معماری",
    "color_hex": "#fed78b",
    "image_url": "https://images.unsplash.com/photo-1772016608838-d8d467da27e8?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-white-kitchen-with-black-countertop-and-stainless-steel-hood-FuWvMvU38ao",
    "photo_credit": "Smart Renovations",
    "rate_code": "lighting",
    "sort_order": 18
  },
  {
    "code": "sink-1",
    "category": "sink",
    "category_title": "سینک ظرفشویی",
    "room_scope": "kitchen",
    "title": "سینک استیل دو لگن",
    "color_hex": "#ccd3d4",
    "image_url": "https://images.unsplash.com/photo-1689504810048-34c4b1efe0a1?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/una-cocina-con-un-horno-de-estufa-junto-a-un-fregadero-BpMNtAska4g",
    "photo_credit": "Michael Le",
    "rate_code": "appliances",
    "sort_order": 19
  },
  {
    "code": "sink-2",
    "category": "sink",
    "category_title": "سینک ظرفشویی",
    "room_scope": "kitchen",
    "title": "سینک گرانیتی مشکی",
    "color_hex": "#292a2a",
    "image_url": "https://images.unsplash.com/photo-1778272617899-038ae0330471?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/modern-black-kitchen-counter-with-sink-and-faucet-RKi6pLKBayg",
    "photo_credit": "krishna kumar c",
    "rate_code": "appliances",
    "sort_order": 20
  },
  {
    "code": "sink-3",
    "category": "sink",
    "category_title": "سینک ظرفشویی",
    "room_scope": "kitchen",
    "title": "سینک زیرکار مینیمال",
    "color_hex": "#eeeeee",
    "image_url": "https://images.unsplash.com/photo-1721743170649-dcdcdc889b1a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-kitchen-with-wooden-cabinets-and-white-counter-tops-qj1UJRoWfSo",
    "photo_credit": "Alex Tyson",
    "rate_code": "appliances",
    "sort_order": 21
  },
  {
    "code": "paint-1",
    "category": "paint",
    "category_title": "رنگ دیوار",
    "room_scope": "all",
    "title": "سفید استخوانی",
    "color_hex": "#e9e3d5",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "paint",
    "sort_order": 22
  },
  {
    "code": "paint-2",
    "category": "paint",
    "category_title": "رنگ دیوار",
    "room_scope": "all",
    "title": "سبز سدری",
    "color_hex": "#a9b9a7",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "paint",
    "sort_order": 23
  },
  {
    "code": "paint-3",
    "category": "paint",
    "category_title": "رنگ دیوار",
    "room_scope": "all",
    "title": "آبی طوسی",
    "color_hex": "#a9c1ce",
    "image_url": "https://images.unsplash.com/photo-1689504810048-34c4b1efe0a1?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/una-cocina-con-un-horno-de-estufa-junto-a-un-fregadero-BpMNtAska4g",
    "photo_credit": "Michael Le",
    "rate_code": "paint",
    "sort_order": 24
  },
  {
    "code": "wallpaper-1",
    "category": "wallpaper",
    "category_title": "کاغذ دیواری",
    "room_scope": "living",
    "title": "بافت کرم ظریف",
    "color_hex": "#e9dbc5",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "paint",
    "sort_order": 25
  },
  {
    "code": "wallpaper-2",
    "category": "wallpaper",
    "category_title": "کاغذ دیواری",
    "room_scope": "living",
    "title": "داماسک مدرن",
    "color_hex": "#b0a3ad",
    "image_url": "https://images.unsplash.com/photo-1726499638665-81bf136a888a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-chandelier-hanging-from-a-ceiling-in-a-room-LkGLeuRhY9A",
    "photo_credit": "Alex Gagareen",
    "rate_code": "paint",
    "sort_order": 26
  },
  {
    "code": "wallpaper-3",
    "category": "wallpaper",
    "category_title": "کاغذ دیواری",
    "room_scope": "living",
    "title": "طرح هندسی ملایم",
    "color_hex": "#bdc7ca",
    "image_url": "https://images.unsplash.com/photo-1721743170649-dcdcdc889b1a?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/a-kitchen-with-wooden-cabinets-and-white-counter-tops-qj1UJRoWfSo",
    "photo_credit": "Alex Tyson",
    "rate_code": "paint",
    "sort_order": 27
  },
  {
    "code": "curtain-1",
    "category": "curtain",
    "category_title": "پرده",
    "room_scope": "living",
    "title": "حریر سفید",
    "color_hex": "#f0eee9",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "paint",
    "sort_order": 28
  },
  {
    "code": "curtain-2",
    "category": "curtain",
    "category_title": "پرده",
    "room_scope": "living",
    "title": "کتان بژ",
    "color_hex": "#ccbea9",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "paint",
    "sort_order": 29
  },
  {
    "code": "curtain-3",
    "category": "curtain",
    "category_title": "پرده",
    "room_scope": "living",
    "title": "مخمل خاکستری",
    "color_hex": "#8d969d",
    "image_url": "https://images.unsplash.com/photo-1771039622311-24e73898b641?auto=format&fit=crop&w=900&q=80",
    "photo_source_url": "https://unsplash.com/photos/cream-curtains-hang-over-a-window-with-a-lamp-EcusinlsfwI",
    "photo_credit": "GLOBALDSIO IT SOLUTION",
    "rate_code": "paint",
    "sort_order": 30
  }
]
JSON
        ,true,512,JSON_THROW_ON_ERROR);
        foreach ($records as $record) {
            $exists=DB::table('spad_renovation_assets')->where('code',$record['code'])->exists();
            if (!$exists) {
                DB::table('spad_renovation_assets')->insert(array_merge($record,[
                    'unit_price_toman'=>null,
                    'enabled'=>true,
                    'created_at'=>now(),
                    'updated_at'=>now()
                ]));
            }
        }
    }

    public function down()
    {
        // Never erase user-selected designs or administrator-edited catalog entries.
    }
}
