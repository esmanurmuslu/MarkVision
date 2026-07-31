<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 'students' tablosu OBS modülünün gerçek öğrenci kayıtlarına ait
// (student_name, student_surname, department_id kolonları). Panel/mobil
// tarafı yanlışlıkla aynı tabloya (name, student_no, class_id, teacher_id
// bekleyerek) yazmaya çalışıyordu — bu da OBS verisini bozma riski
// taşıyordu ve "Unknown column" hatasına sebep oluyordu. Panelin kendi
// öğrencileri için ayrı bir tablo açıyoruz.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panel_students', function (Blueprint $table) {
            $table->id();
            $table->string('student_no', 50);
            $table->string('name', 150);
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_students');
    }
};