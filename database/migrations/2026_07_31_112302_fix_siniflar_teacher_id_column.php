<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Önceki migration (öğretmen_kimliğini_panel_tablolarına_ekle) Laravel'in
// migrations tablosunda "çalıştı" olarak kayıtlıydı ama 'siniflar' tablosunda
// 'teacher_id' kolonu gerçekte yoktu (muhtemelen tablo daha sonra elle
// resetlendi/silindi). Bu yeni dosya, hasColumn kontrolüyle sadece eksikse
// ekler — güvenli, tekrar çalıştırılabilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siniflar', function (Blueprint $table) {
            if (!Schema::hasColumn('siniflar', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('siniflar', function (Blueprint $table) {
            if (Schema::hasColumn('siniflar', 'teacher_id')) {
                $table->dropColumn('teacher_id');
            }
        });
    }
};