<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarkVision - Optik Okuma Paneli</title>
    <!-- Tailwind CSS -->
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <!-- Dinamik PDF Üretimi İçin jsPDF Kütüphanesi -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
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
            display: flex; justify-content: space-around; align-items: center;
            width: 100%; max-width: 480px; margin: 0 auto;
        }
        .mobil-nav-btn {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; gap: 3px; padding: 7px 6px; border: none; background: transparent;
            color: #94a3b8; font-size: 10px; font-weight: 600; border-radius: 12px;
            cursor: pointer; transition: all 0.15s ease; flex: 1 1 0; max-width: 110px;
        }
        .mobil-nav-btn .mobil-ikon { font-size: 19px; line-height: 1; }
        .mobil-nav-btn.mobil-aktif { color: #1d4ed8; background: #dbeafe; }

        #login-screen { isolation: isolate; contain: layout paint style; }

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

        .soru-satiri { display: grid; align-items: center; gap: 8px; padding: 6px 4px; border-radius: 8px; }
        .soru-satiri:nth-child(odd) { background: #f8fafc; }
        .secenek-etiket { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 11px; color: #64748b; cursor: pointer; }
        
        .modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<script>
    const originalFetch = window.fetch;
    window.fetch = async function() {
        let [resource, config] = arguments;
        if (!config) config = {};
        if (!config.headers) config.headers = {};
        config.headers['ngrok-skip-browser-warning'] = 'true';
        return await originalFetch(resource, config);
    };
    document.addEventListener("DOMContentLoaded", function() {
        if (window.axios) window.axios.defaults.headers.common['ngrok-skip-browser-warning'] = 'true';
        if (window.jQuery || window.$) $.ajaxSetup({ headers: { 'ngrok-skip-browser-warning': 'true' } });
    });
</script>
<body id="main-body" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 min-h-screen text-slate-100 antialiased font-sans flex items-center justify-center p-4 transition-all duration-500">

    <!-- GİRİŞ EKRANI -->
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
            <div class="relative">
                <input type="password" id="password" required placeholder="Şifre" class="w-full px-4 py-3 pr-11 bg-white/5 border border-white/10 rounded-xl text-sm text-white">
                <button type="button" id="btnSifreGoster" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 cursor-pointer select-none">👁</button>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl cursor-pointer">Giriş Yap</button>
        </form>

        <p class="text-center text-xs text-slate-400 pt-4">
            Hesabın yok mu?
            <a href="{{ route('register') }}" class="text-blue-400 font-semibold">Kayıt Ol</a>
        </p>
    </div>

    <!-- ANA PANEL -->
    <div id="main-dashboard" class="hidden w-full max-w-7xl bg-slate-50 rounded-2xl border border-slate-200 shadow-2xl overflow-hidden flex min-h-[620px] transition-all duration-300">
        <aside class="w-64 bg-white border-r border-slate-200 p-6 flex flex-col justify-between shrink-0 text-slate-800">
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

        <main class="flex-1 p-8 bg-slate-50/30 text-slate-800 overflow-y-auto relative">

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

                       <button id="btnFormuOkut" style="position: relative; z-index: 10; touch-action: manipulation;" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl text-sm cursor-pointer hover:bg-blue-500 transition disabled:opacity-40 disabled:cursor-not-allowed" disabled>Formu Hizala ve Okut</button>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6">
                    <h3 class="text-xs font-bold text-slate-400 uppercase mb-4">Tarama Sonucu</h3>
                    <div id="anlikSonucAlani" class="py-12 text-slate-400 text-xs flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-xl h-64 text-center">
                        <p>Hizalama yapıldıktan sonra sonuç buraya gelecektir.</p>
                    </div>
                </div>
            </div>

            {{-- ============ CEVAP ANAHTARI & SİHİRBAZ ============ --}}
            <div id="section-cevap" class="hidden max-w-4xl space-y-6 pb-20">
                
                <!-- STANDART ZIPGRADE ŞABLONLARI -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden w-full">
                    <div class="p-6">
                        <p class="text-sm text-slate-600 mb-6 leading-relaxed">Cevap kağıtları, öğretmenlerin farklı ihtiyaçlarını karşılamak üzere çeşitli boyut ve formatlarda mevcuttur. Genel kullanım için PDF sürümünü kullanın. En iyi tarama sonuçları için standart beyaz fotokopi kağıdına yazdırın.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 text-center">
                            <!-- 20 -->
                            <div class="border border-slate-200 rounded-xl p-6 flex flex-col items-center hover:shadow-md transition">
                                <div class="h-16 w-16 bg-orange-50 rounded flex items-center justify-center mb-4 text-3xl">📋</div>
                                <h3 class="font-bold text-slate-700 mb-4 text-sm">20 Soruluk Form</h3>
                                <button onclick="standartPdfIndir(20)" class="w-full bg-[#1e40af] hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition text-sm cursor-pointer shadow-sm">PDF İndir / Yazdır</button>
                            </div>
                            <!-- 50 -->
                            <div class="border border-slate-200 rounded-xl p-6 flex flex-col items-center hover:shadow-md transition">
                                <div class="h-16 w-16 bg-purple-50 rounded flex items-center justify-center mb-4 text-3xl">📄</div>
                                <h3 class="font-bold text-slate-700 mb-4 text-sm">50 Soruluk Form</h3>
                                <button onclick="standartPdfIndir(50)" class="w-full bg-[#1e40af] hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition text-sm cursor-pointer shadow-sm">PDF İndir / Yazdır</button>
                            </div>
                            <!-- 100 -->
                            <div class="border border-slate-200 rounded-xl p-6 flex flex-col items-center hover:shadow-md transition">
                                <div class="h-16 w-16 bg-blue-50 rounded flex items-center justify-center mb-4 text-3xl">📑</div>
                                <h3 class="font-bold text-slate-700 mb-4 text-sm">100 Soruluk Form</h3>
                                <button onclick="standartPdfIndir(100)" class="w-full bg-[#1e40af] hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition text-sm cursor-pointer shadow-sm">PDF İndir / Yazdır</button>
                            </div>
                        </div>

                        <h3 class="font-bold text-slate-800 mb-4 uppercase tracking-wider text-[13px] border-b pb-2">FORM DETAYLARI</h3>
                        <table class="w-full text-sm text-left text-slate-600">
                            <tbody>
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 font-semibold text-slate-700">Soru Sayısının Maksimum Sınırı</td>
                                    <td class="py-3 text-center">20</td>
                                    <td class="py-3 text-center">50</td>
                                    <td class="py-3 text-center">100</td>
                                </tr>
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 font-semibold text-slate-700">Öğrenci Kimlik Bölümü</td>
                                    <td class="py-3 text-center text-rose-500 font-bold uppercase text-[11px]">HAYIR</td>
                                    <td class="py-3 text-center text-emerald-600 font-bold uppercase text-[11px]">EVET</td>
                                    <td class="py-3 text-center text-emerald-600 font-bold uppercase text-[11px]">EVET</td>
                                </tr>
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 font-semibold text-slate-700">Öğrenci kimlik numarasının hane sayısı</td>
                                    <td class="py-3 text-center text-slate-400">Yok</td>
                                    <td class="py-3 text-center">5</td>
                                    <td class="py-3 text-center">9</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ÖZEL SİHİRBAZ VE BİRLEŞTİRİLMİŞ CEVAP ANAHTARI TABLOSU -->
                <div id="zipgrade-wizard" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden w-full mt-8">
                    <div class="bg-[#4f6457] p-4 text-white flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold flex items-center gap-2"><span class="bg-white text-[#4f6457] rounded-full w-5 h-5 flex items-center justify-center text-sm">✓</span> ZİPGRADE SİHİRBAZI</h2>
                        </div>
                        <span class="text-xs opacity-90 font-medium tracking-wide">Özel Cevap Kağıdı</span>
                    </div>

                    <div class="p-6 space-y-6 text-sm">
                        
                        <!-- Step 1 -->
                        <div class="border border-slate-200 rounded-md">
                            <div class="bg-slate-50 p-3 border-b border-slate-200 font-semibold text-slate-700">5 Adımdan 1. Adım: Sınav Detayları</div>
                            <div class="p-4 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Sınav Adı</label>
                                    <input type="text" value="Vize Sınavı" id="zg_form_name" class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-green-600">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Ders Kodu (Opsiyonel)</label>
                                    <input type="text" id="zg_ders_kodu" placeholder="Örn: MAT101" class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:border-green-600">
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="border border-slate-200 rounded-md">
                            <div class="bg-slate-50 p-3 border-b border-slate-200 font-semibold text-slate-700">5 Adımdan 2. Adım: Başlık Kutuları</div>
                            <div class="p-4">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-600 text-white">
                                            <th class="p-2 border border-slate-500">Kullanım</th>
                                            <th class="p-2 border border-slate-500 text-center">Etkin mi?</th>
                                            <th class="p-2 border border-slate-500">Kağıtta Görünen Etiket</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-2 border border-slate-200">Ad</td>
                                            <td class="p-2 border border-slate-200 text-center"><input type="checkbox" checked></td>
                                            <td class="p-2 border border-slate-200"><input type="text" value="Ad Soyad" class="border border-slate-300 rounded p-1 w-full"></td>
                                        </tr>
                                        <tr class="bg-slate-50">
                                            <td class="p-2 border border-slate-200">Sınıf</td>
                                            <td class="p-2 border border-slate-200 text-center"><input type="checkbox" checked></td>
                                            <td class="p-2 border border-slate-200"><input type="text" value="Sınıf" class="border border-slate-300 rounded p-1 w-full"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="border border-slate-200 rounded-md">
                            <div class="bg-slate-50 p-3 border-b border-slate-200 font-semibold text-slate-700">5 Adımdan 3. Adım: Öğrenci No</div>
                            <div class="p-4">
                                <div class="border border-slate-200 p-4 rounded bg-slate-50">
                                    <label class="block font-bold mb-2 text-xs text-slate-700">Öğrenci Numarası Alanı Ekle:</label>
                                    <input type="checkbox" checked class="w-5 h-5 mb-4 accent-blue-600">
                                    <div class="flex items-center gap-4 text-xs">
                                        <span class="text-slate-600">Hane Sayısı:</span>
                                        <select class="border border-slate-300 rounded p-1"><option>5</option><option selected>9</option><option>11</option></select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="border border-slate-200 rounded-md">
                            <div class="bg-slate-50 p-3 border-b border-slate-200 font-semibold text-slate-700">5 Adımdan 4. Adım: Soruları Tanımlama</div>
                            <div class="p-4 flex flex-col md:flex-row gap-6">
                                <div class="w-full md:w-1/3 flex flex-col gap-2">
                                    <button type="button" id="btnModalAc" class="border border-slate-300 text-left px-3 py-2 text-xs hover:bg-slate-100 transition rounded shadow-sm bg-white text-slate-700 font-medium cursor-pointer text-center font-bold text-blue-600">+ Soru Ekle</button>
                                </div>
                                <div class="w-full md:w-2/3 border border-slate-200 rounded overflow-hidden">
                                    <table class="w-full text-xs text-left" id="zg_question_table">
                                        <thead class="bg-slate-100 text-slate-600">
                                            <tr>
                                                <th class="p-2 border-b">Sayı</th>
                                                <th class="p-2 border-b">Tip</th>
                                                <th class="p-2 border-b">Etiketler</th>
                                            </tr>
                                        </thead>
                                        <tbody id="zg_question_body">
                                            <tr><td colspan="3" class="p-4 text-center text-slate-400">Soru eklemek için sol taraftaki butonu kullanın.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 (YENİ - OBS VE CEVAP ANAHTARI BİRLEŞTİRİLDİ) -->
                        <div class="border border-slate-200 rounded-md shadow-sm border-l-4 border-l-emerald-500">
                            <div class="bg-emerald-50 p-3 border-b border-slate-200 font-semibold text-emerald-800">5 Adımdan 5. Adım: OBS Eşleştirme ve Doğru Cevapları İşaretleme</div>
                            <div class="p-4 space-y-4">
                                <div id="cevap-mesaj" class="hidden text-xs rounded-lg p-3"></div>
                                
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 uppercase">OBS Sınavı İle Eşleştir (Opsiyonel)</label>
                                    <select id="cevap_obs_exam_id" class="w-full mt-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:border-emerald-500">
                                        <option value="">— OBS sınavı seçilmedi —</option>
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Sonuçların OBS sisteminize aktarılabilmesi için açılır listeden ilgili sınavı seçin.</p>
                                </div>

                                <div class="bg-white border border-slate-200 rounded-xl p-4 mt-4">
                                    <p class="text-xs font-bold text-slate-700 mb-2">Doğru Cevapları İşaretleyin:</p>
                                    
                                    <div class="grid grid-cols-[40px_repeat(5,1fr)] gap-2 px-1 mb-2 border-b border-slate-200 pb-2" id="soru-harf-basliklari">
                                        <span></span>
                                        <span class="text-center text-xs font-bold text-slate-500">A</span>
                                        <span class="text-center text-xs font-bold text-slate-500">B</span>
                                        <span class="text-center text-xs font-bold text-slate-500">C</span>
                                        <span class="text-center text-xs font-bold text-slate-500">D</span>
                                        <span class="text-center text-xs font-bold text-slate-500">E</span>
                                    </div>
                                    <div id="sorular-konteyner" class="max-h-[300px] overflow-y-auto px-1 space-y-1">
                                        <p class="text-xs text-slate-400 text-center py-6 italic">Doğru cevap tablosunun oluşması için önce 4. adımdan soru ekleyin.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ALT BUTONLAR -->
                    <div class="bg-slate-100 p-4 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center gap-3">
                        <button type="button" id="btnSihirbazGeri" class="w-full md:w-auto px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm text-slate-600 shadow-sm cursor-pointer hover:bg-slate-50 transition font-medium">← Geri Dön (Vazgeç)</button>
                        <div class="flex gap-2 w-full md:w-auto">
                            <button type="button" id="btnPdfIndir" class="w-full md:w-auto px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-[#4f6457] hover:bg-[#3d4d43] shadow-md transition cursor-pointer">Yayınla ve PDF İndir</button>
                            <button type="button" id="btnCevapKaydet" class="w-full md:w-auto px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md transition cursor-pointer">Sisteme Kaydet ve Optik Oku</button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ============ GEÇMİŞ SONUÇLAR ============ --}}
            <div id="section-gecmis" class="hidden space-y-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 flex justify-between items-center shadow-sm">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Geçmiş Optik Okuma Sonuçları</h2>
                        <p class="text-xs text-slate-400 mt-1">Veri tabanında kayıtlı tüm sınav sonuç dökümleri</p>
                    </div>
                    <a href="{{ route('panel.export') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-xl text-xs flex items-center gap-2 shadow-md transition">
                        📥 Excel İndir (ZipGrade)
                    </a>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase">
                                <th class="p-4">Sonuç ID</th>
                                <th class="p-4">Sınav Adı</th>
                                <th class="p-4">Doğru / Yanlış / Boş</th>
                                <th class="p-4">Toplam Puan</th>
                                <th class="p-4">Tarih</th>
                                <th class="p-4">OBS İşlemi</th>
                            </tr>
                        </thead>
                        <tbody id="gecmis-tablo-body" class="text-xs text-slate-600"></tbody>
                    </table>
                </div>
            </div>

            {{-- ============ ÖĞRETMEN ============ --}}
            <div id="section-ogretmen" class="hidden max-w-xl mx-auto space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
                    <div class="flex flex-col items-center text-center">
                        <div id="sayfa-ogretmen-avatar" class="w-20 h-20 rounded-full mb-4 bg-gradient-to-br from-blue-700 to-slate-900 text-white text-2xl font-extrabold flex items-center justify-center shadow-md">?</div>
                        <h2 id="sayfa-ogretmen-ad" class="text-lg font-bold text-slate-800">—</h2>
                        <span class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-1 rounded-full">👨‍🏫 Öğretmen</span>
                        <p id="sayfa-ogretmen-email" class="text-xs text-slate-400 mt-3 break-all">—</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
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

                <button id="sayfa-cikis-btn" class="w-full bg-rose-50 text-rose-600 font-semibold text-sm py-3.5 rounded-xl cursor-pointer hover:bg-rose-100 transition shadow-sm">Çıkış Yap</button>
            </div>
            
            <!-- SİHİRBAZ MODAL PENCERESİ (Dışarıda, Sayfanın Üstünde Açılır) -->
            <div class="modal-bg" id="soruModal">
                <div class="modal-content">
                    <div class="flex justify-between items-center border-b p-4 bg-slate-50 rounded-t-lg">
                        <h3 class="font-bold text-slate-700 text-sm">Soruları Ekleyin</h3>
                        <button type="button" id="btnModalKapat" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">×</button>
                    </div>
                    <div class="p-4 space-y-4 text-sm">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1 text-xs">Soru Sayısı</label>
                            <select id="modalSoruAdet" class="w-full border border-slate-300 rounded p-2 focus:border-blue-500">
                                <option value="5">5 Soru</option>
                                <option value="10">10 Soru</option>
                                <option value="15">15 Soru</option>
                                <option value="20" selected>20 Soru</option>
                                <option value="30">30 Soru</option>
                                <option value="40">40 Soru</option>
                                <option value="50">50 Soru</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1 text-xs">Şık Etiketleri</label>
                            <input type="text" id="modalSoruEtiket" value="ABCDE" class="w-full border border-slate-300 rounded p-2 font-mono tracking-widest uppercase focus:border-blue-500">
                            <p class="text-[10px] text-slate-500 mt-1">Örn: ABCDE (5 Şık), ABCD (4 Şık) veya DY (Doğru/Yanlış).</p>
                        </div>
                    </div>
                    <div class="border-t p-4 flex justify-end gap-2 bg-slate-50 rounded-b-lg">
                        <button type="button" id="btnModalIptal" class="px-4 py-2 border border-slate-300 rounded text-slate-600 bg-white hover:bg-slate-100 cursor-pointer font-medium text-sm transition">Vazgeç</button>
                        <button type="button" id="btnModalEkle" class="px-4 py-2 rounded text-white bg-blue-600 hover:bg-blue-700 font-bold cursor-pointer text-sm shadow-md transition">Soruları Ekle</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ALT MENÜ (Mobiller İçin) -->
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

    <!-- TÜM JAVASCRIPT KODLARI -->
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginScreen = document.getElementById('login-screen');
        const mainDashboard = document.getElementById('main-dashboard');
        const mainBody = document.getElementById('main-body');
        const lazer = document.getElementById('lazer');
        const mobilAltMenu = document.getElementById('mobil-alt-menu');
        let girisYapanEmail = '';

        // Şifre Göster/Gizle
        const sifreInput = document.getElementById('password');
        const btnSifreGoster = document.getElementById('btnSifreGoster');
        btnSifreGoster.addEventListener('click', () => {
            const gosteriliyor = sifreInput.type === 'text';
            sifreInput.type = gosteriliyor ? 'password' : 'text';
            btnSifreGoster.textContent = gosteriliyor ? '👁' : '🙈';
        });

        // Giriş İşlemi
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

        // Menü Gezinme İşlemleri
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
            hepsiniGizle(); secOkuma.classList.remove('hidden'); menuOkut.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-okut'); cevapAnahtariKontrolEt();
        });

        menuCevap.addEventListener('click', () => {
            hepsiniGizle(); secCevap.classList.remove('hidden'); menuCevap.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-cevap');
            obsSinavlariniYukle();
        });

        menuGecmis.addEventListener('click', async () => {
            hepsiniGizle(); secGecmis.classList.remove('hidden'); menuGecmis.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-gecmis');

            const res = await fetch("{{ route('panel.gecmis') }}");
            const veriler = await res.json();
            const tbody = document.getElementById('gecmis-tablo-body');
            tbody.innerHTML = "";

            if (veriler.success && veriler.data.length > 0) {
                veriler.data.forEach(item => {
                    let obsHucre;
                    if (item.obs_kayit_edildi) {
                        obsHucre = `<span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded">✓ Kaydedildi</span>`;
                    } else if (item.obs_hazir) {
                        obsHucre = `<button class="btn-obs-gecmis-kaydet bg-blue-600 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg cursor-pointer hover:bg-blue-500 shadow-sm" data-id="${item.id}">OBS'ye Kaydet</button>`;
                    } else {
                        obsHucre = `<span class="text-slate-400 font-medium">Eşleştirme yok</span>`;
                    }
                    tbody.innerHTML += `
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="p-4 font-bold text-slate-700">#${item.id}</td>
                            <td class="p-4 font-medium">${item.exam_name ?? 'Genel Optik Sınav'}</td>
                            <td class="p-4">
                                <span class="text-emerald-600 font-bold">${item.correct_count}D</span> / 
                                <span class="text-rose-600 font-bold">${item.wrong_count}Y</span> / 
                                <span class="text-slate-400 font-bold">${item.empty_count}B</span>
                            </td>
                            <td class="p-4 font-black text-blue-600">${item.total_score} Puan</td>
                            <td class="p-4 text-slate-400 text-[11px]">${new Date(item.created_at).toLocaleString('tr-TR')}</td>
                            <td class="p-4">${obsHucre}</td>
                        </tr>`;
                });
            } else if (veriler.success) {
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-slate-400">Henüz kayıtlı optik tarama sonucu bulunamadı.</td></tr>`;
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-rose-500">Sonuçlar yüklenemedi.</td></tr>`;
            }
        });

        document.getElementById('gecmis-tablo-body').addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-obs-gecmis-kaydet');
            if (!btn) return;
            const ogrenciSonucId = btn.dataset.id;
            btn.disabled = true; btn.textContent = "Kaydediliyor...";
            const sonuc = await obsKaydetIstegiGonder(ogrenciSonucId);
            if (sonuc.success) {
                btn.outerHTML = `<span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-1 rounded">✓ Kaydedildi</span>`;
            } else {
                btn.disabled = false; btn.textContent = "OBS'ye Kaydet"; alert("Hata: " + sonuc.message);
            }
        });

        menuOgretmen.addEventListener('click', () => {
            hepsiniGizle(); secOgretmen.classList.remove('hidden'); menuOgretmen.className = aktifMenuSinifi;
            mobilAktifAyarla('mobil-nav-ogretmen');
        });

        document.getElementById('btnUyariCevapAnahtarinaGit').addEventListener('click', () => menuCevap.click());
        mobilOkut.addEventListener('click', () => menuOkut.click());
        mobilCevap.addEventListener('click', () => menuCevap.click());
        mobilGecmis.addEventListener('click', () => menuGecmis.click());
        mobilOgretmen.addEventListener('click', () => menuOgretmen.click());
        document.getElementById('sayfa-cikis-btn').addEventListener('click', () => {
            if (confirm('Çıkış yapmak istediğinize emin misiniz?')) window.location.reload();
        });


        // --------------------------------------------------------
        // ZIPGRADE GERÇEKÇİ PDF İNDİRME İŞLEMLERİ (jsPDF)
        // --------------------------------------------------------
        
        // STANDART FORMLAR (20, 50, 100) İÇİN PDF
        function standartPdfIndir(soruSayisi) {
            if (!window.jspdf) return alert("PDF kütüphanesi yüklenemedi. İnternet bağlantınızı kontrol edin.");
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFontSize(16);
            doc.text(soruSayisi + " Soruluk Optik Form", 20, 20);
            
            doc.setFontSize(10);
            doc.text("Ad Soyad: _________________________", 20, 30);
            doc.text("Ogrenci No: ________________________", 110, 30);
            
            let startX = 20, startY = 50, col = 0;
            const secenekler = ['A','B','C','D','E'];
            
            for(let i=1; i<=soruSayisi; i++) {
                if(startY > 270) { startY = 50; col++; startX = 20 + (col * 55); }
                
                doc.text(i+".", startX, startY);
                secenekler.forEach((harf, idx) => {
                    doc.circle(startX + 10 + (idx * 7), startY - 1, 2);
                    doc.setFontSize(6);
                    doc.text(harf, startX + 9 + (idx * 7), startY);
                    doc.setFontSize(10);
                });
                startY += 8;
            }
            doc.save(soruSayisi + "_Soruluk_Optik_Form.pdf");
        }

        // ÖZEL SİHİRBAZ FORMU İÇİN DİNAMİK PDF
        document.getElementById('btnPdfIndir').addEventListener('click', () => {
            if (totalZgQuestions === 0) {
                alert("Lütfen önce 4. Adımdan soru ekleyin!");
                return;
            }
            if (!window.jspdf) return alert("PDF kütüphanesi yüklenemedi. İnternet bağlantınızı kontrol edin.");
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const formAdi = document.getElementById('zg_form_name').value || "Ozel_Optik_Form";
            const etiket = document.getElementById('modalSoruEtiket').value || "ABCDE";
            const secenekler = etiket.split('');
            const soruSayisi = totalZgQuestions;

            doc.setFontSize(16);
            doc.text(formAdi, 20, 20);
            
            doc.setFontSize(10);
            doc.text("Ad Soyad: ___________________________________", 20, 30);
            doc.text("Sinif: ___________   Ogrenci No: _______________", 20, 40);
            
            let startX = 20, startY = 60, col = 0;
            
            for(let i=1; i<=soruSayisi; i++) {
                if (startY > 270) { startY = 60; col++; startX = 20 + (col * 55); }
                
                doc.text(i + ".", startX, startY);
                secenekler.forEach((harf, idx) => {
                    doc.circle(startX + 10 + (idx * 7), startY - 1, 2);
                    doc.setFontSize(6);
                    doc.text(harf, startX + 9 + (idx * 7), startY);
                    doc.setFontSize(10);
                });
                startY += 8;
            }
            doc.save(formAdi + ".pdf");
        });


        // --------------------------------------------------------
        // SİHİRBAZ İŞLEMLERİ (Adım 4 ve Adım 5 Tablo Senkronizasyonu)
        // --------------------------------------------------------
        const soruModal = document.getElementById('soruModal');
        const zgTableBody = document.getElementById('zg_question_body');
        const sorularKonteyner = document.getElementById('sorular-konteyner');
        let totalZgQuestions = 0;

        // Modal Açma / Kapatma
        document.getElementById('btnModalAc').addEventListener('click', () => soruModal.style.display = 'flex');
        document.getElementById('btnModalKapat').addEventListener('click', () => soruModal.style.display = 'none');
        document.getElementById('btnModalIptal').addEventListener('click', () => soruModal.style.display = 'none');

        // Geri Dön Butonu (Vazgeç)
        document.getElementById('btnSihirbazGeri').addEventListener('click', () => {
            document.getElementById('menu-okut').click(); 
        });

        // 4. Adım (Modal'dan Soru Ekleme) ve 5. Adım (Doğru Cevap Tablosu Çizme) Senkronu
        document.getElementById('btnModalEkle').addEventListener('click', () => {
            const eklenecekAdet = parseInt(document.getElementById('modalSoruAdet').value);
            const etiket = document.getElementById('modalSoruEtiket').value;
            
            if(totalZgQuestions === 0) zgTableBody.innerHTML = ''; // İlk eklentide "Boş" mesajını sil
            
            // 4. Adımdaki bilgi tablosunu doldur
            for(let i=0; i<eklenecekAdet; i++) {
                totalZgQuestions++;
                zgTableBody.innerHTML += `
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                        <td class="p-2 font-medium text-slate-600 pl-4">${totalZgQuestions}</td>
                        <td class="p-2 text-slate-500">Dahili Etiket</td>
                        <td class="p-2 font-mono tracking-widest text-slate-700 uppercase">${etiket}</td>
                    </tr>
                `;
            }
            
            soruModal.style.display = 'none';

            // 5. Adımdaki Cevap İşaretleme Tablosunu ÇİZ
            let secenekler = etiket.split('');
            sorularKonteyner.innerHTML = ""; // İçini temizle ve baştan çiz
            
            // Harf başlıklarını ayarla
            let baslikHtml = `<span></span>`;
            secenekler.forEach(h => baslikHtml += `<span class="text-center text-xs font-bold text-slate-500 uppercase">${h}</span>`);
            document.getElementById('soru-harf-basliklari').innerHTML = baslikHtml;
            document.getElementById('soru-harf-basliklari').style.gridTemplateColumns = `40px repeat(${secenekler.length}, 1fr)`;

            // Soru satırlarını oluştur
            for (let i = 1; i <= totalZgQuestions; i++) {
                const satir = document.createElement('div');
                satir.className = "soru-satiri hover:bg-slate-100 transition rounded-lg p-1";
                satir.style.gridTemplateColumns = `40px repeat(${secenekler.length}, 1fr)`;

                let html = `<span class="text-sm font-bold text-slate-600 text-center">${i}.</span>`;
                secenekler.forEach(harf => {
                    html += `<label class="secenek-etiket w-full h-full flex items-center justify-center cursor-pointer">
                                <input type="radio" name="soru_${i}" value="${harf}" class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer">
                             </label>`;
                });
                satir.innerHTML = html;
                sorularKonteyner.appendChild(satir);
            }
        });


        // --------------------------------------------------------
        // PYTHON İÇİN CEVAP ANAHTARI KAYDETME VE OKUMA BAŞLATMA
        // --------------------------------------------------------
        const cevapMesaj = document.getElementById('cevap-mesaj');
        
        function cevapMesajGoster(basarili, metin) {
            cevapMesaj.textContent = metin;
            cevapMesaj.classList.remove('hidden');
            cevapMesaj.className = basarili ? "text-xs rounded-lg p-3 mb-4 bg-emerald-50 text-emerald-700 border border-emerald-200 font-medium" : "text-xs rounded-lg p-3 mb-4 bg-rose-50 text-rose-700 border border-rose-200 font-medium";
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
                        opt.textContent = `${sinav.course_name ?? 'Ders'} — ${sinav.exam_type ?? 'Sınav'}`;
                        select.appendChild(opt);
                    });
                    obsSinavlariYuklendi = true;
                }
            } catch (err) {}
        }

        // Sisteme Kaydetme (Python formatı için gönderim)
        document.getElementById('btnCevapKaydet').addEventListener('click', async () => {
            const sinavAdi = document.getElementById('zg_form_name').value.trim();
            const dersKodu = document.getElementById('zg_ders_kodu').value.trim();
            const obsExamId = document.getElementById('cevap_obs_exam_id').value;

            if (!sinavAdi) return cevapMesajGoster(false, "Lütfen 1. Adımdaki Sınav Adını doldurun.");

            const satirlar = sorularKonteyner.querySelectorAll('.soru-satiri');
            if (satirlar.length === 0) return cevapMesajGoster(false, "Soru sayısı belirlenmemiş. Lütfen önce 4. Adımdan soru ekleyin.");

            const answers = {};
            let eksikVar = false;
            satirlar.forEach((satir, index) => {
                const secili = satir.querySelector(`input[name="soru_${index + 1}"]:checked`);
                if (secili) answers[index + 1] = secili.value;
                else eksikVar = true;
            });

            if (eksikVar) return cevapMesajGoster(false, "Sisteme kaydetmek için lütfen 5. Adımdaki tablodan tüm sorulara bir doğru cevap (şık) işaretleyin.");

            const btn = document.getElementById('btnCevapKaydet');
            btn.disabled = true; btn.textContent = "Sisteme Kaydediliyor...";

            try {
                const res = await fetch("{{ route('panel.cevapkaydet') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ exam_name: sinavAdi, ders_kodu: dersKodu, obs_exam_id: obsExamId || null, answers: answers })
                });
                const veri = await res.json();
                if (veri.success) {
                    cevapMesajGoster(true, "✓ Cevap anahtarı sisteme başarıyla yüklendi. Optik okuma başlayabilir.");
                    cevapAnahtariVarMi = true; guncelleOkumaDurumu();
                    setTimeout(() => document.getElementById('menu-okut').click(), 1200);
                } else {
                    cevapMesajGoster(false, veri.message || "Kayıt başarısız oldu.");
                }
            } catch (err) {
                cevapMesajGoster(false, "Sunucu Bağlantı Hatası: " + err.message);
            } finally {
                btn.disabled = false; btn.textContent = "Sisteme Kaydet ve Optik Oku";
            }
        });

        let cevapAnahtariVarMi = false;
        async function cevapAnahtariKontrolEt() {
            try {
                const res = await fetch("{{ route('panel.cevapgetir') }}");
                const veri = await res.json();
                cevapAnahtariVarMi = !!(veri.success && veri.answers && Object.keys(veri.answers).length > 0);
            } catch (err) { cevapAnahtariVarMi = false; }
            guncelleOkumaDurumu();
        }

        function guncelleOkumaDurumu() {
            const uyari = document.getElementById('uyariCevapYok');
            const btn = document.getElementById('btnFormuOkut');
            if (cevapAnahtariVarMi) { uyari.classList.add('hidden'); btn.disabled = false; } 
            else { uyari.classList.remove('hidden'); btn.disabled = true; }
        }

        // Optik Okutma Kamerası ve Dosya İşlemleri
        const fileInput = document.getElementById('optik_dosya');
        const previewImg = document.getElementById('onizleme-gorsel');
        const video = document.getElementById('webcam');
        let stream = null; let kameraAktif = false;

        fileInput.addEventListener('change', (e) => {
            if(e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (ev) => { previewImg.src = ev.target.result; previewImg.classList.remove('hidden'); }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        document.getElementById('btnYontemKamera').addEventListener('click', async () => {
            document.getElementById('alanDosya').classList.add('hidden');
            document.getElementById('alanKamera').classList.remove('hidden');
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment", width: { ideal: 1600 }, height: { ideal: 1200 } } });
                video.srcObject = stream; kameraAktif = true;
                const track = stream.getVideoTracks()[0];
                if (typeof track.getCapabilities === 'function' && track.getCapabilities().focusMode?.includes('continuous')) {
                    await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] });
                }
            } catch (err) { alert("Kamera donanımına erişilemedi: " + err.message); }
        });

        document.getElementById('btnYontemDosya').addEventListener('click', () => {
            document.getElementById('alanDosya').classList.remove('hidden'); document.getElementById('alanKamera').classList.add('hidden');
            if(stream) stream.getTracks().forEach(t => t.stop());
            kameraAktif = false;
        });

        async function obsKaydetIstegiGonder(ogrenciSonucId) {
            try {
                const res = await fetch("{{ route('panel.obskaydet') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ogrenci_sonuc_id: ogrenciSonucId })
                });
                return await res.json();
            } catch (err) { return { success: false, message: err.message }; }
        }

        document.getElementById('btnFormuOkut').addEventListener('click', async () => {
            if (!cevapAnahtariVarMi) { guncelleOkumaDurumu(); return; }
            const btnOkut = document.getElementById('btnFormuOkut');
            if (btnOkut.disabled) return; 

            btnOkut.disabled = true; btnOkut.textContent = "Fotoğraf Çekiliyor, Sabit Tutun...";
            const formData = new FormData();
            const kameraAlaniAcik = !document.getElementById('alanKamera').classList.contains('hidden');

            if (kameraAlaniAcik && stream) {
                btnOkut.textContent = "Kamera odaklanıyor, sabit tutun...";
                await new Promise(resolve => setTimeout(resolve, 900)); 
                const canvas = document.getElementById('webcam-canvas');
                canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
                formData.append('image', blob, 'kamera_capture.jpg'); formData.append('kaynak', 'kamera');
            } else if (fileInput.files.length > 0) {
                formData.append('image', fileInput.files[0]);
            }

            if (!formData.has('image')) { alert("Lütfen bir dosya yükleyin veya kameradan fotoğraf çekin."); btnOkut.disabled = false; btnOkut.textContent = "Formu Hizala ve Okut"; return; }

            lazer.style.display = 'block'; btnOkut.textContent = "Taranıyor...";
            let veri;
            try {
                const res = await fetch("{{ route('panel.okut') }}", { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: formData });
                veri = await res.json();
            } catch (err) { lazer.style.display = 'none'; btnOkut.disabled = false; btnOkut.textContent = "Formu Hizala ve Okut"; alert("Sunucu Hatası: " + err.message); return; }

            lazer.style.display = 'none'; btnOkut.disabled = false; btnOkut.textContent = "Formu Hizala ve Okut";

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
                    <button id="btnObsKaydet" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2.5 rounded-xl text-xs cursor-pointer transition mt-2">📤 Sonucu OBS'ye Gönder</button>`;
                
                const btnObsKaydet = document.getElementById('btnObsKaydet');
                btnObsKaydet.addEventListener('click', async () => {
                    if (!veri.obs_hazir) { alert("Bu sınav bir OBS sınavıyla eşleştirilmemiş. İşlem yapılamaz."); return; }
                    btnObsKaydet.disabled = true; btnObsKaydet.textContent = "OBS'ye İletiliyor...";
                    const sonuc = await obsKaydetIstegiGonder(veri.ogrenci_sonuc_id);
                    if (sonuc.success) { btnObsKaydet.textContent = "✓ Başarıyla OBS'ye Eklendi"; btnObsKaydet.className = "w-full bg-slate-600 text-white font-bold py-2.5 rounded-xl text-xs mt-2 cursor-not-allowed"; } 
                    else { btnObsKaydet.disabled = false; btnObsKaydet.textContent = "📤 Tekrar Dene (OBS'ye Gönder)"; alert("Hata: " + sonuc.message); }
                });
            } else { alert("Okuma Hatası: " + veri.message); }
        });
    </script>
</body>
</html>