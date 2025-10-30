<?php

namespace App\Providers;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        LogViewer::auth(function () {
            return auth()->check(); // Allow access only if the user is authenticated
        });

        // Ensure morph types like 'hotel_room' resolve correctly
        Relation::enforceMorphMap([
            'property' => \App\Models\Property::class,
            'hotel_room' => \App\Models\HotelRoom::class,
        ]);
    }
}
