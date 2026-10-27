<?php

namespace App\Providers;

use App\Shortcodes\CategoryProducts;
use App\Shortcodes\Slide;
use App\Shortcodes\Slider;
use Illuminate\Support\ServiceProvider;
use Shortcode;

class ShortcodesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Shortcode::register('CategoryProducts', CategoryProducts::class);
        Shortcode::register('Slider', Slider::class);
        Shortcode::register('Slide', Slide::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
