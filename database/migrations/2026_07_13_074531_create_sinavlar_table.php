<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinavlar', function (Blueprint $table) {
            $table->id();
            $table->string('sinav_adi', 150);
            $table->string('ders_kodu', 20);
            $table->json('cevap_anahtari');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinavlar');
    }
};
