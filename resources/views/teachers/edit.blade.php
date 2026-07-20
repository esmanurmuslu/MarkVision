@extends('layouts.app')

@section('title','Öğretmen Düzenle')

@section('content')

<div class="card shadow">

    <div class="card-header bg-warning">

        <h4 class="mb-0">

            <i class="bi bi-pencil-square"></i>

            Öğretmen Düzenle

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

        <form action="{{ route('teachers.update',$teacher->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label>TC Kimlik No</label>

                    <input
                        type="text"
                        name="tc_no"
                        class="form-control"
                        value="{{ old('tc_no',$teacher->tc_no) }}"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label>E-Posta</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email',$teacher->email) }}">

                </div>

                <div class="col-md-6 mb-3">

                    <label>Adı</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name',$teacher->name) }}"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label>Soyadı</label>

                    <input
                        type="text"
                        name="surname"
                        class="form-control"
                        value="{{ old('surname',$teacher->surname) }}"
                        required>

                </div>

            </div>

            <button class="btn btn-primary">

                Güncelle

            </button>

            <a href="{{ route('teachers.index') }}"
               class="btn btn-secondary">

                Geri

            </a>

        </form>

    </div>

</div>

@endsection