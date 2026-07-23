<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EduScan - İnceleme Bekleyen Formlar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8fafc;
        }

        .card {
            border-radius: 15px;
        }

        code {
            word-break: break-all;
        }
    </style>

</head>


<body>


<div class="container mt-5">


    <div class="mb-4">


        <a href="{{ route('obs.tarama') }}"
           class="btn btn-sm btn-outline-secondary">

            ← Öğrenci Listesine Dön

        </a>


        <h2 class="mt-3 text-warning">

            Onay ve İnceleme Bekleyen Optik Sonuçlar

        </h2>


    </div>



    @if($pendingResults->isEmpty())


        <div class="alert alert-success shadow-sm">

            Şu anda kontrol edilmesi gereken hatalı veya numarasız form bulunmuyor.

        </div>



    @else



        <div class="row">


            @foreach($pendingResults as $result)



                <div class="col-md-6 mb-4">


                    <div class="card shadow-sm border-warning">


                        <div class="card-body">


                            <h5 class="card-title text-danger">

                                ⚠ Öğrenci Numarası Okunamadı!

                            </h5>



                            <p class="mb-1">

                                <strong>Sınav ID:</strong>

                                {{ $result->exam_id }}

                            </p>



                            <p>

                                <strong>Görsel Yolu:</strong>

                                <br>

                                <code>

                                    {{ $result->optical_image_url }}

                                </code>

                            </p>




                            <h6>

                                Okunan Cevap Dizilimi (JSON):

                            </h6>




                            <div class="bg-dark text-white p-3 rounded mb-3">


                                <small>


                                    @if(is_array($result->student_answers))


                                        @foreach($result->student_answers as $soru => $cevap)


                                            Soru {{ $soru }}:

                                            {{ $cevap }}

                                            <br>


                                        @endforeach


                                    @else


                                        {{ $result->student_answers }}


                                    @endif


                                </small>


                            </div>




                            <button class="btn btn-primary w-100">

                                Görseli İncele & Öğrenciyi Elle Seç

                            </button>



                        </div>


                    </div>


                </div>



            @endforeach



        </div>



    @endif



</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>