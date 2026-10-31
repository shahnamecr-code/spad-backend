<?php

use App\Models\InquiryService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedSpadPropertyInquiryCatalog extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inquiry_services')) {
            throw new \RuntimeException('Install the existing SPAD inquiry system before the property catalogue.');
        }
        foreach (['partner_handler_type', 'partner_payout', 'form_schema', 'enabled', 'price'] as $field) {
            if (!Schema::hasColumn('inquiry_services', $field)) {
                throw new \RuntimeException('Missing inquiry service column: ' . $field);
            }
        }

        // Catalogue drafts only: never advertise unconfigured services as
        // free, automatically payable, or already connected to official APIs.
        // Idempotent, and safe for administrator edits made after installation.
        $records = json_decode(<<<'JSON'
[
  {
    "slug": "property-deed-authenticity",
    "title": "تصدیق اصالت سند مالکیت",
    "category": "ثبت و اسناد",
    "short_description": "کنترل اصالت اطلاعات مندرج روی سند مالکیت از مسیر ثبت‌من؛ با اطلاعات سند و در صورت نیاز ورود مالک.",
    "provider": "سازمان ثبت اسناد",
    "reference_url": "https://my.ssaa.ir",
    "fee_label": "رایگان در استعلام عمومی",
    "fee_note": "خدمت عمومی، مشروط به اطلاعات سند",
    "region": "ملی",
    "sort_order": 500
  },
  {
    "slug": "property-notary-document",
    "title": "تصدیق اصالت اسناد رسمی",
    "category": "ثبت و اسناد",
    "short_description": "کنترل اصالت سند تنظیم‌شده در دفترخانه با شناسه و رمز تصدیق.",
    "provider": "سازمان ثبت اسناد",
    "reference_url": "https://my.ssaa.ir",
    "fee_label": "مطابق تعرفه مرجع",
    "fee_note": "بسته به خدمت و احراز هویت",
    "region": "ملی",
    "sort_order": 501
  },
  {
    "slug": "property-mortgage-attachment",
    "title": "وضعیت رهن و بازداشت ملک",
    "category": "ثبت و اسناد",
    "short_description": "بررسی محدودیت نقل‌وانتقال، توقیف و رهن؛ نتیجه رسمی نیازمند دسترسی مالک یا دفترخانه است.",
    "provider": "سازمان ثبت / دفترخانه",
    "reference_url": "https://kateb.ir",
    "fee_label": "طبق تعرفه دفترخانه",
    "fee_note": "نیازمند احراز سمت یا مراجعه رسمی",
    "region": "ملی",
    "sort_order": 502
  },
  {
    "slug": "property-registry-followup",
    "title": "پیگیری استعلام الکترونیک ملک",
    "category": "ثبت و اسناد",
    "short_description": "پیگیری استعلام‌های ثبت‌شده با شناسه یا اطلاعات پیگیری معتبر.",
    "provider": "سازمان ثبت اسناد",
    "reference_url": "https://my.ssaa.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "برای درخواست‌های ثبت‌شده",
    "region": "ملی",
    "sort_order": 503
  },
  {
    "slug": "property-deed-issuance",
    "title": "پیگیری صدور سند تک‌برگ",
    "category": "ثبت و اسناد",
    "short_description": "بررسی روند صدور و وضعیت درخواست سند جدید یا جایگزین.",
    "provider": "سازمان ثبت اسناد",
    "reference_url": "https://kateb.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "بسته به نوع درخواست",
    "region": "ملی",
    "sort_order": 504
  },
  {
    "slug": "property-deed-conversion",
    "title": "تعویض سند دفترچه‌ای به تک‌برگ",
    "category": "ثبت و اسناد",
    "short_description": "ثبت یا پیگیری درخواست تعویض سند قدیمی؛ نیازمند مدارک مالکیت.",
    "provider": "سامانه کاتب",
    "reference_url": "https://kateb.ir",
    "fee_label": "۲۰۱٬۷۰۰ تومان (گزارش ۱۴۰۴)",
    "fee_note": "مبلغ گزارش‌شده در خبر؛ نرخ فعلی در مرجع بررسی شود",
    "region": "ملی",
    "sort_order": 505
  },
  {
    "slug": "property-agency-authenticity",
    "title": "تصدیق اصالت وکالت‌نامه ملکی",
    "category": "ثبت و اسناد",
    "short_description": "بررسی اصالت وکالت‌نامه رسمی مرتبط با ملک از طریق شناسه و رمز سند.",
    "provider": "سازمان ثبت اسناد",
    "reference_url": "https://my.ssaa.ir",
    "fee_label": "مطابق تعرفه مرجع",
    "fee_note": "در صورت نیاز احراز هویت",
    "region": "ملی",
    "sort_order": 506
  },
  {
    "slug": "property-cadastral-status",
    "title": "اطلاعات ثبتی و حدنگار (کاداستر)",
    "category": "ثبت و اسناد",
    "short_description": "بررسی مشخصات ثبتی و محدودیت‌های دسترسی به اطلاعات ملک در سامانه ثبت.",
    "provider": "سازمان ثبت اسناد",
    "reference_url": "https://my.ssaa.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "اطلاعات شخصی صرفاً با مجوز قانونی",
    "region": "ملی",
    "sort_order": 507
  },
  {
    "slug": "property-contract-verification",
    "title": "پیگیری قرارداد و شناسه ثبت",
    "category": "ثبت و اسناد",
    "short_description": "بررسی وضعیت پیش‌نویس و ثبت قراردادهای مرتبط با ملک از مسیرهای رسمی.",
    "provider": "سامانه کاتب",
    "reference_url": "https://kateb.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "دسترسی با شناسه و اطلاعات قرارداد",
    "region": "ملی",
    "sort_order": 508
  },
  {
    "slug": "property-building-permit",
    "title": "استعلام پروانه ساختمانی",
    "category": "شهرداری و شهرسازی",
    "short_description": "کنترل وضعیت و مشخصات پروانه شهرسازی و ساختمان براساس پرونده شهرداری.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "هزینه در مرجع مشخص می‌شود",
    "fee_note": "بسته به پرونده و خدمت",
    "region": "شیراز",
    "sort_order": 509
  },
  {
    "slug": "property-completion",
    "title": "استعلام گواهی پایان‌کار",
    "category": "شهرداری و شهرسازی",
    "short_description": "بررسی صدور و صحت گواهی پایان‌کار ساختمان با شماره پرونده یا مشخصات ملک.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "هزینه در مرجع مشخص می‌شود",
    "fee_note": "بسته به پرونده و خدمت",
    "region": "شیراز",
    "sort_order": 510
  },
  {
    "slug": "property-violation-clearance",
    "title": "استعلام گواهی عدم خلاف",
    "category": "شهرداری و شهرسازی",
    "short_description": "بررسی وجود گواهی عدم خلاف یا درخواست پیگیری وضعیت تخلفات ساختمانی.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "هزینه در مرجع مشخص می‌شود",
    "fee_note": "در صورت لزوم بررسی شهرداری",
    "region": "شیراز",
    "sort_order": 511
  },
  {
    "slug": "property-renovation-tax",
    "title": "استعلام عوارض نوسازی",
    "category": "شهرداری و شهرسازی",
    "short_description": "مشاهده بدهی و پرداخت عوارض نوسازی ملک با کد نوسازی؛ مبلغ برای هر ملک متفاوت است.",
    "provider": "سامانه سرو شیراز",
    "reference_url": "https://sarv.shiraz.ir",
    "fee_label": "بدهی براساس اطلاعات ملک",
    "fee_note": "مبلغ بدهی ملک با کارمزد استعلام متفاوت است",
    "region": "شیراز",
    "sort_order": 512
  },
  {
    "slug": "property-waste-fee",
    "title": "استعلام عوارض پسماند",
    "category": "شهرداری و شهرسازی",
    "short_description": "بررسی عوارض مرتبط با پسماند و خدمات شهری، در صورت پوشش شهرداری.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://shiraz.ir",
    "fee_label": "بدهی براساس پرونده ملک",
    "fee_note": "مبلغ وابسته به ملک و سال",
    "region": "شیراز",
    "sort_order": 513
  },
  {
    "slug": "property-city-renovation-code",
    "title": "کد نوسازی و شناسایی ملک",
    "category": "شهرداری و شهرسازی",
    "short_description": "بازیابی یا پیگیری کد نوسازی جهت خدمات شهرسازی و عوارض.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "بر حسب پرونده",
    "region": "شیراز",
    "sort_order": 514
  },
  {
    "slug": "property-city-plan",
    "title": "استعلام کاربری و طرح تفصیلی",
    "category": "شهرداری و شهرسازی",
    "short_description": "بررسی پهنه، کاربری مصوب و محدودیت‌های شهرسازی براساس موقعیت و پرونده.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "هزینه در مرجع مشخص می‌شود",
    "fee_note": "منوط به داده‌های شهرداری",
    "region": "شیراز",
    "sort_order": 515
  },
  {
    "slug": "property-map-request",
    "title": "دستور نقشه و ضوابط ساخت",
    "category": "شهرداری و شهرسازی",
    "short_description": "پیگیری دستور نقشه، سطح اشغال و ضوابط ساخت ملک از درگاه شهروندسپاری.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "تعرفه پرونده متغیر",
    "fee_note": "هزینه صدور با استعلام ساده متفاوت است",
    "region": "شیراز",
    "sort_order": 516
  },
  {
    "slug": "property-urban-file",
    "title": "پیگیری پرونده شهرسازی",
    "category": "شهرداری و شهرسازی",
    "short_description": "پیگیری پرونده پروانه، تخلف و درخواست‌های ساختمانی در شهرداری.",
    "provider": "شهرداری شیراز",
    "reference_url": "https://eshahr.shiraz.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "دسترسی با اطلاعات پرونده",
    "region": "شیراز",
    "sort_order": 517
  },
  {
    "slug": "property-tehran-permit",
    "title": "تصدیق پروانه و پایان‌کار تهران",
    "category": "شهرداری و شهرسازی",
    "short_description": "استعلام گواهی‌ها و پروانه‌های صادرشده در سامانه رسمی شهرداری تهران.",
    "provider": "شهرداری تهران",
    "reference_url": "https://urban.tehran.ir/Inquiry",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "سرویس اختصاصی املاک تهران",
    "region": "تهران",
    "sort_order": 518
  },
  {
    "slug": "property-transfer-tax",
    "title": "استعلام مالیات نقل‌وانتقال ملک",
    "category": "مالیات و بدهی",
    "short_description": "بررسی پرونده و بدهی مالیاتی نقل‌وانتقال؛ مالیات با ارزش معاملاتی تعیین می‌شود.",
    "provider": "سازمان امور مالیاتی",
    "reference_url": "https://my.tax.gov.ir",
    "fee_label": "مبلغ متغیر طبق پرونده",
    "fee_note": "نرخ مالیات، قیمت خدمات استعلام نیست",
    "region": "ملی",
    "sort_order": 519
  },
  {
    "slug": "property-property-tax-debt",
    "title": "بدهی مالیاتی املاک",
    "category": "مالیات و بدهی",
    "short_description": "مشاهده بدهی‌های مالیاتی مرتبط با ملک و مؤدی از حساب احرازشده.",
    "provider": "سازمان امور مالیاتی",
    "reference_url": "https://my.tax.gov.ir",
    "fee_label": "مبلغ براساس پرونده مالیاتی",
    "fee_note": "نیازمند احراز هویت مؤدی",
    "region": "ملی",
    "sort_order": 520
  },
  {
    "slug": "property-vacant-home-tax",
    "title": "وضعیت مالیات خانه خالی",
    "category": "مالیات و بدهی",
    "short_description": "پیگیری وضعیت اطلاعات ملک در سامانه املاک و اسکان و مالیات احتمالی.",
    "provider": "سامانه املاک و اسکان",
    "reference_url": "https://amlak.mrud.ir",
    "fee_label": "مبلغ براساس وضعیت ملک",
    "fee_note": "ممکن است مشمول مالیات نباشد",
    "region": "ملی",
    "sort_order": 521
  },
  {
    "slug": "property-rental-tax",
    "title": "استعلام مالیات اجاره",
    "category": "مالیات و بدهی",
    "short_description": "بررسی مالیات مربوط به درآمد اجاره و سوابق پرونده مؤدی.",
    "provider": "سازمان امور مالیاتی",
    "reference_url": "https://my.tax.gov.ir",
    "fee_label": "مبلغ طبق پرونده مؤدی",
    "fee_note": "بسته به درآمد و شرایط معافیت",
    "region": "ملی",
    "sort_order": 522
  },
  {
    "slug": "property-postal-code",
    "title": "یافتن کد پستی ۱۰رقمی ملک",
    "category": "کد پستی و نشانی",
    "short_description": "جستجوی کد پستی بر اساس نشانی یا نقشه در خدمات مکانی پست.",
    "provider": "شرکت ملی پست (GNAF)",
    "reference_url": "https://gnaf.post.ir",
    "fee_label": "رایگان در جستجوی پایه",
    "fee_note": "خدمات تکمیلی ممکن است هزینه داشته باشند",
    "region": "ملی",
    "sort_order": 523
  },
  {
    "slug": "property-postal-certificate",
    "title": "گواهی تأییدیه کد پستی",
    "category": "کد پستی و نشانی",
    "short_description": "دریافت گواهی نشانی یا تأیید کدپستی از سامانه شرکت پست.",
    "provider": "شرکت ملی پست (GNAF)",
    "reference_url": "https://gnaf.post.ir",
    "fee_label": "۳۰٬۸۰۰ تا حدود ۳۸٬۵۰۰ تومان",
    "fee_note": "بازه منتشرشده در منابع غیردولتی ۱۴۰۵؛ نرخ قطعی در پست",
    "region": "ملی",
    "sort_order": 524
  },
  {
    "slug": "property-postal-address",
    "title": "احراز نشانی و تطبیق آدرس",
    "category": "کد پستی و نشانی",
    "short_description": "پیگیری تأیید نشانی ثبت‌شده و وضعیت تطبیق با اطلاعات پستی.",
    "provider": "شرکت ملی پست",
    "reference_url": "https://gnaf.post.ir",
    "fee_label": "هزینه در مرجع مشخص می‌شود",
    "fee_note": "وابسته به نوع احراز و مراجعه",
    "region": "ملی",
    "sort_order": 525
  },
  {
    "slug": "property-amlak-sokna",
    "title": "ثبت و بررسی وضعیت اقامتگاه",
    "category": "کد پستی و نشانی",
    "short_description": "پیگیری اقامتگاه ثبت‌شده توسط شخص واجد دسترسی در سامانه ملی املاک و اسکان.",
    "provider": "وزارت راه و شهرسازی",
    "reference_url": "https://amlak.mrud.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "نیازمند احراز هویت مالک یا ساکن",
    "region": "ملی",
    "sort_order": 526
  },
  {
    "slug": "property-water-bill",
    "title": "استعلام بدهی قبض آب",
    "category": "انشعابات و قبوض",
    "short_description": "مشاهده بدهی صورتحساب آب ملک با شناسه قبض یا اشتراک.",
    "provider": "سامانه‌های خدمات قبض",
    "reference_url": "https://pishkhan24.com",
    "fee_label": "بدهی براساس قبض",
    "fee_note": "سامانه واسط؛ تعرفه خدمت در مرحله استعلام",
    "region": "ملی",
    "sort_order": 527
  },
  {
    "slug": "property-power-bill",
    "title": "استعلام بدهی قبض برق",
    "category": "انشعابات و قبوض",
    "short_description": "بررسی سوابق و بدهی برق با شناسه قبض در مسیر ارائه‌دهنده خدمت.",
    "provider": "سامانه‌های خدمات قبض",
    "reference_url": "https://pishkhan24.com",
    "fee_label": "بدهی براساس قبض",
    "fee_note": "سامانه واسط؛ تعرفه خدمت در مرحله استعلام",
    "region": "ملی",
    "sort_order": 528
  },
  {
    "slug": "property-gas-bill",
    "title": "استعلام بدهی قبض گاز",
    "category": "انشعابات و قبوض",
    "short_description": "پیگیری مانده بدهی قبض گاز با شناسه یا اطلاعات اشتراک.",
    "provider": "سامانه‌های خدمات قبض",
    "reference_url": "https://pishkhan24.com",
    "fee_label": "بدهی براساس قبض",
    "fee_note": "سامانه واسط؛ تعرفه خدمت در مرحله استعلام",
    "region": "ملی",
    "sort_order": 529
  },
  {
    "slug": "property-telephone-bill",
    "title": "استعلام قبض تلفن ثابت",
    "category": "انشعابات و قبوض",
    "short_description": "مشاهده بدهی تلفن ثابت ملک با شماره تلفن یا شناسه مربوطه.",
    "provider": "سامانه‌های خدمات قبض",
    "reference_url": "https://pishkhan24.com",
    "fee_label": "بدهی براساس قبض",
    "fee_note": "سامانه واسط؛ تعرفه خدمت در مرحله استعلام",
    "region": "ملی",
    "sort_order": 530
  },
  {
    "slug": "property-water-branch",
    "title": "وضعیت انشعاب آب و فاضلاب",
    "category": "انشعابات و قبوض",
    "short_description": "پیگیری مالکیت انشعاب، تسویه‌حساب و سوابق در آبفای منطقه.",
    "provider": "شرکت آب و فاضلاب",
    "reference_url": "https://www.abfa-ilam.ir/support/form/?n=12",
    "fee_label": "تعرفه در شرکت آبفا",
    "fee_note": "خدمات منطقه‌ای؛ پیگیری با آبفای محل ملک",
    "region": "ملی",
    "sort_order": 531
  },
  {
    "slug": "property-national-lands",
    "title": "استعلام اراضی ملی و مستثنیات",
    "category": "زمین و منابع طبیعی",
    "short_description": "پیگیری درخواست‌های مجاز مرتبط با اراضی ملی، مستثنیات و کاربری زمین.",
    "provider": "پنجره واحد مدیریت زمین",
    "reference_url": "https://iraneland.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "نیازمند اطلاعات زمین و مجوز قانونی",
    "region": "ملی",
    "sort_order": 532
  },
  {
    "slug": "property-land-boundaries",
    "title": "استعلام وضعیت و تداخلات اراضی",
    "category": "زمین و منابع طبیعی",
    "short_description": "بررسی درخواست‌های اراضی، محدوده‌ها و تداخلات از مسیر پنجره زمین.",
    "provider": "پنجره واحد مدیریت زمین",
    "reference_url": "https://iraneland.ir",
    "fee_label": "در درگاه اعلام می‌شود",
    "fee_note": "فرآیند به نوع زمین بستگی دارد",
    "region": "ملی",
    "sort_order": 533
  }
]
JSON
        , true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                InquiryService::firstOrCreate(
                    ['slug' => $record['slug']],
                    [
                        'title' => $record['title'],
                        'category' => $record['category'],
                        'short_description' => $record['short_description'],
                        'description' => 'مرجع ارائه: ' . $record['provider']
                            . ' | نشانی مرجع: ' . $record['reference_url']
                            . ' | تعرفه گزارش‌شده: ' . $record['fee_label']
                            . ' | توضیح تعرفه: ' . $record['fee_note']
                            . ' | پوشش جغرافیایی: ' . $record['region']
                            . ' | قبل از فعال‌سازی، تعرفه، فرم و مسئول اجرا یا API معتبر را در پنل مدیر مشخص کنید.',
                        'price' => 0, // Placeholder only; service is DISABLED.
                        'partner_payout' => 0,
                        'execution_type' => 'manual', // Placeholder; manager may switch to API.
                        'partner_handler_type' => null, // No routing before configuration.
                        'estimated_minutes' => null,
                        'form_schema' => [
                            [
                                'key' => 'property_reference',
                                'label' => 'شماره پرونده، سند یا شناسه ملک',
                                'type' => 'text',
                                'required' => true,
                                'placeholder' => 'شناسه موردنیاز این استعلام را طبق راهنمای مدیر وارد کنید',
                            ],
                            [
                                'key' => 'notes',
                                'label' => 'توضیحات تکمیلی',
                                'type' => 'textarea',
                                'required' => false,
                            ],
                        ],
                        'enabled' => false,
                        'featured' => false,
                        'sort_order' => $record['sort_order'],
                    ]
                );
            }
        });
    }

    public function down()
    {
        // Deliberately preserve all records, later pricing/configuration edits,
        // and any inquiries once enabled. Disable/remove only from the admin.
    }
}
