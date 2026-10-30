<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateSpadMaterialPrices extends Migration
{
    public function up()
    {
        Schema::create('material_price_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->boolean('enabled')->default(true);
            $table->boolean('sync_enabled')->default(false);
            $table->string('provider', 32)->default('persianapi');
            $table->text('encrypted_api_token')->nullable();
            $table->unsignedSmallInteger('refresh_minutes')->default(30);
            $table->unsignedSmallInteger('stale_minutes')->default(120);
            $table->unsignedTinyInteger('provider_price_divisor')->default(1);
            $table->string('title', 160)->default('قیمت روز مصالح ساختمانی');
            $table->text('subtitle')->nullable();
            $table->text('notice')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status', 120)->nullable();
            $table->timestamps();
        });
        Schema::create('material_price_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sku', 100)->unique();
            $table->string('external_id', 160)->nullable()->unique();
            $table->string('name', 200);
            $table->string('category', 100)->index();
            $table->string('brand', 130)->nullable();
            $table->string('unit', 60)->default('استعلام واحد');
            $table->unsignedBigInteger('price_toman')->nullable();
            $table->unsignedBigInteger('previous_price_toman')->nullable();
            $table->decimal('change_percent', 9, 2)->nullable();
            $table->string('source_type', 24)->default('pending');
            $table->string('source_url', 255)->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
        });
        Schema::create('material_price_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('price_toman');
            $table->string('source_type', 24);
            $table->timestamp('quoted_at');
            $table->timestamps();
            $table->index(['product_id','quoted_at']);
        });
        DB::table('material_price_settings')->insert([
            'id' => 1, 'enabled' => true, 'sync_enabled' => false,
            'provider' => 'persianapi', 'refresh_minutes' => 30, 'stale_minutes' => 120,
            'provider_price_divisor' => 1, 'title' => 'قیمت روز مصالح ساختمانی',
            'subtitle' => 'بازار مصالح ساختمانی ایران؛ قیمت‌های تاریخ‌دار، دسته‌بندی‌های تخصصی و مقایسه تغییرات نرخ.',
            'notice' => 'نرخ‌ها بر اساس برند، شهر، نوع تحویل و هزینه حمل متفاوت هستند و الزاماً قیمت خرید در شیراز نیستند.',
            'created_at' => now(), 'updated_at' => now()
        ]);
        $items = [
            ['sku' => 'spad-material-001', 'category' => 'آهن‌آلات و فولاد', 'name' => 'میلگرد آجدار A3 سایز ۱۲', 'unit' => 'کیلوگرم', 'sort_order' => 1],
            ['sku' => 'spad-material-002', 'category' => 'آهن‌آلات و فولاد', 'name' => 'میلگرد آجدار A3 سایز ۱۴', 'unit' => 'کیلوگرم', 'sort_order' => 2],
            ['sku' => 'spad-material-003', 'category' => 'آهن‌آلات و فولاد', 'name' => 'میلگرد آجدار A3 سایز ۱۶', 'unit' => 'کیلوگرم', 'sort_order' => 3],
            ['sku' => 'spad-material-004', 'category' => 'آهن‌آلات و فولاد', 'name' => 'میلگرد آجدار A3 سایز ۱۸', 'unit' => 'کیلوگرم', 'sort_order' => 4],
            ['sku' => 'spad-material-005', 'category' => 'آهن‌آلات و فولاد', 'name' => 'میلگرد آجدار A4 سایز ۱۶', 'unit' => 'کیلوگرم', 'sort_order' => 5],
            ['sku' => 'spad-material-006', 'category' => 'آهن‌آلات و فولاد', 'name' => 'میلگرد ساده A1', 'unit' => 'کیلوگرم', 'sort_order' => 6],
            ['sku' => 'spad-material-007', 'category' => 'آهن‌آلات و فولاد', 'name' => 'تیرآهن IPE سایز ۱۴', 'unit' => 'شاخه', 'sort_order' => 7],
            ['sku' => 'spad-material-008', 'category' => 'آهن‌آلات و فولاد', 'name' => 'تیرآهن IPE سایز ۱۶', 'unit' => 'شاخه', 'sort_order' => 8],
            ['sku' => 'spad-material-009', 'category' => 'آهن‌آلات و فولاد', 'name' => 'تیرآهن IPE سایز ۱۸', 'unit' => 'شاخه', 'sort_order' => 9],
            ['sku' => 'spad-material-010', 'category' => 'آهن‌آلات و فولاد', 'name' => 'نبشی فولادی', 'unit' => 'کیلوگرم', 'sort_order' => 10],
            ['sku' => 'spad-material-011', 'category' => 'آهن‌آلات و فولاد', 'name' => 'ناودانی فولادی', 'unit' => 'کیلوگرم', 'sort_order' => 11],
            ['sku' => 'spad-material-012', 'category' => 'آهن‌آلات و فولاد', 'name' => 'ورق سیاه', 'unit' => 'کیلوگرم', 'sort_order' => 12],
            ['sku' => 'spad-material-013', 'category' => 'آهن‌آلات و فولاد', 'name' => 'قوطی پروفیل آهنی', 'unit' => 'کیلوگرم', 'sort_order' => 13],
            ['sku' => 'spad-material-014', 'category' => 'آهن‌آلات و فولاد', 'name' => 'رابیتس ۱۳ ستون', 'unit' => 'برگ', 'sort_order' => 14],
            ['sku' => 'spad-material-015', 'category' => 'سیمان، گچ و ملات', 'name' => 'سیمان پرتلند تیپ ۲ پاکتی ۵۰ کیلویی', 'unit' => 'کیسه', 'sort_order' => 15],
            ['sku' => 'spad-material-016', 'category' => 'سیمان، گچ و ملات', 'name' => 'سیمان تیپ ۵ پاکتی ۵۰ کیلویی', 'unit' => 'کیسه', 'sort_order' => 16],
            ['sku' => 'spad-material-017', 'category' => 'سیمان، گچ و ملات', 'name' => 'سیمان سفید پاکتی', 'unit' => 'کیسه', 'sort_order' => 17],
            ['sku' => 'spad-material-018', 'category' => 'سیمان، گچ و ملات', 'name' => 'گچ سفیدکاری', 'unit' => 'کیسه', 'sort_order' => 18],
            ['sku' => 'spad-material-019', 'category' => 'سیمان، گچ و ملات', 'name' => 'گچ زیرکار', 'unit' => 'کیسه', 'sort_order' => 19],
            ['sku' => 'spad-material-020', 'category' => 'سیمان، گچ و ملات', 'name' => 'پودر سنگ', 'unit' => 'کیلوگرم', 'sort_order' => 20],
            ['sku' => 'spad-material-021', 'category' => 'سیمان، گچ و ملات', 'name' => 'ملات آماده بنایی', 'unit' => 'کیسه', 'sort_order' => 21],
            ['sku' => 'spad-material-022', 'category' => 'سیمان، گچ و ملات', 'name' => 'چسب بلوک هبلکس', 'unit' => 'کیسه', 'sort_order' => 22],
            ['sku' => 'spad-material-023', 'category' => 'آجر، بلوک و سفال', 'name' => 'آجر فشاری', 'unit' => 'عدد', 'sort_order' => 23],
            ['sku' => 'spad-material-024', 'category' => 'آجر، بلوک و سفال', 'name' => 'آجر ده سوراخ', 'unit' => 'عدد', 'sort_order' => 24],
            ['sku' => 'spad-material-025', 'category' => 'آجر، بلوک و سفال', 'name' => 'آجر سفالی دیواری ۱۵ سانتی', 'unit' => 'عدد', 'sort_order' => 25],
            ['sku' => 'spad-material-026', 'category' => 'آجر، بلوک و سفال', 'name' => 'بلوک سیمانی ۲۰ سانتی', 'unit' => 'عدد', 'sort_order' => 26],
            ['sku' => 'spad-material-027', 'category' => 'آجر، بلوک و سفال', 'name' => 'بلوک سبک پوکه‌ای', 'unit' => 'عدد', 'sort_order' => 27],
            ['sku' => 'spad-material-028', 'category' => 'آجر، بلوک و سفال', 'name' => 'بلوک هبلکس AAC', 'unit' => 'متر مکعب', 'sort_order' => 28],
            ['sku' => 'spad-material-029', 'category' => 'آجر، بلوک و سفال', 'name' => 'آجر نسوز نما', 'unit' => 'عدد', 'sort_order' => 29],
            ['sku' => 'spad-material-030', 'category' => 'آجر، بلوک و سفال', 'name' => 'سفال سقفی', 'unit' => 'عدد', 'sort_order' => 30],
            ['sku' => 'spad-material-031', 'category' => 'آجر، بلوک و سفال', 'name' => 'بلوک لیکا', 'unit' => 'عدد', 'sort_order' => 31],
            ['sku' => 'spad-material-032', 'category' => 'بتن و سنگدانه', 'name' => 'بتن آماده C25', 'unit' => 'متر مکعب', 'sort_order' => 32],
            ['sku' => 'spad-material-033', 'category' => 'بتن و سنگدانه', 'name' => 'بتن آماده C30', 'unit' => 'متر مکعب', 'sort_order' => 33],
            ['sku' => 'spad-material-034', 'category' => 'بتن و سنگدانه', 'name' => 'ماسه شسته', 'unit' => 'متر مکعب', 'sort_order' => 34],
            ['sku' => 'spad-material-035', 'category' => 'بتن و سنگدانه', 'name' => 'شن نخودی', 'unit' => 'متر مکعب', 'sort_order' => 35],
            ['sku' => 'spad-material-036', 'category' => 'بتن و سنگدانه', 'name' => 'شن بادامی', 'unit' => 'متر مکعب', 'sort_order' => 36],
            ['sku' => 'spad-material-037', 'category' => 'بتن و سنگدانه', 'name' => 'پوکه معدنی', 'unit' => 'متر مکعب', 'sort_order' => 37],
            ['sku' => 'spad-material-038', 'category' => 'بتن و سنگدانه', 'name' => 'فوق روان‌کننده بتن', 'unit' => 'لیتر', 'sort_order' => 38],
            ['sku' => 'spad-material-039', 'category' => 'بتن و سنگدانه', 'name' => 'الیاف بتن', 'unit' => 'کیلوگرم', 'sort_order' => 39],
            ['sku' => 'spad-material-040', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'سرامیک کف ۶۰×۶۰', 'unit' => 'متر مربع', 'sort_order' => 40],
            ['sku' => 'spad-material-041', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'سرامیک پرسلان ۸۰×۸۰', 'unit' => 'متر مربع', 'sort_order' => 41],
            ['sku' => 'spad-material-042', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'کاشی دیوار', 'unit' => 'متر مربع', 'sort_order' => 42],
            ['sku' => 'spad-material-043', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'سنگ تراورتن نما', 'unit' => 'متر مربع', 'sort_order' => 43],
            ['sku' => 'spad-material-044', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'سنگ گرانیت کف', 'unit' => 'متر مربع', 'sort_order' => 44],
            ['sku' => 'spad-material-045', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'سنگ مرمریت', 'unit' => 'متر مربع', 'sort_order' => 45],
            ['sku' => 'spad-material-046', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'چسب کاشی پودری', 'unit' => 'کیسه', 'sort_order' => 46],
            ['sku' => 'spad-material-047', 'category' => 'کاشی، سرامیک و سنگ', 'name' => 'بندکشی کاشی', 'unit' => 'کیسه', 'sort_order' => 47],
            ['sku' => 'spad-material-048', 'category' => 'عایق و آب‌بندی', 'name' => 'ایزوگام دولایه فویل‌دار', 'unit' => 'رول', 'sort_order' => 48],
            ['sku' => 'spad-material-049', 'category' => 'عایق و آب‌بندی', 'name' => 'ایزوگام دولایه ساده', 'unit' => 'رول', 'sort_order' => 49],
            ['sku' => 'spad-material-050', 'category' => 'عایق و آب‌بندی', 'name' => 'قیر ۶۰/۷۰', 'unit' => 'کیلوگرم', 'sort_order' => 50],
            ['sku' => 'spad-material-051', 'category' => 'عایق و آب‌بندی', 'name' => 'عایق رطوبتی پلیمری', 'unit' => 'کیلوگرم', 'sort_order' => 51],
            ['sku' => 'spad-material-052', 'category' => 'عایق و آب‌بندی', 'name' => 'پشم سنگ', 'unit' => 'متر مربع', 'sort_order' => 52],
            ['sku' => 'spad-material-053', 'category' => 'عایق و آب‌بندی', 'name' => 'فوم XPS', 'unit' => 'متر مربع', 'sort_order' => 53],
            ['sku' => 'spad-material-054', 'category' => 'عایق و آب‌بندی', 'name' => 'پشم شیشه', 'unit' => 'رول', 'sort_order' => 54],
            ['sku' => 'spad-material-055', 'category' => 'لوله و تأسیسات', 'name' => 'لوله پنج لایه ۱۶ میلی‌متر', 'unit' => 'متر', 'sort_order' => 55],
            ['sku' => 'spad-material-056', 'category' => 'لوله و تأسیسات', 'name' => 'لوله پلی‌پروپیلن ۲۰', 'unit' => 'متر', 'sort_order' => 56],
            ['sku' => 'spad-material-057', 'category' => 'لوله و تأسیسات', 'name' => 'لوله فاضلاب PVC سایز ۱۱۰', 'unit' => 'شاخه', 'sort_order' => 57],
            ['sku' => 'spad-material-058', 'category' => 'لوله و تأسیسات', 'name' => 'لوله گالوانیزه', 'unit' => 'متر', 'sort_order' => 58],
            ['sku' => 'spad-material-059', 'category' => 'لوله و تأسیسات', 'name' => 'اتصالات پنج لایه', 'unit' => 'عدد', 'sort_order' => 59],
            ['sku' => 'spad-material-060', 'category' => 'لوله و تأسیسات', 'name' => 'شیرآلات ساختمانی', 'unit' => 'ست', 'sort_order' => 60],
            ['sku' => 'spad-material-061', 'category' => 'لوله و تأسیسات', 'name' => 'رادیاتور پنلی', 'unit' => 'عدد', 'sort_order' => 61],
            ['sku' => 'spad-material-062', 'category' => 'برق و کابل', 'name' => 'سیم افشان ۱٫۵', 'unit' => 'متر', 'sort_order' => 62],
            ['sku' => 'spad-material-063', 'category' => 'برق و کابل', 'name' => 'سیم افشان ۲٫۵', 'unit' => 'متر', 'sort_order' => 63],
            ['sku' => 'spad-material-064', 'category' => 'برق و کابل', 'name' => 'کابل برق ۳×۲٫۵', 'unit' => 'متر', 'sort_order' => 64],
            ['sku' => 'spad-material-065', 'category' => 'برق و کابل', 'name' => 'لوله برق پلیکا', 'unit' => 'شاخه', 'sort_order' => 65],
            ['sku' => 'spad-material-066', 'category' => 'برق و کابل', 'name' => 'کلید و پریز', 'unit' => 'عدد', 'sort_order' => 66],
            ['sku' => 'spad-material-067', 'category' => 'برق و کابل', 'name' => 'تابلو برق', 'unit' => 'عدد', 'sort_order' => 67],
            ['sku' => 'spad-material-068', 'category' => 'برق و کابل', 'name' => 'چراغ پنلی LED', 'unit' => 'عدد', 'sort_order' => 68],
            ['sku' => 'spad-material-069', 'category' => 'چوب و دکوراسیون', 'name' => 'ورق MDF خام', 'unit' => 'ورق', 'sort_order' => 69],
            ['sku' => 'spad-material-070', 'category' => 'چوب و دکوراسیون', 'name' => 'ورق MDF ملامینه', 'unit' => 'ورق', 'sort_order' => 70],
            ['sku' => 'spad-material-071', 'category' => 'چوب و دکوراسیون', 'name' => 'گچبرگ کناف RG', 'unit' => 'برگ', 'sort_order' => 71],
            ['sku' => 'spad-material-072', 'category' => 'چوب و دکوراسیون', 'name' => 'پروفیل سازه کناف', 'unit' => 'شاخه', 'sort_order' => 72],
            ['sku' => 'spad-material-073', 'category' => 'چوب و دکوراسیون', 'name' => 'لمینت کف', 'unit' => 'متر مربع', 'sort_order' => 73],
            ['sku' => 'spad-material-074', 'category' => 'چوب و دکوراسیون', 'name' => 'قرنیز MDF', 'unit' => 'متر', 'sort_order' => 74],
            ['sku' => 'spad-material-075', 'category' => 'چوب و دکوراسیون', 'name' => 'چوب روسی', 'unit' => 'متر مکعب', 'sort_order' => 75],
            ['sku' => 'spad-material-076', 'category' => 'رنگ و پوشش', 'name' => 'رنگ اکریلیک داخلی', 'unit' => 'گالن', 'sort_order' => 76],
            ['sku' => 'spad-material-077', 'category' => 'رنگ و پوشش', 'name' => 'رنگ پلاستیک ساختمانی', 'unit' => 'گالن', 'sort_order' => 77],
            ['sku' => 'spad-material-078', 'category' => 'رنگ و پوشش', 'name' => 'رنگ روغنی', 'unit' => 'گالن', 'sort_order' => 78],
            ['sku' => 'spad-material-079', 'category' => 'رنگ و پوشش', 'name' => 'بتونه آماده', 'unit' => 'کیلوگرم', 'sort_order' => 79],
            ['sku' => 'spad-material-080', 'category' => 'رنگ و پوشش', 'name' => 'پرایمر دیوار', 'unit' => 'گالن', 'sort_order' => 80],
            ['sku' => 'spad-material-081', 'category' => 'رنگ و پوشش', 'name' => 'رزین نما', 'unit' => 'لیتر', 'sort_order' => 81],
            ['sku' => 'spad-material-082', 'category' => 'درب، پنجره و نما', 'name' => 'پنجره UPVC دوجداره', 'unit' => 'متر مربع', 'sort_order' => 82],
            ['sku' => 'spad-material-083', 'category' => 'درب، پنجره و نما', 'name' => 'پنجره آلومینیومی ترمال بریک', 'unit' => 'متر مربع', 'sort_order' => 83],
            ['sku' => 'spad-material-084', 'category' => 'درب، پنجره و نما', 'name' => 'شیشه دوجداره', 'unit' => 'متر مربع', 'sort_order' => 84],
            ['sku' => 'spad-material-085', 'category' => 'درب، پنجره و نما', 'name' => 'درب ضد سرقت', 'unit' => 'عدد', 'sort_order' => 85],
            ['sku' => 'spad-material-086', 'category' => 'درب، پنجره و نما', 'name' => 'درب داخلی HDF', 'unit' => 'عدد', 'sort_order' => 86],
            ['sku' => 'spad-material-087', 'category' => 'درب، پنجره و نما', 'name' => 'کامپوزیت نما', 'unit' => 'متر مربع', 'sort_order' => 87],
            ['sku' => 'spad-material-088', 'category' => 'درب، پنجره و نما', 'name' => 'آجرنمای نسوز', 'unit' => 'متر مربع', 'sort_order' => 88],
            ['sku' => 'spad-material-089', 'category' => 'سایر مصالح', 'name' => 'توری مرغی', 'unit' => 'رول', 'sort_order' => 89],
            ['sku' => 'spad-material-090', 'category' => 'سایر مصالح', 'name' => 'میخ ساختمانی', 'unit' => 'کیلوگرم', 'sort_order' => 90],
            ['sku' => 'spad-material-091', 'category' => 'سایر مصالح', 'name' => 'چسب بتن', 'unit' => 'لیتر', 'sort_order' => 91],
            ['sku' => 'spad-material-092', 'category' => 'سایر مصالح', 'name' => 'دستکش کار', 'unit' => 'جفت', 'sort_order' => 92],
            ['sku' => 'spad-material-093', 'category' => 'سایر مصالح', 'name' => 'سیم آرماتوربندی', 'unit' => 'کیلوگرم', 'sort_order' => 93],
        ];
        foreach ($items as $i => $item) {
            DB::table('material_price_products')->insert(array_merge($item, [
                'enabled' => true, 'featured' => $i < 8,
                'created_at' => now(), 'updated_at' => now()
            ]));
        }
    }
    public function down()
    {
        Schema::dropIfExists('material_price_history');
        Schema::dropIfExists('material_price_products');
        Schema::dropIfExists('material_price_settings');
    }
}
