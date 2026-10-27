@extends('digiTheme.main')

@php
    $url=asset('digiTheme');
    $home_setting=App\Models\Setting::where('group','home_page')->whereIn('code',array_column(\App\Models\Setting::$settings['home_page']['subs'],'code'))->get()->pluck('value','code');
    $main_settings=\App\Models\Setting::whereIn('code',['general_logo','general_copyright','general_phone','general_site_description','general_site_name','app_categories'])->get()->pluck('value','code');
    $products=[];
    $default_image='images/default/unknown-product.png';
    foreach ($main_settings['app_categories'] as $category){
        $item = \App\Models\Product::where('status', \App\Models\Product::APPROVED)
            ->whereHas('template', function ($q) use ($category) {
                return $q->where('mainCat', $category->id)->orWhereHas('categories', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                });
            })
            ->with(['template', 'template.main_category', 'template.properties' => function ($q) {
                $q->whereHas('property', function ($q) {
                    $q->whereIn('code', ['main_image']);
                });
            }])
            ->leftJoin(DB::raw('(SELECT product_id,sale_price,sale_start,sale_end,MIN(price) as min_price,MAX(price) as max_price,SUM(quantity) as count FROM (SELECT * FROM `product_sale_infos` as psi WHERE psi.quantity > 0 GROUP BY psi.product_variant_id ORDER BY psi.created_at) as p GROUP BY p.product_id ORDER BY id) as prices'),'prices.product_id','products.id');

        $products[]=[
            'category'=>$category,
            'products'=>$item->limit(6)->get(),
        ];

    }

    $upload_url='storage/';
@endphp

