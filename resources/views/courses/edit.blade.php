@extends('layouts.app')

@section('title', 'Ders Düzenle')

@section('content')

<div class="card shadow">

    <div class="card-header bg-warning">

        <h4 class="mb-0">
            <i class="bi bi-pencil-square"></i>
            Ders Düzenle
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

        <form action="{{ route('courses.update',$course->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">

                <label class="form-label">Bölüm</label>

                <select name="department_id" class="form-select">

                    @foreach($departments as $department)

                        <option
                            value="{{ $department->id }}"
                            {{ $course->department_id == $department->id ? 'selected' : '' }}>

                            {{ $department->department_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">Ders Kodu</label>

                <input
                    type="text"
                    name="course_code"
                    class="form-control"
                    value="{{ old('course_code',$course->course_code) }}">

            </div>

            <div class="mb-4">

                <label class="form-label">Ders Adı</label>

                <input
                    type="text"
                    name="course_name"
                    class="form-control"
                    value="{{ old('course_name',$course->course_name) }}">

            </div>

            <button class="btn btn-primary">

                Güncelle

            </button>

            <a href="{{ route('courses.index') }}"
               class="btn btn-secondary">

                İptal

            </a>

        </form>

    </div>

</div>

@endsection