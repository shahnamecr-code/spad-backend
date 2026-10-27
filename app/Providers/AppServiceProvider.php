<?php

namespace App\Providers;

use App\helper\Cart;
use App\Models\Advertisement;
use App\Observers\AdvertisementObserver;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spad\Crm\Models\CrmRequest;
use Spad\Crm\Observers\CrmRequestObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        $this->app->bind('cart', Cart::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        JsonResource::withoutWrapping();
        Paginator::defaultView('digiTheme.paginationLinks');
        Schema::defaultStringLength(191);
        Advertisement::observe(AdvertisementObserver::class);
        CrmRequest::observe(CrmRequestObserver::class);
    }
}
