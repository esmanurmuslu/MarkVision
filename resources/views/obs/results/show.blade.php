@extends('obs.layouts.app')

@section('title', 'Sonuç Detayı')

@section('content')

<div class="container-fluid">

    <div class="card shadow">

        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

            <h4 class="mb-0">
                <i class="bi bi-clipboard-data"></i>
                Optik Okuma Sonucu
            </h4>

            <a href="{{ route('obs.results.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i>
                Geri Dön
            </a>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">

                    <table class="table table-bordered">

                        <tr>
                            <th width="180">Öğrenci No</th>
                            <td>{{ $result->student_no ?? '-' }}</td>
                        </tr>

                        <tr>
                            <th>Ad Soyad</th>
                            <td>
                                {{ $result->student->student_name ?? '-' }}
                                {{ $result->student->student_surname ?? '' }}
                            </td>
                        </tr>

                        <tr>
                            <th>Ders</th>
                            <td>{{ $result->exam->course_name ?? '-' }}</td>
                        </tr>

                        <tr>
                            <th>Sınav Türü</th>
                            <td>{{ $result->exam->exam_type ?? '-' }}</td>
                        </tr>

                    </table>

                </div>

                <div class="col-md-6">

                    <table class="table table-bordered">

                        <tr>
                            <th width="180">Doğru</th>
                            <td class="text-success fw-bold">
                                {{ $result->correct_count }}
                            </td>
                        </tr>

                        <tr>
                            <th>Yanlış</th>
                            <td class="text-danger fw-bold">
                                {{ $result->wrong_count }}
                            </td>
                        </tr>

                        <tr>
                            <th>Boş</th>
                            <td>
                                {{ $result->blank_count }}
                            </td>
                        </tr>

                        <tr>
                            <th>Puan</th>
                            <td>

                                <span class="badge bg-primary fs-5">

                                    {{ number_format($result->score,2) }}

                                </span>

                            </td>
                        </tr>

                    </table>

                </div>

            </div>

            <hr>

            <h5 class="mb-3">

                <i class="bi bi-list-check"></i>

                Öğrenci Cevapları

            </h5>

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th width="120">Soru</th>

                            <th>Öğrenci Cevabı</th>

                            <th>Doğru Cevap</th>

                            <th>Durum</th>

                        </tr>

                    </thead>

                    <tbody>

                    @foreach(($result->student_answers ?? []) as $question => $answer)

                        @php

                            $correct = $result->exam->answer_key[$question] ?? null;

                        @endphp

                        <tr>

                            <td>{{ $question }}</td>

                            <td>{{ $answer }}</td>

                            <td>{{ $correct }}</td>

                            <td>

                                @if($answer == $correct)

                                    <span class="badge bg-success">
                                        Doğru
                                    </span>

                                @elseif(empty($answer))

                                    <span class="badge bg-secondary">
                                        Boş
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Yanlış
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection
