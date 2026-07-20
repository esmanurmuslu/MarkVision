@extends('layouts.app')

@section('title', 'Öğrenci Yönetimi')

@section('content')

<div class="card shadow">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h4 class="mb-0">Öğrenci Listesi</h4>

        <a href="{{ route('students.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Yeni Öğrenci
        </a>

    </div>

    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('students.index') }}" class="mb-3">

            <div class="input-group">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Öğrenci Ara..."
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

                        <th>No</th>
                        <th>Ad</th>
                        <th>Soyad</th>
                        <th>Bölüm</th>
                        <th width="180">İşlemler</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($students as $student)

                    <tr>

                        <td>{{ $student->student_no }}</td>

                        <td>{{ $student->student_name }}</td>

                        <td>{{ $student->student_surname }}</td>
                        
                        <td>{{ $student->department->department_name ?? '-' }}</td>

                        <td>

                            <a href="{{ route('students.edit', $student->student_no) }}"
                               class="btn btn-warning btn-sm">
                                Düzenle
                            </a>

                           <form action="{{ route('students.destroy', $student->student_no) }}"
      method="POST"
      class="d-inline">

    @csrf
    @method('DELETE')

    <button
        type="submit"
        class="btn btn-danger btn-sm"
        onclick="return confirm('Bu öğrenciyi silmek istediğinize emin misiniz?')">

        <i class="bi bi-trash"></i> Sil

    </button>

</form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" class="text-center">

                            Öğrenci bulunamadı.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{ $students->links() }}

    </div>

</div>

@endsection