@extends('digiTheme.main')

@php
    $url=asset('digiTheme');
    $home_setting=App\Models\Setting::where('group','home_page')->whereIn('code',array_column(\App\Models\Setting::$settings['home_page']['subs'],'code'))->get()->pluck('value','code');
    $main_settings=\App\Models\Setting::whereIn('code',['general_logo','general_copyright','general_phone','general_site_description','general_site_name','app_categories'])->get()->pluck('value','code');

    $default_image='images/default/unknown-product.png';
    $upload_url='/storage/';


    $info=[
        'id'=>$product->id,
        'name' => $product->name?($product->name):($product->template->title),
        'category' => [
            'id' => $product->template->main_category->id,
            'name' => $product->template->main_category->name,
            'slug' => $product->template->main_category->slug,
        ],
        'properties' => $product->template->properties->pluck(null, 'property.code'),
        'group_attributes'=>$product->template->main_category->group_attributes,
        'is_variant'=>$product->template->super_attributes && $product->template->super_attributes->count()>0,
        'variants'=>$product->variants()->with(['inventories'=>function($q){
            $q->where('quantity','>','0')->oldest()->first();
        }])->get(),
        'super_attributes'=>$product->template->super_attributes->pluck(null,'code'),
        'crumb'=>$product->template->main_category->product_single_crumb,
    ];

    $desc=$info['properties']->first(function ($value,$key){
        return $key=='description';
    });


    $crumb_list=[];
    foreach ($info['crumb'] as $crumb){
        $t="<a href='".route('category',$crumb->slug)."'>".$crumb->name."</a>";
        $crumb_list[]=$t;
    }

    $group=[];
    foreach($info['group_attributes'] as $item){
        $group[]=[
            'title'=>$item->title,
            'subs'=>$info['properties']->filter(function ($value,$key) use($item){
                return in_array($key,$item->attributes->pluck('code')->toArray());
            })
        ];
    };
    $info['group_attributes']=$group;


    if (isset($info['properties']) && isset($info['properties']['main_image'])){
        $main_image=$upload_url.$info['properties']['main_image']->value;
    }else{
        $main_image=$default_image;
    }
@endphp

