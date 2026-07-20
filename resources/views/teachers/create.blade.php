@extends('layouts.app')

@section('title','Yeni Öğretmen')

@section('content')

<div class="card shadow">

    <div class="card-header bg-success text-white">

        <h4 class="mb-0">

            <i class="bi bi-person-plus-fill"></i>

            Yeni Öğretmen Ekle

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

        <form action="{{ route('teachers.store') }}" method="POST">

            @csrf

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label>TC Kimlik No</label>

                    <input
                        type="text"
                        name="tc_no"
                        class="form-control"
                        maxlength="11"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label>Şifre</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label>Adı</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label>Soyadı</label>

                    <input
                        type="text"
                        name="surname"
                        class="form-control"
                        required>

                </div>

                <div class="col-md-12 mb-3">

                    <label>E-Posta</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control">

                </div>

            </div>

            <button class="btn btn-success">

                Kaydet

            </button>

            <a href="{{ route('teachers.index') }}"
               class="btn btn-secondary">

                Vazgeç

            </a>

        </form>

    </div>

</div>

@endsection