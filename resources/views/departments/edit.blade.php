@extends('layouts.app')

@section('title','Bölüm Düzenle')

@section('content')

<div class="card shadow">

    <div class="card-header bg-warning text-dark">

        <h4 class="mb-0">

            <i class="bi bi-pencil-square"></i>

            Bölüm Düzenle

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

        <form action="{{ route('departments.update',$department->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="mb-3">

                <label>Fakülte</label>

                <select name="faculty_id" class="form-select">

                    @foreach($faculties as $faculty)

                        <option
                            value="{{ $faculty->id }}"
                            {{ $department->faculty_id == $faculty->id ? 'selected' : '' }}>

                            {{ $faculty->faculty_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="mb-3">

                <label>Bölüm Adı</label>

                <input
                    type="text"
                    name="department_name"
                    class="form-control"
                    value="{{ old('department_name',$department->department_name) }}">

            </div>

            <div class="mb-4">

                <label>Eğitim Türü</label>

                <select name="degree_type" class="form-select">

                    <option
                        value="Önlisans"
                        {{ $department->degree_type=='Önlisans' ? 'selected':'' }}>

                        Önlisans

                    </option>

                    <option
                        value="Lisans"
                        {{ $department->degree_type=='Lisans' ? 'selected':'' }}>

                        Lisans

                    </option>

                </select>

            </div>

            <button class="btn btn-primary">

                Güncelle

            </button>

            <a href="{{ route('departments.index') }}"
               class="btn btn-secondary">

                Vazgeç

            </a>

        </form>

    </div>

</div>

@endsection