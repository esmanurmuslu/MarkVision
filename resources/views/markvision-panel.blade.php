<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarkVision - Optik Okuma Paneli</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        body { overflow-x: hidden; width: 100vw; margin: 0; padding: 0; }
        @media (max-width: 768px) {
            body { padding: 10px !important; width: 100vw !important; }
            #main-dashboard { flex-direction: column; min-height: auto !important; }
            aside { width: 100% !important; padding: 1rem !important; }
            main { padding: 1rem !important; }
            #section-okuma { display: flex !important; flex-direction: column !important; }
            .col-span-2 { width: 100% !important; }
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

        /* Cevap Anahtarı radio satırları */
        .soru-satiri { display: grid; grid-template-columns: 40px repeat(5, 1fr); align-items: center; gap: 8px; padding: 6px 4px; border-radius: 8px; }
        .soru-satiri:nth-child(odd) { background: #f8fafc; }
        .secenek-etiket { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 11px; color: #64748b; cursor: pointer; }
        .secenek-etiket input { accent-color: #2563eb; width: 15px; height: 15px; cursor: pointer; }
    </style>
</head>
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
                                <video id="webcam" autoplay playsinline class="w-full max-h-60 bg-black rounded-lg object-cover"></video>
                            </div>
                        </div>

                        <button id="btnFormuOkut" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl text-sm cursor-pointer hover:bg-blue-500 transition">Formu Hizala ve Okut</button>
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
                            </tr>
                        </thead>
                        <tbody id="gecmis-tablo-body" class="text-xs text-slate-600"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginScreen = document.getElementById('login-screen');
        const mainDashboard = document.getElementById('main-dashboard');
        const mainBody = document.getElementById('main-body');
        const lazer = document.getElementById('lazer');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const response = await fetch("{{ route('panel.login') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ email: document.getElementById('email').value, password: document.getElementById('password').value })
            });
            const data = await response.json();
            if (data.success) {
                loginScreen.classList.add('hidden'); mainDashboard.classList.remove('hidden');
                mainBody.className = "bg-slate-100 min-h-screen text-slate-800 p-6 flex items-start justify-center";
                document.getElementById('user-display-name').textContent = data.user.ad;
            } else {
                const err = document.getElementById('login-error');
                err.textContent = data.message;
                err.classList.remove('hidden');
            }
        });

        // ---- Menü / Sekme geçişleri ----
        const menuOkut = document.getElementById('menu-okut');
        const menuCevap = document.getElementById('menu-cevap');
        const menuGecmis = document.getElementById('menu-gecmis');
        const secOkuma = document.getElementById('section-okuma');
        const secCevap = document.getElementById('section-cevap');
        const secGecmis = document.getElementById('section-gecmis');

        const aktifMenuSinifi = "w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold bg-blue-50 text-blue-700 rounded-xl border border-blue-100 cursor-pointer";
        const pasifMenuSinifi = "w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-200/50 rounded-xl transition cursor-pointer";

        function hepsiniGizle() {
            secOkuma.classList.add('hidden');
            secCevap.classList.add('hidden');
            secGecmis.classList.add('hidden');
            [menuOkut, menuCevap, menuGecmis].forEach(b => b.className = pasifMenuSinifi);
        }

        menuOkut.addEventListener('click', () => {
            hepsiniGizle();
            secOkuma.classList.remove('hidden');
            menuOkut.className = aktifMenuSinifi;
        });

        menuCevap.addEventListener('click', () => {
            hepsiniGizle();
            secCevap.classList.remove('hidden');
            menuCevap.className = aktifMenuSinifi;
            if (document.getElementById('sorular-konteyner').children.length === 0) {
                sorulariOlustur();
            }
        });

        menuGecmis.addEventListener('click', async () => {
            hepsiniGizle();
            secGecmis.classList.remove('hidden');
            menuGecmis.className = aktifMenuSinifi;

            const res = await fetch("{{ route('panel.gecmis') }}");
            const veriler = await res.json();
            const tbody = document.getElementById('gecmis-tablo-body');
            tbody.innerHTML = "";

            if (veriler.success && veriler.data.length > 0) {
                veriler.data.forEach(item => {
                    tbody.innerHTML += `
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="p-4 font-bold">#${item.id}</td>
                            <td class="p-4">${item.exam_name ?? 'Genel Optik Sınav'}</td>
                            <td class="p-4"><span class="text-emerald-600 font-semibold">${item.correct_count}D</span> / <span class="text-rose-600 font-semibold">${item.wrong_count}Y</span> / <span class="text-slate-400">${item.empty_count}B</span></td>
                            <td class="p-4 font-bold text-blue-600">${item.total_score} Puan</td>
                            <td class="p-4 text-slate-400">${new Date(item.created_at).toLocaleString('tr-TR')}</td>
                        </tr>`;
                });
            } else if (veriler.success) {
                tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-slate-400">Henüz kayıtlı optik tarama sonucu bulunamadı.</td></tr>`;
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-rose-500">Sonuçlar yüklenemedi: ${veriler.message ?? 'Bilinmeyen hata'}</td></tr>`;
            }
        });

        // ---- CEVAP ANAHTARI: soruları dinamik oluştur ----
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

        document.getElementById('btnCevapKaydet').addEventListener('click', async () => {
            const sinavAdi = document.getElementById('cevap_sinav_adi').value.trim();
            const dersKodu = document.getElementById('cevap_ders_kodu').value.trim();

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
                    body: JSON.stringify({ exam_name: sinavAdi, ders_kodu: dersKodu, answers: answers })
                });
                const veri = await res.json();

                if (veri.success) {
                    cevapMesajGoster(true, "✓ Cevap anahtarı kaydedildi. Optik okuma artık bu anahtara göre yapılacak.");
                } else {
                    cevapMesajGoster(false, veri.message || "Kaydetme sırasında bir hata oluştu.");
                }
            } catch (err) {
                cevapMesajGoster(false, "Sunucuya bağlanılamadı: " + err.message);
            } finally {
                btn.disabled = false;
                btn.textContent = "Cevap Anahtarını Kaydet";
            }
        });

        // ---- OPTİK OKUT bölümü ----
        const fileInput = document.getElementById('optik_dosya');
        const previewImg = document.getElementById('onizleme-gorsel');
        const video = document.getElementById('webcam');
        let stream = null;

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
            try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }); video.srcObject = stream; }
            catch (err) { alert("Kamera donanımına erişilemedi."); }
        });

        document.getElementById('btnYontemDosya').addEventListener('click', () => {
            document.getElementById('alanDosya').classList.remove('hidden');
            document.getElementById('alanKamera').classList.add('hidden');
            if(stream) { stream.getTracks().forEach(t => t.stop()); }
        });

        document.getElementById('btnFormuOkut').addEventListener('click', async () => {
            lazer.style.display = 'block';
            document.getElementById('btnFormuOkut').disabled = true;
            document.getElementById('btnFormuOkut').textContent = "4 Köşe Hizalanıyor, Taranıyor...";

            const formData = new FormData();
            const fileInput = document.getElementById('optik_dosya');

            if(fileInput.files.length > 0) {
                formData.append('image', fileInput.files[0]);
            }

            const res = await fetch("{{ route('panel.okut') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            });

            lazer.style.display = 'none';
            document.getElementById('btnFormuOkut').disabled = false;
            document.getElementById('btnFormuOkut').textContent = "Formu Hizala ve Okut";

            const veri = await res.json();

            if (veri.success) {
                // DEĞİŞTİ: "Öğrenci Adı" satırı kaldırıldı, sadece numara gösteriliyor.
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
                    </div>`;
            } else {
                alert("Okuma hatası: " + veri.message);
            }
        });
    </script>
</body>
</html>