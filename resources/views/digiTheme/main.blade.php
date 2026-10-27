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

    <title>تبریز قطعه</title>
</head>
<body id="body" class="{{isset($bodyClassName)?$bodyClassName:''}}">

@include('digiTheme.header')
<main class="{{isset($mainClassName)?$mainClassName:'home-main-container'}}">
    @yield('content')
    @include('digiTheme.footer')
</main>

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