@section('content')
    <section class="top-banners-container">
        <a href="#" class="top-banner"><img src="{{$url}}/img/6d2ddb4fe71cb9764834b20bc25fc4bac92abce2_1628332407.jpg" alt=""></a>
        <div class="splide slide-n1">
            <div class="splide__track">
                @if(isset($home_setting['main_slider']))
                    <ul class="splide__list">
                        @foreach($home_setting['main_slider'] as $slide)
                            <li class="splide__slide">
                                <a href="{{isset($slide['link'])?$slide['link']:'#'}}">
                                    @if(isset($slide['image']))
                                        <img src="{{$upload_url.$slide['image']}}" alt="">
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
        <div class="b-1">
            @if(isset($home_setting['slider_side_box']))
                @foreach($home_setting['slider_side_box'] as $item)
                    <a href="{{isset($item['link'])?$item['link']:'#'}}" class="b-12" style="margin-bottom: 1vh;">
                        <img src="{{$upload_url.$item['image']}}">
                    </a>
                @endforeach
            @endif
        </div>
    </section>
    {{--    <section class="cart-slider-container">
            <div class="cart-slider-title">
                <img src="{{$url}}/img/cart-title.png" alt="" alt="شگفت انگیز">
                <a href="#">مشاهده همه<i class="fas fa-angle-left"></i></a>
            </div>
            <div class="splide splide-n2">
                <div class="splide__track splide-n2-track">
                    <div class="splide__list splide-n2-list">
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#" class="slide-cart-see-more">
                                <i class="las la-arrow-left"></i>
                                <p>مشاهده همه</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="small-banner-container">
            <a href="#"><img src="{{$url}}/img/b-glass.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/b-h.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/b-shirt.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/b-wash.jpg" alt="بنر"></a>
        </section>
        <section class="cart-slider-container2">
            <div class="cart-slider-title">
                <img src="{{$url}}/img/cart-title.png" alt="" alt="شگفت انگیز">
                <a href="#">مشاهده همه<i class="fas fa-angle-left"></i></a>
            </div>
            <div class="splide splide-n3">
                <div class="splide__track splide-n2-track">
                    <div class="splide__list splide-n2-list">
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-sold-slider">
                                    <div class="sold-slider"></div>
                                </div>
                                <div class="slide-cart-footer">
                                    <p><span>۴۸٪</span>فروش رفته</p>
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#">
                                <div class="slide-cart-img">
                                    <img src="{{$url}}/img/laptop.jpg" alt="محصول">
                                </div>
                                <div class="slide-cart-p">
                                    <p>لپ تاپ 15.6 اینچی اچ پی مدل ProBook 455 G7-D</p>
                                </div>
                                <div class="slide-cart-price">
                                    <div class="slide-cart-off">
                                        <div class="slide-cart-off-count">۲۰٪</div>
                                        <del>۱۷،۵۰۰،۰۰۰</del>
                                        <p><span>۱۳،۹۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </div>
                                <div class="slide-cart-footer">
                                    <div class="slide-cart-timer">10:20:20 <i class="las la-clock"></i></div>
                                </div>
                            </a>
                        </div>
                        <div class="splide__slide splid-n2-item">
                            <a href="#" class="slide-cart-see-more">
                                <i class="las la-arrow-left"></i>
                                <p>مشاهده همه</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="medium-banner-container">
            <a href="#"><img src="{{$url}}/img/arian.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/homeplus.jpg" alt="بنر"></a>
        </section>
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n4">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
                                            <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="single-cart-auto-slider">
                <p>پیشنهادهای لحظه ای برای شما</p>
                <div class="splide splide-n5">
                    <div class="splide__progress">
                        <div class="splide__progress__bar">
                        </div>
                    </div>
                    <div class="splide__track">
                        <div class="splide__list">
                            <div class="splide__slide splide-n5-item">
                                <a href="#">
                                    <div class="multi-slider-img single-slider-img">
                                        <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                    </div>
                                    <div class="multi-slider-p single-slider-p">
                                        <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                    </div>
                                    <div class="multi-slider-price">
                                        <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </a>
                            </div>
                            <div class="splide__slide splide-n5-item">
                                <a href="#">
                                    <div class="multi-slider-img single-slider-img">
                                        <img src="{{$url}}/img/mo-mi.jpg" alt="محصول">
                                    </div>
                                    <div class="multi-slider-p single-slider-p">
                                        <p>گوشی موبایل شیایومی مدل POCO M3</p>
                                    </div>
                                    <div class="multi-slider-price">
                                        <p><span>۴،۱۹۹،۰۰۰</span>تومان</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="cate-count-container">
            <h3>بیش از ۴،۰۰۰،۰۰۰ کالا در دسته‌بندی‌های مختلف</h3>
            <div class="cate-count-link-container">
                <a href="#">
                    <i class="las la-laptop"></i>
                    <h4>کالای دیجیتال</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-wrench"></i>
                    <h4>خودرو ، ابزار و تجهیزات صنعتی</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-tshirt"></i>
                    <h4>مد و پوشاک</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-baby-carriage"></i>
                    <h4>اسباب بازی ، کودک و نوزاد</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-shopping-basket"></i>
                    <h4>کالا‌های سوپرمارکتی</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-heartbeat"></i>
                    <h4>زیبایی و سلامت</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-couch"></i>
                    <h4>خانه و آشپزخانه</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-book"></i>
                    <h4>کتاب و لوازم تحریر</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-futbol"></i>
                    <h4>ورزش و سفر</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
                <a href="#">
                    <i class="las la-spa"></i>
                    <h4>محصولات بومی و محلی</h4>
                    <span>+ ۹۸۰۰۰ کالا</span>
                </a>
            </div>
        </section>
        <section class="two-cart-slider">
            <div class="custum-offer-container">
                <i class="las la-award"></i>
                <h3>شخصی‌سازی پیشنهادها!</h3>
                <p>برای مشاهده پیشنهادهای مناسب خود و همچنین داشتن تجربه بهتر لطفا وارد حساب کاربری شوید.</p>
                <a href="#"><i class="las la-sign-in-alt"></i> ورود به حساب دیجی کالا</a>
                <span>ثبت نام رایگان در صورت نداشتن حساب کاربری</span>
            </div>
            <div class="multi-cart-slider-container multi-cart-slider-container2">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
        <section class="mini-banner-container">
            <a href="#"><img src="{{$url}}/img/qarch.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/beSeller.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/99report.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/postSeller.jpg" alt="بنر"></a>
        </section>--}}
    @if(isset($products))
        @foreach($products as $product)
            @if(isset($product['products']) && count($product['products'])>0)
                <section class="two-cart-slider">
                    <div class="multi-cart-slider-container multi-cart-slider-container3">
                        <div class="multi-cart-slider-title">
                            <h3>{{$product['category']->name}}</h3>
                        </div>
                        <div class="multi-slider-border-wrapper">
                            <div class="multi-cart-p-border"></div>
                            <div class="multi-cart-t-border"></div>
                        </div>

                        <div class="multi-cart-slider">
                            <div class="splide splide-n6 splide-n4 splide-n700 s-he">
                                <div class="splide__track">
                                    <div class="splide__list splide-n4-list">
                                        @foreach($product['products'] as $product_item)
                                            <div class="splide__slide splide-n4-item">
                                                <a href="{{route('single',$product_item->id)}}">
                                                    <div class="multi-slider-img">
                                                        <img src="{{isset($product_item->template->properties->pluck(null, 'property.code')->toArray()['main_image'])?$upload_url.$product_item->template->properties->pluck(null, 'property.code')->toArray()['main_image']['value']:$default_image}}" alt="{{$product_item->template->title}}">
                                                    </div>
                                                    <div class="multi-slider-p">
                                                        <p>{{$product_item->template->title}}</p>
                                                    </div>
                                                    @if($product_item->sale_price && (is_null($product_item->sale_start) || (new \Illuminate\Support\Carbon($product_item->sale_start))->isPast()) && (is_null($this->sale_end) || (new \Illuminate\Support\Carbon($product_item->sale_end))->isFuture()))
                                                        <div class="multi-slider-price m-s-p-2">
                                                            <div class="slide-cart-off-count">۲۰٪</div>
                                                            <del>{{number_format($product_item->sale_price)}}</del>
                                                            <p><span>{{number_format($product_item->min_price)}}</span>تومان</p>
                                                        </div>
                                                    @elseif($product_item->min_price)
                                                        <div class="multi-slider-price">
                                                            <p><span>{{number_format($product_item->min_price)}}</span>تومان</p>
                                                        </div>
                                                    @endif
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        @endforeach
    @endif
    {{--    <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوش111111111111ی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n4 splide-n700 s-he" style="width: 100% !important">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n4 splide-n700 s-he">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
                                        <div class="multi-slider-price">
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
        <section class="medium-banner-container medium-banner-container2">
            <a href="#"><img src="{{$url}}/img/arian.jpg" alt="بنر"></a>
            <a href="#"><img src="{{$url}}/img/homeplus.jpg" alt="بنر"></a>
        </section>
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n9 s-he">
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
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n10 s-he">
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
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n11 s-he">
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
        </section>--}}
    {{--    <section class="medium-banner-container medium-banner-container3">
            <a href="#"><img src="{{$url}}/img/bbbbbb.jpg" alt="بنر"></a>
        </section>--}}
    {{--    <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n12 s-he">
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
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n13 s-he">
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
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>
                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n14 b-he">
                        <div class="splide__track">
                            <div class="splide__list splide-n4-list">
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/adata.png" alt="">
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/snowa.png" alt="">
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/adata.png" alt="">
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/snowa.png" alt="">
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/adata.png" alt="">
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/snowa.png" alt="">
                                    </a>
                                </div>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <img src="{{$url}}/img/adata.png" alt="">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3>گوشی موبایل</h3>
                    <p>بر اساس بازدید های شما</p>
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
        </section>--}}
@endsection
