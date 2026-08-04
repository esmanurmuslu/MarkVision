@extends('obs.layouts.app')

@section('title', 'Cevap Anahtarı')

@section('content')

<div class="card shadow">

    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">
            {{ $exam->course_name }} - {{ $exam->exam_type }}
            <small>(Toplam {{ $exam->total_questions }} Soru)</small>
        </h4>
    </div>

    <div class="card-body">

        <form method="POST" action="{{ route('obs.exams.answerkey.save',$exam->id) }}">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $exam->id }}">

            @php
                $answers = $exam->answer_key ?? [];
            @endphp

            @for($i=1; $i <= $exam->total_questions; $i++)

                <div class="row align-items-center border rounded p-3 mb-2">

                    <div class="col-md-2">
                        <strong>{{ $i }}. Soru</strong>
                    </div>

                    <div class="col-md-10">

                        @foreach(['A','B','C','D','E'] as $option)

                            <div class="form-check form-check-inline">

                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="answers[{{ $i }}]"
                                    value="{{ $option }}"
                                    {{ (($answers[$i] ?? '') == $option) ? 'checked' : '' }}>

                                <label class="form-check-label">

                                    {{ $option }}

                                </label>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endfor

            <button class="btn btn-success mt-3">
                Cevap Anahtarını Kaydet
            </button>

            <a href="{{ route('obs.exams.index') }}"
               class="btn btn-secondary mt-3">
                Geri Dön
            </a>

        </form>

    </div>

</div>

@endsection
