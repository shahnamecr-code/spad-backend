@php
    $url=asset('digiTheme');
    $home_setting=App\Models\Setting::where('group','home_page')->whereIn('code',array_column(\App\Models\Setting::$settings['home_page']['subs'],'code'))->get()->pluck('value','code');
    $main_settings=\App\Models\Setting::whereIn('code',['general_logo','general_copyright','general_phone','general_site_description','general_site_name','app_categories'])->get()->pluck('value','code');

    $categories=\App\Models\Category::select(['name','slug'])->get();
    $upload_url='/storage/';

@endphp
<header class="header-container" id="header">
    <section class="top-header-container">
        <div class="logo-search-container">
            @if(isset($main_settings['general_logo']))
                <h1>
                    <a href="{{url('')}}">
                        <img src="{{$upload_url.$main_settings['general_logo']}}" alt="{{isset($main_settings['general_site_name'])?$main_settings['general_site_name']:''}}">
                    </a>
                </h1>
            @endif
            <i class="fas fa-search"></i>
            <input type="search" placeholder="جستجو در محصولات ...">
        </div>
        <div class="sign-cart-container">
            <a href="#" class="sign-in-btn"> <i class="las la-user"></i>  ورود به حساب کاربری </a>
            <div class="border-solid"></div>
            <a href="#"><i class="las la-shopping-cart cart-icon"></i></a>
        </div>
    </section>
    <section class="bottom-header-container" id="navbar">
        <nav class="nav-bar-container">
            <ul>
                <li id="daste-bandy" href="#">
                    <a>
                        <i class="las la-align-justify"></i>
                        دسته بندی ها
                    </a>
                    <ul class="under-menu" id="under-menu" >
                        @foreach($categories as $category)
                            <a href="{{route('category',$category->slug)}}">{{$category->name}}</a>
                        @endforeach
                    </ul>
                </li>
                <li><a href="{{route('about')}}">درباره ما</a></li>
                <li><a href="{{route('contact')}}">تماس با ما</a></li>

            </ul>
        </nav>
        {{--        <button class="select-loc-btn">لطفا شهر و استان خود را انتخاب کنید <i class="las la-map-marker-alt"></i></button>--}}
    </section>
</header>
