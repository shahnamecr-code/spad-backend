<?php

namespace Spad\Crm;

use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider {
    public function register() {

    }

    public function boot() {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'real-estate-crm');
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        $this->mergeConfigFrom(__DIR__ . '/../config/crm.php','crm');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang','crm');
    }
}
