<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            // Tabloda 'status' sütunu yoksa ekle
            if (!Schema::hasColumn('exam_results', 'status')) {
                $table->string('status')->default('completed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            // Geri alma işlemi gerekirse sütunu sil
            if (Schema::hasColumn('exam_results', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};