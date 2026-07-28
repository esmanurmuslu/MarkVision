@extends('obs.layouts.app')

@section('title','Onay Bekleyen Formlar')

@section('content')

<div class="container">

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="text-warning">
            Onay Bekleyen Formlar
        </h2>

        <a href="{{ route('obs.dashboard') }}" class="btn btn-secondary">
            Geri Dön
        </a>

    </div>

    @if($pendingResults->isEmpty())

        <div class="alert alert-success">
            Onay bekleyen herhangi bir form bulunmuyor.
        </div>

    @else

        <div class="table-responsive">

            <table class="table table-bordered table-hover">

                <thead class="table-dark">

                    <tr>
                        <th>Öğrenci No</th>
                        <th>Öğrenci</th>
                        <th>Sınav</th>
                        <th>Puan</th>
                        <th>Durum</th>
                        <th width="220">İşlem</th>
                    </tr>

                </thead>

                <tbody>

                @foreach($pendingResults as $result)

                    <tr>

                        <td>{{ $result->student_no ?? '-' }}</td>

                        <td>
                            @if($result->student)
                                {{ $result->student->student_name }}
                                {{ $result->student->student_surname }}
                            @else
                                <span class="text-danger">
                                    Öğrenci Bulunamadı
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $result->exam->exam_type ?? '-' }}
                        </td>

                        <td>
                            {{ number_format($result->score,2) }}
                        </td>

                        <td>
                            <span class="badge bg-warning text-dark">
                                Onay Bekliyor
                            </span>
                        </td>

                        <td>

                            <div class="d-flex gap-2">

                                <a href="{{ route('obs.results.show',$result->id) }}"
                                   class="btn btn-info btn-sm">
                                    <i class="bi bi-eye"></i>
                                    İncele
                                </a>

                                <form action="{{ route('obs.results.approve',$result->id) }}"
                                      method="POST">

                                    @csrf

                                    <button type="submit"
                                            class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle"></i>
                                        Onay Ver
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

    @endif

</div>

@endsection