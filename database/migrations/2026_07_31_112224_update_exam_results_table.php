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
        $table->json('student_answers')->nullable()->after('student_no');
        $table->integer('correct_count')->default(0)->after('student_answers');
        $table->integer('wrong_count')->default(0)->after('correct_count');
        $table->integer('blank_count')->default(0)->after('wrong_count');
        $table->string('status')->default('success')->after('score');
    });
}

public function down(): void
{
    Schema::table('exam_results', function (Blueprint $table) {
        $table->dropColumn([
            'student_answers',
            'correct_count',
            'wrong_count',
            'blank_count',
            'status'
        ]);
    });
}
};
