@extends('layouts.app')

@section('title','Bölüm Yönetimi')

@section('content')

<div class="card shadow">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h4 class="mb-0">
            <i class="bi bi-building"></i>
            Bölüm Listesi
        </h4>

        <a href="{{ route('departments.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i>
            Yeni Bölüm
        </a>

    </div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('departments.index') }}" method="GET" class="mb-3">

            <div class="input-group">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Bölüm ara..."
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

                        <th>Fakülte</th>

                        <th>Bölüm</th>

                        <th>Eğitim Türü</th>

                        <th width="180">İşlemler</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($departments as $department)

                    <tr>

                        <td>
                            {{ $department->faculty->faculty_name ?? '-' }}
                        </td>

                        <td>
                            {{ $department->department_name }}
                        </td>

                        <td>

                            <span class="badge bg-info">

                                {{ $department->degree_type }}

                            </span>

                        </td>

                        <td>

                            <a href="{{ route('departments.edit',$department->id) }}"
                               class="btn btn-warning btn-sm">

                                Düzenle

                            </a>

                            <form action="{{ route('departments.destroy',$department->id) }}"
                                  method="POST"
                                  class="d-inline">

                                @csrf
                                @method('DELETE')

                                <button
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bölüm silinsin mi?')">

                                    Sil

                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" class="text-center">

                            Kayıtlı bölüm bulunamadı.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{ $departments->links() }}

    </div>

</div>

@endsection