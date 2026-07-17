<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // YENİ EKLENEN SATIR (1)
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191); // YENİ EKLENEN SATIR (2)
        // Eski MySQL sürümleri için varsayılan string uzunluğunu sınırlar
        Schema::defaultStringLength(191);
    }
}