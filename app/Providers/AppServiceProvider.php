<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\Studio;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Status commission dan kontak dipakai di header dan footer semua halaman publik.
        View::composer(['layouts.app', 'pages.*', 'orders.*'], function ($view) {
            $view->with('studioStatus', Studio::status());
            $view->with('settings', Setting::allValues());
        });
    }
}
