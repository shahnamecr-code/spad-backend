@php
    $url=asset('digiTheme');
    $home_setting=App\Models\Setting::where('group','home_page')->whereIn('code',array_column(\App\Models\Setting::$settings['home_page']['subs'],'code'))->get()->pluck('value','code');
    $main_settings=\App\Models\Setting::whereIn('code',['general_enamd','general_logo','general_copyright','general_phone','general_site_description','general_site_name','app_categories'])->get()->pluck('value','code');
    $upload_url='/storage/';

@endphp

<footer>
    <div class="footer-title-container">
        <div class="footer-title-r">
            {{--            <div class="footer-title-img"></div>--}}
            <p>
                تلفن پشتیبانی:
                {{isset($main_settings['general_phone'])?$main_settings['general_phone']:''}}
                |
                هفت روز هفته، ۲۴ ساعت شبانه‌روز پاسخگوی شما هستیم.</p>
        </div>
        <button>بازگشت به بالا <i class="fas fa-angle-up"></i></button>
    </div>
    <div class="services-container">
        <a href="#" class="service-detail-container">
            <div class="service-icon-wrapper"><img src="{{$url}}/img/express.svg" alt="سرویس"></div>
            <p>امکان تحویل اکسپرس</p>
        </a>
        <a href="#" class="service-detail-container">
            <div class="service-icon-wrapper"><img src="{{$url}}/img/payloc.svg" alt="سرویس"></div>
            <p>امکان پرداخت در محل</p>
        </a>
        <a href="#" class="service-detail-container">
            <div class="service-icon-wrapper"><img src="{{$url}}/img/24hsvg.svg" alt="سرویس"></div>
            <p>۷ روز هفته ۲۴ ساعته</p>
        </a>
        <a href="#" class="service-detail-container">
            <div class="service-icon-wrapper"><img src="{{$url}}/img/returnItem.svg" alt="سرویس"></div>
            <p>۷ روز ضمانت بازگشت کالا</p>
        </a>
        <a href="#" class="service-detail-container">
            <div class="service-icon-wrapper"><img src="{{$url}}/img/org-o.svg" alt="سرویس"></div>
            <p>ضمانت اصل بودن کالا</p>
        </a>
    </div>
{{--
        <div class="footer-info-container">
            <div class="footer-info">
                <h3>با دیجی کالا</h3>
                <ul>
                    <li><a href="#">اتاق خبر دیجی کالا</a></li>
                    <li><a href="#">فروش در دیجی کالا</a></li>
                    <li><a href="#">فرصت های شغلی</a></li>
                    <li><a href="#">تماس با دیجی کالا</a></li>
                    <li><a href="#">درباره دیجی کالا</a></li>
                </ul>
            </div>
            <div class="footer-info">
                <h3>خدمات مشتریان</h3>
                <ul>
                    <li><a href="#">پاسخ به پرسش های متداول </a></li>
                    <li><a href="#">رویه های بازگرداندن کالا</a></li>
                    <li><a href="#">شرایط استفاده</a></li>
                    <li><a href="#">حریم خصوصی</a></li>
                    <li><a href="#">گزارش باگ</a></li>
                </ul>
            </div>
            <div class="footer-info">
                <h3>راهنمای خرید از دیجی کالا</h3>
                <ul>
                    <li><a href="#">نحوه ثبت سفارش</a></li>
                    <li><a href="#">رویه ارسال سفارش</a></li>
                    <li><a href="#">شیوه های پرداخت</a></li>
                </ul>
            </div>
            <div class="footer-info">
                <h3>با ما همراه باشید</h3>
                <ul class="social-container">
                    <li><a href="#"><i class="lab la-instagram"></i></a></li>
                    <li><a href="#"><i class="lab la-twitter"></i></a></li>
                    <li><a href="#"><i class="lab la-linkedin"></i></a></li>
                    <li><a href="#"><i class="lab la-youtube"></i></a></li>
                </ul>
                <h3>از جدید ترین تخفیف ها با خبر شوید</h3>
                <div class="footer-email-container">
                    <input type="email" name="" id="f-email" placeholder="آدرس ایمیل خود را وارد کنید" onchange="emailHandler()">
                    <button id="f-email-btn">ثبت</button>
                </div>
            </div>
        </div>
--}}
    <div class="download-app-container">
        <div class="download-app-title">
            <img src="{{$url}}/img//diglip.svg" alt="لوگو">
            <p>دانلود اپلیکیشن تبریز قطعه</p>
        </div>
        <div class="download-app-links">
            <a href="#"><img src="{{$url}}/img/googleplay.svg" alt="دانلود"></a>
            <a href="#"><img src="{{$url}}/img/bazar.svg" alt="دانلود"></a>
            <a href="#"><img src="{{$url}}/img/myket.png" alt="دانلود"></a>
            <a href="#"><img src="{{$url}}/img/sibapp.svg" alt="دانلود"></a>
            <a href="#" class="download-app-more"><i class="fas fa-ellipsis-h"></i></a>
        </div>
    </div>
    <div class="store-bio-container">
        {!! isset($main_settings['general_site_description'])?$main_settings['general_site_description']:'' !!}
        {{--            <h3>فروشگاه اینترنتی دیجی‌کالا، بررسی، انتخاب و خرید آنلاین</h3>--}}
        {{--            <p>دیجی‌کالا به عنوان یکی از قدیمی‌ترین فروشگاه های اینترنتی با بیش از یک دهه تجربه، با پایبندی به سه اصل، پرداخت در محل، ۷ روز ضمانت بازگشت کالا و تضمین اصل‌بودن کالا موفق شده تا همگام با فروشگاه‌های معتبر جهان، به بزرگ‌ترین فروشگاه اینترنتی ایران تبدیل شود. به محض ورود به سایت دیجی‌کالا با دنیایی از کالا رو به رو می‌شوید! هر آنچه که نیاز دارید و به ذهن شما خطور می‌کند در اینجا پیدا خواهید کرد.--}}
        </p>
        <div class="e-nemad-container">
            @if(isset($main_settings['general_enamd']))
                {!! html_entity_decode(str_replace(['<p>','</p>'],'',$main_settings['general_enamd'])) !!}
            @endif
{{--            <a href="#"><img src="{{$url}}/img/social-i.png" alt="نماد"></a>
            <a href="#"><img src="{{$url}}/img/e-nemad.png" alt="نماد"></a>--}}
        </div>
    </div>
    <p class="footer-p">
        {!! isset($main_settings['general_copyright'])?$main_settings['general_copyright']:'' !!}
    </p>
</footer>
{{--
<div class="footer-end-container">
    <a href="#"><img src="{{$url}}/img/digiMag.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiPay.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiPlus.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiclub.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/ebook.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiNext.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img//pindo.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img//komeda.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiAff.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiExpress.svg" alt="برند"></a>
    <div class="footer-end-border"></div>
    <a href="#"><img src="{{$url}}/img/digiStyle.svg" alt="برند"></a>
</div>
--}}
