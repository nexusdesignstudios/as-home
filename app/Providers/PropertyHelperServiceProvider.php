<?php

namespace App\Providers;

use App\Helpers\PropertyHelper;
use Illuminate\Support\ServiceProvider;

class PropertyHelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('property-helper', function () {
            return new PropertyHelper();
        });
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
