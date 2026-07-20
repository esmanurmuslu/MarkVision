@extends('layouts.app')

@section('title', 'Yeni Ders Ekle')

@section('content')

<div class="card shadow">

    <div class="card-header bg-success text-white">

        <h4 class="mb-0">

            <i class="bi bi-book-fill"></i>

            Yeni Ders Ekle

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

        <form action="{{ route('courses.store') }}" method="POST">

            @csrf

            <div class="mb-3">

                <label class="form-label">

                    Bölüm

                </label>

                <select
                    name="department_id"
                    class="form-select"
                    required>

                    <option value="">Bölüm Seçiniz</option>

                    @foreach($departments as $department)

                        <option
                            value="{{ $department->id }}"
                            {{ old('department_id')==$department->id ? 'selected':'' }}>

                            {{ $department->department_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Ders Kodu

                </label>

                <input
                    type="text"
                    name="course_code"
                    class="form-control"
                    value="{{ old('course_code') }}"
                    placeholder="Örn: BPR101"
                    required>

            </div>

            <div class="mb-4">

                <label class="form-label">

                    Ders Adı

                </label>

                <input
                    type="text"
                    name="course_name"
                    class="form-control"
                    value="{{ old('course_name') }}"
                    placeholder="Örn: Programlamaya Giriş"
                    required>

            </div>

            <button class="btn btn-success">

                <i class="bi bi-save"></i>

                Kaydet

            </button>

            <a href="{{ route('courses.index') }}"
               class="btn btn-secondary">

                İptal

            </a>

        </form>

    </div>

</div>

@endsection