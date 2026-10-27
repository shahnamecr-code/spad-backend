<?php

namespace App\Providers;

use App\Events\UserRegisterEvent;
use App\Listeners\UserRegisterListener;
use App\Models\Advertisement;
use App\Models\AdvertisementProperty;
use App\Observers\AdvertisementObserver;
use App\Observers\AdvertisementPropertyObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Spad\Crm\Models\CrmRequest;
use Spad\Crm\Observers\CrmRequestObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserRegisterEvent::class => [
            UserRegisterListener::class
        ]
    ];

/*    protected $observers = [
        Advertisement::class => [AdvertisementObserver::class],
        CrmRequest::class => [CrmRequestObserver::class]
    ];*/

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
//        Advertisement::observe(AdvertisementObserver::class);
//        CrmRequest::observe(CrmRequestObserver::class);
    }
}
