<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>EduScan - İnceleme Bekleyen Formlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="mb-4">
            <a href="{{ route('obs.students') }}" class="btn btn-sm btn-outline-secondary">← Öğrenci Listesine Dön</a>
            <h2 class="mt-2 text-warning">Onay ve İnceleme Bekleyen Optik Sonuçlar</h2>
        </div>

        @if($pendingResults->isEmpty())
            <div class="alert alert-success">Şu anda kontrol edilmesi gereken hatalı veya numarasız form bulunmuyor.</div>
        @else
            <div class="row">
                @foreach($pendingResults as $result)
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm border-warning">
                            <div class="card-body">
                                <h5 class="card-title text-danger">⚠ Öğrenci Numarası Okunamadı!</h5>
                                <p class="card-text mb-1"><strong>Sınav ID:</strong> {{ $result->exam_id }}</p>
                                <p class="card-text"><strong>Görsel Yolu:</strong> <code>{{ $result->optical_image_url }}</code></p>
                                
                                <h6>Okunan Cevap Dizilimi (JSON):</h6>
                                <div class="bg-dark text-white p-2 rounded mb-3">
                                    <small>
                                        @foreach($result->student_answers as $soru => $cevap)
                                            Soru {{ $soru }}: {{ $cevap }} | 
                                        @endforeach
                                    </small>
                                </div>
                                <button class="btn btn-sm btn-primary w-100" style="background-color: #2B59C3;">Görseli İncele & Öğrenciyi Elle Seç</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>