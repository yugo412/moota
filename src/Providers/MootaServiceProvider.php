<?php

namespace Yugo\Moota\Providers;

use Illuminate\Support\ServiceProvider;
use Yugo\Moota\Libraries\Moota;

class MootaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(realpath(__DIR__ . '/../../config/moota.php'), 'moota');

        $this->loadTranslationsFrom(realpath(__DIR__ . '/../../resources/lang'), 'moota');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('moota', function () {
            return new Moota;
        });
    }
}
