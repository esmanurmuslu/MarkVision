@extends('obs.layouts.app')

@section('title', 'Dersler')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="text-primary">
        <i class="bi bi-book-fill"></i>
        Ders Listesi
    </h2>

    <a href="{{ route('obs.courses.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i>
        Yeni Ders
    </a>

</div>

@if(session('success'))

<div class="alert alert-success">

    {{ session('success') }}

</div>

@endif

<div class="card shadow">

    <div class="card-body">

        <form method="GET" action="{{ route('obs.courses.index') }}" class="row mb-3">

            <div class="col-md-6">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Ders kodu veya ders adı ara..."
                    value="{{ $search }}">

            </div>

            <div class="col-md-2">

                <button class="btn btn-primary w-100">

                    Ara

                </button>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-hover table-bordered">

                <thead class="table-dark">

                    <tr>

                        <th>Ders Kodu</th>

                        <th>Ders Adı</th>

                        <th>Bölüm</th>

                        <th width="180">İşlemler</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($courses as $course)

                    <tr>

                        <td>{{ $course->course_code }}</td>

                        <td>{{ $course->course_name }}</td>

                        <td>{{ $course->department->department_name ?? '-' }}</td>

                        <td>

                            <a href="{{ route('obs.courses.edit',$course->id) }}"
                               class="btn btn-warning btn-sm">

                                Düzenle

                            </a>

                            <form action="{{ route('obs.courses.destroy',$course->id) }}"
                                  method="POST"
                                  class="d-inline">

                                @csrf
                                @method('DELETE')

                                <button
                                    onclick="return confirm('Bu dersi silmek istiyor musunuz?')"
                                    class="btn btn-danger btn-sm">

                                    Sil

                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" class="text-center text-danger">

                            Henüz ders bulunmuyor.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-3">

            {{ $courses->links() }}

        </div>

    </div>

</div>

@endsection
