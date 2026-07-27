<!DOCTYPE html>
<html lang="tr">

<head>

    <meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EduScan | Giriş</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

</head>

<body class="bg-primary bg-gradient">

<div class="container vh-100 d-flex justify-content-center align-items-center">

    <div class="card shadow-lg border-0" style="width:430px; border-radius:20px;">

        <div class="card-body p-5">

            <div class="text-center mb-4">

                <i class="bi bi-mortarboard-fill text-primary" style="font-size:60px;"></i>

                <h2 class="mt-3 fw-bold text-primary">

                  EduScan

                </h2>

                <p class="text-muted">

                    Öğrenci Bilgi Sistemi

                </p>

            </div>

            @if($errors->has('login'))

                <div class="alert alert-danger">

                    {{ $errors->first('login') }}

                </div>

            @endif

            <form method="POST" action="{{ route('obs.login.post') }}">

                @csrf

                <div class="mb-3">

                    <label class="form-label">

                        TC Kimlik No

                    </label>

                    <input
                        type="text"
                        name="tc_no"
                        value="{{ old('tc_no') }}"
                        class="form-control"
                        maxlength="11"
                        required>

                </div>

                <div class="mb-4">

                    <label class="form-label">

                        Şifre

                    </label>

                    <div class="input-group">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            required>

                        <button
                            class="btn btn-outline-secondary"
                            type="button"
                            onclick="togglePassword()">

                            <i class="bi bi-eye"></i>

                        </button>

                    </div>

                </div>

                <button class="btn btn-primary w-100">

                    <i class="bi bi-box-arrow-in-right"></i>

                    Giriş Yap

                </button>

            </form>

        </div>

    </div>

</div>

<script>

function togglePassword(){

    let password=document.getElementById('password');

    password.type=password.type==='password' ? 'text' : 'password';

}

</script>

</body>

</html>