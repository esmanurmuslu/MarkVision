@extends('layouts.app')

@section('title','Öğretmen Yönetimi')

@section('content')

<div class="card shadow">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h4 class="mb-0">
            <i class="bi bi-person-badge-fill"></i>
            Öğretmen Listesi
        </h4>

        <a href="{{ route('teachers.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i>
            Yeni Öğretmen
        </a>

    </div>

    <div class="card-body">

        @if(session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif

        <form action="{{ route('teachers.index') }}" method="GET" class="mb-3">

            <div class="input-group">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="TC, Ad veya Soyad ile ara..."
                    value="{{ $search ?? '' }}">

                <button class="btn btn-primary">

                    Ara

                </button>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead class="table-dark">

                    <tr>

                        <th>TC Kimlik</th>

                        <th>Adı</th>

                        <th>Soyadı</th>

                        <th>E-Posta</th>

                        <th width="180">İşlemler</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($teachers as $teacher)

                    <tr>

                        <td>{{ $teacher->tc_no }}</td>

                        <td>{{ $teacher->name }}</td>

                        <td>{{ $teacher->surname }}</td>

                        <td>{{ $teacher->email ?? '-' }}</td>

                        <td>

                            <a href="{{ route('teachers.edit',$teacher->id) }}"
                               class="btn btn-warning btn-sm">

                                Düzenle

                            </a>

                            <form action="{{ route('teachers.destroy',$teacher->id) }}"
                                  method="POST"
                                  class="d-inline">

                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Öğretmen silinsin mi?')">

                                    Sil

                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="text-center">

                            Henüz öğretmen bulunmuyor.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{ $teachers->links() }}

    </div>

</div>

@endsection