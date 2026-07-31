<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Aynı 'siniflar' sorununun bir eşi: eski migration (öğretmen_kimliğini
// panel_tablolarına_ekle) Laravel'in migrations tablosunda "çalıştı" diye
// kayıtlı ama 'sinavlar' tablosunda 'teacher_id' kolonu gerçekte yok.
// Bu yüzden Sınavlar (Quizzes) sekmesi 500 hatası veriyordu. hasColumn
// kontrolüyle sadece eksikse ekliyoruz — güvenli, tekrar çalıştırılabilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sinavlar', function (Blueprint $table) {
            if (!Schema::hasColumn('sinavlar', 'teacher_id')) {
                $table->unsignedBigInteger('teacher_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sinavlar', function (Blueprint $table) {
            if (Schema::hasColumn('sinavlar', 'teacher_id')) {
                $table->dropColumn('teacher_id');
            }
        });
    }
};