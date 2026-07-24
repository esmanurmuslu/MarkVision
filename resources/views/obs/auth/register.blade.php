<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarkVision - Kayıt Ol</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 min-h-screen text-slate-100 antialiased font-sans flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-4">
                <!-- LOGO -->
                <svg width="48" height="48" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#2563eb" stroke-width="6"/>
                    <circle cx="50" cy="50" r="28" fill="#dbeafe" opacity="0.3"/>
                    <circle cx="50" cy="50" r="16" fill="#1e3a8a"/>
                    <line x1="50" y1="50" x2="66" y2="50" stroke="#10b981" stroke-width="2" stroke-dasharray="2,2"/>
                    <circle cx="68" cy="50" r="4" fill="#10b981"/>
                    <!-- köşe işaretleri -->
                    <path d="M18 22 L18 12 L28 12" fill="none" stroke="#10b981" stroke-width="4"/>
                    <path d="M72 12 L82 12 L82 22" fill="none" stroke="#10b981" stroke-width="4"/>
                </svg>
                <div class="text-left">
                    <div class="flex items-center text-2xl font-black tracking-tight leading-none">
                        <span class="text-blue-400">MARK</span><span class="text-emerald-400 font-light ml-0.5">VISION</span>
                    </div>
                    <p class="text-[10px] text-slate-400 tracking-widest uppercase">Optical Reader System</p>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-white">Hesap Oluştur</h2>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-rose-500/20 border border-rose-500/30 rounded-xl text-rose-200 text-xs">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="text" name="name" required placeholder="Ad"
                   class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
            <input type="text" name="surname" required placeholder="Soyad"
                   class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
            <input type="email" name="email" required placeholder="E-posta"
                   class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
            <input type="text" name="tc_no" required placeholder="T.C. Kimlik No" maxlength="11"
                   class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
            <input type="password" name="password" required placeholder="Şifre"
                   class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl cursor-pointer transition">
                Kayıt Ol
            </button>

            <p class="text-center text-xs text-slate-400 pt-2">
                Zaten hesabın var mı?
                <a href="{{ url('/') }}" class="text-blue-400 font-semibold">Giriş Yap</a>
            </p>
        </form>
    </div>

</body>
</html>