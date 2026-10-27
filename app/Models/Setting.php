<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;
    protected $casts = [
        'json_value' => 'array',
        'datetime_value' => 'datetime',
        'date_value' => 'date',
        'boolean_value' => 'boolean',
        'integer_value' => 'integer',
        'float_value' => 'float',
    ];
//    protected $visible=['id','group','code','value'];
    protected $appends = ['value', 'user_show'];

    protected $with = ['files'];

    public static $settings = [
        'general_setting' => [
            'name' => 'تنظیمات عمومی',
            'subs' => [
                [
                    'code' => 'general_site_name',
                    'name' => 'عنوان سایت',
                    'type' => 'text',
                ],
                [
                    'code' => 'footer_phone',
                    'name' => 'شماره تماس سایت در فوتر',
                    'type' => 'text',
                ],
                [
                    'code' => 'general_site_description',
                    'name' => 'توضیحات در مورد سایت',
                    'type' => 'text',
                ],
                [
                    'code' => 'general_logo',
                    'name' => 'لوگو',
                    'type' => 'image',
                ],
                [
                    'code' => 'panel_logo',
                    'name' => 'لوگوی پنل',
                    'type' => 'image',
                ],
                [
                    'code' => 'general_favicon',
                    'name' => 'تصویر favicon',
                    'type' => 'image',
                ],
                [
                    'code' => 'header_phone',
                    'name' => 'شماره تماس جهت نمایش در هدر سایت',
                    'type' => 'text',
                ],
                [
                    'code' => 'footer_description',
                    'name' => 'توضیحات جهت نمایش در فوتر',
                    'type' => 'textarea',
                    'simple'=>true,
                ],
                [
                    'code' => 'samandehi',
                    'name' => 'لینک ساماندهی',
                    'type' => 'text',
                ],
                [
                    'code' => 'enamad',
                    'name' => 'لینک اینماد',
                    'type' => 'text',
                ],
                [
                    'code' => 'kasbokar',
                    'name' => 'لینک کسب و کار مجازی',
                    'type' => 'text',
                ],
                [
                    'code' => 'whatsapp',
                    'name' => 'شماره تماس واتس اپ',
                    'type' => 'text',
                ],

                [
                    'code' => 'android_app_download',
                    'name' => 'لینک دانلود اپلیکیشن اندروید (فایل APK یا مارکت)',
                    'type' => 'text',
                ],
                [
                    'code' => 'ios_app_download',
                    'name' => 'لینک دانلود اپلیکیشن iOS (اپ استور یا وب اپ)',
                    'type' => 'text',
                ],
                [
                    'code' => 'direct_app_download',
                    'name' => 'لینک دانلود مستقیم',
                    'type' => 'text',
                ],
                [
                    'code' => 'copy_right',
                    'name' => 'متن کپی رایت',
                    'type' => 'textarea',
                ],
                [
                    'code' => 'developer_text',
                    'name' => 'متن طراحی و توسعه',
                    'type' => 'textarea',
                ],
                [
                    'code' => 'facebook_account',
                    'name' => 'اکانت فیسبوک',
                    'type' => 'text',
                ],
                [
                    'code' => 'twitter_account',
                    'name' => 'اکانت توییتر',
                    'type' => 'text',
                ],
                [
                    'code' => 'youtube_account',
                    'name' => 'اکانت یوتیوب',
                    'type' => 'text',
                ],
                [
                    'code' => 'instagram_account',
                    'name' => 'اکانت اینستاگرام',
                    'type' => 'text',
                ],
                [
                    'code' => 'telegram_account',
                    'name' => 'اکانت تلگرام',
                    'type' => 'text',
                ],
                [
                    'code' => 'email',
                    'name' => 'ایمیل',
                    'type' => 'text',
                ],
                [
                    'code' => 'address',
                    'name' => 'آدرس',
                    'type' => 'text',
                ],
                [
                    'code' => 'user_default_profile',
                    'name' => 'عکس پیش فرض پروفایل',
                    'type' => 'image',
                ],
                [
                    'code' => 'search_ad_by_code',
                    'name' => 'آیکون جستجوی کد ملک',
                    'type' => 'image',
                ]
            ],
        ],
        'market_ticker' => [
            'name' => 'نوار قیمت ارز و طلا',
            'subs' => [
                [
                    'code' => 'market_ticker_enabled',
                    'name' => 'نمایش نوار قیمت در سایت',
                    'type' => 'select',
                    'options' => [
                        ['id' => '1', 'value' => 'فعال'],
                        ['id' => '0', 'value' => 'غیرفعال'],
                    ],
                ],
                [
                    'code' => 'market_ticker_api_url',
                    'name' => 'آدرس API قیمت‌ها',
                    'type' => 'text',
                ],
                [
                    'code' => 'market_ticker_api_key',
                    'name' => 'API Key',
                    'type' => 'text',
                ],
                [
                    'code' => 'market_ticker_refresh_seconds',
                    'name' => 'فاصله بروزرسانی API بر حسب ثانیه',
                    'type' => 'text',
                ],
                [
                    'code' => 'market_ticker_usd',
                    'name' => 'دلار',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_gold_18',
                    'name' => 'طلای ۱۸ عیار',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_gold_24',
                    'name' => 'طلای ۲۴ عیار',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_new_coin',
                    'name' => 'سکه جدید',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_old_coin',
                    'name' => 'سکه قدیم',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_half_coin',
                    'name' => 'نیم سکه',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_quarter_coin',
                    'name' => 'ربع سکه',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_gold_ounce',
                    'name' => 'انس طلا',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_melted_gold',
                    'name' => 'آبشده نقدی',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_mesghal_gold',
                    'name' => 'مثقال طلا',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_eur',
                    'name' => 'یورو',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_gbp',
                    'name' => 'پوند انگلیس',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_usdt',
                    'name' => 'تتر',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_aed',
                    'name' => 'درهم امارات',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
                [
                    'code' => 'market_ticker_try',
                    'name' => 'لیر ترکیه',
                    'type' => 'select',
                    'options' => [['id' => '1', 'value' => 'فعال'], ['id' => '0', 'value' => 'غیرفعال']],
                ],
            ],
        ],
        /*        'home_setting' => [
                    'name' => 'تنظیمات صفحه اصلی',
                    'subs' => [
                        [
                            'code' => 'about_us',
                            'name' => 'درباره ما',
                            'type' => 'textarea',
                        ],
                        [
                            'code' => 'app_common_questions',
                            'name' => 'سوالات متداول',
                            'type' => 'list-item',
                            'settings' => [
                                [
                                    'code' => 'question',
                                    'name' => 'سوال',
                                    'type' => 'textarea',
                                ],
                                [
                                    'code' => 'answer',
                                    'name' => 'پاسخ',
                                    'type' => 'textarea',
                                ],
                            ]
                        ],
                        [
                            'code' => 'app_main_slider',
                            'name' => 'اسلایدر اصلی',
                            'type' => 'list-item',
                            'settings' => [
                                [
                                    'code' => 'title',
                                    'name' => 'عنوان',
                                    'type' => 'text',
                                ],
                                [
                                    'code' => 'link',
                                    'name' => 'لینک',
                                    'type' => 'text',
                                ],
                                [
                                    'code' => 'image',
                                    'name' => 'تصویر',
                                    'type' => 'image',
                                ],
                            ]
                        ],
                        [
                            'code' => 'app_services',
                            'name' => 'خدمات',
                            'type' => 'list-item',
                            'settings' => [
                                [
                                    'code' => 'title',
                                    'name' => 'عنوان',
                                    'type' => 'text',
                                ],
                                [
                                    'code' => 'image',
                                    'name' => 'تصویر',
                                    'type' => 'image',
                                ],
                            ]
                        ],
                        [
                            'code' => 'app_categories',
                            'name' => 'دسته های محصولات منتخب',
                            'type' => 'product-category-multi-select',
                        ],
                    ],
                ],*/
        'live_property_price' => [
            'name' => 'قیمت لحظه‌ای ملک',
            'subs' => [
                [
                    'code' => 'live_property_price_enabled',
                    'name' => 'فعال بودن صفحه قیمت لحظه‌ای ملک',
                    'type' => 'select',
                    'options' => [
                        ['id' => '1', 'value' => 'فعال'],
                        ['id' => '0', 'value' => 'غیرفعال'],
                    ],
                ],
                [
                    'code' => 'live_property_price_header_enabled',
                    'name' => 'نمایش در منوی هدر سایت',
                    'type' => 'select',
                    'options' => [
                        ['id' => '1', 'value' => 'نمایش داده شود'],
                        ['id' => '0', 'value' => 'مخفی باشد'],
                    ],
                ],
                [
                    'code' => 'live_property_price_header_label',
                    'name' => 'عنوان لینک در منوی هدر',
                    'type' => 'text',
                ],
                [
                    'code' => 'live_property_price_badge',
                    'name' => 'متن لیبل بالای صفحه',
                    'type' => 'text',
                ],
                [
                    'code' => 'live_property_price_title',
                    'name' => 'عنوان اصلی صفحه',
                    'type' => 'text',
                ],
                [
                    'code' => 'live_property_price_description',
                    'name' => 'توضیحات صفحه',
                    'type' => 'textarea',
                    'simple' => true,
                ],
                [
                    'code' => 'live_property_price_min_samples',
                    'name' => 'حداقل تعداد فایل برای معتبر بودن قیمت یک منطقه',
                    'type' => 'text',
                    'description' => 'منطقه‌هایی با فایل کمتر همچنان نمایش داده می‌شوند اما با برچسب «داده محدود».',
                ],
                [
                    'code' => 'live_property_price_max_age_days',
                    'name' => 'حداکثر سن فایل‌های محاسبه‌شده (روز)',
                    'type' => 'text',
                    'description' => 'عدد 0 یعنی تمام آگهی‌های فعال بدون محدودیت زمانی بررسی شوند.',
                ],
                [
                    'code' => 'live_property_price_disclaimer',
                    'name' => 'متن توضیح / سلب مسئولیت پایین صفحه',
                    'type' => 'textarea',
                    'simple' => true,
                ],
            ],
        ],
        'mortgage_setting' => [
            'name' => 'تنظیمات وام',
            'subs' => [
                [
                    'code' => 'mortgage_enabled',
                    'name' => 'فعال سازی ماشین حساب وام مسکن',
                    'type' => 'boolean',
                ],
                [
                    'code' => 'mortgage_default_interest_rate',
                    'name' => 'نرخ سود سالانه وام (درصد)',
                    'type' => 'text',
                    'description' => 'این عدد را مطابق شرایط بانک و طرح وام به روز نگه دارید.',
                ],
                [
                    'code' => 'mortgage_terms_years',
                    'name' => 'مدت های بازپرداخت مجاز (سال)',
                    'type' => 'text',
                    'description' => 'با ویرگول جدا کنید؛ مثال: 5,10,12,15,20',
                ],
                [
                    'code' => 'mortgage_min_amount',
                    'name' => 'حداقل مبلغ قابل انتخاب وام (تومان)',
                    'type' => 'text',
                ],
                [
                    'code' => 'mortgage_step_amount',
                    'name' => 'گام تغییر مبلغ وام در ماشین حساب (تومان)',
                    'type' => 'text',
                ],
                [
                    'code' => 'mortgage_bond_loan_per_sheet',
                    'name' => 'میزان وام پوشش داده شده توسط هر برگ اوراق (تومان)',
                    'type' => 'text',
                ],
                [
                    'code' => 'mortgage_bond_price_per_sheet',
                    'name' => 'قیمت هر برگ اوراق (تومان)',
                    'type' => 'text',
                ],
                [
                    'code' => 'mortgage_note',
                    'name' => 'توضیح زیر ماشین حساب وام',
                    'type' => 'textarea',
                    'simple' => true,
                ],
            ],
        ],
        'home_page' => [
            'name' => 'صفحه اصلی',
            'subs' => [
                [
                    'name' => 'عنوان سئو',
                    'code' => 'home_seo_title',
                    'type' => 'text'
                ],
                [
                    'name' => 'توضیحات متا',
                    'code' => 'home_meta_description',
                    'type' => 'text'
                ],
                [
                    'name' => 'تصویر پس زمینه بخش فیلترها',
                    'code' => 'filters_background',
                    'type' => 'image'
                ],
/*                [
                    'name' => 'اسلایدر اصلی',
                    'code' => 'main_slider',
                    'type' => 'list-item',
                    'settings' => [
                        [
                            'code' => 'desktop_image',
                            'type' => 'image',
                            'name' => 'تصویر سایز دسکتاپ'
                        ],
                        [
                            'code' => 'mobile_image',
                            'type' => 'image',
                            'name' => 'تصویر سایز موبایل'
                        ],
                        [
                            'code' => 'tablet_image',
                            'type' => 'image',
                            'name' => 'تصویر سایز تبلت'
                        ],
                        [
                            'code' => 'link',
                            'type' => 'text',
                            'name' => 'لینک اسلاید'
                        ],
                    ]
                ],*/
                [
                    'name' => 'تصویر بنر اول',
                    'code' => 'banner_1_image',
                    'type' => 'image'
                ],
                [
                    'name' => 'لینک بنر اول',
                    'code' => 'banner_1_link',
                    'type' => 'text'
                ],
                [
                    'name' => 'تصویر بنر دوم',
                    'code' => 'banner_2_image',
                    'type' => 'image'
                ],
                [
                    'name' => 'لینک بنر دوم',
                    'code' => 'banner_2_link',
                    'type' => 'text'
                ],
/*                [
                    'name' => 'باکس دسته بندی های آگهی',
                    'code' => 'categories_list',
                    'type' => 'list-item',
                    'settings' => [
                        [
                            'code' => 'icon',
                            'type' => 'image',
                            'name' => 'آیکون'
                        ],
                        [
                            'code' => 'link',
                            'type' => 'text',
                            'name' => 'لینک'
                        ],
                        [
                            'code' => 'title',
                            'type' => 'text',
                            'name' => 'عنوان'
                        ],
                    ]
                ],*/
            ]
        ],
        'about_page' => [
            'name' => 'صفحه درباره ما',
            'subs' => [
                [
                    'code' => 'about_us_title',
                    'type' => 'text',
                    'name' => 'عنوان صفحه درباره ما',
                ],
                [
                    'code' => 'about_us',
                    'type' => 'textarea',
                    'name' => 'متن درباره ما',
                ],
                [
                    'code' => 'about_image',
                    'type' => 'image',
                    'name' => 'عکس صفحه درباره ما',
                    'description' => 'عکس با نسبت ۱ به ۳ آپلود شود'
                ],
                [
                    'code' => 'about_experts_list',
                    'type' => 'list-item',
                    'name' => 'لیست کارشناسان',
                    'settings' => [
                        [
                            'code' => 'image',
                            'type' => 'image',
                            'name' => 'تصویر'
                        ],
                        [
                            'code' => 'name',
                            'type' => 'text',
                            'name' => 'نام'
                        ],
                        [
                            'code' => 'mobile',
                            'type' => 'text',
                            'name' => 'شماره تماس'
                        ]
                    ]
                ],
            ]
        ],
        'contact_page' => [
            'name' => 'تماس با ما',
            'subs' => [
                /*[
                    'name' => 'تاریخچه شرکت',
                    'code' => 'contact_history',
                    'type' => 'textarea'
                ],*/
                [
                    'name' => 'آدرس شرکت',
                    'code' => 'contact_address',
                    'type' => 'text'
                ],
                [
                    'name' => 'شماره تماس شرکت',
                    'code' => 'contact_phone',
                    'type' => 'text'
                ],
                [
                    'code' => 'contact_email',
                    'name' => 'ایمیل',
                    'type' => 'text',
                ],
                // [
                //     'name'=>'مکان شرکت روی نقشه',
                //     'code'=>'contact_location',
                //     'type'=>'textarea'
                // ],
            ],
        ],
        'ads_single' => [
            'name' => 'تنظیمات صفحه آگهی',
            'subs' => [
                [
                    'name' => 'لیست هیئت مدیره',
                    'code' => 'managers_list',
                    'type' => 'list-item',
                    'settings' => [
                        [
                            'name' => 'نام',
                            'type' => 'text',
                            'code' => 'title',
                        ],
                        [
                            'name' => 'تصویر',
                            'type' => 'image',
                            'code' => 'image',
                        ],
                        [
                            'name' => 'پست سازمانی',
                            'type' => 'text',
                            'code' => 'job',
                        ],
                        [
                            'name' => 'شماره تماس',
                            'type' => 'text',
                            'code' => 'phone',
                        ],
                        [
                            'name' => 'شماره داخلی',
                            'type' => 'text',
                            'code' => 'inner_code',
                        ],
                    ]
                ],
                [
                    'name' => 'علت های گزارش آگهی',
                    'code' => 'ads_report_reasons',
                    'type' => 'list-item',
                    'settings' => [
                        [
                            'name' => 'عنوان',
                            'type' => 'text',
                            'code' => 'title',
                        ]
                    ]
                ],
                [
                    'name' => 'نحوه ارزیابی قیمت',
                    'code' => 'evaluation_type',
                    'type' => 'textarea',
                    'simple' => true,
                ],
                [
                    'name' => 'عوامل موثر در ارزیابی',
                    'code' => 'evaluation_params',
                    'type' => 'list-item',
                    'settings' => [
                        [
                            'name' => 'عنوان',
                            'type' => 'text',
                            'code' => 'title',
                        ]
                    ]
                ],
            ]
        ],
        'rules_page' => [
            'name' => 'قوانین و مقررات',
            'subs' => [
                [
                    'name' => 'مقررات سایت',
                    'code' => 'rules',
                    'type' => 'list-item',
                    'settings' => [
                        [
                            'name' => 'نام',
                            'type' => 'text',
                            'code' => 'title',
                        ],
                        [
                            'name' => 'توضیحات',
                            'type' => 'textarea',
                            'simple' => true,
                            'code' => 'description',
                        ],
                    ]
                ],
            ]
        ],
        'ads_expert_page' => [
            'name' => 'صفحه کارشناسی ملک',
            'subs' => [
                [
                    'code' => 'description',
                    'name' => 'توضیح',
                    'type' => 'textarea',
                    'simple' => true,
                ]
            ]
        ]
    ];

    public function getValueAttribute()
    {
        if (isset(self::$settings[$this->group]) && isset(self::$settings[$this->group]['subs'])) {
            $group = array_column(self::$settings[$this->group]['subs'], null, 'code');
            if (isset($group[$this->code])) {
                $setting_item = ($group[$this->code]);
                if (isset($setting_item['type'])) {
                    switch ($setting_item['type']) {
                        case 'image':
                        case 'gallery':
                            return $this->files()->get()->map(function ($file) {
                                return collect($file)->only(['id', 'url'])->toArray();
                            });
                        case 'list-item':
                            $val=$this->json_value;
                            $files=$this->files()->get()->map(function ($file) {
                                return collect($file)->only(['id', 'url'])->toArray();
                            })->pluck(null,'id')->toArray();
                            if (isset($setting_item['settings'])){
                                return self::getListItemFormInitData($setting_item, $val,$files);
                            }
//                            ............
                            break;
                    }
                    return $this->{get_attribute_type($setting_item['type'])};
                }
            }
        }
    }

    public function setValAttribute($val)
    {
        if (isset(self::$settings[$this->group]) && isset(self::$settings[$this->group]['subs'])) {
            $group = array_column(self::$settings[$this->group]['subs'], null, 'code');
            if (isset($group[$this->code])) {
                $setting_item = ($group[$this->code]);
                if (isset($setting_item['type'])) {
                    $this->{get_attribute_type_v1($setting_item['type'])} = $val;
                }
            }
        }
    }

    public static function getListItemFiles($item, $val, &$files)
    {
        if (isset($item['type']) && isset($item['code']) && $val) {
            if ($item['type'] == 'list-item' && isset($item['settings']) && count($item['settings']) > 0 && is_array($val) && count($val) > 0) {
                foreach ($val as $list_item) {
                    foreach ($item['settings'] as $setting) {
                        if (isset($list_item[$setting['code']])){
                            self::getListItemFiles($setting, $list_item[$setting['code']], $files);
                        }
                    }
                }

            } elseif ($item['type'] == "image" || $item['type'] == "gallery") {
                if (is_array($val)){
                    $files = array_merge($files, array_column($val,'id'));
                }
            }
        }
    }

    public function getListItemFormInitData($item, $val,$files)
    {
        if (isset($item['type']) && isset($item['code']) && $val) {
            if ($item['type'] == 'list-item' && isset($item['settings']) && count($item['settings']) > 0 && is_array($val) && count($val) > 0) {
                foreach ($val as $index=>$list_item) {
                    foreach ($item['settings'] as $setting) {
                        if (isset($list_item[$setting['code']])){
                            $val[$index][$setting['code']]=self::getListItemFormInitData($setting, $list_item[$setting['code']],$files);
                        }
                    }
                }
                return $val;
            } elseif ($item['type'] == "image" || $item['type'] == "gallery") {
                if (is_array($val)){
                    $arr=array_column($val,'id');
                    array_walk($arr,function (&$img)use($files){
                        $img=$files[$img]??null;
                    });
                }
            }
        }
        return $val;
    }

    public function setFiles($val)
    {
        if (isset(self::$settings[$this->group]) && isset(self::$settings[$this->group]['subs'])) {
            $group = array_column(self::$settings[$this->group]['subs'], null, 'code');
            if (isset($group[$this->code])) {
                $setting_item = ($group[$this->code]);
                if (isset($setting_item['type'])) {
                    switch ($setting_item['type']) {
                        case 'image':
                        case 'gallery':
                            if (is_array($val)){
                                $this->files()->syncWithPivotValues(array_column($val,'id'), ['collection_name' => $this->code]);
                            }
                            break;
                        case "list-item":
                            $files = [];
                            self::getListItemFiles($setting_item, $val, $files);
                            $this->files()->syncWithPivotValues($files, ['collection_name' => $this->code]);
                            break;
                    }
                }
            }
        }
    }

    public function getUserShowAttribute()
    {
        if (isset(self::$settings[$this->group])) {
            $group = self::$settings[$this->group];
            if (isset($group['subs'])) {
                $filtered_subs = array_filter($group['subs'], function ($val) {
                    return isset($val['code']) && $val['code'] == $this->code;
                });
                if ($filtered_subs && count($filtered_subs) > 0) {
                    $structure = $filtered_subs[array_key_first($filtered_subs)];
                    $value = $this->value;
                    $files=$this->files()->get()->map(function ($file) {
                        return collect($file)->only(['id', 'url'])->toArray();
                    })->pluck(null,'id');
                    self::userShowSetting($structure,$files, $value);
                    return $value;
                }
            }
        }
    }

    public static function userShowSetting($structure,$files, &$value)
    {
        if (isset($structure['type'])) {
            switch ($structure['type']) {
                case 'image':
                    $arr=collect($value)->toArray();
                    if (count($arr)>0){
                        $value = $arr[array_key_first($arr)]['url'];
                    }else{
                        $value=null;
                    }
                case 'select':
                    break;
                case 'boolean':
                case 'color':
                case 'gallery':
                $arr=collect($value)->toArray();
                array_walk($arr, function (&$v) {
                    if (is_array($v) && count($v)>0){
                            $v = $v[array_key_first($v)]['url'];
                        }else{
                            $v=null;
                        }
                    });
                $value=$arr;
                case 'list-item':
                    if (isset($structure['settings'])) {
                        $settings = array_column($structure['settings'], null, 'code');
                        $items = [];
                        foreach ($value as $item) {
                            $clone_item = [];
                            foreach ($item as $index => $v) {
                                if (isset($settings[$index])) {
                                    self::userShowSetting($settings[$index],$files, $v);
                                    $clone_item[$index] = $v;
                                }
                            }
                            $items[] = $clone_item;
                        }
                        $value = $items;
                        /*                        foreach ($structure['settings'] as $setting){
                                                    if (isset($setting['code']) && is_array($value) && isset($value[$setting['code']])){
                                                        $val=$value[$setting['code']];
                                                        self::userShowSetting($setting,$val);
                                                        $value[$setting['code']]=$val;
                                                    }
                                                }*/
                    }
                case 'textarea':
                default:
                    return $value;

            }
        }
    }

    public function files()
    {
        return $this->morphToMany(File::class, 'fileable');
    }
}
