@extends('layouts.app')

@section('title','Öğrenci Düzenle')

@section('content')

<div class="card shadow">

    <div class="card-header bg-warning">

        <h4>Öğrenci Düzenle</h4>

    </div>

    <div class="card-body">

        @if($errors->any())

            <div class="alert alert-danger">

                <ul>

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <form action="{{ route('students.update',$student->student_no) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">

                <label>Öğrenci No</label>

                <input
                    type="text"
                    class="form-control"
                    value="{{ $student->student_no }}"
                    disabled>

            </div>

            <div class="mb-3">

                <label>Adı</label>

                <input
                    type="text"
                    name="student_name"
                    class="form-control"
                    value="{{ old('student_name',$student->student_name) }}">

            </div>

            <div class="mb-3">

                <label>Soyadı</label>

                <input
                    type="text"
                    name="student_surname"
                    class="form-control"
                    value="{{ old('student_surname',$student->student_surname) }}">

            </div>

            <button class="btn btn-primary">

                Güncelle

            </button>

            <a href="{{ route('students.index') }}" class="btn btn-secondary">

                Geri

            </a>

        </form>

    </div>

</div>

@endsection