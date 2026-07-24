@extends('obs.layouts.app')

@section('title', 'Yeni Bölüm Ekle')

@section('content')

<div class="container-fluid">

    <div class="card shadow">

        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                <i class="bi bi-building-add"></i>
                Yeni Bölüm Ekle
            </h4>

            <a href="{{ route('obs.departments.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left"></i>
                Geri Dön
            </a>

        </div>

        <div class="card-body">

            @if ($errors->any())

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        @foreach ($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif

            <form action="{{ route('obs.departments.store') }}" method="POST">

                @csrf

                <div class="mb-3">

                    <label class="form-label">
                        Fakülte
                    </label>

                    <select name="faculty_id" class="form-select" required>

                        <option value="">Fakülte Seçiniz</option>

                        @foreach($faculties as $faculty)

                            <option
                                value="{{ $faculty->id }}"
                                {{ old('faculty_id') == $faculty->id ? 'selected' : '' }}>

                                {{ $faculty->faculty_name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Bölüm Adı

                    </label>

                    <input
                        type="text"
                        name="department_name"
                        class="form-control"
                        value="{{ old('department_name') }}"
                        placeholder="Örn: Bilgisayar Programcılığı"
                        required>

                </div>

                <div class="mb-4">

                    <label class="form-label">

                        Eğitim Türü

                    </label>

                    <select name="degree_type" class="form-select" required>

                        <option value="">Seçiniz</option>

                        <option value="Önlisans"
                            {{ old('degree_type') == 'Önlisans' ? 'selected' : '' }}>
                            Önlisans
                        </option>

                        <option value="Lisans"
                            {{ old('degree_type') == 'Lisans' ? 'selected' : '' }}>
                            Lisans
                        </option>

                    </select>

                </div>

                <button type="submit" class="btn btn-success">

                    <i class="bi bi-save"></i>

                    Kaydet

                </button>

                <a href="{{ route('obs.departments.index') }}" class="btn btn-secondary">

                    Vazgeç

                </a>

            </form>

        </div>

    </div>

</div>

@endsection
