@php
    $url=asset('digiTheme')
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{$url}}/style/style.css">
    <link rel="stylesheet"
          href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/css/splide.min.css">
    <link rel="stylesheet" href="{{$url}}/style/star-rating-svg.css">
    <link rel="stylesheet" href="{{$url}}/style/owl.carousel.min.css">
    <title>Cate</title>
</head>
<body class="cate">
<a class="t-banner-wrapper" href="#">
    <img src="{{$url}}/img/product-top-b.gif" alt="بنر"  id="nav-menu2">
</a>
<header class="header-container product-header" id="header">
    <section class="top-header-container">
        <div class="logo-search-container">
            <a href="#" class="logo-wrapper"></a>
            <i class="fas fa-search"></i>
            <input type="search" placeholder="جستجو در دیجی کالا ...">
        </div>
        <div class="sign-cart-container">
            <a href="#" class="sign-in-btn"> <i class="las la-user"></i> ورود به حساب کاربری </a>
            <div class="border-solid"></div>
            <a href="#"><i class="las la-shopping-cart cart-icon"></i></a>
        </div>
    </section>
    <section class="bottom-header-container" id="navbar">
        <nav class="nav-bar-container">
            <ul>
                <a href="#">
                    <li><i class="las la-align-justify"></i> دسته بندی ها </li>
                </a>
                <a href="#">
                    <li><i class="las la-store"></i> سوپرمارکت</li>
                </a>
                <a href="#">
                    <li><i class="fas fa-percentage"></i>تخفیف ها و پیشنهاد ها</li>
                </a>
                <a href="#">
                    <li><i class="las la-certificate"></i>دیجی کالای من</li>
                </a>
                <a href="#">
                    <li><i class="las la-dice-d20"></i>دیجی پلاس</li>
                </a>
                <a href="#">
                    <li><i class="las la-link"></i>دیجی کلاب</li>
                </a>
                <a href="#">
                    <li><i class="lab la-product-hunt"></i> دیجی پی </li>
                </a>
                <a href="#">
                    <li>سوالی دارید؟</li>
                </a>
                <a href="#">
                    <li>فروشنده شوید</li>
                </a>

            </ul>
        </nav>
        <button class="select-loc-btn">لطفا شهر و استان خود را انتخاب کنید <i
                class="las la-map-marker-alt"></i></button>
    </section>
