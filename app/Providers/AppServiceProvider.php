<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // YENİ EKLENEN SATIR (1)
use Illuminate\Pagination\Paginator;

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

        // OBS modülü Bootstrap kullanıyor (bi bi-* ikonları, btn/card sınıfları),
        // Tailwind yüklü değil -- bu yüzden {{ $items->links() }} çağrılarının
        // varsayılan Tailwind görünümü yerine Bootstrap 5 görünümünü kullanmasını
        // sağlıyoruz. Aksi halde pagination ok ikonları (SVG) hiç boyutlandırılmadan,
        // dev/ham haliyle render oluyordu.
        Paginator::useBootstrapFive();
    }
}