@extends('layouts.app')

@section('title', 'Yeni Öğrenci')

@section('content')

<div class="container-fluid">

    <div class="card shadow">

        <div class="card-header bg-success text-white">

            <h4 class="mb-0">
                <i class="bi bi-person-plus-fill"></i>
                Yeni Öğrenci Ekle
            </h4>

        </div>

        <div class="card-body">

            @if ($errors->any())

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        @foreach($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif

            <form action="{{ route('students.store') }}" method="POST">

                @csrf

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label>Öğrenci No</label>

                        <input
                            type="text"
                            name="student_no"
                            class="form-control"
                            value="{{ old('student_no') }}"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>Adı</label>

                        <input
                            type="text"
                            name="student_name"
                            class="form-control"
                            value="{{ old('student_name') }}"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>Soyadı</label>

                        <input
                            type="text"
                            name="student_surname"
                            class="form-control"
                            value="{{ old('student_surname') }}"
                            required>

                    </div>

<div class="col-md-6 mb-3">

    <label class="form-label">Bölüm</label>

    <select name="department_id" class="form-select" required>

        <option value="">Bölüm Seçiniz</option>

        @foreach($departments as $department)

            <option value="{{ $department->id }}">

                {{ $department->department_name }}

                ({{ $department->degree_type }})

            </option>

        @endforeach

    </select>

</div>
                </div>

                <button class="btn btn-success">

                    <i class="bi bi-check-circle"></i>

                    Kaydet

                </button>

                <a href="{{ route('students.index') }}"
                   class="btn btn-secondary">

                    Vazgeç

                </a>

            </form>

        </div>

    </div>

</div>

@endsection