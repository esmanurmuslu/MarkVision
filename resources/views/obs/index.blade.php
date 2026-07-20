@extends('layouts.app')

@section('title', 'OBS Öğrenci Listesi')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="text-primary">
        <i class="bi bi-people-fill"></i> OBS Öğrenci Listesi
    </h2>

    <a href="{{ route('obs.pending') }}" class="btn btn-warning">
        <i class="bi bi-exclamation-circle"></i>
        Onay Bekleyen Formlar
    </a>

</div>

<div class="card shadow">

    <div class="card-header bg-primary text-white">

        <h5 class="mb-0">
            Öğrenci Listesi
        </h5>

    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover table-bordered align-middle">

                <thead class="table-dark">

                    <tr>
                        <th width="150">Öğrenci No</th>
                        <th>Adı</th>
                        <th>Soyadı</th>
                    </tr>

                </thead>

                <tbody>

                @forelse($students as $student)

                    <tr>

                        <td>{{ $student->student_no }}</td>

                        <td>{{ $student->student_name }}</td>

                        <td>{{ $student->student_surname }}</td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="3" class="text-center text-danger">

                            Henüz öğrenci bulunmuyor.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-3">

            {{ $students->links() }}

        </div>

    </div>

</div>

@endsection