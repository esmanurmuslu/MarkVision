@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="row mb-4">

    <div class="col-md-8">

        <div class="card border-0 shadow-lg rounded-4">

            <div class="card-body p-4">

                <h2 class="fw-bold mb-2">

                    👋 Hoş Geldiniz

                </h2>

                <p class="text-secondary mb-0">

                    EduScan Optik Değerlendirme Sistemine hoş geldiniz.

                </p>

            </div>

        </div>

    </div>

    <div class="col-md-4">

        <div class="card border-0 shadow-lg rounded-4 bg-primary text-white">

            <div class="card-body text-center p-4">

                <i class="bi bi-person-circle display-3"></i>

                <h4 class="mt-3">

                    Yönetici

                </h4>

                <small>Sistem Yöneticisi</small>

            </div>

        </div>

    </div>

</div>



<div class="row g-4">

    <div class="col-lg-3 col-md-6">

        <div class="card border-0 shadow rounded-4">

            <div class="card-body text-center">

                <i class="bi bi-people-fill display-5 text-primary"></i>

                <h2 class="mt-3">

                    {{ $studentCount }}

                </h2>

                <p class="text-secondary mb-0">

                    Toplam Öğrenci

                </p>

            </div>

        </div>

    </div>



    <div class="col-lg-3 col-md-6">

        <div class="card border-0 shadow rounded-4">

            <div class="card-body text-center">

                <i class="bi bi-person-workspace display-5 text-success"></i>

                <h2 class="mt-3">

                    {{ $teacherCount }}

                </h2>

                <p class="text-secondary mb-0">

                    Öğretmen

                </p>

            </div>

        </div>

    </div>



    <div class="col-lg-3 col-md-6">

        <div class="card border-0 shadow rounded-4">

            <div class="card-body text-center">

                <i class="bi bi-pencil-square display-5 text-warning"></i>

                <h2 class="mt-3">

                    {{ $examCount }}

                </h2>

                <p class="text-secondary mb-0">

                    Sınav

                </p>

            </div>

        </div>

    </div>



    <div class="col-lg-3 col-md-6">

        <div class="card border-0 shadow rounded-4">

            <div class="card-body text-center">

                <i class="bi bi-file-earmark-check display-5 text-danger"></i>

                <h2 class="mt-3">

                    {{ $resultCount }}

                </h2>

                <p class="text-secondary mb-0">

                    Okunan Form

                </p>

            </div>

        </div>

    </div>

</div>

@endsection