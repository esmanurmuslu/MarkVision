<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarkVision - Yönetim Paneli</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        html, body { overflow-x: hidden; width: 100%; margin: 0; padding: 0; }
        body { min-height: 100vh; font-family: system-ui, -apple-system, sans-serif; background-color: #f8fafc; }
        .modal-bg { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; display: none; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; }
        .tablo-satir:hover { background-color: #f1f5f9; }
        @keyframes onayModalGiris { from { opacity: 0; transform: scale(.94) translateY(6px); } to { opacity: 1; transform: scale(1) translateY(0); } }
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
</script>
<body id="main-body" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 transition-all duration-500">

    <!-- 1. GİRİŞ EKRANI -->
    <div id="login-screen" class="w-full max-w-md bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center text-3xl font-black tracking-wider mb-2">
                <span class="text-blue-400">MARK</span><span class="text-emerald-400 font-light ml-0.5">VISION</span>
            </div>
            <h2 class="text-xl font-bold text-white">Öğretmen Giriş Paneli</h2>
            <p class="text-xs text-slate-300 mt-1">Cevap kağıtları oluşturmak için giriş yapın.</p>
        </div>

        <div id="login-error" class="hidden mb-4 p-3 bg-rose-500/20 border border-rose-500/30 rounded-xl text-rose-200 text-xs text-center font-medium"></div>

        <form id="loginForm" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">E-Posta Adresi</label>
                <input type="email" id="email" required placeholder="ogretmen@universite.edu.tr" class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Şifre</label>
                <div class="relative">
                    <input type="password" id="password" required placeholder="••••••••" class="w-full px-4 py-3 pr-11 bg-white/5 border border-white/10 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500">
                    <button type="button" id="btnSifreGoster" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200 cursor-pointer">👁</button>
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-xl cursor-pointer transition shadow-lg">Giriş Yap</button>
        </form>

        <p class="text-center text-xs text-slate-400 pt-6 border-t border-white/10 mt-6">
            Hesabınız yok mu? 
            <a href="{{ route('register') }}" class="text-blue-400 font-semibold hover:underline">Kayıt Olun</a>
        </p>
    </div>

    <!-- 2. ANA YÖNETİM PANELİ -->
    <div id="main-dashboard" class="hidden mx-auto w-full max-w-7xl bg-slate-50 text-slate-800 rounded-lg overflow-hidden shadow-2xl flex flex-col min-h-[90vh]">
        
        <!-- ÜST MENÜ BAR -->
        <header class="bg-[#34495e] text-slate-200">
            <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
                <div class="text-xl font-black tracking-wider text-emerald-400 flex items-center gap-2">
                    <span class="bg-emerald-500 text-white rounded-full w-6 h-6 inline-flex items-center justify-center text-sm">✔</span>
                    MARK<span class="text-white font-light">VISION</span>
                </div>
                <div class="relative" id="user-menu-wrapper">
                    <button type="button" onclick="kullaniciMenusuAcKapat(event)" class="text-xs flex items-center gap-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full pl-1.5 pr-3.5 py-1.5 cursor-pointer transition">
                        <span class="w-7 h-7 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs" id="user-avatar-badge">Ö</span>
                        <span class="text-slate-300">Aktif Kullanıcı: <span id="user-display-name" class="font-bold text-white">Öğretmen</span></span>
                        <span id="user-menu-caret" class="text-slate-400 text-[9px] transition-transform">▾</span>
                    </button>
                    <div id="user-dropdown-menu" class="hidden absolute right-0 top-full mt-2 w-48 bg-slate-800 border border-slate-700/80 rounded-xl shadow-2xl overflow-hidden z-50 py-1.5">
                        <button type="button" onclick="kullaniciMenusundenHesabimaGit()" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-left text-slate-200 hover:bg-slate-700 text-xs font-semibold cursor-pointer transition">
                            <span class="w-5 text-blue-400">👤</span> Hesabım
                        </button>
                        <button type="button" onclick="window.location.reload()" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-left text-rose-300 hover:bg-slate-700 text-xs font-semibold cursor-pointer transition">
                            <span class="w-5 text-rose-400">🔑</span> Çıkış Yap
                        </button>
                    </div>
                </div>
            </div>
            <!-- SEKMELER -->
            <div class="bg-[#2c3e50] border-t border-slate-600">
                <nav class="max-w-7xl mx-auto px-6 flex items-center gap-1 text-sm font-semibold">
                    <button onclick="sekmeDegistir('view-quizzes')" id="nav-quizzes" class="px-5 py-3 bg-slate-100 text-slate-800 rounded-t-lg transition cursor-pointer">Sınavlar (Quizzes)</button>
                    <button onclick="sekmeDegistir('view-classes')" id="nav-classes" class="px-5 py-3 hover:bg-slate-600 text-slate-300 rounded-t-lg transition cursor-pointer">Sınıflar (Classes)</button>
                    <button onclick="sekmeDegistir('view-students')" id="nav-students" class="px-5 py-3 hover:bg-slate-600 text-slate-300 rounded-t-lg transition cursor-pointer">Öğrenciler (Students)</button>
                    <button onclick="sekmeDegistir('view-answer-sheets')" id="nav-answer-sheets" class="px-5 py-3 hover:bg-slate-600 text-slate-300 rounded-t-lg transition cursor-pointer">Optik Formlar (Answer Sheets)</button>
                    <button onclick="sekmeDegistir('view-gecmis')" id="nav-gecmis" class="px-5 py-3 hover:bg-slate-600 text-slate-300 rounded-t-lg transition cursor-pointer">Geçmiş Sonuçlar</button>
                    <button onclick="sekmeDegistir('view-account')" id="nav-account" class="px-5 py-3 hover:bg-slate-600 text-slate-300 rounded-t-lg transition cursor-pointer">Hesabım</button>
                </nav>
            </div>
        </header>

        <!-- İÇERİK ALANI -->
        <main class="max-w-6xl w-full mx-auto p-8 flex-1 space-y-6">
            
            <!-- 1. SINAVLAR SEKMESİ -->
            <div id="view-quizzes" class="sekme-icerik space-y-6">
                <h2 class="text-2xl font-light text-center text-slate-700">Tüm Sınavlar</h2>
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2">
                        <button onclick="panelSinavlariSil()" class="px-3 py-1.5 bg-white border border-slate-300 text-slate-600 text-xs rounded hover:bg-slate-50 cursor-pointer">Seçilenleri Sil</button>
                        <button onclick="sekmeDegistir('view-answer-sheets')" class="px-3 py-1.5 bg-emerald-600 text-white font-bold text-xs rounded hover:bg-emerald-700 cursor-pointer">+ Yeni Sınav / Form Oluştur</button>
                    </div>
                </div>
                <div class="bg-white border border-slate-300 rounded overflow-hidden shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#4b5563] text-white text-xs">
                            <tr>
                                <th class="p-3 w-10 text-center"><input type="checkbox" id="sinav-hepsi-sec" onchange="panelSinavHepsiniSecToggle(this)"></th>
                                <th class="p-3 border-l border-slate-500">Sınıf</th>
                                <th class="p-3 border-l border-slate-500">Sınav Adı</th>
                                <th class="p-3 border-l border-slate-500">Tarih</th>
                                <th class="p-3 border-l border-slate-500">Soru Sayısı</th>
                            </tr>
                        </thead>
                      <tbody id="sinav-tablo-govde" class="text-slate-600 text-xs">
    <tr><td colspan="5" class="p-4 text-center text-slate-400">Yükleniyor...</td></tr>
</tbody>
                    </table>
                </div>
            </div>

            <!-- 2. SINIFLAR SEKMESİ -->
            <div id="view-classes" class="sekme-icerik hidden space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-light text-slate-700">Tüm Sınıflar</h2>
                    <button onclick="sinifEkleModalAc()" class="bg-[#2c3e50] hover:bg-slate-800 text-white px-4 py-2 rounded text-xs font-bold cursor-pointer">+ Yeni Sınıf Ekle</button>
                </div>
                <div class="bg-white border border-slate-300 rounded overflow-hidden shadow-sm p-4 text-xs">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 text-slate-600 font-bold">
                            <tr><th class="p-2">Sınıf Adı</th><th class="p-2">Öğrenci Sayısı</th><th class="p-2">İşlemler</th></tr>
                        </thead>
                        <tbody id="sinif-tablo-govde">
                            <tr><td colspan="3" class="p-4 text-center text-slate-400">Yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. ÖĞRENCİLER SEKMESİ -->
            <div id="view-students" class="sekme-icerik hidden space-y-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-light text-slate-700">Tüm Öğrenciler</h2>
                    <button onclick="ogrenciEkleModalAc()" class="bg-[#2c3e50] hover:bg-slate-800 text-white px-4 py-2 rounded text-xs font-bold cursor-pointer">+ Yeni Öğrenci Ekle</button>
                </div>
                <div class="bg-white border border-slate-300 rounded overflow-hidden shadow-sm p-4 text-xs">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 text-slate-600 font-bold">
                            <tr><th class="p-2">Öğrenci No</th><th class="p-2">Adı Soyadı</th><th class="p-2">Sınıfı</th><th class="p-2">İşlemler</th></tr>
                        </thead>
                        <tbody id="ogrenci-tablo-govde">
                            <tr><td colspan="4" class="p-4 text-center text-slate-400">Yükleniyor...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. OPTİK FORMLAR SEKMESİ -->
            <div id="view-answer-sheets" class="sekme-icerik hidden space-y-6">
                <div class="flex justify-between items-center border-b pb-4">
                    <div>
                        <h2 class="text-2xl font-light text-slate-700">Optik Form Tasarımcısı</h2>
                        <p class="text-xs text-slate-500 mt-1">Standart hazır şablonlar indirebilir veya özel form sihirbazını kullanabilirsiniz.</p>
                    </div>
                    <button onclick="sekmeAcSihirbaz()" class="bg-[#2c3e50] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-lg text-xs shadow transition cursor-pointer">+ Özel Form Sihirbazı</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                    <div class="border border-slate-200 rounded-xl p-5 bg-white flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="text-center font-bold text-slate-700 text-sm mb-2">20 Soru Formu</div>
                            <ul class="text-[11px] text-slate-600 space-y-1 mb-4">
                                <li>• Maksimum Soru: <b>20</b></li>
                                <!-- DÜZELTİLDİ: Öğrenci Numarası Var (5 Hane) olarak güncellendi -->
                                <li>• Öğrenci No: <b class="text-emerald-600">Var (5 Hane)</b></li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <button onclick="standartPdfIndir(20)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg text-xs shadow cursor-pointer">PDF İNDİR</button>
                            <button onclick="standartPngAc(20)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs shadow cursor-pointer">PNG AÇ / KAYDET</button>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-xl p-5 bg-white flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="text-center font-bold text-slate-700 text-sm mb-2">50 Soru Formu</div>
                            <ul class="text-[11px] text-slate-600 space-y-1 mb-4">
                                <li>• Maksimum Soru: <b>50</b></li>
                                <li>• Öğrenci No: <b>Var (5 Hane)</b></li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <button onclick="standartPdfIndir(50)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg text-xs shadow cursor-pointer">PDF İNDİR</button>
                            <button onclick="standartPngAc(50)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs shadow cursor-pointer">PNG AÇ / KAYDET</button>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-xl p-5 bg-white flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="text-center font-bold text-slate-700 text-sm mb-2">100 Soru Formu</div>
                            <ul class="text-[11px] text-slate-600 space-y-1 mb-4">
                                <li>• Maksimum Soru: <b>100</b></li>
                                <li>• Öğrenci No: <b>Var (9 Hane)</b></li>
                            </ul>
                        </div>
                        <div class="space-y-2">
                            <button onclick="standartPdfIndir(100)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg text-xs shadow cursor-pointer">PDF İNDİR</button>
                            <button onclick="standartPngAc(100)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs shadow cursor-pointer">PNG AÇ / KAYDET</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ÖZEL FORM SİHİRBAZI -->
            <div id="view-sihirbaz" class="sekme-icerik hidden max-w-4xl mx-auto space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-[#2c3e50] p-4 text-white flex items-center justify-between">
                        <!-- Güncellendi: 6 Adım Oldu -->
                        <h2 class="text-sm font-bold" id="sihirbaz-baslik">Adım 1 / 6: Form Adı</h2>
                        <span class="text-xs opacity-80">MarkVision Tasarımcısı</span>
                    </div>

                    <div class="p-8 space-y-6 text-sm">
                        <div id="adim-1" class="sihirbaz-adim space-y-4">
                            <label class="block text-xs font-bold text-slate-600 uppercase">Sınav / Form Adı</label>
                            <input type="text" id="wiz_form_name" placeholder="Örn: Fizik Vize Sınavı" class="w-full border border-slate-300 rounded-lg p-3 text-sm bg-white">
                        </div>

                        <div id="adim-2" class="sihirbaz-adim hidden space-y-4">
                            <h3 class="font-bold text-slate-700 text-xs uppercase">Üst Bilgi Alanları</h3>
                            <table class="w-full text-xs border border-slate-200">
                                <thead class="bg-slate-100"><tr><th class="p-2 border">Alan</th><th class="p-2 border text-center">Aktif mi?</th><th class="p-2 border">Etiket</th></tr></thead>
                                <tbody>
                                    <tr><td class="p-2 border">Ad Soyad</td><td class="p-2 border text-center"><input type="checkbox" checked id="box_name" class="w-4 h-4"></td><td class="p-2 border"><input type="text" value="Ad Soyad" id="lbl_name" class="border rounded p-1 w-full text-xs bg-white"></td></tr>
                                    <tr><td class="p-2 border">Sınıf</td><td class="p-2 border text-center"><input type="checkbox" checked id="box_class" class="w-4 h-4"></td><td class="p-2 border"><input type="text" value="Sinif" id="lbl_class" class="border rounded p-1 w-full text-xs bg-white"></td></tr>
                                    <tr><td class="p-2 border">Sınav Adı</td><td class="p-2 border text-center"><input type="checkbox" checked id="box_quiz" class="w-4 h-4"></td><td class="p-2 border"><input type="text" value="Sinav Adi" id="lbl_quiz" class="border rounded p-1 w-full text-xs bg-white"></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="adim-3" class="sihirbaz-adim hidden space-y-4">
                            <div class="border p-4 rounded-lg bg-slate-50 space-y-3">
                                <label class="flex items-center gap-2 font-bold text-xs"><input type="checkbox" id="wiz_has_student_id" checked class="w-4 h-4"> Öğrenci Numarası Alanı Ekle</label>
                                <div class="flex items-center gap-4 text-xs">
                                    <span>Hane Sayısı:</span>
                                    <select id="wiz_id_digits" class="border rounded p-1.5 bg-white"><option value="5">5</option><option value="9" selected>9</option><option value="11">11</option></select>
                                </div>
                            </div>
                        </div>

                        <div id="adim-4" class="sihirbaz-adim hidden space-y-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-xs uppercase text-slate-700">Soru Blokları</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-slate-600">Ceza Katsayısı:</span>
                                    <input type="number" id="wiz_penalty_coef" value="0" step="0.25" min="0" max="1" class="border rounded p-1 w-20 text-xs bg-white text-center font-bold" title="Sınav kuralına göre yanlış cevapların neti düşürme oranıdır. (Örn: 4 yanlış 1 doğruyu götürüyorsa 0.25, 3 yanlış 1 doğruyu götürüyorsa 0.33, yanlış doğruyu götürmüyorsa 0 giriniz.)">
                                </div>
                                <button type="button" id="btnSoruBlokEkle" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-2 rounded text-xs cursor-pointer">+ Soru Bloğu Ekle</button>
                            </div>
                            <p class="text-[11px] text-slate-500 italic -mt-2">
                                Sınav kuralına göre yanlış cevapların neti düşürme oranıdır. (Örn: 4 yanlış 1 doğruyu götürüyorsa 0.25, 3 yanlış 1 doğruyu götürüyorsa 0.33, yanlış doğruyu götürmüyorsa 0 giriniz.)
                            </p>
                            <table class="w-full text-xs border border-slate-200">
                                <thead class="bg-slate-100"><tr><th class="p-2 border">Soru Adeti</th><th class="p-2 border">Şıklar</th><th class="p-2 border">Puan</th></tr></thead>
                                <tbody id="wiz_question_list">
                                    <tr><td colspan="3" class="p-4 text-center text-slate-400 italic">Henüz soru eklenmedi.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- ADIM 5: C KİŞİSİ - CEVAP ANAHTARI GİRİŞİ (YENİ EKLENDİ) -->
                        <div id="adim-5" class="sihirbaz-adim hidden space-y-4">
                            <h3 class="font-bold text-xs uppercase text-slate-700">Cevap Anahtarı Belirleme</h3>
                            <p class="text-xs text-slate-500">Her soru için doğru şıkkı seçiniz (Otomatik 'A' ataması kaldırılmıştır).</p>
                            <div id="cevap-anahtari-container" class="max-h-64 overflow-y-auto border border-slate-200 rounded-lg p-3 space-y-2 bg-slate-50"></div>
                        </div>

                        <div id="adim-6" class="sihirbaz-adim hidden space-y-4 text-center">
                            <h3 class="font-bold text-sm text-slate-800">Tebrikler! Tasarım Hazır</h3>
                            <p class="text-xs text-slate-500">Formu kaydetmek ve indirmek için Yayınla butonuna basın.</p>
                            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-xs font-semibold">
                                Sınav Adı: <span id="onizleme_ad" class="font-bold"></span> | Toplam Soru: <span id="onizleme_soru" class="font-bold"></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-100 p-4 border-t flex justify-between items-center">
                        <button type="button" id="wizBtnBack" class="px-4 py-2 bg-white border rounded text-xs font-semibold text-slate-600 cursor-pointer">← Geri</button>
                        <button type="button" id="wizBtnNext" class="px-5 py-2 bg-[#2c3e50] hover:bg-slate-800 text-white rounded text-xs font-bold cursor-pointer">İleri →</button>
                    </div>
                </div>
            </div>

            <!-- 5. GEÇMİŞ SONUÇLAR -->
            <div id="view-gecmis" class="sekme-icerik hidden space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 flex justify-between items-center shadow-sm">
                    <div><h2 class="text-lg font-bold text-slate-800">Geçmiş Sınav Sonuçları</h2><p class="text-xs text-slate-400 mt-1">Sistemdeki tüm okutulmuş sınavlar</p></div>
                    <a href="{{ route('panel.export') }}" class="bg-emerald-600 text-white font-bold py-2 px-4 rounded-xl text-xs shadow">📥 Excel İndir</a>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-100 font-bold text-slate-600"><tr><th class="p-3">ID</th><th class="p-3">Sınav Adı</th><th class="p-3">Doğru/Yanlış</th><th class="p-3">Puan</th><th class="p-3">Tarih</th></tr></thead>
                        <tbody id="gecmis-tablo-body"></tbody>
                    </table>
                </div>
            </div>

            <!-- 6. HESABIM (YENİDEN TASARLANDI) -->
            <div id="view-account" class="sekme-icerik hidden max-w-2xl mx-auto space-y-6">
                <h2 class="text-2xl font-light text-slate-700">Hesabım</h2>

                <!-- PROFİL KARTI -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-[#2c3e50] to-[#34495e] p-6 flex items-center gap-4">
                        <div id="hesap-avatar" class="w-16 h-16 rounded-full bg-emerald-500 text-white flex items-center justify-center text-2xl font-black shadow-lg ring-4 ring-white/10 shrink-0">Ö</div>
                        <div class="min-w-0">
                            <div id="hesap-adsoyad" class="font-bold text-white text-lg truncate">-</div>
                            <div id="hesap-email" class="text-slate-300 text-sm truncate">-</div>
                        </div>
                    </div>
                    <div class="p-4 flex justify-end">
                        <button type="button" onclick="window.location.reload()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold text-xs cursor-pointer transition">
                            <span>↩</span> Çıkış Yap
                        </button>
                    </div>
                </div>

                <!-- ŞİFRE DEĞİŞTİR -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">🔒</span>
                        <div>
                            <div class="font-bold text-slate-700 text-sm">Şifre Değiştir</div>
                            <div class="text-[11px] text-slate-400">Hesabının güvenliği için şifreni güncel tut</div>
                        </div>
                    </div>
                    <div class="p-6 space-y-4 text-xs">
                        <div>
                            <label class="block font-semibold mb-1.5 text-slate-600">Mevcut Şifre</label>
                            <input type="password" id="hesap-eski-sifre" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition" placeholder="••••••••">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold mb-1.5 text-slate-600">Yeni Şifre</label>
                                <input type="password" id="hesap-yeni-sifre" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition" placeholder="En az 6 karakter">
                            </div>
                            <div>
                                <label class="block font-semibold mb-1.5 text-slate-600">Yeni Şifre (Tekrar)</label>
                                <input type="password" id="hesap-yeni-sifre-tekrar" class="w-full border border-slate-200 rounded-lg p-2.5 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition" placeholder="••••••••">
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-end">
                        <button type="button" onclick="hesapSifreDegistir()" class="px-5 py-2.5 rounded-lg bg-[#2c3e50] hover:bg-[#1a252f] text-white font-bold text-xs cursor-pointer shadow-sm shadow-slate-900/30 transition">Şifreyi Güncelle</button>
                    </div>
                </div>

                <!-- Hesabı Sil (YENİDEN TASARLANDI) -->
                <div class="bg-white border border-rose-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-rose-100 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm">🗑</span>
                        <div>
                            <div class="font-bold text-rose-700 text-sm">Hesabı Sil</div>
                            <div class="text-[11px] text-rose-400">Bu işlem geri alınamaz</div>
                        </div>
                    </div>
                    <div class="p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-xs text-slate-500 text-center sm:text-left">Hesabınızı ve verilerinizi sistemimizden kalıcı olarak silmek istiyorsanız, lütfen bu adımları izleyin ve şifrenizle onaylayın.</p>
                        <button type="button" onclick="document.getElementById('hesabiSilModal').style.display='flex'" class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-6 py-2.5 rounded-lg text-xs shadow-sm shadow-rose-500/30 cursor-pointer transition shrink-0">Hesabı Sil</button>
                    </div>
                </div>
            </div>

        </main>

        <footer class="bg-[#34495e] text-slate-400 text-[11px] py-8 border-t border-slate-700 mt-auto">
            <div class="max-w-6xl mx-auto px-6 grid grid-cols-3 gap-8 text-xs">
                <div><h4 class="text-emerald-400 font-bold mb-2">MARKVISION İNDİR</h4><p class="mb-2">iPhone ve Android için</p></div>
                <div><h4 class="text-emerald-400 font-bold mb-2">ÜRÜN</h4><ul class="space-y-1"><li>Fiyatlandırma</li><li>Optik Formlar</li><li>Destek</li></ul></div>
                <div><h4 class="text-emerald-400 font-bold mb-2">ŞİRKET:</h4><ul class="space-y-1"><li>Hakkımızda</li><li>Gizlilik Politikası</li><li>Kullanım Şartları</li></ul></div>
            </div>
        </footer>
    </div>

    <!-- MODAL: Sınıf Ekleme -->
    <div class="modal-bg" id="sinifEkleModal">
        <div class="modal-content">
            <div class="bg-slate-100 p-4 border-b font-bold text-slate-700 text-sm flex justify-between items-center">
                <span>Yeni Sınıf Ekle</span>
                <button type="button" onclick="modalKapat('sinifEkleModal')" class="cursor-pointer text-lg font-bold">×</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div><label class="block font-semibold mb-1">Sınıf Adı</label><input type="text" id="yeni_sinif_adi" placeholder="Örn: 10-A" class="w-full border rounded p-2 text-xs bg-white"></div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-end gap-2">
                <button type="button" onclick="modalKapat('sinifEkleModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">İptal</button>
                <button type="button" onclick="panelSinifKaydet()" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Kaydet</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Öğrenci Ekleme -->
    <div class="modal-bg" id="ogrenciEkleModal">
        <div class="modal-content">
            <div class="bg-slate-100 p-4 border-b font-bold text-slate-700 text-sm flex justify-between items-center">
                <span>Yeni Öğrenci Ekle</span>
                <button type="button" onclick="modalKapat('ogrenciEkleModal')" class="cursor-pointer text-lg font-bold">×</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div><label class="block font-semibold mb-1">Öğrenci Numarası</label><input type="text" id="yeni_ogr_no" placeholder="Örn: 101" class="w-full border rounded p-2 text-xs bg-white"></div>
                <div><label class="block font-semibold mb-1">Adı Soyadı</label><input type="text" id="yeni_ogr_ad" placeholder="Örn: Ahmet Yılmaz" class="w-full border rounded p-2 text-xs bg-white"></div>
                <div><label class="block font-semibold mb-1">Sınıfı</label>
                    <select id="yeni_ogr_sinif" class="w-full border rounded p-2 text-xs bg-white">
                        <option value="">Sınıf seçin (opsiyonel)</option>
                    </select>
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-end gap-2">
                <button type="button" onclick="modalKapat('ogrenciEkleModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">İptal</button>
                <button type="button" onclick="panelOgrenciKaydet()" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Kaydet</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Sınıf Düzenle -->
    <div class="modal-bg" id="sinifDuzenleModal">
        <div class="modal-content">
            <div class="bg-slate-100 p-4 border-b font-bold text-slate-700 text-sm flex justify-between items-center">
                <span>Sınıfı Düzenle</span>
                <button type="button" onclick="modalKapat('sinifDuzenleModal')" class="cursor-pointer text-lg font-bold">×</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <input type="hidden" id="duzenle_sinif_id">
                <div><label class="block font-semibold mb-1">Sınıf Adı</label><input type="text" id="duzenle_sinif_adi" class="w-full border rounded p-2 text-xs bg-white"></div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-between items-center">
                <button type="button" onclick="panelSinifSilOnayla()" class="px-4 py-2 rounded bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs">Sil</button>
                <div class="flex gap-2">
                    <button type="button" onclick="modalKapat('sinifDuzenleModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">İptal</button>
                    <button type="button" onclick="panelSinifGuncelleKaydet()" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Öğrenci Düzenle -->
    <div class="modal-bg" id="ogrenciDuzenleModal">
        <div class="modal-content">
            <div class="bg-slate-100 p-4 border-b font-bold text-slate-700 text-sm flex justify-between items-center">
                <span>Öğrenciyi Düzenle</span>
                <button type="button" onclick="modalKapat('ogrenciDuzenleModal')" class="cursor-pointer text-lg font-bold">×</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <input type="hidden" id="duzenle_ogr_id">
                <div><label class="block font-semibold mb-1">Öğrenci Numarası</label><input type="text" id="duzenle_ogr_no" class="w-full border rounded p-2 text-xs bg-white"></div>
                <div><label class="block font-semibold mb-1">Adı Soyadı</label><input type="text" id="duzenle_ogr_ad" class="w-full border rounded p-2 text-xs bg-white"></div>
                <div><label class="block font-semibold mb-1">Sınıfı</label><select id="duzenle_ogr_sinif" class="w-full border rounded p-2 text-xs bg-white"><option value="">— Sınıf Seçin (opsiyonel) —</option></select></div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-between items-center">
                <button type="button" onclick="panelOgrenciSilOnayla()" class="px-4 py-2 rounded bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs">Sil</button>
                <div class="flex gap-2">
                    <button type="button" onclick="modalKapat('ogrenciDuzenleModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">İptal</button>
                    <button type="button" onclick="panelOgrenciGuncelleKaydet()" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Onay Penceresi -->
    <div class="modal-bg" id="onayModal" style="z-index: 10000;">
        <div class="modal-content max-w-sm" style="animation: onayModalGiris .15s ease-out;">
            <div class="p-6 text-center">
                <div class="mx-auto mb-4 w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center">
                    <span class="text-rose-600 text-2xl">⚠</span>
                </div>
                <h3 id="onayModalBaslik" class="text-base font-bold text-slate-800 mb-1">Emin misiniz?</h3>
                <p id="onayModalMesaj" class="text-xs text-slate-500">Bu işlem geri alınamaz.</p>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-center gap-3">
                <button type="button" onclick="onayModalSonucVer(false)" class="px-5 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 text-xs font-semibold hover:bg-slate-100 cursor-pointer transition">Vazgeç</button>
                <button type="button" onclick="onayModalSonucVer(true)" class="px-5 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer transition shadow-sm">Evet, Sil</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Bilgi/Uyarı Penceresi -->
    <div class="modal-bg" id="bilgiModal" style="z-index: 10000;">
        <div class="modal-content max-w-sm" style="animation: onayModalGiris .15s ease-out;">
            <div class="p-6 text-center">
                <div id="bilgiModalIkonKutu" class="mx-auto mb-4 w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center">
                    <span id="bilgiModalIkon" class="text-rose-600 text-2xl">⚠</span>
                </div>
                <p id="bilgiModalMesaj" class="text-sm text-slate-700 font-medium"></p>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-center">
                <button type="button" onclick="bilgiModalKapat()" class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold cursor-pointer transition shadow-sm">Tamam</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Hesabı Sil -->
    <div class="modal-bg" id="hesabiSilModal" style="z-index: 10000;">
        <div class="modal-content max-w-sm" style="animation: onayModalGiris .15s ease-out;">
            <div class="bg-slate-100 p-4 border-b font-bold text-slate-700 text-sm flex justify-between items-center">
                <span>Hesabı Sil</span>
                <button type="button" onclick="modalKapat('hesabiSilModal')" class="cursor-pointer text-lg font-bold">×</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <p class="text-rose-600 font-semibold">Bu işlem geri alınamaz. Hesabınız ve tüm sınav / sınıf / öğrenci verileriniz kalıcı olarak silinecek.</p>
                <div>
                    <label class="block font-semibold mb-1 text-slate-600">Şifreniz</label>
                    <input type="password" id="hesabi-sil-sifre" class="w-full border rounded p-2 text-xs bg-white" placeholder="••••••••">
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-end gap-2">
                <button type="button" onclick="modalKapat('hesabiSilModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">Vazgeç</button>
                <button type="button" onclick="hesabiSilOnayla()" class="px-5 py-2 rounded bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs">Evet, Kalıcı Olarak Sil</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Soru Ekleme -->
    <div class="modal-bg" id="soruEkleModal">
        <div class="modal-content">
            <div class="bg-slate-100 p-4 border-b font-bold text-slate-700 text-sm flex justify-between items-center">
                <span>Soru Bloğu Ekle</span>
                <button type="button" onclick="modalKapat('soruEkleModal')" class="cursor-pointer text-lg font-bold">×</button>
            </div>
            <div class="p-6 space-y-4 text-xs">
                <div><label class="block font-semibold mb-1">Soru Sayısı</label><input type="number" id="modal_adet" value="20" min="1" max="100" class="w-full border rounded p-2 text-xs bg-white"></div>
                <div><label class="block font-semibold mb-1">Şık Etiketleri</label><input type="text" id="modal_etiket" value="ABCDE" class="w-full border rounded p-2 text-xs font-mono uppercase bg-white"></div>
                <div><label class="block font-semibold mb-1 text-emerald-700">Soru Başına Puan</label><input type="number" id="modal_puan" value="5" step="0.5" class="w-full border border-emerald-300 rounded p-2 text-xs font-bold text-emerald-700 bg-white"></div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-end gap-2">
                <button type="button" onclick="modalKapat('soruEkleModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">İptal</button>
                <button type="button" id="modalEkleBtn" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Ekle</button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT MOTORU -->
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginScreen = document.getElementById('login-screen');
        const mainDashboard = document.getElementById('main-dashboard');
        const mainBody = document.getElementById('main-body');
        let girisYapanKullanici = null; 

        document.getElementById('btnSifreGoster').addEventListener('click', () => {
            const inp = document.getElementById('password');
            inp.type = inp.type === 'text' ? 'password' : 'text';
        });

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch("{{ route('panel.login') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ email: document.getElementById('email').value, password: document.getElementById('password').value })
            });
            const data = await res.json();
            if (data.success) {
                loginScreen.classList.add('hidden');
                mainDashboard.classList.remove('hidden');
                mainBody.className = "bg-[#f8fafc] text-slate-800 min-h-screen transition-all duration-500";
                document.getElementById('user-display-name').textContent = data.user.ad;
                
                const avatarBadge = document.getElementById('user-avatar-badge');
                if (avatarBadge) avatarBadge.textContent = (data.user.ad || '?').trim().charAt(0).toUpperCase();
                
                girisYapanKullanici = data.user;
                const hesapAdSoyad = document.getElementById('hesap-adsoyad');
                const hesapEmail = document.getElementById('hesap-email');
                const hesapAvatar = document.getElementById('hesap-avatar');
                const fullName = [data.user.ad, data.user.soyad].filter(Boolean).join(' ') || '-';
                if (hesapAdSoyad) hesapAdSoyad.textContent = fullName;
                if (hesapEmail) hesapEmail.textContent = data.user.email || '-';
                if (hesapAvatar) hesapAvatar.textContent = (data.user.ad || '?').trim().charAt(0).toUpperCase();
                
                panelSinavlariDoldur();
                panelSiniflariDoldur();
                panelOgrencileriDoldur();
            } else {
                const err = document.getElementById('login-error');
                err.textContent = data.message;
                err.classList.remove('hidden');
            }
        });

        function kullaniciMenusuAcKapat(event) {
            if (event) event.stopPropagation();
            const menu = document.getElementById('user-dropdown-menu');
            const caret = document.getElementById('user-menu-caret');
            const acik = !menu.classList.contains('hidden');
            menu.classList.toggle('hidden', acik);
            if (caret) caret.style.transform = acik ? 'rotate(0deg)' : 'rotate(180deg)';
        }
        document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('user-menu-wrapper');
            const menu = document.getElementById('user-dropdown-menu');
            if (!wrapper || !menu || menu.classList.contains('hidden')) return;
            if (!wrapper.contains(e.target)) {
                menu.classList.add('hidden');
                const caret = document.getElementById('user-menu-caret');
                if (caret) caret.style.transform = 'rotate(0deg)';
            }
        });
        function kullaniciMenusundenHesabimaGit() {
            document.getElementById('user-dropdown-menu').classList.add('hidden');
            sekmeDegistir('view-account');
        }

        function sekmeDegistir(hedefId) {
            document.querySelectorAll('.sekme-icerik').forEach(el => el.classList.add('hidden'));
            document.getElementById(hedefId).classList.remove('hidden');

            document.querySelectorAll('header nav button').forEach(btn => {
                btn.className = "px-5 py-3 hover:bg-slate-600 text-slate-300 rounded-t-lg transition cursor-pointer";
            });

            const aktifBtn = document.getElementById('nav-' + hedefId.replace('view-', ''));
            if(aktifBtn) {
                aktifBtn.className = "px-5 py-3 bg-slate-100 text-slate-800 font-bold rounded-t-lg transition cursor-pointer";
            }
            
            if(hedefId === 'view-gecmis') {
                gecmisTablosunuDoldur();
            }
            
            if(hedefId === 'view-classes') {
                panelSiniflariDoldur();
            }
            if(hedefId === 'view-students') {
                panelOgrencileriDoldur();
            }
            if(hedefId === 'view-quizzes') {
                panelSinavlariDoldur();
            }
        }

        async function gecmisTablosunuDoldur() {
            try {
                const res = await fetch("{{ route('panel.gecmis') }}"); 
                const d = await res.json();
                const tb = document.getElementById('gecmis-tablo-body'); tb.innerHTML = "";
                if (d.success && d.data && d.data.length > 0) {
                    d.data.forEach(i => {
                        tb.innerHTML += `<tr class="border-b"><td class="p-3 font-bold">#${i.id}</td><td class="p-3">${i.exam_name}</td><td class="p-3 text-emerald-600 font-bold">${i.correct_count}D / ${i.wrong_count}Y</td><td class="p-3 font-black text-blue-600">${i.total_score} Puan</td><td class="p-3 text-slate-400">${new Date(i.created_at).toLocaleString('tr-TR')}</td></tr>`;
                    });
                } else { tb.innerHTML = `<tr><td colspan="5" class="p-6 text-center text-slate-400">Kayıt bulunamadı.</td></tr>`; }
            } catch (err) {
                console.warn("Geçmiş sonuçlar yüklenemedi: " + err.message);
            }
        }

        function sekmeAcSihirbaz() {
            sekmeDegistir('view-sihirbaz');
            aktifAdim = 1; sihirbazSorular = [];
            sihirbazGuncelle();
        }

        function sinifEkleModalAc() { document.getElementById('sinifEkleModal').style.display = 'flex'; }
        function ogrenciEkleModalAc() { document.getElementById('ogrenciEkleModal').style.display = 'flex'; }
        function modalKapat(id) { document.getElementById(id).style.display = 'none'; }

        let apiSiniflarCache = [];

        async function siniflariGetirVeDoldur() {
            const tbody = document.getElementById('sinif-tablo-govde');
            const secim = document.getElementById('yeni_ogr_sinif');
            try {
                const res = await fetch('/api/v1/classes');
                const data = await res.json();
                apiSiniflarCache = (data.success && Array.isArray(data.data)) ? data.data : [];

                if (apiSiniflarCache.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="3" class="p-4 text-center text-slate-400">Henüz sınıf eklenmedi.</td></tr>`;
                } else {
                    tbody.innerHTML = apiSiniflarCache.map(s => {
                        const ad = s.class_name ?? s.isim ?? 'İsimsiz Sınıf';
                        return `<tr class="border-t"><td class="p-3">${ad}</td><td class="p-3">-</td><td class="p-3"><button onclick="sinifSil(${s.id})" class="text-rose-600 cursor-pointer font-semibold">Sil</button></td></tr>`;
                    }).join('');
                }

                if (secim) {
                    const eskiSecili = secim.value;
                    secim.innerHTML = '<option value="">Sınıf seçin (opsiyonel)</option>' +
                        apiSiniflarCache.map(s => `<option value="${s.id}">${s.class_name ?? s.isim ?? 'İsimsiz Sınıf'}</option>`).join('');
                    secim.value = eskiSecili;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="3" class="p-4 text-center text-rose-500">Sınıflar yüklenemedi: ${err.message}</td></tr>`;
            }
        }

        async function sinifKaydet() {
            const adInput = document.getElementById('yeni_sinif_adi');
            const ad = adInput.value.trim();
            if(!ad) return alert("Sınıf adı giriniz!");
            try {
                const res = await fetch('/api/v1/classes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class_name: ad })
                });
                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Sınıf kaydedilemedi.');
                    return;
                }
                modalKapat('sinifEkleModal');
                adInput.value = "";
                await siniflariGetirVeDoldur();
            } catch (err) {
                alert('Sınıf kaydedilirken hata oluştu: ' + err.message);
            }
        }

        async function sinifSil(id) {
            if (!confirm('Bu sınıfı silmek istediğine emin misin?')) return;
            try {
                const res = await fetch(`/api/v1/classes/${id}`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Sınıf silinemedi.');
                    return;
                }
                await siniflariGetirVeDoldur();
            } catch (err) {
                alert('Sınıf silinirken hata oluştu: ' + err.message);
            }
        }

        async function ogrencileriGetirVeDoldur() {
            const tbody = document.getElementById('ogrenci-tablo-govde');
            try {
                const res = await fetch('/api/v1/students');
                const data = await res.json();
                const ogrenciler = (data.success && Array.isArray(data.data)) ? data.data : [];

                if (ogrenciler.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-slate-400">Henüz öğrenci eklenmedi.</td></tr>`;
                } else {
                    tbody.innerHTML = ogrenciler.map(o => {
                        const no = o.student_no ?? o.no ?? '-';
                        const ad = o.name ?? o.ad ?? o.ad_soyad ?? 'İsimsiz';
                        const sinifAdi = apiSiniflarCache.find(s => s.id == o.class_id)?.class_name ?? '-';
                        return `<tr class="border-t"><td class="p-3">${no}</td><td class="p-3">${ad}</td><td class="p-3">${sinifAdi}</td><td class="p-3 text-blue-600 cursor-pointer" onclick="ogrenciDuzenle(${o.id}, '${ad.replace(/'/g, "\\'")}')">Düzenle</td></tr>`;
                    }).join('');
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-rose-500">Öğrenciler yüklenemedi: ${err.message}</td></tr>`;
            }
        }

        async function ogrenciKaydet() {
            const noInput = document.getElementById('yeni_ogr_no');
            const adInput = document.getElementById('yeni_ogr_ad');
            const sinifSecim = document.getElementById('yeni_ogr_sinif');
            const no = noInput.value.trim();
            const ad = adInput.value.trim();
            const classId = sinifSecim.value || null;
            if(!no || !ad) return alert("Bilgileri doldurunuz!");
            try {
                const res = await fetch('/api/v1/students', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: ad, student_no: no, class_id: classId })
                });
                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Öğrenci kaydedilemedi.');
                    return;
                }
                modalKapat('ogrenciEkleModal');
                noInput.value = ""; adInput.value = ""; sinifSecim.value = "";
                await ogrencileriGetirVeDoldur();
            } catch (err) {
                alert('Öğrenci kaydedilirken hata oluştu: ' + err.message);
            }
        }

        async function ogrenciDuzenle(id, mevcutAd) {
            const yeniAd = prompt('Öğrencinin adı soyadı:', mevcutAd === 'İsimsiz' ? '' : mevcutAd);
            if (yeniAd === null) return; 
            if (!yeniAd.trim()) return alert('İsim boş olamaz.');
            try {
                const res = await fetch(`/api/v1/students/${id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: yeniAd.trim() })
                });
                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Öğrenci güncellenemedi.');
                    return;
                }
                await ogrencileriGetirVeDoldur();
            } catch (err) {
                alert('Öğrenci güncellenirken hata oluştu: ' + err.message);
            }
        }

        let aktifAdim = 1;
        let sihirbazSorular = [];

        function sihirbazGuncelle() {
            document.querySelectorAll('.sihirbaz-adim').forEach(a => a.classList.add('hidden'));
            document.getElementById(`adim-${aktifAdim}`).classList.remove('hidden');
            document.getElementById('sihirbaz-baslik').textContent = `Adım ${aktifAdim} / 6: ${adimBasligiGetir(aktifAdim)}`;
            
            if (aktifAdim === 5) {
                cevapAnahtariArayuzunuOlustur();
            }

            if (aktifAdim === 6) {
                document.getElementById('onizleme_ad').textContent = document.getElementById('wiz_form_name').value || "İsimsiz Sınav";
                let toplamSoru = sihirbazSorular.reduce((acc, curr) => acc + curr.adet, 0);
                document.getElementById('onizleme_soru').textContent = toplamSoru;
                document.getElementById('wizBtnNext').textContent = "Yayınla";
            } else {
                document.getElementById('wizBtnNext').textContent = "İleri →";
            }
        }

        function adimBasligiGetir(a) {
            if(a===1) return "Sınav Adı";
            if(a===2) return "Üst Bilgiler";
            if(a===3) return "Öğrenci Numarası";
            if(a===4) return "Soruları ve Ceza Katsayısını Tanımla";
            if(a===5) return "Cevap Anahtarı Belirleme";
            if(a===6) return "Önizleme ve Yayınla";
        }

        function cevapAnahtariArayuzunuOlustur() {
            const container = document.getElementById('cevap-anahtari-container');
            if(!container) return;
            container.innerHTML = "";
            
            let sayac = 1;
            sihirbazSorular.forEach((blok) => {
                const harfler = blok.etiket.split('');
                for (let i = 0; i < blok.adet; i++) {
                    const soruNo = sayac++;
                    let html = `<div class="flex items-center justify-between bg-white p-2 rounded border border-slate-200">
                        <span class="font-bold text-xs text-slate-700">Soru ${soruNo}:</span>
                        <div class="flex gap-2">`;
                    
                    harfler.forEach((harf, hIdx) => {
                        const isChecked = hIdx === 0 ? 'checked' : '';
                        html += `<label class="inline-flex items-center gap-1 cursor-pointer text-xs font-semibold px-2 py-1 rounded bg-slate-100 hover:bg-slate-200">
                            <input type="radio" name="dogru_cevap_${soruNo}" value="${harf}" ${isChecked} class="text-blue-600"> ${harf}
                        </label>`;
                    });
                    
                    html += `</div></div>`;
                    container.innerHTML += html;
                }
            });
        }

        document.getElementById('wizBtnBack').addEventListener('click', () => {
            if (aktifAdim > 1) { aktifAdim--; sihirbazGuncelle(); }
            else { sekmeDegistir('view-answer-sheets'); }
        });

        document.getElementById('wizBtnNext').addEventListener('click', async () => {
            if (aktifAdim < 6) {
                aktifAdim++; sihirbazGuncelle();
            } else {
                publisFormuKaydet();
            }
        });

        document.getElementById('btnSoruBlokEkle').addEventListener('click', () => document.getElementById('soruEkleModal').style.display = 'flex');

        document.getElementById('modalEkleBtn').addEventListener('click', () => {
            const adet = parseInt(document.getElementById('modal_adet').value);
            const etiket = document.getElementById('modal_etiket').value.toUpperCase();
            const puan = parseFloat(document.getElementById('modal_puan').value) || 1.0;

            sihirbazSorular.push({ adet, etiket, puan });
            const liste = document.getElementById('wiz_question_list');
            if (sihirbazSorular.length === 1) liste.innerHTML = "";
            liste.innerHTML += `<tr class="border-b"><td class="p-2 border">${adet} Soru</td><td class="p-2 border font-mono">${etiket}</td><td class="p-2 border text-emerald-600 font-bold">${puan} Puan</td></tr>`;
            modalKapat('soruEkleModal');
        });

function dosyaIndir(url, dosyaAdi) {
    if (!url) return;
    const a = document.createElement('a');
    a.href = url;
    a.download = dosyaAdi;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

async function hizliSinavOlustur(examName, soruSayisi, sikHarfleri, haneSayisi) {
    let answers = {};
    let question_weights = {};
    for (let i = 1; i <= soruSayisi; i++) {
        answers[i] = sikHarfleri.charAt(0);
        question_weights[i] = 1;
    }

    try {
        const res = await fetch("{{ route('panel.cevapkaydet') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                exam_name: examName,
                answers: answers,
                question_weights: question_weights,
                sik_harfleri: sikHarfleri,
                hane_sayisi: haneSayisi,
                penalty_coef: 0
            })
        });
        const d = await res.json();

        if (!d.success) {
            bilgiGoster("⚠️ " + (d.message || "Form üretilemedi."));
            return;
        }

        dosyaIndir(d.form_url, `${examName}.pdf`);
        // JSON dosya indirme tamamen KALDIRILDI
    } catch (err) {
        bilgiGoster("⚠️ Sunucuya bağlanılamadı: " + err.message);
    }
}

function standartPdfIndir(qCount) {
    const hane = (qCount === 100) ? 9 : 5;
    hizliSinavOlustur(`MarkVision_${qCount}_Question_Form`, qCount, "ABCDE", hane);
}

function standartPngAc(qCount) {
    const hane = (qCount === 100) ? 9 : 5;
    hizliSinavOlustur(`MarkVision_${qCount}_Question_Form`, qCount, "ABCDE", hane);
}

async function publisFormuKaydet() {
    const formAdi = document.getElementById('wiz_form_name').value || "Ozel_Optik_Form";
    const hasId = document.getElementById('wiz_has_student_id').checked;
    const haneSayisi = hasId ? parseInt(document.getElementById('wiz_id_digits').value) : 9;

    const penaltyCoef = parseFloat(document.getElementById('wiz_penalty_coef')?.value) || 0;

    const gosterAdSoyad = document.getElementById('box_name')?.checked ?? true;
    const gosterSinif = document.getElementById('box_class')?.checked ?? true;
    const gosterSinavAdi = document.getElementById('box_quiz')?.checked ?? true;
    const etiketAdSoyad = gosterAdSoyad ? (document.getElementById('lbl_name')?.value || 'Ad Soyad') : '';
    const etiketSinif = gosterSinif ? (document.getElementById('lbl_class')?.value || 'Sinif') : '';
    const etiketSinavAdi = gosterSinavAdi ? (document.getElementById('lbl_quiz')?.value || 'Sinav Adi') : '';

    const farkliEtiketVarMi = sihirbazSorular.some(b => b.etiket !== sihirbazSorular[0].etiket);
    if (farkliEtiketVarMi) {
        bilgiGoster("Şu an bir sınavdaki TÜM soruların aynı şık sayısında olması gerekiyor (ör. hepsi ABCDE). Lütfen tüm blokları aynı şık düzeniyle oluşturun.");
        return;
    }
    const sikHarfleri = sihirbazSorular[0].etiket;

    let answers = {};
    let question_weights = {};
    let sayac = 1;
    sihirbazSorular.forEach(blok => {
        for (let i = 0; i < blok.adet; i++) {
            const radyoSecim = document.querySelector(`input[name="dogru_cevap_${sayac}"]:checked`);
            answers[sayac] = radyoSecim ? radyoSecim.value : blok.etiket.charAt(0);
            question_weights[sayac] = blok.puan;
            sayac++;
        }
    });

    try {
        const res = await fetch("{{ route('panel.cevapkaydet') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                exam_name: formAdi,
                answers: answers,
                question_weights: question_weights,
                sik_harfleri: sikHarfleri,
                hane_sayisi: haneSayisi,
                etiket_ad_soyad: etiketAdSoyad,
                etiket_sinif: etiketSinif,
                etiket_sinav_adi: etiketSinavAdi,
                penalty_coef: penaltyCoef
            })
        });
        const d = await res.json();

        if (!d.success) {
            bilgiGoster("⚠️ " + (d.message || "Sınav kaydedilemedi."));
            return;
        }

        bilgiGoster("Cevap Kağıdı Başarıyla Yayınlandı! Form indiriliyor...", 'basari');
        sekmeDegistir('view-quizzes');
        dosyaIndir(d.form_url, `${formAdi}.pdf`);
        // JSON dosya indirme tamamen KALDIRILDI

    } catch (err) {
        bilgiGoster("⚠️ Sunucuya bağlanılamadı: " + err.message);
    }
}
async function sinavlariGetirVeDoldur() {
        const tbody = document.getElementById('sinav-tablo-govde');
        try {
            const res = await fetch('/api/v1/exams');
            const data = await res.json();
            const sinavlar = (data.success && Array.isArray(data.data)) ? data.data : [];

            if (sinavlar.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-slate-400">Henüz sınav eklenmedi.</td></tr>`;
            } else {
                tbody.innerHTML = sinavlar.map(s => {
                    const ad = s.exam_name || s.sinav_adi || 'İsimsiz Sınav';
                    const tarih = s.created_at ? s.created_at.split('T')[0] : '-';
                    let soruSayisi = 20;
                    if(s.answer_key || s.cevap_anahtari) {
                        try {
                             let parsed = typeof (s.answer_key || s.cevap_anahtari) === 'string' ? JSON.parse(s.answer_key || s.cevap_anahtari) : (s.answer_key || s.cevap_anahtari);
                             soruSayisi = Object.keys(parsed).length;
                        } catch(e) {}
                    }
                    return `<tr class="border-b border-slate-200 tablo-satir">
                        <td class="p-3 text-center"><input type="checkbox"></td>
                        <td class="p-3 border-l border-slate-200">Genel</td>
                        <td class="p-3 border-l border-slate-200 font-bold text-blue-600">${ad}</td>
                        <td class="p-3 border-l border-slate-200">${tarih}</td>
                        <td class="p-3 border-l border-slate-200">${soruSayisi}</td>
                    </tr>`;
                }).join('');
            }
        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-center text-rose-500">Yüklenemedi: ${err.message}</td></tr>`;
        }
    }

