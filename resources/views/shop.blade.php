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
    <title>Shop Page</title>
</head>
<body>
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
<main class="shop-page-container">
    <aside>
        <div class="shop-page-profile">
            <div class="shop-page-profile-header">
                <i class="las la-store-alt "></i>
            </div>
            <h2>ایرانیان قایم همراه کاوش تیم</h2>
            <p>عضویت از ۴ سال و ۲ ماه پیش</p>
            <div class="shop-page-feed">
                <a href="#">81% رضایت از کالا ها</a>
                |
                <span>18,680 رای</span>
            </div>
            <div class="shop-page-work">
                <div>
                    <span>100 %</span>
                    تامین به موقع
                </div>
                <div>
                    <span>100 %</span>
                    تامین به موقع
                </div>
                <div>
                    <span>100 %</span>
                    تامین به موقع
                </div>
            </div>
            <div class="shop-page-profile-link">
                <i class="las la-share-alt"></i>
                <a href="#">www.google.com</a>
            </div>
        </div>
        <div class="catr2-tree-plan">
            <h2>دسته بندی نتایج</h2>
            <ul class="cate2-tree-list">
                <li><i class="fas fa-angle-left"></i><a href="#">کالای دیجیتال</a></li>
                <li><i class="fas fa-angle-down"></i><a href="#">موبایل</a></li>
                <li>گوشی موبایل</li>
            </ul>
        </div>
        <div class="catr2-tree-plan">
            <h2>جستجو در نتایج</h2>
            <input type="search" class="cate2-search" placeholder="نام محصول یا برند مورد نظر خود را بنویسید ..."/>
            <i class="fas fa-search"></i>
        </div>
        <div class="catr2-tree-plan cursor-tree">
            <h2 id="open-cate2" onclick="openCateHandler(this)">برند <i class="fas fa-chevron-down trans"></i></h2>
            <input type="search" class="cate2-search" placeholder="نام محصول یا برند مورد نظر خود را بنویسید ..."/>
            <i class="fas fa-search"></i>
            <ul class="cate2-brand-list">
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                    SAMSUNG
                </li>
            </ul>
        </div>
        <div class="catr2-tree-plan cursor-tree">
            <h2 id="open-cate2" onclick="openCateHandler(this)">برند <i class="fas fa-chevron-down trans"></i></h2>
            <input type="search" class="cate2-search" placeholder="نام محصول یا برند مورد نظر خود را بنویسید ..."/>
            <i class="fas fa-search"></i>
            <ul class="cate2-brand-list">
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>

                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>

                </li>
                <li>
                    <label>
                        <input type="checkbox">
                        سامسونگ
                    </label>
                </li>
            </ul>
        </div>
        <div class="cate2-toggle-container">
            ​<label class="switch">
                <input type="checkbox">
                <span class="sliderr round"></span>
            </label>
            فقط فوری
        </div>
        </div>
    </aside>
    <section class="cate2-container">
        <div class="shop-page-about">
            <ul class="shop-page-bar">
                <li class="shop-page-bar-active" onclick="shopPageBarHandler(this)" id="shop-p"><i class="las la-store-alt "></i> درباره فروشنده</li>
                <li id="shop-p" onclick="shopPageBarHandler(this)"><i class="las la-truck"></i> رویه های ارسال</li>
                <li id="shop-p" onclick="shopPageBarHandler(this)"><i class="las la-undo-alt"></i> رویه های بازگرداندن</li>
            </ul>
            <div class="shop-page-info">
                <p >این فروشنده به صورت رسمی محصولات زیر را به فروش می‌رساند:</p>
                <p >این  به صورت رسمی محصولات زیر را به فروش می‌رساند:</p>
                <p >این فروشنده به صورت رسمی  زیر را به فروش می‌رساند:</p>
            </div>
        </div>
        <section class="cate2-container shop-page-cart">
            <div class="cate2-slugs">
                <a href="#">فروشگاه اینترنتی دیجی کالا</a>/
                <a href="#">کالای دیجیتاال</a>/
                <a href="#">موبایل</a>
            </div>
            <div class="cate2-cart-container">
                <div class="cate2-cart-sorting">
                    <i class="fas fa-sort-amount-down"></i>
                    <span>مرتب سازی بر اساس :</span>
                    <a href="#" id="cate2-sort"  onclick="sortHandler(this)">پر بازدیدترین</a>
                    <a href="#" id="cate2-sort"  onclick="sortHandler(this)">پر فروش ترین</a>
                    <a href="#" id="cate2-sort"  onclick="sortHandler(this)">محبوب ترین</a>
                    <a href="#" id="cate2-sort"  onclick="sortHandler(this)">جدید ترین</a>
                    <a href="#" id="cate2-sort"  onclick="sortHandler(this)">ارزان ترین</a>
                    <a href="#" id="cate2-sort"  onclick="sortHandler(this)">گران ترین</a>
                </div>
                <div class="cate2-cart-wrapper">
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                    <div class="cate2-cart">
                        <a href="#" class="cate2-cart-img">
                            <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                        </a>
                        <p>گوشی موبایل شیائومی مدل Redmi Note 10 pro Max M2101K6I دو سیم‌ کارت ظرفیت 128 گیگابایت و رم 8 گیگابایت</p>
                        <div class="cate2-cart-info">
                            <div class="cate2-cart-inventory">
                                <i class="fas fa-save"></i>
                                موجود در انبار
                            </div>
                            <div class="cate2-cart-rate">
                                (<span> 256 </span>)
                                4
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="cate2-cart-price">
                            تومان
                            <span> 12,000,000 </span>
                        </div>
                        <a href="#" class="cate2-cart-footer">
                            <i class="fas fa-store-alt"></i>
                            فروشنده :
                            <span>رایان شاپ</span>
                        </a>
                        <div class="cate2-cart-color">
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                            <i class="fas fa-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="pagention-container  pagention-cate2">
                    <a href="#"><i class="fas fa-angle-double-right"></i></a>
                    <a href="#" class="active-page" onclick="pageHandler(this)">1</a>
                    <a href="#" class="unactive-page" onclick="pageHandler(this)">2</a>
                    <a href="#" class="unactive-page" onclick="pageHandler(this)">3</a>
                    <a href="#" class="unactive-page" onclick="pageHandler(this)">4</a>
                    <a href="#"><i class="fas fa-angle-double-left"></i></a>
                </div>
            </div>
        </section>
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