</header>
<main class="cate-main">
    <aside>
        <h2>دسته بندی کالا ها</h2>
        <ul>
            <li><a href="#">موبایل</a></li>
            <li><a href="#">گوشی موبایل</a></li>
            <li><a href="#">لوازم جانبی</a></li>
        </ul>
        <ul>
            <li><a href="#">لپ تاپ و الترابوک</a></li>
            <li><a href="#">لپ تاپ</a></li>
            <li><a href="#">لوازم جانبی</a></li>
            <li><a href="#"> الترابوک</a></li>
        </ul>
    </aside>
    <section class="cate-container">
        <div class="amazing-offer-container">
                <span class="amazing-tag">
                    <img src="{{$url}}/img/offer.svg" alt="tag">
                </span>
            <div class="amazing-show-item">
                <div class="amazing-show-img">
                    <img src="{{$url}}/img/laptop.jpg" alt="">
                </div>
                <div class="amazing-show-info">
                    <h2>لپ تاپ 14 اینچی لنوو مدل V14 - ADA 82C6</h2>
                    <ul>
                        <li><i class="fas fa-circle"></i> با ظرفیت 4 گیگابایت</li>
                        <li><i class="fas fa-circle"></i> با ظرفیت یک ترابایت</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="cate-4-banner-container">
            <a href="#" class="cate-4-banner">
                <img src="{{$url}}/img/b-shirt.jpg" alt="بنر">
            </a>
            <a href="#" class="cate-4-banner">
                <img src="{{$url}}/img/b-shirt.jpg" alt="بنر">
            </a>
            <a href="#" class="cate-4-banner">
                <img src="{{$url}}/img/b-shirt.jpg" alt="بنر">
            </a>
            <a href="#" class="cate-4-banner">
                <img src="{{$url}}/img/b-shirt.jpg" alt="بنر">
            </a>
        </div>
        <section class="two-cart-slider cate-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title cate-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <a href="#">مشاهده همه</a>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n15 s-he">
                        <div class="splide__track">
                            <div class="splide__list splide-n4-list">
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="two-cart-slider cate-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title cate-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <a href="#">مشاهده همه</a>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n16 s-he">
                        <div class="splide__track">
                            <div class="splide__list splide-n4-list">
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                        </div>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del>۱۷،۵۰۰،۰۰۰</del>
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <article class="cate-detail">
            <h2>کالای دیجیتال</h2>
            <h3>خرید انواع کالای دیجیتال از فروشگاه اینترنتی دیجی کالا</h3>
            <p id="cateP">عمر انقلاب دیجیتال به کمتر از نیم قرن می‌رسد، اما همین برهه‌ی زمانی کوتاه در تاریخ، زندگی نوع بشر را کاملا دگرگون کرده و شتاب قابل توجهی به پیشرفت انسان در زمینه‌های مختلف بخشیده است. شاید اولین قدم‌های دیجیتالی شدن دنیا را بتوان تغییر تراتزیستورهای آنالوگ به دیجیتال دانست که بستر لازم برای کالای دیجیتال نظیر کامپیوتر، تلفن را فراهم کرد. در حال حاضر محصولات دیجیتالی آنقدر توسعه یافته‌اند که می‌توان ردپای آنها را در ساده‌ترین امور روزمره مشاهده کرد. بعضی از این محصولات به ابزارهای جدانشدنی برای انسان تبدیل شده‌اند، تصور زندگی بدون آنها و امکاناتی که در اختیار انسان قرار می‌دهند، وجود ندارد. موبایل‌ها، لپ‌تاپ، ماشین‌های ادارای از این دسته محصولات هستند. کامپیوترهای شخصی، لپ‌تاپ و گوشی تلفن همراه نیز مسیر پر فراز و نشیبی را پشت سر گذاشته‌اند تا به شکل هوشمند امروزی عرضه شود. اولین کامپیوتر قابل حمل جهان در سال 1981 به بازار آمد. این دستگاه صفحه نمایشی 5 اینچی داشت و وزن آن حدود 11 کیلوگرم بود. در حالیکه شرکت‌هایی نظیر اپل یا هواوی لپ‌تاپ‌هایی به وزن یک کیلوگرم ساخته‌اند.

                اولین تلفن همراه نیز در سال 1983 در دنیا به فروش رسید که وزنی معادل یک کیلوگرم داشت و امکانات بسیار کمی را در اختیار داشت. قیمت این دستگاه بیش از سه هزار دلار بود. در حالیکه امروزه شاهد تلفن‌های همراه هوشمندی هستیم که ده‌ها وسیله را در دل خود جای داده‌اند و هر یک از آنها عملکرد فوق‌العاده‌ای رو در اختیار انسان قرار می‌دهد. هم‌چنین محدوده‌ی قیمت این دستگاه بسیار گسترده است و همه‌ی افراد می‌توانند آن را تهیه کنند.

                دنیای دیجیتال نه تنها زندگی بشر را راحت‌تر از قبل کرده و زمان بیشتری برای آنها خریده است بلکه وسایل و سرگرمی‌های پیشرفته‌تر و مهیج‌تری را هم فراهم کرده است. تاریخچه‌ی ساخت کنسول بازی نیز مشابه سایر دستگاه‌های دیجیتال است. این صنعت از سال 1972 شروع به کار کرد. اولین کنسول‌های بازی تصویری سیاه و سفید داشتند و محیط بازی آنها بسیار ساده بود. این صنعت با تولید کنسول بازی ایکس باکس در ابتدای قرن 21 ام به اوج خود رسید و دنیای سرگرمی و بازی را متحول کرد. این دستگاه بسیار پیشرفته بود و گرافیک عالی و بازی‌های با کیفیتی را در اختیار علاقمندان قرار می‌داد. کنسول‌های بازی تاکنون 8 نسل را پشت سر گذاشته‌اند. شرکت‌هایی نظیر سونی و مایکروسافت مطرح‌ترین برندهای تولید کننده‌ی کنسول بازی هستند و شرکت‌های متعدد دیگری مثل لاجیتک لوازم جانبی آنها را عرضه می‌کند.

                کالاهای دیجیتال به موبایل، لپ تاپ یا وسایل سرگرمی محدود نمی‌شوند. مچ‌بند و ساعت‌های هوشمند از جدیدترین محصولات دیجیتالی به شمار می‌آیند که امکانات زیادی را در اختیار انسان قرار می‌دهد. عملکرد ساعت‌های هوشمند برند اپل آنقدر پیشرفته است که می‌توان آن را یک کامپیوتر مچی در نظر گرفت. شما می‌توانید با ای سیم، تماس‌های اضطراری بین‌المللی برقرار کنید، در وب جستجو کنید و جواب تماس و پیام‌های خود را بدهید. علاوه بر این، اغلب مچ‌بندها و ساعت‌های هوشمند ابزارهای لازم برای پایش سلامت را در اختیار دارند و به طور دائم ضربان قلب، کیفیت خواب، تعداد قدم‌های برداشته شده و ... را محاسبه و گزارش می‌کنند. همچنین در نسخه‌های پیشرفته‌تر می‌توان برنامه‌هایی برای بیماران دیابتی و بهداشت و سلامت بانوان مشاهده کرد.

                دیجی کالا جدیدترین و به روزترین کالاهای دیجیتال عرضه شده در دنیا را برای مشتریان خود گردآوری کرده است. شما می‌توانید خوشنام‌ترین برندهای جهانی را در وبسایت دیجی کالا مشاهده و محصول مورد نظر خود از میان هزاران کالای دیجیتال خریداری کنید.

            </p>
            <button onclick="cateShowMoreP(this)">نمایش بیشتر <i class="fas fa-angle-down"></i></button>
        </article>
    </section>
