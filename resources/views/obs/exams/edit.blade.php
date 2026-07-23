@extends('obs.layouts.app')

@section('title', 'Sınav Düzenle')

@section('content')

<div class="card shadow">

    <div class="card-header bg-warning">
        <h4 class="mb-0">
            <i class="bi bi-pencil-square"></i>
            Sınav Düzenle
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

        <form action="{{ route('obs.exams.update',$exam->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Bölüm</label>

                <select name="department_id" class="form-select">

                    @foreach($departments as $department)
                        <option value="{{ $department->id }}"
                            {{ $exam->department_id == $department->id ? 'selected' : '' }}>
                            {{ $department->department_name }}
                        </option>
                    @endforeach

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Öğretmen</label>

                <select name="teacher_id" class="form-select">

                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}"
                            {{ $exam->teacher_id == $teacher->id ? 'selected' : '' }}>
                            {{ $teacher->name }} {{ $teacher->surname }}
                        </option>
                    @endforeach

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Ders</label>

                <select name="course_id" id="course_id" class="form-select">

                    @foreach($courses as $course)
                        <option value="{{ $course->id }}"
                                data-name="{{ $course->course_name }}"
                                {{ $exam->course_id == $course->id ? 'selected' : '' }}>
                            {{ $course->course_code }} - {{ $course->course_name }}
                        </option>
                    @endforeach

                </select>

                <input type="hidden"
                       name="course_name"
                       id="course_name"
                       value="{{ $exam->course_name }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Sınav Türü</label>

                <select name="exam_type" class="form-select">

                    <option {{ $exam->exam_type=='Vize' ? 'selected':'' }}>Vize</option>
                    <option {{ $exam->exam_type=='Final' ? 'selected':'' }}>Final</option>
                    <option {{ $exam->exam_type=='Bütünleme' ? 'selected':'' }}>Bütünleme</option>
                    <option {{ $exam->exam_type=='Quiz' ? 'selected':'' }}>Quiz</option>

                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Toplam Soru Sayısı</label>

                <input type="number"
                       name="total_questions"
                       class="form-control"
                       value="{{ $exam->total_questions }}">
            </div>

            <button class="btn btn-primary">
                Güncelle
            </button>

            <a href="{{ route('obs.exams.index') }}"
               class="btn btn-secondary">
                İptal
            </a>

        </form>

    </div>

</div>

<script>
document.getElementById('course_id').addEventListener('change', function () {
    document.getElementById('course_name').value =
        this.options[this.selectedIndex].dataset.name;
});
</script>

@endsection
