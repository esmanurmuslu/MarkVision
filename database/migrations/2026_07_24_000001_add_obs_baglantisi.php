<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sinavlar', function (Blueprint $table) {
            $table->unsignedBigInteger('obs_exam_id')->nullable()->after('ders_kodu');
        });

        Schema::table('ogrenci_sonuclar', function (Blueprint $table) {
            $table->unsignedBigInteger('obs_exam_result_id')->nullable()->after('gorsel_yolu');
            $table->boolean('obs_kayit_edildi')->default(false)->after('obs_exam_result_id');
        });
    }

    public function down(): void
    {
        Schema::table('sinavlar', function (Blueprint $table) {
            $table->dropColumn('obs_exam_id');
        });

        Schema::table('ogrenci_sonuclar', function (Blueprint $table) {
            $table->dropColumn(['obs_exam_result_id', 'obs_kayit_edildi']);
        });
    }
};