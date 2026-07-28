<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // YENİ EKLENEN SATIR (1)

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
       if (request()->header('X-Forwarded-Proto') === 'https' || str_contains(request()->getHost(), 'ngrok')) {
    \Illuminate\Support\Facades\URL::forceScheme('https');
}

        // Eski MySQL sürümleri için varsayılan string uzunluğunu sınırlar
        Schema::defaultStringLength(191);
    }
}