let _onayModalCozumle = null;

        function onayIste(mesaj, baslik = "Emin misiniz?") {
            return new Promise((resolve) => {
                _onayModalCozumle = resolve;
                document.getElementById('onayModalBaslik').textContent = baslik;
                document.getElementById('onayModalMesaj').textContent = mesaj;
                document.getElementById('onayModal').style.display = 'flex';
            });
        }

        function onayModalSonucVer(sonuc) {
            document.getElementById('onayModal').style.display = 'none';
            if (_onayModalCozumle) {
                _onayModalCozumle(sonuc);
                _onayModalCozumle = null;
            }
        }

        function bilgiGoster(mesaj, tur = 'hata') {
            const ikonKutu = document.getElementById('bilgiModalIkonKutu');
            const ikon = document.getElementById('bilgiModalIkon');
            if (tur === 'basari') {
                ikonKutu.className = 'mx-auto mb-4 w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center';
                ikon.className = 'text-emerald-600 text-2xl';
                ikon.textContent = '✓';
            } else {
                ikonKutu.className = 'mx-auto mb-4 w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center';
                ikon.className = 'text-rose-600 text-2xl';
                ikon.textContent = '⚠';
            }
            document.getElementById('bilgiModalMesaj').textContent = mesaj;
            document.getElementById('bilgiModal').style.display = 'flex';
        }

        function bilgiModalKapat() { document.getElementById('bilgiModal').style.display = 'none'; }

        async function panelSinavlariDoldur() {
            const tbody = document.getElementById('sinav-tablo-govde');
            try {
                const res = await fetch("{{ route('panel.sinavlar.listele') }}", { headers: { 'Accept': 'application/json' } });
                const d = await res.json();
                tbody.innerHTML = "";
                if (d.success && d.data && d.data.length > 0) {
                    d.data.forEach(s => {
                        tbody.innerHTML += `<tr class="border-b border-slate-200 tablo-satir">
                            <td class="p-3 text-center"><input type="checkbox" class="sinav-checkbox" value="${s.id}"></td>
                            <td class="p-3 border-l border-slate-200">${s.ders_kodu ?? '-'}</td>
                            <td class="p-3 border-l border-slate-200 font-bold text-blue-600">${s.sinav_adi}</td>
                            <td class="p-3 border-l border-slate-200">${s.tarih ?? '-'}</td>
                            <td class="p-3 border-l border-slate-200">${s.soru_sayisi}</td>
                        </tr>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" class="p-6 text-center text-slate-400 italic">Henüz sınav yok. "+ Yeni Sınav / Form Oluştur" ile ekleyin.</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="5" class="p-6 text-center text-rose-500">Sınavlar yüklenemedi: ${err.message}</td></tr>`;
            }
        }

        function panelSinavHepsiniSecToggle(kutu) {
            document.querySelectorAll('.sinav-checkbox').forEach(cb => cb.checked = kutu.checked);
        }

        async function panelSinavlariSil() {
            const secilenler = Array.from(document.querySelectorAll('.sinav-checkbox:checked')).map(cb => parseInt(cb.value));
            if (secilenler.length === 0) { bilgiGoster("Silmek için önce en az bir sınav seçin."); return; }
            if (!(await onayIste(secilenler.length + " sınav silinecek.", "Sınavları Sil"))) return;

            try {
                const res = await fetch("{{ route('panel.sinavlar.sil') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ ids: secilenler })
                });
                const d = await res.json();
                if (d.success) {
                    panelSinavlariDoldur();
                    document.getElementById('sinav-hepsi-sec').checked = false;
                } else {
                    bilgiGoster(d.message || "Silinemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function panelSiniflariDoldur() {
            const tbody = document.getElementById('sinif-tablo-govde');
            const yeniSelect = document.getElementById('yeni_ogr_sinif');
            const duzenleSelect = document.getElementById('duzenle_ogr_sinif');
            try {
                const res = await fetch("{{ route('panel.siniflar.listele') }}", { headers: { 'Accept': 'application/json' } });
                const d = await res.json();
                tbody.innerHTML = "";

                if (yeniSelect) yeniSelect.innerHTML = '<option value="">— Sınıf Seçin (opsiyonel) —</option>';
                if (duzenleSelect) duzenleSelect.innerHTML = '<option value="">— Sınıf Seçin (opsiyonel) —</option>';

                if (d.success && d.data && d.data.length > 0) {
                    d.data.forEach(s => {
                        tbody.innerHTML += `<tr>
                            <td class="p-3 border-t">${s.class_name}</td>
                            <td class="p-3 border-t">${s.ogrenci_sayisi}</td>
                            <td class="p-3 border-t text-blue-600 cursor-pointer" onclick="panelSinifDuzenleAc(${s.id}, '${s.class_name.replace(/'/g, "\\'")}')">Düzenle</td>
                        </tr>`;
                        if (yeniSelect) yeniSelect.innerHTML += `<option value="${s.id}">${s.class_name}</option>`;
                        if (duzenleSelect) duzenleSelect.innerHTML += `<option value="${s.id}">${s.class_name}</option>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="3" class="p-6 text-center text-slate-400 italic">Henüz sınıf yok.</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="3" class="p-6 text-center text-rose-500">Sınıflar yüklenemedi: ${err.message}</td></tr>`;
            }
        }

        function panelSinifDuzenleAc(id, ad) {
            document.getElementById('duzenle_sinif_id').value = id;
            document.getElementById('duzenle_sinif_adi').value = ad;
            document.getElementById('sinifDuzenleModal').style.display = 'flex';
        }

        async function panelSinifGuncelleKaydet() {
            const id = document.getElementById('duzenle_sinif_id').value;
            const ad = document.getElementById('duzenle_sinif_adi').value;
            if(!ad) { bilgiGoster("Sınıf adı giriniz!"); return; }
            try {
                const res = await fetch(`/panel/siniflar/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ class_name: ad })
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('sinifDuzenleModal');
                    panelSiniflariDoldur();
                } else {
                    bilgiGoster(d.message || "Güncellenemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function panelSinifSilOnayla() {
            const id = document.getElementById('duzenle_sinif_id').value;
            if (!(await onayIste("Bu sınıf silinecek.", "Sınıfı Sil"))) return;
            try {
                const res = await fetch(`/panel/siniflar/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('sinifDuzenleModal');
                    panelSiniflariDoldur();
                } else {
                    bilgiGoster(d.message || "Silinemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function panelOgrencileriDoldur() {
            const tbody = document.getElementById('ogrenci-tablo-govde');
            try {
                const res = await fetch("{{ route('panel.ogrenciler.listele') }}", { headers: { 'Accept': 'application/json' } });
                const d = await res.json();
                tbody.innerHTML = "";
                if (d.success && d.data && d.data.length > 0) {
                    d.data.forEach(o => {
                        const adEscaped = String(o.name).replace(/'/g, "\\'");
                        tbody.innerHTML += `<tr>
                            <td class="p-3 border-t">${o.student_no}</td>
                            <td class="p-3 border-t">${o.name}</td>
                            <td class="p-3 border-t">${o.class_name ?? '-'}</td>
                            <td class="p-3 border-t text-blue-600 cursor-pointer" onclick="panelOgrenciDuzenleAc(${o.id}, '${o.student_no}', '${adEscaped}', ${o.class_id ?? 'null'})">Düzenle</td>
                        </tr>`;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="p-6 text-center text-slate-400 italic">Henüz öğrenci yok.</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-6 text-center text-rose-500">Öğrenciler yüklenemedi: ${err.message}</td></tr>`;
            }
        }

        function panelOgrenciDuzenleAc(id, no, ad, classId) {
            document.getElementById('duzenle_ogr_id').value = id;
            document.getElementById('duzenle_ogr_no').value = no;
            document.getElementById('duzenle_ogr_ad').value = ad;
            document.getElementById('duzenle_ogr_sinif').value = classId ?? "";
            document.getElementById('ogrenciDuzenleModal').style.display = 'flex';
        }

        async function panelOgrenciGuncelleKaydet() {
            const id = document.getElementById('duzenle_ogr_id').value;
            const no = document.getElementById('duzenle_ogr_no').value;
            const ad = document.getElementById('duzenle_ogr_ad').value;
            const sinif = document.getElementById('duzenle_ogr_sinif').value;
            if(!no || !ad) { bilgiGoster("Bilgileri doldurunuz!"); return; }
            try {
                const res = await fetch(`/panel/ogrenciler/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ student_no: no, name: ad, class_id: sinif || null })
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('ogrenciDuzenleModal');
                    panelOgrencileriDoldur();
                    panelSiniflariDoldur();
                } else {
                    bilgiGoster(d.message || "Güncellenemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function panelOgrenciSilOnayla() {
            const id = document.getElementById('duzenle_ogr_id').value;
            if (!(await onayIste("Bu öğrenci silinecek.", "Öğrenciyi Sil"))) return;
            try {
                const res = await fetch(`/panel/ogrenciler/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('ogrenciDuzenleModal');
                    panelOgrencileriDoldur();
                    panelSiniflariDoldur();
                } else {
                    bilgiGoster(d.message || "Silinemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function panelSinifKaydet() {
            const ad = document.getElementById('yeni_sinif_adi').value;
            if(!ad) { bilgiGoster("Sınıf adı giriniz!"); return; }
            try {
                const res = await fetch("{{ route('panel.siniflar.ekle') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ class_name: ad })
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('sinifEkleModal');
                    document.getElementById('yeni_sinif_adi').value = "";
                    panelSiniflariDoldur();
                } else {
                    bilgiGoster(d.message || "Sınıf eklenemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function panelOgrenciKaydet() {
            const no = document.getElementById('yeni_ogr_no').value;
            const ad = document.getElementById('yeni_ogr_ad').value;
            const sinif = document.getElementById('yeni_ogr_sinif').value;
            if(!no || !ad) { bilgiGoster("Bilgileri doldurunuz!"); return; }
            try {
                const res = await fetch("{{ route('panel.ogrenciler.ekle') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ student_no: no, name: ad, class_id: sinif || null })
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('ogrenciEkleModal');
                    document.getElementById('yeni_ogr_no').value = ""; document.getElementById('yeni_ogr_ad').value = "";
                    panelOgrencileriDoldur();
                    panelSiniflariDoldur(); 
                } else {
                    bilgiGoster(d.message || "Öğrenci eklenemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function hesapSifreDegistir() {
            const eski = document.getElementById('hesap-eski-sifre').value;
            const yeni = document.getElementById('hesap-yeni-sifre').value;
            const tekrar = document.getElementById('hesap-yeni-sifre-tekrar').value;

            if (!eski || !yeni || !tekrar) { bilgiGoster("Lütfen tüm alanları doldurun."); return; }
            if (yeni.length < 6) { bilgiGoster("Yeni şifre en az 6 karakter olmalı."); return; }
            if (yeni !== tekrar) { bilgiGoster("Yeni şifreler birbiriyle eşleşmiyor."); return; }

            try {
                const res = await fetch("{{ route('panel.sifredegistir') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ eski_sifre: eski, yeni_sifre: yeni })
                });
                const d = await res.json();
                if (d.success) {
                    document.getElementById('hesap-eski-sifre').value = "";
                    document.getElementById('hesap-yeni-sifre').value = "";
                    document.getElementById('hesap-yeni-sifre-tekrar').value = "";
                    bilgiGoster("Şifreniz başarıyla güncellendi.", 'basari');
                } else {
                    bilgiGoster(d.message || "Şifre güncellenemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }

        async function hesabiSilOnayla() {
            const sifre = document.getElementById('hesabi-sil-sifre').value;
            if (!sifre) { bilgiGoster("Lütfen şifrenizi girin."); return; }

            try {
                const res = await fetch("{{ route('panel.hesabisil') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ sifre: sifre })
                });
                const d = await res.json();
                if (d.success) {
                    modalKapat('hesabiSilModal');
                    window.location.reload();
                } else {
                    bilgiGoster(d.message || "Hesap silinemedi.");
                }
            } catch (err) {
                bilgiGoster("Bağlantı hatası: " + err.message);
            }
        }
    </script>
</body>
</html>