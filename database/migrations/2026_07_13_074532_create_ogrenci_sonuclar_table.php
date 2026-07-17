<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ogrenci_sonuclar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sinav_id')->constrained('sinavlar')->onDelete('cascade');
            $table->string('ogrenci_no', 15);
            $table->string('ogrenci_ad_soyad', 100)->nullable();
            $table->json('ogrenci_cevaplari'); 
            $table->integer('dogru_sayisi')->default(0);
            $table->integer('yanlis_sayisi')->default(0);
            $table->integer('bos_sayisi')->default(0);
            $table->decimal('toplam_puan', 5, 2)->default(0.00);
            $table->string('gorsel_yolu', 255); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ogrenci_sonuclar');
    }
};