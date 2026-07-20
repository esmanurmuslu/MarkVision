@extends('layouts.app')

@section('title', 'Yeni Sınav')

@section('content')

<div class="card shadow">

    <div class="card-header bg-success text-white">
        <h4 class="mb-0">
            <i class="bi bi-plus-circle"></i>
            Yeni Sınav Ekle
        </h4>
    </div>

    <div class="card-body">

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('exams.store') }}" method="POST">

            @csrf

            <div class="mb-3">
                <label class="form-label">Bölüm</label>

                <select name="department_id" class="form-select" required>

                    <option value="">Seçiniz</option>

                    @foreach($departments as $department)

                        <option value="{{ $department->id }}">

                            {{ $department->department_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">Öğretmen</label>

                <select name="teacher_id" class="form-select" required>

                    <option value="">Seçiniz</option>

                    @foreach($teachers as $teacher)

                        <option value="{{ $teacher->id }}">

                            {{ $teacher->name }} {{ $teacher->surname }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">Ders</label>

                <select name="course_id" class="form-select" required>

                    <option value="">Seçiniz</option>

                    @foreach($courses as $course)

                        <option
                            value="{{ $course->id }}"
                            data-name="{{ $course->course_name }}">

                            {{ $course->course_code }}
                            -
                            {{ $course->course_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <input
                type="hidden"
                name="course_name"
                id="course_name">

            <div class="mb-3">

                <label class="form-label">Sınav Türü</label>

                <select name="exam_type" class="form-select">

                    <option>Vize</option>
                    <option>Final</option>
                    <option>Bütünleme</option>
                    <option>Quiz</option>

                </select>

            </div>

            <div class="mb-4">

                <label class="form-label">

                    Toplam Soru Sayısı

                </label>

                <input
                    type="number"
                    name="total_questions"
                    class="form-control"
                    required>

            </div>

            <button class="btn btn-success">

                Kaydet

            </button>

            <a href="{{ route('exams.index') }}"
               class="btn btn-secondary">

                İptal

            </a>

        </form>

    </div>

</div>

<script>

document.querySelector('[name="course_id"]').addEventListener('change', function(){

    document.getElementById('course_name').value =
        this.options[this.selectedIndex].dataset.name;

});

</script>

@endsection