@section('content')
    <div class="product-main-address">
        <p>
            <a href="#">دیجی کالا</a> /
            <a href="#">کالای دیجیتال</a> /
            <a href="#">لپ تاپ</a> /
            <a href="#">لپ تلپ و الترابوک</a>
        </p>
        {{--        <a href="#" class="add-p">
                    کالای خود را در دیجی کالا بفروشید
                    <i class="las la-store"></i>
                </a>--}}
    </div>
    <section class="product-info-container " id="widget1Parent">
        <div class="p-img-c-w " id="widget1">
            <div class="product-img-container side-1" id="side-1">
                <div class="product-img-wrapper ">
                    <img src="{{$main_image}}" alt="{{$info['name']}}">
                    <ul>
                        <li><a href="#" class="tooltips"><i class="far fa-heart"></i> <span>افزودن به
                                        علاقمندی</span></a></li>
                        <li><a href="#" class="tooltips"><i class="fas fa-share-alt"></i> <span>اشتراک
                                        گذاری</span></a></li>
                        <li><a href="#" class="tooltips"><i class="far fa-bell"></i> <span>اطلاع رسانی شگفت
                                        انگیز</span></a></li>
                        <li><a href="#" class="tooltips"><i class="fas fa-chart-line"></i> <span>نمودار
                                        قیمت</span></a></li>
                        <li><a href="#" class="tooltips"><i class="fas fa-columns"></i> <span>مقایسه</span></a></li>
                        <li><a href="#" class="tooltips"><i class="fas fa-list"></i> <span>لیست های عمومی</span></a>
                        </li>
                    </ul>
                </div>
                {{--
                                <div class="product-other-img">
                                    <a class="pro-o-img-wrapper" href="#">
                                        <img src="{{$url}}/img//proasus.jpg" alt="محصول">
                                    </a>
                                    <a class="pro-o-img-wrapper" href="#">
                                        <img src="{{$url}}/img//proasus.jpg" alt="محصول">
                                    </a>
                                    <a class="pro-o-img-wrapper" href="#">
                                        <img src="{{$url}}/img//proasus.jpg" alt="محصول">
                                    </a>
                                    <a class="pro-o-img-wrapper" href="#">
                                        <img src="{{$url}}/img//proasus.jpg" alt="محصول">
                                    </a>
                                    <a class="pro-o-img-wrapper" href="#">
                                        <img src="{{$url}}/img//proasus.jpg" alt="محصول">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </a>
                                </div>
                --}}
                {{--                <a href="#" class="pro-feedback"><i class="fas fa-comment-dots"></i> بازخورد درباره این کالا </a>--}}
            </div>
        </div>
        <div class="product-details-container">
            <p>
                {!! implode('/',$crumb_list) !!}
            </p>
            <h1>{{$info['name']}}</h1>
            {{--            <div class="details-title-wrapper">
                            ASUS VivoBook S533EQ - A 15 inch Laptop
                            <div class="p-d-t-border"></div>
                        </div>
                        <div class="buyers-offers">
                            <i class="las la-thumbs-up"></i>
                            ۸۸٪ (۱۰ نفر) از خریداران، این کالا را پیشنهاد کرده‌اند
                            <a class="tooltips1">
                                <i class="las la-info-circle"></i>
                                <span>خریداران کالا با انتخاب یکی از گزینه‌های پیشنهاد یا عدم پیشنهاد، تجربه خرید خود را با
                                        کاربران به اشتراک می‌گذارند.</span>
                            </a>
                        </div>
                        <div class="pro-details-rate">
                            <i class="fas fa-star pro-star"></i>
                            ۳.۷ (۱۸)
                            <i class="fas fa-circle det-dot"></i>
                            <a href="#">۴۴ دیدگاه کاربران</a>
                            <i class="fas fa-circle det-dot"></i>
                            <a href="#">۳۷ پرسش و پاسخ</a>
                        </div>--}}
            @if($info['is_variant'])
                <span class="product-color-name">رنگ : قرمز</span>
                <form class="control-group">
                    <label class="control control-radio">
                        <input type="radio" name="radio" checked="checked" />
                        <div class="control_indicator"></div>
                    </label>
                    <label class="control control-radio">
                        <input type="radio" name="radio" checked="checked" />
                        <div class="control_indicator"></div>
                    </label>
                </form>
            @endif
            <h3 class="pro-h3-info">ویژگی های کالا</h3>
            <ul class="pro-list-info" id="list-info">
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
                <li> <i class="fas fa-circle det-dot"></i> ظرفیت حافظه RAM:16 گیگابایت</li>
            </ul>
            <button id="pro-see-more-btn" onclick="seeMoreList()"> <i class="fas fa-plus"></i> مشاهده بیشتر</button>
            <button id="pro-close-more-btn" onclick="closeMoreList()"> <i class="fas fa-minus"></i>بستن</button>
            {{--            <div class="send-free-pro">
                            <div class="send-free-info">
                                <h4><i class="las la-truck"></i> ارسال رایگان سفارش</h4>
                                <p>اولین سفارش کاربران جدید</p>
                            </div>
                            <div class="send-free-img">
                                <img src="{{$url}}/img/free-car.png" alt="ارسال رایگان">
                            </div>
                        </div>
                        <div class="send-free-pro">
                            <div class="send-free-info">
                                <h4><i class="lab la-product-hunt"></i> به اعتبار دیجی پی قسطی بخرید</h4>
                                <p><span> ۲،۲۰۰،۰۰۰ </span> تومان پیش پرداخت <i class="fas fa-circle det-dot"></i> <span>
                                            ۱،۸۸۰،۵۰۰ </span> تومان ماهانه</p>
                            </div>
                            <a href="#">اطلاعات بیشتر <i class="fas fa-angle-left"></i></a>
                        </div>--}}
        </div>
        <div class="pro-left-col">
            <div class="product-seller-container">
                <h4>فروشنده</h4>
                <a href="#">۴ فروشنده‌ دیگر</a>
                <a href="#" class="pro-seller-name">
                    <div class="right">
                        <h4>پیشروتک</h4>
                        <p>عضویت از <span>۳</span> سال و <span>۶</span> ماه پیش</p>
                        <span class="feedback-par">۱۰۰٪</span>
                        <h5>رضایت خریداران از کیفیت کالا</h5>
                        <p style="width: unset;margin-top: 1.5vh;">از مجموع ۲ نفر</p>
                        <div class="feedback-green-div"></div>
                        <div class="feedback-green-info"><span>کاملا راضی</span><span>کاملا راضی</span></div>
                        <div class="right-border"></div>
                        <span style="font-size: 2rem; font-weight: 700; color: #d86b00; margin-top: 4vh;">خوب</span>
                        <h5>عملکرد کلی فروشنده</h5>
                        <div class="shop-performance">
                            <p><span>۱۰۰٪</span></br>تامین به موقع</p>
                            <p><span>۱۰۰٪</span></br>تعهد ارسال</p>
                            <p><span>۱۰۰٪</span></br>بدون ثبت مرجوعی</p>
                        </div>
                        <i></i>
                    </div>
                    <i class="las la-store"></i>
                    <div class="seller-name">
                        <h5>آرامیس</h5>
                        <span>برگزیده</span>
                        <p>عملکرد <span>عالی</span></p>
                    </div>
                    <div class="link-link"><i class="las la-arrow-left"></i></div>
                </a>
                <div class="pro-seller-b-border"></div>
                <div class="Warranty-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <p>گارانتی ۲۴ ماهه مدیا پردازش</p>
                </div>
                <div class="pro-seller-b-border"></div>
                <a href="#" class="Inventory-container">
                    <div class="pro-seller-uls-container">
                        <ul class="pro-ul-icone">
                            <li><i class="far fa-save"></i></li>
                            <li>
                                <div></div>
                            </li>
                            <li><i class="fas fa-dot-circle"></i></li>
                            <li>
                                <div></div>
                            </li>
                            <li><i class="fas fa-dot-circle"></i></li>
                        </ul>
                        <ul class="pro-ul">
                            <li>
                                <h5>موجود در انبار فروشنده</h5>
                            </li>
                            <li><i class="las la-truck"></i> ارسال توسط دیجی‌کالا از ۱ روز کاری دیگر</li>
                            <li><i class="fas fa-user-tag" style="color: #d86b00;"></i> ارسال فروشنده</li>
                        </ul>
                    </div>
                    <i class="fas fa-angle-left"></i>
                </a>
                <div class="pro-seller-b-border"></div>
                <h4 class="seller-price">قیمت فروشنده <i class="las la-question-circle"></i></h4>
                <p class="s-p-price"> <span> ۳۸,۸۰۰,۰۰۰ </span> تـومـان</p>
                <a href="#" class="add-to-cart-btn">افزودن به سبد خرید</a>
            </div>
            {{--            <a class="other-sellers" href="#">
                            <div class="o-s-first-line">
                                <h4><i class="fas fa-exclamation-circle"></i> این کالا را ارزان تر بخرید</h4>
                                <i class="fas fa-angle-left"></i>
                            </div>
                            <p>از <span>۲۶،۲۸۰،۰۰۰</span> تومان توسط فروشندگان دیگر</p>
                        </a>
                        <a href="#" class="other-sellers">
                            <div class="o-s-first-line">
                                <h4><i class="fas fa-exclamation-circle" style="color:#a1a3a8; font-size: 1rem;"></i>فرایند قیمت
                                    گذاری و نظارت بر قیمت ها</h4>
                                <i class="fas fa-angle-left"></i>
                            </div>
                        </a>
                        <a href="#" class="low-price">آیا قیمت مناسب تری سراغ دارید ؟ <i class="fas fa-tag"></i></a>--}}
        </div>
    </section>
    {{--
        <div class="pro-serv-containner ">
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
        </div>
    --}}
    {{--    <section class="sellers-list-container">
            <h3>لیست فروشندگان این کالا</h3>
            <div class="p-s-c-b-pink"></div>
            <div class="sellers-list-row">
                <div class="sellers-list-row-name">
                    <i class="las la-store"></i>
                    <div class="seller-name">
                        <h5>آرامیس</h5>
                        <span>برگزیده</span>
                        <p>عملکرد <span>عالی</span></p>
                    </div>
                </div>
                <li><i class="fas fa-user-tag" style="color: #d86b00;"></i> ارسال فروشنده</li>
                <div class="Warranty-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <p>گارانتی ۲۴ ماهه مدیا پردازش</p>
                </div>
                <p> ۲۱,۷۰۰,۰۰۰ <span> تومان </span></p>
                <a href="#">افزودن به سبد</a>
            </div>
            <div class="sellers-list-row">
                <div class="sellers-list-row-name">
                    <i class="las la-store"></i>
                    <div class="seller-name">
                        <h5>آرامیس</h5>
                        <span>برگزیده</span>
                        <p>عملکرد <span>عالی</span></p>
                    </div>
                </div>
                <li><i class="fas fa-user-tag" style="color: #d86b00;"></i> ارسال فروشنده</li>
                <div class="Warranty-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <p>گارانتی ۲۴ ماهه مدیا پردازش</p>
                </div>
                <p> ۲۱,۷۰۰,۰۰۰ <span> تومان </span></p>
                <a href="#">افزودن به سبد</a>
            </div>
            <div class="sellers-list-row">
                <div class="sellers-list-row-name">
                    <i class="las la-store"></i>
                    <div class="seller-name">
                        <h5>آرامیس</h5>
                        <span>برگزیده</span>
                        <p>عملکرد <span>عالی</span></p>
                    </div>
                </div>
                <li><i class="fas fa-user-tag" style="color: #d86b00;"></i> ارسال فروشنده</li>
                <div class="Warranty-wrapper">
                    <i class="fas fa-shield-alt"></i>
                    <p>گارانتی ۲۴ ماهه مدیا پردازش</p>
                </div>
                <p> ۲۱,۷۰۰,۰۰۰ <span> تومان </span></p>
                <a href="#">افزودن به سبد</a>
            </div>
        </section>
        <section class="product-slider-container" >
            <h3>محصولات مرتبط</h3>
            <div class="p-s-c-b-pink"></div>
            <div class="owl-carousel">
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
                <a href="#" class="item product-page-slider-product">
                    <div class="item-product-img">
                        <img src="/img/laptop.jpg" alt="محصول">
                    </div>
                    <p>لپ تاپ 15 اینچی لنوو</p>
                    <p class="p-disc"><i class="las la-dice-d20"></i> <span>  25000  </span> تومان هدیه نقدی</p>
                    <p class="p-price-p">تومان<span> 9,000,000 </span></p>
                </a>
            </div>
        </section>--}}
    <section class="product-analyze-container">
        <!-- <div class="product-analyze-detail">
            <ul class="p-a-nav" >
                <li><a href="#"> نقد و بررسی </a></li>
                <li><a href="#"> مشخصات </a></li>
                <li><a href="#"> دیدگاه کاربران </a></li>
                <li><a href="#"> پرسش و پاسخ </a></li>
            </ul>
        </div> -->
        <div class="product-analyze-detail" id="widget2Parent">
            <nav id="nav-menu" class="p-analyze-nav"  >
                <ul class="p-a-nav">
                    <li><a href="#content">نقد و بررسی</a></li>
                    <li><a href="#section-2">مشخصات</a></li>
                    {{--                    <li><a href="#section-3">دیدگاه کاربران</a></li>
                                        <li><a href="#section-4">پرسش و پاسخ</a></li>--}}
                </ul>
            </nav>

            <div id="content">
                <section id="section-1">
                    <div class="content">
                        <h3>نقد و بررسی اجمالی</h3>
                        <span>{{$info['name']}}</span>
                        <div class="a-p-b"></div>
                        {!! $desc?$desc->value:'' !!}
                    </div>
                </section>
                <section id="section-2">
                    <div class="content" id="moreDetail">
                        <button id="showMoreDetail">نمایش همه مشخصات کالا <i class="fas fa-angle-down"></i></button>
                        <button id="closeMoreDetail"> فقط نمایش مشخصات کلی کالا <i class="fas fa-angle-up"></i></button>
                        <h3>مشخصات کالا</h3>
                        <span>{{$info['name']}}</span>
                        @foreach($info['group_attributes'] as $item)
                            <div class="p-detail-wrapper-list" style="border-top: unset;">
                                <h3>{{$item['title']}}</h3>
                                <ul>
                                    @foreach($item['subs'] as $sub)
                                        <li>{{$sub->property->name}}</li>
                                    @endforeach
                                </ul>
                                <ul>
                                    @foreach($item['subs'] as $sub)
                                        <li>{{$sub->string_value}}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                        {{--                        <div class="p-detail-wrapper-list">
                                                    <h3>مشخصات فیزیکی</h3>
                                                    <ul>
                                                        <li>ابعاد</li>
                                                        <li>وزن</li>
                                                    </ul>
                                                    <ul>
                                                        <li>363x23x254 میلی‌متر</li>
                                                        <li>2.2 کیلوگرم</li>
                                                    </ul>
                                                </div>
                                                <div class="p-detail-wrapper-list">
                                                    <h3>مشخصات فیزیکی</h3>
                                                    <ul>
                                                        <li>ابعاد</li>
                                                        <li>وزن</li>
                                                    </ul>
                                                    <ul>
                                                        <li>363x23x254 میلی‌متر</li>
                                                        <li>2.2 کیلوگرم</li>
                                                    </ul>
                                                </div>
                                                <div class="p-detail-wrapper-list">
                                                    <h3>مشخصات فیزیکی</h3>
                                                    <ul>
                                                        <li>ابعاد</li>
                                                        <li>وزن</li>
                                                    </ul>
                                                    <ul>
                                                        <li>363x23x254 میلی‌متر</li>
                                                        <li>2.2 کیلوگرم</li>
                                                    </ul>
                                                </div>
                                                <div class="p-detail-wrapper-list">
                                                    <h3>مشخصات فیزیکی</h3>
                                                    <ul>
                                                        <li>ابعاد</li>
                                                        <li>وزن</li>
                                                    </ul>
                                                    <ul>
                                                        <li>363x23x254 میلی‌متر</li>
                                                        <li>2.2 کیلوگرم</li>
                                                    </ul>
                                                </div>--}}
                    </div>
                </section>
                {{--                <section id="section-3">
                                    <div class="content">
                                        <h3>امتیاز و دیدگاه کاربران</h3>
                                        <span class="spa-spa">Lenovo Ideapad L3 - 15IML05 - NPA - 15 inch Laptop</span>
                                        <div class="question-answer-container">
                                            <div class="rating-container" id="widget3">
                                                <p><span> 3.5 </span>از 5</p>
                                                <div class="rate-feed">
                                                    <div class="my-rating-7"></div>
                                                    <p>از مجموع ۱۱۷ امتیاز</p>
                                                </div>
                                                <div class="rating-snack-bar-container">
                                                    <p>ارزش خرید در برابر قیمت</p>
                                                    <div class="rating-snack-bar-wrapper">
                                                        <div class="rating-snack-bar"></div>
                                                    </div>
                                                    <span>3.5</span>
                                                </div>
                                                <div class="rating-snack-bar-container">
                                                    <p>ارزش خرید در برابر قیمت</p>
                                                    <div class="rating-snack-bar-wrapper">
                                                        <div class="rating-snack-bar"></div>
                                                    </div>
                                                    <span>3.5</span>
                                                </div>
                                                <div class="rating-snack-bar-container">
                                                    <p>ارزش خرید در برابر قیمت</p>
                                                    <div class="rating-snack-bar-wrapper">
                                                        <div class="rating-snack-bar"></div>
                                                    </div>
                                                    <span>3.5</span>
                                                </div>
                                                <p class="rating-p1">دیدگاه خود را درباره این کالا بیان کنید</p>
                                                <a href="#">افزودن دیدگاه</a>
                                                <div class="reward-digi-point-container">
                                                    <i class="fab fa-bitcoin"></i>
                                                    <div class="reward-digi-point-txt">
                                                        <p>۵ امتیاز دیجی‌کلاب</p>
                                                        <span>با بیان دیدگاه برای این کالا دریافت کنید</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="qu-an-container">
                                                <p>  <i class="fas fa-award"></i> از ۴۰ نفر از خریداران این محصول را پیشنهاد داده‌اند </p>
                                                <div class="qu-an-img-container">
                                                    <div class="qu-an-img">
                                                        <img src="{{$url}}/img/laptop.jpg" alt="">
                                                    </div>
                                                    <div class="qu-an-img">
                                                        <img src="{{$url}}/img/laptop.jpg" alt="">
                                                    </div>
                                                    <div class="qu-an-img">
                                                        <img src="{{$url}}/img/laptop.jpg" alt="">
                                                    </div>
                                                    <div class="qu-an-img">
                                                        <img src="{{$url}}/img/laptop.jpg" alt="">
                                                    </div>
                                                </div>
                                                <button class="see-more-user-img">مشاهده همه تصاویر کاربران <i class="fas fa-angle-left"></i></button>
                                                <div class="qu-an-border"></div>
                                                <div class="comments-sort-qu-an">
                                                    <p><i class="fas fa-sort-amount-down-alt"></i> مرتب سازی دیدگاه ها بر اساس:</p>
                                                    <span class="sort-span" onclick="sortClickHandler(this)">جدیدترین دیدگاه ها</span>
                                                    <span class="sort-span"  onclick="sortClickHandler(this)">مفیدترین دیدگاه ها</span>
                                                    <span class="sort-span" onclick="sortClickHandler(this)">دیدگاه خریداران</span>
                                                </div>
                                                <div class="comment-wrapper">
                                                    <h4 class="comment-title">لپ تاپ خوبیه</h4>
                                                    <p class="c-w-p-p"><span> ۲۶ شهریور ۱۳۹۹ </span><i class="fas fa-circle"></i><span> امیررضا الهی </span><span> خریدار </span></p>
                                                    <div class="com-inner-border"></div>
                                                    <p class="user-com-offer"><i class="far fa-thumbs-up"></i> خرید این محصول را توصیه میکنم </p>
                                                    <p class="user-comment-content">سلام من کلی وقت گذاشتم درایورهارو بشناسه یکی یکی تو اینتدنت گشتم دانلود کردم که با یه سی دی حل میشد با این قیمتای میلیونی یه سی دی باید روش باشه دیگه بخصوص کارت گرافیک رو نمیشناخت به هیچ عنوان تهش رفتم گارانتی گفت آخرین نسخه ویندوز ۱۰ باید نصب کنید تا بشناسه نمیدونستم همچین روال عجیبی داره ولی آخرین نسخه رو نصب کردم از نو و کار کرد! در کل سیستم خوبیه و بازی هم اجرا میکنه فوتبال و ماشین و... توصیه میکنم فقط اون نکته نسخه آخر ویندوز ۱۰ رو نوشتم به کار کسی بیاد شاید</p>
                                                    <div class="com-inner-border"></div>
                                                    <ul class="plus-list">
                                                        <li><i class="fas fa-plus-circle"></i> کیبورد جزیره ای ، سی پی یو نسل ده ، صفحه نمایش فول اچ دی ، رم هشت</li>
                                                    </ul>
                                                    <ul class="minus-list">
                                                        <li><i class="fas fa-minus-circle"></i> باطری</li>
                                                    </ul>
                                                    <div class="qu-an-img-container">
                                                        <div class="qu-an-img">
                                                            <img src="{{$url}}/img/laptop.jpg" alt="">
                                                        </div>
                                                        <div class="qu-an-img">
                                                            <img src="{{$url}}/img/laptop.jpg" alt="">
                                                        </div>
                                                        <div class="qu-an-img">
                                                            <img src="{{$url}}/img/laptop.jpg" alt="">
                                                        </div>
                                                        <div class="qu-an-img">
                                                            <img src="{{$url}}/img/laptop.jpg" alt="">
                                                        </div>
                                                    </div>
                                                    <div class="com-inner-border"></div>
                                                    <p class="comment-market-color"><i class="fas fa-circle"></i>آبی</p>
                                                    <p class="comment-market-name"><i class="fas fa-store-alt"></i> مصباح رایانه</p>
                                                    <div class="like-this-c">
                                                        <a href="#"><i class="fas fa-thumbs-down"></i></a>
                                                        <div>0</div>
                                                        <a href="#"><i class="fas fa-thumbs-up"></i></a>
                                                        <div>1</div>
                                                    </div>
                                                    <div class="qu-an-border"></div>
                                                    <div class="pagention-container">
                                                        <a href="#"><i class="fas fa-angle-double-right"></i></a>
                                                        <a href="#" class="active-page" onclick="pageHandler(this)">1</a>
                                                        <a href="#" class="unactive-page" onclick="pageHandler(this)">2</a>
                                                        <a href="#" class="unactive-page" onclick="pageHandler(this)">3</a>
                                                        <a href="#" class="unactive-page" onclick="pageHandler(this)">4</a>
                                                        <a href="#"><i class="fas fa-angle-double-left"></i></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>--}}
                {{--
                                <section id="section-4">
                                    <div class="content q-container">
                                        <h3>پرسش و پاسخ</h3>
                                        <span class="spa-sp">Lenovo Ideapad L3 - 15IML05 - NPA - 15 inch Laptop</span>
                                        <div class="ques-container">
                                            <div class="create-my-q">
                                                <div class="create-q-filter">
                                                    <label>
                                                        <input type="checkbox">
                                                        پرسش های پاسخ داده شده
                                                    </label>
                                                    <label>
                                                        <input type="checkbox">
                                                        پرسش های بی پاسخ
                                                    </label>
                                                    <div class="create-q-border"></div>
                                                    <label class="my-q-toggle">
                                                        پرسش های من
                                                        ​<label class="switch">
                                                            <input type="checkbox">
                                                            <span class="sliderr round"></span>
                                                        </label>
                                                    </label>
                                                </div>
                                                <p>پرسش خود را درباره این کالا بیان کنید</p>
                                                <a href="#">ثبت پرسش</a>
                                            </div>
                                            <div class="q-a-list-container">
                                                <div class="comments-sort-qu-an">
                                                    <p><i class="fas fa-sort-amount-down-alt"></i> مرتب سازی پرسش ها بر اساس:</p>
                                                    <span class="sort-span" onclick="sortClickHandler(this)">جدیدترین پرسش ها</span>
                                                    <span class="sort-span"  onclick="sortClickHandler(this)">مفیدترین پرسش ها</span>
                                                </div>
                                                <div class="question-wrapper">
                                                    <h5><i class="far fa-question-circle"></i> سلام . به درد بازی میخوره؟</h5>
                                                    <p id="firstAnswerP">با این قیمت قطعاً لپ تاپ های خیلی خیلی بهتری در دسترس هست این لپ تاپ در حد بازی های 5 سال پیشه</p>
                                                    <div class="ques-wrapper-footer">
                                                        <span>ناشناس</span>
                                                        <div class="like-this-c">
                                                            <a href="#"><i class="fas fa-thumbs-down"></i></a>
                                                            <div>0</div>
                                                            <a href="#"><i class="fas fa-thumbs-up"></i></a>
                                                            <div>1</div>
                                                        </div>
                                                    </div>
                                                    <p id="firstAnswerP">با این قیمت قطعاً لپ تاپ های خیلی خیلی بهتری  سال پیشه</p>
                                                    <div class="ques-wrapper-footer">
                                                        <span>ناشناس</span>
                                                        <div class="like-this-c">
                                                            <a href="#"><i class="fas fa-thumbs-down"></i></a>
                                                            <div>0</div>
                                                            <a href="#"><i class="fas fa-thumbs-up"></i></a>
                                                            <div>1</div>
                                                        </div>
                                                    </div>
                                                    <button class="see-more-answers" onclick="moreAnswerHandler(this)">مشاهده پاسخ های دیگر <i class="fas fa-angle-down"></i></button>
                                                </div>
                                                <div class="pagention-container">
                                                    <a href="#"><i class="fas fa-angle-double-right"></i></a>
                                                    <a href="#" class="active-page" onclick="pageHandler(this)">1</a>
                                                    <a href="#" class="unactive-page" onclick="pageHandler(this)">2</a>
                                                    <a href="#" class="unactive-page" onclick="pageHandler(this)">3</a>
                                                    <a href="#" class="unactive-page" onclick="pageHandler(this)">4</a>
                                                    <a href="#"><i class="fas fa-angle-double-left"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                --}}
            </div>
        </div>
        {{--
                <div class="force-sell-container">
                    <h2>فروش ویژه</h2>
                    <div class="force-sell-pro">
                        <div class="force-sell-pro-img">
                            <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                        </div>
                        <div class="force-sell-pro-txt">
                            <p>لپ تاپ 13.9 اینچی هوآوی مدل MateBook X Pro MACHC-WAE9LP - A</p>
                            <p><i class="fas fa-circle"></i> خاکستری</p>
                        </div>
                    </div>
                    <ul>
                        <li><i class="las la-store"></i>دیجی کالا</li>
                        <li><i class="fas fa-shield-alt"></i> گارانتی ۲۴ ماهه مدیا پردازش</li>
                        <li><i class="far fa-save"></i> موجود در انبار دیجی‌کالا</li>
                        <li><i class="fas fa-gift"></i> 2 هدیه</li>
                    </ul>
                    <p class="force-sell-pro-price"><span>8%</span><del>40,000,000</del></p>
                    <p class="force-sell-pro-price2"> <span> 38,000,000 </span> تومان</p>
                    <a href="#">افزودن به سبد خرید</a>
                </div>
        --}}
    </section>
@endsection
