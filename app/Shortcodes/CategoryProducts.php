<?php

namespace App\Shortcodes;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CategoryProducts
{
    public function register($shortcode, $content, $compiler, $name, $viewData)
    {
        $default_image='images/default/unknown-product.png';
        $upload_url='storage/';

        $category=Category::firstWhere('slug',$shortcode->slug);
        $item = Product::where('status', Product::APPROVED)
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

        $products=$item->limit(6)->get();
        ob_start();
        ?>
        <section class="two-cart-slider">
            <div class="multi-cart-slider-container multi-cart-slider-container3">
                <div class="multi-cart-slider-title">
                    <h3><?php echo $category->name?></h3>
                </div>
                <div class="multi-slider-border-wrapper">
                    <div class="multi-cart-p-border"></div>
                    <div class="multi-cart-t-border"></div>
                </div>

                <div class="multi-cart-slider">
                    <div class="splide splide-n6 splide-n4 splide-n700 s-he">
                        <div class="splide__track">
                            <div class="splide__list splide-n4-list">
                                <?php foreach ($products as $product_item):?>
                                <div class="splide__slide splide-n4-item">
                                    <a href="#">
                                        <div class="multi-slider-img">
                                            <img
                                                src="<?php echo isset($product_item->template->properties->pluck(null, 'property.code')->toArray()['main_image'])?$upload_url.$product_item->template->properties->pluck(null, 'property.code')->toArray()['main_image']['value']:$default_image?>"
                                                alt="<?php echo $product_item->template->title?>">
                                        </div>
                                        <div class="multi-slider-p">
                                            <p><?php echo $product_item->template->title?></p>
                                        </div>
                                        <?php if($product_item->sale_price && (is_null($product_item->sale_start) || (new \Illuminate\Support\Carbon($product_item->sale_start))->isPast()) && (is_null($this->sale_end) || (new \Illuminate\Support\Carbon($product_item->sale_end))->isFuture())){?>
                                        <div class="multi-slider-price m-s-p-2">
                                            <div class="slide-cart-off-count">۲۰٪</div>
                                            <del><?php echo number_format($product_item->sale_price)?></del>
                                            <p><span><?php echo number_format($product_item->min_price)?></span>تومان
                                            </p>
                                        </div>
                                        <?php }elseif($product_item->min_price){?>
                                        <div class="multi-slider-price">
                                            <p><span><?php echo number_format($product_item->min_price)?></span>تومان
                                            </p>
                                        </div>
                                        <?php }?>
                                    </a>
                                </div>
                                <?php endforeach;?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
//        return sprintf('<strong class="%s">%s</strong>', $shortcode->class, $content);
    }
}
