<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarkVision - Optik Okuma Paneli</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        html, body { overflow-x: hidden; width: 100%; margin: 0; padding: 0; }
        body { min-height: 100vh; }

        @media (max-width: 768px) {
            body { padding: 10px !important; }
            #main-dashboard { flex-direction: column; min-height: auto !important; padding-bottom: 82px; }
            aside { display: none !important; }
            main { padding: 1rem !important; }
            #section-okuma:not(.hidden) { display: flex !important; flex-direction: column !important; }
            .col-span-2 { width: 100% !important; }

            #mobil-alt-menu:not(.hidden) { display: flex !important; }
        }

        #mobil-alt-menu {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 50;
            background: #f8fafc;
            border-top: 1px solid #cbd5e1;
            padding: 6px 4px calc(6px + env(safe-area-inset-bottom)) 4px;
            box-shadow: 0 -8px 24px rgba(15, 23, 42, 0.14);
        }
        #mobil-alt-menu .mobil-nav-grid {
            display: flex;
            justify-content: space-around;
            align-items: center;
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        .mobil-nav-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 3px;
            padding: 7px 6px;
            border: none;
            background: transparent;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            flex: 1 1 0;
            max-width: 110px;
        }
        .mobil-nav-btn .mobil-ikon {
            font-size: 19px;
            line-height: 1;
        }
        .mobil-nav-btn.mobil-aktif {
            color: #1d4ed8;
            background: #dbeafe;
        }

        #login-screen {
            isolation: isolate;
            contain: layout paint style;
        }

        .kose-kutusu { position: relative; overflow: hidden; }
        .kose { position: absolute; width: 16px; height: 16px; border-color: #10b981; border-width: 3px; z-index: 20; }
        .sol-ust { top: 8px; left: 8px; border-right: 0; border-bottom: 0; }
        .sag-ust { top: 8px; right: 8px; border-left: 0; border-bottom: 0; }
        .sol-alt { bottom: 8px; left: 8px; border-right: 0; border-top: 0; }
        .sag-alt { bottom: 8px; right: 8px; border-left: 0; border-top: 0; }
        .lazer-cizgi {
            position: absolute; left: 0; width: 100%; height: 2px;
            background: linear-gradient(to right, transparent, #10b981, transparent);
            box-shadow: 0 0 8px #10b981; z-index: 30; top: 0; display: none;
            animation: tarama 2s linear infinite;
        }
        @keyframes tarama { 0% { top: 0%; } 50% { top: 100%; } 100% { top: 0%; } }

        .soru-satiri { display: grid; grid-template-columns: 40px repeat(5, 1fr); align-items: center; gap: 8px; padding: 6px 4px; border-radius: 8px; }
        .soru-satiri:nth-child(odd) { background: #f8fafc; }
        .secenek-etiket { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 11px; color: #64748b; cursor: pointer; }
        .secenek-etiket input { accent-color: #2563eb; width: 15px; height: 15px; cursor: pointer; }
    </style>
</head>
<script>
    // 1. Fetch ile atılan tüm isteklere Ngrok izni ekler
    const originalFetch = window.fetch;
    window.fetch = async function() {
        let [resource, config] = arguments;
        if (!config) config = {};
        if (!config.headers) config.headers = {};
        config.headers['ngrok-skip-browser-warning'] = 'true';
        return await originalFetch(resource, config);
    };

    // 2. Axios veya jQuery ile atılan isteklere Ngrok izni ekler
    document.addEventListener("DOMContentLoaded", function() {
        if (window.axios) {
            window.axios.defaults.headers.common['ngrok-skip-browser-warning'] = 'true';
        }
        if (window.jQuery || window.$) {
            $.ajaxSetup({ headers: { 'ngrok-skip-browser-warning': 'true' } });
        }
    });
</script>
<body id="main-body" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 min-h-screen text-slate-100 antialiased font-sans flex items-center justify-center p-4 transition-all duration-500">

    <div id="login-screen" class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl p-8 transition-all duration-300">
        <div class="text-center mb-8">
            <div class="flex flex-col items-center justify-center gap-3 mb-4">
                <svg width="48" height="48" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#2563eb" stroke-width="6"/>
                    <circle cx="50" cy="50" r="28" fill="#dbeafe" opacity="0.3"/>
                    <circle cx="50" cy="50" r="16" fill="#1e3a8a"/>
                    <line x1="50" y1="50" x2="66" y2="50" stroke="#10b981" stroke-width="2" stroke-dasharray="2,2"/>
                    <circle cx="68" cy="50" r="4" fill="#10b981"/>
                    <path d="M18 22 L18 12 L28 12" fill="none" stroke="#10b981" stroke-width="4"/>
                    <path d="M72 12 L82 12 L82 22" fill="none" stroke="#10b981" stroke-width="4"/>
                </svg>
                <div class="flex items-center text-2xl font-black tracking-tight leading-none">
                    <span class="text-blue-400">MARK</span><span class="text-emerald-400 font-light ml-0.5">VISION</span>
                </div>
            </div>
            <h2 class="text-2xl font-bold text-white">Sisteme Giriş Yap</h2>
        </div>

        <div id="login-error" class="hidden mb-4 p-3 bg-rose-500/20 border border-rose-500/30 rounded-xl text-rose-200 text-xs"></div>

        <form id="loginForm" class="space-y-4">
            <input type="email" id="email" required placeholder="E-posta" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
            <input type="password" id="password" required placeholder="Şifre" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl cursor-pointer">Giriş Yap</button>
        </form>

        <p class="text-center text-xs text-slate-400 pt-4">
            Hesabın yok mu?
            <a href="{{ route('register') }}" class="text-blue-400 font-semibold">Kayıt Ol</a>
        </p>
    </div>

    <div id="main-dashboard" class="hidden w-full max-w-7xl bg-white rounded-2xl border border-slate-200 shadow-2xl overflow-hidden flex min-h-[620px] transition-all duration-300">
        <aside class="w-64 bg-slate-50 border-r border-slate-200 p-6 flex flex-col justify-between shrink-0 text-slate-800">
            <div class="space-y-8">
                <nav class="space-y-1">
                    <button id="menu-okut" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold bg-blue-50 text-blue-700 rounded-xl border border-blue-100 cursor-pointer">🎯 Optik Okut</button>
                    <button id="menu-cevap" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200/50 rounded-xl transition cursor-pointer">📝 Cevap Anahtarı</button>
                    <button id="menu-gecmis" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200/50 rounded-xl transition cursor-pointer">🕒 Geçmiş Sonuçlar</button>
                    <button id="menu-ogretmen" class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200/50 rounded-xl transition cursor-pointer">👤 Öğretmen</button>
                </nav>
            </div>
            <div class="pt-4 flex items-center justify-between border-t border-slate-200">
                <div>
                    <p class="text-xs font-bold" id="user-display-name">Derya Avcı</p>
                    <p class="text-[10px] text-slate-400">Öğretmen</p>
                </div>
                <button onclick="window.location.reload()" class="text-xs text-rose-600 font-semibold cursor-pointer">Çıkış</button>
            </div>
        </aside>

        <main class="flex-1 p-8 bg-slate-50/30 text-slate-800 overflow-y-auto">

            {{-- ============ OPTİK OKUT ============ --}}
            <div id="section-okuma" class="grid grid-cols-3 gap-6">
                <div class="col-span-2 space-y-6">

                    <div id="uyariCevapYok" class="hidden flex items-start justify-between gap-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-xs">
                        <div>
                            <p class="font-bold">⚠ Önce bir cevap anahtarı oluşturmalısınız</p>
                            <p class="mt-1 text-amber-700">Optik okuma yapabilmek için "Cevap Anahtarı" sekmesinden bir sınav eklemeniz gerekiyor.</p>
                        </div>
                        <button id="btnUyariCevapAnahtarinaGit" class="shrink-0 bg-amber-600 hover:bg-amber-500 text-white font-semibold px-3 py-2 rounded-lg cursor-pointer whitespace-nowrap">Cevap Anahtarına Git</button>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
                        <div class="flex gap-3">
                            <button id="btnYontemDosya" class="flex-1 py-2 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 border border-blue-200 cursor-pointer">📁 Dosya Yükle</button>
                            <button id="btnYontemKamera" class="flex-1 py-2 text-xs font-semibold rounded-lg bg-slate-50 text-slate-600 border border-slate-200 cursor-pointer">📷 Canlı Kamera</button>
                        </div>

                        <div id="kose-konteyner" class="kose-kutusu border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 p-4 min-h-[260px] flex items-center justify-center relative">
                            <div class="kose sol-ust"></div><div class="kose sag-ust"></div>
                            <div class="kose sol-alt"></div><div class="kose sag-alt"></div>
                            <div id="lazer" class="lazer-cizgi"></div>

                            <div id="alanDosya" class="text-center w-full">
                                <input type="file" id="optik_dosya" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <p class="text-xs text-slate-600">Optik formu sürükleyin veya <span class="text-blue-600 font-semibold">tıklayın</span></p>
                                <img id="onizleme-gorsel" class="hidden max-h-52 mx-auto rounded-lg mt-2 object-contain">
                            </div>

                            <div id="alanKamera" class="hidden w-full flex flex-col items-center">
                                <video id="webcam" autoplay playsinline muted class="w-full max-h-[420px] bg-black rounded-lg object-contain"></video>
                                <canvas id="webcam-canvas" class="hidden"></canvas>
                            </div>
                        </div>

                       <button id="btnFormuOkut" style="position: relative; z-index: 9999; touch-action: manipulation;" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl text-sm cursor-pointer hover:bg-blue-500 transition disabled:opacity-40 disabled:cursor-not-allowed" disabled>Formu Hizala ve Okut</button>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase mb-4">Tarama Sonucu</h3>
                    <div id="anlikSonucAlani" class="py-12 text-slate-400 text-xs flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-xl h-64 text-center">
                        <p>Hizalama yapıldıktan sonra sonuç buraya gelecektir.</p>
                    </div>
                </div>
            </div>

            {{-- ============ CEVAP ANAHTARI ============ --}}
            <div id="section-cevap" class="hidden max-w-3xl">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Yeni Cevap Anahtarı</h2>
                        <p class="text-xs text-slate-400">Optik okumadan önce cevap anahtarını gir</p>
                    </div>

                    <div id="cevap-mesaj" class="hidden text-xs rounded-lg p-3"></div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="text-[11px] font-semibold text-slate-500 uppercase">Sınav Adı</label>
                            <input type="text" id="cevap_sinav_adi" placeholder="Örn: Halkla İlişkilerde İletişim Kuramları Vize"
                                   class="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-500 uppercase">Ders Kodu <span class="normal-case text-slate-400">(opsiyonel)</span></label>
                            <input type="text" id="cevap_ders_kodu" placeholder="Örn: HIT101"
                                   class="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-semibold text-slate-500 uppercase">OBS Sınavı <span class="normal-case text-slate-400">(opsiyonel — seçersen sonuçlar OBS'ye kaydedilebilir)</span></label>
                        <select id="cevap_obs_exam_id" class="w-full mt-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            <option value="">— OBS sınavı seçilmedi —</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] font-semibold text-slate-500 uppercase">Soru Sayısı</label>
                        <div class="flex gap-2 mt-1 max-w-xs">
                            <input type="number" id="cevap_soru_sayisi" min="1" max="40" value="10"
                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                            <button id="btnSorulariOlustur" class="shrink-0 px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 border border-slate-200 cursor-pointer whitespace-nowrap">Soruları Oluştur</button>
                        </div>
                    </div>

                    <div>
                        <div class="grid grid-cols-[40px_repeat(5,1fr)] gap-2 px-1 mb-1">
                            <span></span>
                            <span class="text-center text-[10px] font-bold text-slate-400">A</span>
                            <span class="text-center text-[10px] font-bold text-slate-400">B</span>
                            <span class="text-center text-[10px] font-bold text-slate-400">C</span>
                            <span class="text-center text-[10px] font-bold text-slate-400">D</span>
                            <span class="text-center text-[10px] font-bold text-slate-400">E</span>
                        </div>
                        <div id="sorular-konteyner" class="max-h-96 overflow-y-auto border border-slate-100 rounded-xl p-2"></div>
                    </div>

                    <button id="btnCevapKaydet" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl text-sm cursor-pointer hover:bg-blue-500 transition">Cevap Anahtarını Kaydet</button>
                </div>
            </div>

            {{-- ============ GEÇMİŞ SONUÇLAR ============ --}}
            <div id="section-gecmis" class="hidden space-y-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-bold">Geçmiş Optik Okuma Sonuçları</h2>
                        <p class="text-xs text-slate-400">Veri tabanında kayıtlı tüm sınav sonuç dökümleri</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase">
                                <th class="p-4">Sonuç ID</th>
                                <th class="p-4">Sınav Adı</th>
                                <th class="p-4">Doğru / Yanlış / Boş</th>
                                <th class="p-4">Toplam Skor</th>
                                <th class="p-4">Tarih</th>
                                <th class="p-4">OBS</th>
                            </tr>
                        </thead>
                        <tbody id="gecmis-tablo-body" class="text-xs text-slate-600"></tbody>
                    </table>
                </div>
            </div>

            {{-- ============ ÖĞRETMEN ============ --}}
            <div id="section-ogretmen" class="hidden max-w-xl mx-auto space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-8">
                    <div class="flex flex-col items-center text-center">
                        <div id="sayfa-ogretmen-avatar" class="w-20 h-20 rounded-full mb-4 bg-gradient-to-br from-blue-700 to-slate-900 text-white text-2xl font-extrabold flex items-center justify-center shadow-md">?</div>
                        <h2 id="sayfa-ogretmen-ad" class="text-lg font-bold text-slate-800">—</h2>
                        <span class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-1 rounded-full">👨‍🏫 Öğretmen</span>
                        <p id="sayfa-ogretmen-email" class="text-xs text-slate-400 mt-3 break-all">—</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-8">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">🔒 Şifre Değiştir</h3>
                    <p class="text-xs text-slate-400 mt-1 mb-5">Hesap güvenliğiniz için şifrenizi güncelleyebilirsiniz.</p>

                    <div id="sifre-mesaj" class="hidden text-xs rounded-lg p-3 mb-4"></div>

                    <div class="space-y-3">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-500 uppercase">Mevcut Şifre</label>
                            <input type="password" id="sifre_eski" placeholder="••••••••" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-500 uppercase">Yeni Şifre</label>
                            <input type="password" id="sifre_yeni" placeholder="••••••••" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-500 uppercase">Yeni Şifre (Tekrar)</label>
                            <input type="password" id="sifre_yeni_tekrar" placeholder="••••••••" class="w-full mt-1 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm">
                        </div>
                    </div>

                    <button id="btnSifreGuncelle" class="w-full mt-5 bg-blue-600 text-white font-bold py-3 rounded-xl text-sm cursor-pointer hover:bg-blue-500 transition disabled:opacity-40 disabled:cursor-not-allowed">Şifreyi Güncelle</button>
                </div>

                <button id="sayfa-cikis-btn" class="w-full bg-rose-50 text-rose-600 font-semibold text-sm py-3.5 rounded-xl cursor-pointer hover:bg-rose-100 transition">Çıkış Yap</button>
            </div>
        </main>
    </div>

    <div id="mobil-alt-menu" class="hidden">
        <div class="mobil-nav-grid">
            <button id="mobil-nav-okut" class="mobil-nav-btn mobil-aktif">
                <span class="mobil-ikon">🎯</span>
                <span>Optik Okut</span>
            </button>
            <button id="mobil-nav-cevap" class="mobil-nav-btn">
                <span class="mobil-ikon">📝</span>
                <span>Cevap Anah.</span>
            </button>
            <button id="mobil-nav-gecmis" class="mobil-nav-btn">
                <span class="mobil-ikon">🕒</span>
                <span>Geçmiş</span>
            </button>
            <button id="mobil-nav-ogretmen" class="mobil-nav-btn">
                <span class="mobil-ikon">👤</span>
                <span>Öğretmen</span>
            </button>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginScreen = document.getElementById('login-screen');
        const mainDashboard = document.getElementById('main-dashboard');
        const mainBody = document.getElementById('main-body');
        const lazer = document.getElementById('lazer');
        const mobilAltMenu = document.getElementById('mobil-alt-menu');

        let girisYapanEmail = '';

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const emailDeger = document.getElementById('email').value;

            const response = await fetch("{{ route('panel.login') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ email: emailDeger, password: document.getElementById('password').value })
            });
            const data = await response.json();
            if (data.success) {
                loginScreen.classList.add('hidden'); mainDashboard.classList.remove('hidden');
                mobilAltMenu.classList.remove('hidden');
                mainBody.className = "bg-slate-100 min-h-screen text-slate-800 p-6 flex items-start justify-center";
                document.getElementById('user-display-name').textContent = data.user.ad;

                girisYapanEmail = emailDeger;
                document.getElementById('sayfa-ogretmen-ad').textContent = data.user.ad;
                document.getElementById('sayfa-ogretmen-email').textContent = girisYapanEmail;
                const bashHarf = (data.user.ad || '?').trim().charAt(0).toUpperCase();
                document.getElementById('sayfa-ogretmen-avatar').textContent = bashHarf;

                cevapAnahtariKontrolEt();
            } else {
                const err = document.getElementById('login-error');
                err.textContent = data.message;
                err.classList.remove('hidden');
            }
        });

        const menuOkut = document.getElementById('menu-okut');
        const menuCevap = document.getElementById('menu-cevap');
        const menuGecmis = document.getElementById('menu-gecmis');
        const menuOgretmen = document.getElementById('menu-ogretmen');
        const secOkuma = document.getElementById('section-okuma');
        const secCevap = document.getElementById('section-cevap');
        const secGecmis = document.getElementById('section-gecmis');
        const secOgretmen = document.getElementById('section-ogretmen');

        const mobilOkut = document.getElementById('mobil-nav-okut');
        const mobilCevap = document.getElementById('mobil-nav-cevap');
        const mobilGecmis = document.getElementById('mobil-nav-gecmis');
        const mobilOgretmen = document.getElementById('mobil-nav-ogretmen');

        const aktifMenuSinifi = "w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold bg-blue-50 text-blue-700 rounded-xl border border-blue-100 cursor-pointer";
        const pasifMenuSinifi = "w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200/50 rounded-xl transition cursor-pointer";

        function mobilAktifAyarla(aktifId) {
            [mobilOkut, mobilCevap, mobilGecmis, mobilOgretmen].forEach(btn => btn.classList.remove('mobil-aktif'));
            if (aktifId) document.getElementById(aktifId).classList.add('mobil-aktif');
        }

        function hepsiniGizle() {
            secOkuma.classList.add('hidden');
            secCevap.classList.add('hidden');
            secGecmis.classList.add('hidden');
            secOgretmen.classList.add('hidden');
            [menuOkut, menuCevap, menuGecmis, menuOgretmen].forEach(b => b.className = pasifMenuSinifi);
        }

        menuOkut.addEventListener('click', () => {
            hepsiniGizle();
            secOkuma.classList.remove('hidden');
            menuOkut.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-okut');
            cevapAnahtariKontrolEt();
        });

        menuCevap.addEventListener('click', () => {
            hepsiniGizle();
            secCevap.classList.remove('hidden');
            menuCevap.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-cevap');
            if (document.getElementById('sorular-konteyner').children.length === 0) {
                sorulariOlustur();
            }
            obsSinavlariniYukle();
        });

        menuGecmis.addEventListener('click', async () => {
            hepsiniGizle();
            secGecmis.classList.remove('hidden');
            menuGecmis.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-gecmis');

            const res = await fetch("{{ route('panel.gecmis') }}");
            const veriler = await res.json();
            const tbody = document.getElementById('gecmis-tablo-body');
            tbody.innerHTML = "";

            if (veriler.success && veriler.data.length > 0) {
                veriler.data.forEach(item => {
                    let obsHucre;
                    if (item.obs_kayit_edildi) {
                        obsHucre = `<span class="text-emerald-600 font-semibold">✓ Kaydedildi</span>`;
                    } else if (item.obs_hazir) {
                        obsHucre = `<button class="btn-obs-gecmis-kaydet bg-blue-600 text-white text-[11px] font-semibold px-3 py-1.5 rounded-lg cursor-pointer" data-id="${item.id}">OBS'ye Kaydet</button>`;
                    } else {
                        obsHucre = `<span class="text-slate-300">Eşleştirme yok</span>`;
                    }

                    tbody.innerHTML += `
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="p-4 font-bold">#${item.id}</td>
                            <td class="p-4">${item.exam_name ?? 'Genel Optik Sınav'}</td>
                            <td class="p-4"><span class="text-emerald-600 font-semibold">${item.correct_count}D</span> / <span class="text-rose-600 font-semibold">${item.wrong_count}Y</span> / <span class="text-slate-400">${item.empty_count}B</span></td>
                            <td class="p-4 font-bold text-blue-600">${item.total_score} Puan</td>
                            <td class="p-4 text-slate-400">${new Date(item.created_at).toLocaleString('tr-TR')}</td>
                            <td class="p-4">${obsHucre}</td>
                        </tr>`;
                });
            } else if (veriler.success) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-slate-400">Henüz kayıtlı optik tarama sonucu bulunamadı.</td></tr>`;
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-rose-500">Sonuçlar yüklenemedi: ${veriler.message ?? 'Bilinmeyen hata'}</td></tr>`;
            }
        });

        document.getElementById('gecmis-tablo-body').addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-obs-gecmis-kaydet');
            if (!btn) return;

            const ogrenciSonucId = btn.dataset.id;
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";

            const sonuc = await obsKaydetIstegiGonder(ogrenciSonucId);

            if (sonuc.success) {
                btn.outerHTML = `<span class="text-emerald-600 font-semibold">✓ Kaydedildi</span>`;
            } else {
                btn.disabled = false;
                btn.textContent = "OBS'ye Kaydet";
                alert("OBS'ye kaydedilemedi: " + sonuc.message);
            }
        });

        menuOgretmen.addEventListener('click', () => {
            hepsiniGizle();
            secOgretmen.classList.remove('hidden');
            menuOgretmen.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-ogretmen');
        });

        document.getElementById('btnUyariCevapAnahtarinaGit').addEventListener('click', () => {
            menuCevap.click();
        });

        mobilOkut.addEventListener('click', () => menuOkut.click());
        mobilCevap.addEventListener('click', () => menuCevap.click());
        mobilGecmis.addEventListener('click', () => menuGecmis.click());
        mobilOgretmen.addEventListener('click', () => menuOgretmen.click());

        document.getElementById('sayfa-cikis-btn').addEventListener('click', () => {
            if (confirm('Çıkış yapmak istediğinize emin misiniz?')) {
                window.location.reload();
            }
        });

        function sifreMesajGoster(basarili, metin) {
            const el = document.getElementById('sifre-mesaj');
            el.textContent = metin;
            el.classList.remove('hidden');
            el.className = basarili
                ? "text-xs rounded-lg p-3 mb-4 bg-emerald-50 text-emerald-700 border border-emerald-200"
                : "text-xs rounded-lg p-3 mb-4 bg-rose-50 text-rose-700 border border-rose-200";
        }

        document.getElementById('btnSifreGuncelle').addEventListener('click', async () => {
            const eski = document.getElementById('sifre_eski').value;
            const yeni = document.getElementById('sifre_yeni').value;
            const tekrar = document.getElementById('sifre_yeni_tekrar').value;

            if (!eski || !yeni || !tekrar) {
                sifreMesajGoster(false, "Lütfen tüm alanları doldurun.");
                return;
            }
            if (yeni.length < 6) {
                sifreMesajGoster(false, "Yeni şifre en az 6 karakter olmalı.");
                return;
            }
            if (yeni !== tekrar) {
                sifreMesajGoster(false, "Yeni şifreler birbiriyle eşleşmiyor.");
                return;
            }

            const btn = document.getElementById('btnSifreGuncelle');
            btn.disabled = true;
            btn.textContent = "Güncelleniyor...";

            try {
                const res = await fetch("{{ route('panel.sifredegistir') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ eski_sifre: eski, yeni_sifre: yeni })
                });
                const veri = await res.json();

                if (veri.success) {
                    sifreMesajGoster(true, "✓ Şifreniz başarıyla güncellendi.");
                    document.getElementById('sifre_eski').value = "";
                    document.getElementById('sifre_yeni').value = "";
                    document.getElementById('sifre_yeni_tekrar').value = "";
                } else {
                    sifreMesajGoster(false, veri.message || "Şifre güncellenemedi.");
                }
            } catch (err) {
                sifreMesajGoster(false, "Sunucuya bağlanılamadı: " + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = "Şifreyi Güncelle";
            }
        });

        const sorularKonteyner = document.getElementById('sorular-konteyner');
        const cevapMesaj = document.getElementById('cevap-mesaj');

        function sorulariOlustur() {
            let adet = parseInt(document.getElementById('cevap_soru_sayisi').value, 10);
            if (!adet || adet < 1) adet = 1;
            if (adet > 40) adet = 40;
            document.getElementById('cevap_soru_sayisi').value = adet;

            sorularKonteyner.innerHTML = "";
            const secenekler = ['A', 'B', 'C', 'D', 'E'];

            for (let i = 1; i <= adet; i++) {
                const satir = document.createElement('div');
                satir.className = "soru-satiri";

                let html = `<span class="text-xs font-bold text-slate-500">${i}</span>`;
                secenekler.forEach(harf => {
                    html += `
                        <label class="secenek-etiket">
                            <input type="radio" name="soru_${i}" value="${harf}">
                        </label>`;
                });
                satir.innerHTML = html;
                sorularKonteyner.appendChild(satir);
            }
        }

        document.getElementById('btnSorulariOlustur').addEventListener('click', sorulariOlustur);

        function cevapMesajGoster(basarili, metin) {
            cevapMesaj.textContent = metin;
            cevapMesaj.classList.remove('hidden');
            if (basarili) {
                cevapMesaj.className = "text-xs rounded-lg p-3 bg-emerald-50 text-emerald-700 border border-emerald-200";
            } else {
                cevapMesaj.className = "text-xs rounded-lg p-3 bg-rose-50 text-rose-700 border border-rose-200";
            }
        }

        let obsSinavlariYuklendi = false;

        async function obsSinavlariniYukle() {
            if (obsSinavlariYuklendi) return;

            const select = document.getElementById('cevap_obs_exam_id');

            try {
                const res = await fetch("{{ route('panel.obssinavlari') }}");
                const veri = await res.json();

                if (veri.success && veri.data.length > 0) {
                   veri.data.forEach(sinav => {
                        const opt = document.createElement('option');
                        opt.value = sinav.id;
                        opt.textContent = `${sinav.course_name ?? 'Ders'} — ${sinav.exam_type ?? 'Sınav'} (${sinav.total_questions} soru)`;
                        select.appendChild(opt);
                    });
                    obsSinavlariYuklendi = true;
                } else if (veri.success) {
                    const opt = document.createElement('option');
                    opt.value = "";
                    opt.textContent = "OBS'de kayıtlı sınavınız bulunamadı";
                    opt.disabled = true;
                }
            } catch (err) {
                // sessizce geç
            }
        }

        document.getElementById('btnCevapKaydet').addEventListener('click', async () => {
            const sinavAdi = document.getElementById('cevap_sinav_adi').value.trim();
            const dersKodu = document.getElementById('cevap_ders_kodu').value.trim();
            const obsExamId = document.getElementById('cevap_obs_exam_id').value;

            if (!sinavAdi) {
                cevapMesajGoster(false, "Lütfen sınav adını girin.");
                return;
            }

            const satirlar = sorularKonteyner.querySelectorAll('.soru-satiri');
            if (satirlar.length === 0) {
                cevapMesajGoster(false, "Önce 'Soruları Oluştur' butonuna basıp soru sayısını belirleyin.");
                return;
            }

            const answers = {};
            let eksikVar = false;

            satirlar.forEach((satir, index) => {
                const soruNo = index + 1;
                const secili = satir.querySelector(`input[name="soru_${soruNo}"]:checked`);
                if (secili) {
                    answers[soruNo] = secili.value;
                } else {
                    eksikVar = true;
                }
            });

            if (eksikVar) {
                cevapMesajGoster(false, "Lütfen tüm sorular için bir şık işaretleyin.");
                return;
            }

            const btn = document.getElementById('btnCevapKaydet');
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";

            try {
                const res = await fetch("{{ route('panel.cevapkaydet') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        exam_name: sinavAdi,
                        ders_kodu: dersKodu,
                        obs_exam_id: obsExamId || null,
                        answers: answers
                    })
                });
                const veri = await res.json();

                if (veri.success) {
                    cevapMesajGoster(true, "✓ Cevap anahtarı kaydedildi. Optik okuma artık bu anahtara göre yapılacak.");
                    cevapAnahtariVarMi = true;
                    guncelleOkumaDurumu();

                    setTimeout(() => {
                        document.getElementById('menu-okut').click();
                    }, 800);

                } else {
                    cevapMesajGoster(false, veri.message || "Kayıt başarısız.");
                }
            } catch (err) {
                cevapMesajGoster(false, "Sunucuya bağlanılamadı: " + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = "Cevap Anahtarını Kaydet";
            }
        });

        let cevapAnahtariVarMi = false;

        async function cevapAnahtariKontrolEt() {
            try {
                const res = await fetch("{{ route('panel.cevapgetir') }}");
                const veri = await res.json();
                cevapAnahtariVarMi = !!(veri.success && veri.answers && Object.keys(veri.answers).length > 0);
            } catch (err) {
                cevapAnahtariVarMi = false;
            }
            guncelleOkumaDurumu();
        }

        function guncelleOkumaDurumu() {
            const uyari = document.getElementById('uyariCevapYok');
            const btn = document.getElementById('btnFormuOkut');
            if (cevapAnahtariVarMi) {
                uyari.classList.add('hidden');
                btn.disabled = false;
            } else {
                uyari.classList.remove('hidden');
                btn.disabled = true;
            }
        }

        const fileInput = document.getElementById('optik_dosya');
        const previewImg = document.getElementById('onizleme-gorsel');
        const video = document.getElementById('webcam');
        let stream = null;
        let kameraAktif = false;

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = (e) => { previewImg.src = e.target.result; previewImg.classList.remove('hidden'); }
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('btnYontemKamera').addEventListener('click', async () => {
            document.getElementById('alanDosya').classList.add('hidden');
            document.getElementById('alanKamera').classList.remove('hidden');
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "environment",
                        width: { ideal: 1600 },
                        height: { ideal: 1200 }
                    }
                });
                video.srcObject = stream;
                kameraAktif = true;

                // Sürekli otomatik odaklama (destekleniyorsa) - 'advanced' altında
                // vermek 'focusMode'u doğrudan video constraint'i olarak vermekten
                // daha geniş tarayıcı desteğine sahip.
                const track = stream.getVideoTracks()[0];
                if (typeof track.getCapabilities === 'function') {
                    const capabilities = track.getCapabilities();
                    if (capabilities.focusMode && capabilities.focusMode.includes('continuous')) {
                        await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] });
                    }
                }
            }
            catch (err) { alert("Kamera donanımına erişilemedi: " + err.message); }
        });

        document.getElementById('btnYontemDosya').addEventListener('click', () => {
            document.getElementById('alanDosya').classList.remove('hidden');
            document.getElementById('alanKamera').classList.add('hidden');
            if(stream) { stream.getTracks().forEach(t => t.stop()); }
            kameraAktif = false;
        });

        async function obsKaydetIstegiGonder(ogrenciSonucId) {
            try {
                const res = await fetch("{{ route('panel.obskaydet') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ogrenci_sonuc_id: ogrenciSonucId })
                });
                return await res.json();
            } catch (err) {
                return { success: false, message: err.message };
            }
        }

        document.getElementById('btnFormuOkut').addEventListener('click', async () => {
            if (!cevapAnahtariVarMi) {
                guncelleOkumaDurumu();
                return;
            }

            const btnOkut = document.getElementById('btnFormuOkut');
            // Eğer buton zaten kilitliyse (arka arkaya basıldıysa) işlemi durdur
            if (btnOkut.disabled) return; 

            // Tıklanır tıklanmaz butonu anında kilitle ve yazıyı değiştir
            btnOkut.disabled = true;
            btnOkut.textContent = "Fotoğraf Çekiliyor, Sabit Tutun...";

            const formData = new FormData();
            const fileInput = document.getElementById('optik_dosya');
            const kameraAlaniAcik = !document.getElementById('alanKamera').classList.contains('hidden');

            // KAMERA MODU: aktif videodan bir kare yakalayıp JPEG olarak ekle
            // NOT: ImageCapture.takePhoto() denendi ama bu WebView'de güvenilmez
            // çıktı (butona basıldığı andan çekim anına kadar beklenenden uzun
            // sürüp yanlış/eski bir kareyi yakalayabiliyor) - canvas yöntemine
            // geri dönüldü, bu öngörülebilir şekilde "şu an ekranda ne varsa
            // onu" yakalıyor.
            if (kameraAlaniAcik && stream) {
                btnOkut.textContent = "Kamera odaklanıyor, sabit tutun...";
                await new Promise(resolve => setTimeout(resolve, 900)); // odaklanma icin bekleme

                const canvas = document.getElementById('webcam-canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
                formData.append('image', blob, 'kamera_capture.jpg');
                formData.append('kaynak', 'kamera');
            }
            // DOSYA MODU: seçili dosyayı ekle
            else if (fileInput.files.length > 0) {
                formData.append('image', fileInput.files[0]);
            }

            if (!formData.has('image')) {
                alert("Lütfen bir dosya yükleyin veya kamerayı açıp bir form gösterin.");
                btnOkut.disabled = false;
                btnOkut.textContent = "Formu Hizala ve Okut";
                return;
            }

            lazer.style.display = 'block';
            document.getElementById('btnFormuOkut').disabled = true;
            document.getElementById('btnFormuOkut').textContent = "4 Köşe Hizalanıyor, Taranıyor...";

            let veri;
            try {
                const res = await fetch("{{ route('panel.okut') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });
                veri = await res.json();
            } catch (err) {
                lazer.style.display = 'none';
                document.getElementById('btnFormuOkut').disabled = false;
                document.getElementById('btnFormuOkut').textContent = "Formu Hizala ve Okut";
                alert("Sunucuya bağlanılamadı: " + err.message);
                return;
            }

            lazer.style.display = 'none';
            document.getElementById('btnFormuOkut').disabled = false;
            document.getElementById('btnFormuOkut').textContent = "Formu Hizala ve Okut";

            if (veri.success) {
                const anlikSonuc = document.getElementById('anlikSonucAlani');
                anlikSonuc.className = "bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-xl p-6 text-left shadow-lg h-64 flex flex-col justify-between";
                anlikSonuc.innerHTML = `
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">✓ Optik Hizalama Başarılı</p>
                        <h4 class="text-sm font-bold mt-2 text-slate-300">Öğrenci Numarası: ${veri.ogrenci_no}</h4>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center text-[11px]">
                            <div class="bg-white/5 rounded-lg p-2"><p class="text-slate-400">Doğru</p><p class="font-bold text-emerald-400 text-sm">${veri.dogru}</p></div>
                            <div class="bg-white/5 rounded-lg p-2"><p class="text-slate-400">Yanlış</p><p class="font-bold text-rose-400 text-sm">${veri.yanlis}</p></div>
                            <div class="bg-white/5 rounded-lg p-2"><p class="text-slate-400">Boş</p><p class="font-bold text-slate-300 text-sm">${veri.bos}</p></div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-white/10 flex justify-between items-center">
                        <span class="text-xs text-slate-400">Sınav Notu:</span><span class="text-2xl font-black text-amber-400">${veri.puan}</span>
                    </div>
                    <button id="btnObsKaydet" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-xl text-xs cursor-pointer transition mt-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        📤 OBS'ye Kaydet
                    </button>`;

                const btnObsKaydet = document.getElementById('btnObsKaydet');

                btnObsKaydet.addEventListener('click', async () => {
                    if (!veri.obs_hazir) {
                        alert("Bu sınav bir OBS sınavıyla eşleştirilmemiş. Önce 'Cevap Anahtarı' ekranından bu sınavı OBS sınavıyla eşleştirmelisiniz.");
                        return;
                    }

                    btnObsKaydet.disabled = true;
                    btnObsKaydet.textContent = "Kaydediliyor...";

                    const sonuc = await obsKaydetIstegiGonder(veri.ogrenci_sonuc_id);

                    if (sonuc.success) {
                        btnObsKaydet.textContent = "✓ OBS'ye Kaydedildi";
                        btnObsKaydet.className = "w-full bg-slate-600 text-white font-bold py-2.5 rounded-xl text-xs mt-2 cursor-not-allowed";
                    } else {
                        btnObsKaydet.disabled = false;
                        btnObsKaydet.textContent = "📤 OBS'ye Kaydet";
                        alert("OBS'ye kaydedilemedi: " + sonuc.message);
                    }
                });
                    
                
            } else {
                alert("Okuma hatası: " + veri.message);
            }
        });
    </script>
</body>
</html>