</main>
<footer class="footer-no-pa">
    <div class="footer-title-container">
        <div class="footer-title-r">
            <div class="footer-title-img"></div>
            <p>
                تلفن پشتیبانی:
                ۶۱۹۳۰۰۰۰ - ۰۲۱
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
    <div class="download-app-container">
        <div class="download-app-title">
            <img src="{{$url}}/img//diglip.svg" alt="لوگو">
            <p>دانلود اپلیکیشن دیجی کالا</p>
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
        <h3>فروشگاه اینترنتی دیجی‌کالا، بررسی، انتخاب و خرید آنلاین</h3>
        <p>دیجی‌کالا به عنوان یکی از قدیمی‌ترین فروشگاه های اینترنتی با بیش از یک دهه تجربه، با پایبندی به سه اصل، پرداخت در محل، ۷ روز ضمانت بازگشت کالا و تضمین اصل‌بودن کالا موفق شده تا همگام با فروشگاه‌های معتبر جهان، به بزرگ‌ترین فروشگاه اینترنتی ایران تبدیل شود. به محض ورود به سایت دیجی‌کالا با دنیایی از کالا رو به رو می‌شوید! هر آنچه که نیاز دارید و به ذهن شما خطور می‌کند در اینجا پیدا خواهید کرد.
        </p>
        <div class="e-nemad-container">
            <a href="#"><img src="{{$url}}/img/digital-i.png" alt="نماد"></a>
            <a href="#"><img src="{{$url}}/img/social-i.png" alt="نماد"></a>
            <a href="#"><img src="{{$url}}/img/e-nemad.png" alt="نماد"></a>
        </div>
    </div>
    <p class="footer-p">استفاده از مطالب فروشگاه اینترنتی دیجی‌کالا فقط برای مقاصد غیرتجاری و با ذکر منبع بلامانع است. کلیه حقوق این سایت متعلق به شرکت نوآوران فن آوازه (فروشگاه آنلاین دیجی‌کالا) می‌باشد.
    </p>
</footer>

<script src="https://kit.fontawesome.com/906a17977e.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@2.4.21/dist/js/splide.min.js"></script>
<script src="{{$url}}/Js/jQuery.js"></script>
<script src="{{$url}}/Js/jquery.JStick.js"></script>
<script src="{{$url}}/Js/jquery.malihu.PageScroll2id.js"></script>
<script src="{{$url}}/js/owl.carousel.min.js"></script>
<script src="{{$url}}/Js/jquery.star-rating-svg.js"></script>
<script src="{{$url}}/Js/javascript.js"></script>
</body>
</html>
