@extends('obs.layouts.app')

@section('title', 'Optik Okuma Sonuçları')

@section('content')

<!-- Başlık ve Aksiyon Butonu (Mobilde alt alta, masaüstünde yan yana dizilir) -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">

    <h2 class="text-primary m-0">
        <i class="bi bi-clipboard-data"></i>
        Optik Okuma Sonuçları
    </h2>

    <a href="{{ route('obs.results.export') }}" class="btn btn-success">
        <i class="bi bi-file-earmark-excel"></i>
        Excel'e Aktar
    </a>

</div>

<div class="card shadow">
    <div class="card-body">

        <!-- Arama Formu (g-2 ile mobilde elementler arasına boşluk eklendi) -->
        <form action="{{ route('obs.results.index') }}" method="GET" class="row g-2 mb-4">

            <div class="col-12 col-md-5">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Öğrenci numarası ara..."
                    value="{{ $search ?? '' }}">
            </div>

            <div class="col-12 col-md-2">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-search"></i>
                    Ara
                </button>
            </div>

            <div class="col-12 col-md-2">
                <a href="{{ route('obs.results.index') }}" class="btn btn-secondary w-100">
                    Temizle
                </a>
            </div>

        </form>

        <!-- Esnek Tablo Alanı -->
        <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            
            <!-- min-width: 900px eklenerek tablonun daralması engellendi, kaydırma zorunlu kılındı -->
            <table class="table table-bordered table-hover align-middle mb-0" style="min-width: 900px;">

                <thead class="table-dark">
                    <tr>
                        <th>Öğrenci No</th>
                        <th>Öğrenci</th>
                        <th>Ders</th>
                        <th>Sınav</th>
                        <th class="text-center">Doğru</th>
                        <th class="text-center">Yanlış</th>
                        <th class="text-center">Boş</th>
                        <th class="text-center">Puan</th>
                        <th class="text-center">Durum</th>
                        <th class="text-center">İşlem</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($results as $result)

                    <tr>
                        <td>
                            {{ $result->student_no ?? '-' }}
                        </td>

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
                            {{ $result->exam->course_name ?? '-' }}
                        </td>

                        <td>
                            {{ $result->exam->exam_type ?? '-' }}
                        </td>

                        <td class="text-center text-success fw-bold">
                            {{ $result->correct_count }}
                        </td>

                        <td class="text-center text-danger fw-bold">
                            {{ $result->wrong_count }}
                        </td>

                        <td class="text-center text-secondary fw-bold">
                            {{ $result->blank_count }}
                        </td>

                        <td class="text-center">
                            <span class="badge bg-primary fs-6">
                                {{ number_format($result->score, 2) }}
                            </span>
                        </td>

                        <td class="text-center">
                            @if($result->status == 'success')
                                <span class="badge bg-success">
                                    Başarılı
                                </span>
                            @elseif($result->status == 'pending_review')
                                <span class="badge bg-warning text-dark">
                                    Onay Bekliyor
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    {{ $result->status }}
                                </span>
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('obs.results.show', $result->id) }}"
                               class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i>
                                Detay
                            </a>
                        </td>
                    </tr>

                @empty

                    <tr>
                        <td colspan="10" class="text-center text-danger py-4">
                            <i class="bi bi-info-circle"></i>
                            Henüz optik okuma sonucu bulunmuyor.
                        </td>
                    </tr>

                @endforelse

                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $results->links() }}
        </div>

    </div>
</div>

@endsection