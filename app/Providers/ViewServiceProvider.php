<?php

namespace App\Providers;

use App\Services\CachingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        $cache = app(CachingService::class);

        /*** Main Blade File ***/
        View::composer('layouts.main', static function (\Illuminate\View\View $view) use ($cache) {
            $lang = Session::get('language');
            if($lang){
                $view->with('language', $lang);
            }else{
                $cache = app(CachingService::class);
                $defaultLanguage = $cache->getDefaultLanguage();
                if($defaultLanguage && isset($defaultLanguage->code)){
                    Session::put('language', $defaultLanguage);
                    Session::put('locale', $defaultLanguage->code);
                    Session::save();
                    app()->setLocale($defaultLanguage->code);
                    Artisan::call('cache:clear');
                    $view->with('language', $defaultLanguage);
                } else {
                    // Fallback to default locale if no language is found
                    $defaultLocale = config('app.locale', 'en');
                    Session::put('locale', $defaultLocale);
                    app()->setLocale($defaultLocale);
                    // Pass null to the view so it can handle it gracefully
                    $view->with('language', null);
                }
            }
        });

        View::composer('auth.login', static function (\Illuminate\View\View $view) use ($cache) {
            $cache = app(CachingService::class);
            $defaultLanguage = $cache->getDefaultLanguage();
            if($defaultLanguage && isset($defaultLanguage->code)){
                Session::put('language', $defaultLanguage);
                Session::put('locale', $defaultLanguage->code);
                Session::save();
                app()->setLocale($defaultLanguage->code);
                Artisan::call('cache:clear');
                $view->with('language', $defaultLanguage);
            } else {
                // Fallback to default locale if no language is found
                $defaultLocale = config('app.locale', 'en');
                Session::put('locale', $defaultLocale);
                app()->setLocale($defaultLocale);
                // Pass null to the view so it can handle it gracefully
                $view->with('language', null);
            }
        });

        View::composer('customers.reset-password', static function (\Illuminate\View\View $view) use ($cache) {
            $cache = app(CachingService::class);
            $defaultLanguage = $cache->getDefaultLanguage();
            if($defaultLanguage && isset($defaultLanguage->code)){
                Session::put('language', $defaultLanguage);
                Session::put('locale', $defaultLanguage->code);
                Session::save();
                app()->setLocale($defaultLanguage->code);
                Artisan::call('cache:clear');
                $view->with('language', $defaultLanguage);
            } else {
                // Fallback to default locale if no language is found
                $defaultLocale = config('app.locale', 'en');
                Session::put('locale', $defaultLocale);
                app()->setLocale($defaultLocale);
                // Pass null to the view so it can handle it gracefully
                $view->with('language', null);
            }
        });
    }
}
