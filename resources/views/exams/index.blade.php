@extends('layouts.app')

@section('title', 'Sınavlar')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="text-primary">
        <i class="bi bi-pencil-square"></i>
        Sınav Listesi
    </h2>

    <a href="{{ route('exams.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i>
        Yeni Sınav
    </a>

</div>

@if(session('success'))

<div class="alert alert-success">
    {{ session('success') }}
</div>

@endif

<div class="card shadow">

    <div class="card-body">

        <form action="{{ route('exams.index') }}" method="GET" class="row mb-3">

            <div class="col-md-6">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Ders adı veya sınav türü ara..."
                    value="{{ $search }}">

            </div>

            <div class="col-md-2">

                <button class="btn btn-primary w-100">

                    Ara

                </button>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead class="table-dark">

                    <tr>

                        <th>Bölüm</th>

                        <th>Öğretmen</th>

                        <th>Ders</th>

                        <th>Sınav Türü</th>

                        <th>Soru Sayısı</th>

                        <th width="180">İşlemler</th>

                    </tr>

                </thead>

                <tbody>

                @forelse($exams as $exam)

                    <tr>

                        <td>{{ $exam->department->department_name ?? '-' }}</td>

                        <td>{{ $exam->teacher->name ?? '-' }} {{ $exam->teacher->surname ?? '' }}</td>

                        <td>{{ $exam->course_name }}</td>

                        <td>{{ $exam->exam_type }}</td>

                        <td>{{ $exam->total_questions }}</td>

                        <td>

                            <a href="{{ route('exams.edit',$exam->id) }}"
                               class="btn btn-warning btn-sm">

                                Düzenle

                            </a>

                            <a href="{{ route('exams.answerkey',$exam->id) }}"
   class="btn btn-info btn-sm">

    Cevap Anahtarı

</a>

                            <form
                                action="{{ route('exams.destroy',$exam->id) }}"
                                method="POST"
                                class="d-inline">

                                @csrf
                                @method('DELETE')

                                <button
                                    onclick="return confirm('Bu sınav silinsin mi?')"
                                    class="btn btn-danger btn-sm">

                                    Sil

                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="text-center text-danger">

                            Henüz sınav bulunmuyor.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-3">

            {{ $exams->links() }}

        </div>

    </div>

</div>

@endsection