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
                <div class="text-xs flex items-center gap-4">
                    <span>Aktif Kullanıcı: <span id="user-display-name" class="font-bold text-white">Öğretmen</span></span>
                    <button onclick="window.location.reload()" class="text-rose-400 hover:text-rose-300 font-bold cursor-pointer">Çıkış Yap</button>
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
                </nav>
            </div>
        </header>

        <!-- BİLGİ BARI -->
        <div class="bg-emerald-100 border-b border-emerald-200 text-emerald-800 text-xs py-2 px-6 text-center">
            Ücretsiz hesabınızla her ay 100 kağıt okutabilirsiniz. Kalan hak: <b>100 kağıt.</b> 
            <button class="ml-4 bg-emerald-500 text-white px-3 py-1 rounded">Sınırsız Kullanım Satın Al</button>
        </div>

        <!-- İÇERİK ALANI -->
        <main class="max-w-6xl w-full mx-auto p-8 flex-1 space-y-6">
            
            <!-- 1. SINAVLAR SEKMESİ -->
            <div id="view-quizzes" class="sekme-icerik space-y-6">
                <h2 class="text-2xl font-light text-center text-slate-700">Tüm Sınavlar</h2>
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2">
                        <button class="px-3 py-1.5 bg-white border border-slate-300 text-slate-600 text-xs rounded hover:bg-slate-50 cursor-pointer">Seçilenleri Sil</button>
                        <button onclick="sekmeDegistir('view-answer-sheets')" class="px-3 py-1.5 bg-emerald-600 text-white font-bold text-xs rounded hover:bg-emerald-700 cursor-pointer">+ Yeni Sınav / Form Oluştur</button>
                    </div>
                </div>
                <div class="bg-white border border-slate-300 rounded overflow-hidden shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#4b5563] text-white text-xs">
                            <tr>
                                <th class="p-3 w-10 text-center"><input type="checkbox"></th>
                                <th class="p-3 border-l border-slate-500">Sınıf</th>
                                <th class="p-3 border-l border-slate-500">Sınav Adı</th>
                                <th class="p-3 border-l border-slate-500">Tarih</th>
                                <th class="p-3 border-l border-slate-500">Soru Sayısı</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600 text-xs">
                            <tr class="border-b border-slate-200 tablo-satir">
                                <td class="p-3 text-center"><input type="checkbox"></td>
                                <td class="p-3 border-l border-slate-200">Fizik Sınıfı</td>
                                <td class="p-3 border-l border-slate-200 font-bold text-blue-600">Fizik Vize Sınavı</td>
                                <td class="p-3 border-l border-slate-200">2026-07-28</td>
                                <td class="p-3 border-l border-slate-200">20</td>
                            </tr>
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
                            <tr><td class="p-3 border-t">A Sınıfı</td><td class="p-3 border-t">0</td><td class="p-3 border-t text-blue-600 cursor-pointer">Düzenle</td></tr>
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
                            <tr><td class="p-3 border-t">101</td><td class="p-3 border-t">Ahmet Yılmaz</td><td class="p-3 border-t">A Sınıfı</td><td class="p-3 border-t text-blue-600 cursor-pointer">Düzenle</td></tr>
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
                        <h2 class="text-sm font-bold" id="sihirbaz-baslik">Adım 1 / 5: Form Adı</h2>
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
                                <button type="button" id="btnSoruBlokEkle" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-2 rounded text-xs cursor-pointer">+ Soru Bloğu Ekle</button>
                            </div>
                            <table class="w-full text-xs border border-slate-200">
                                <thead class="bg-slate-100"><tr><th class="p-2 border">Soru Adeti</th><th class="p-2 border">Şıklar</th><th class="p-2 border">Puan</th></tr></thead>
                                <tbody id="wiz_question_list">
                                    <tr><td colspan="3" class="p-4 text-center text-slate-400 italic">Henüz soru eklenmedi.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="adim-5" class="sihirbaz-adim hidden space-y-4 text-center">
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
                <button type="button" onclick="sinifKaydet()" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Kaydet</button>
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
                <div><label class="block font-semibold mb-1">Sınıfı</label><input type="text" id="yeni_ogr_sinif" placeholder="Örn: A Sınıfı" class="w-full border rounded p-2 text-xs bg-white"></div>
            </div>
            <div class="bg-slate-50 p-4 border-t flex justify-end gap-2">
                <button type="button" onclick="modalKapat('ogrenciEkleModal')" class="px-4 py-2 border rounded bg-white text-slate-600 text-xs font-semibold">İptal</button>
                <button type="button" onclick="ogrenciKaydet()" class="px-5 py-2 rounded bg-blue-600 text-white font-bold text-xs">Kaydet</button>
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
            } else {
                const err = document.getElementById('login-error');
                err.textContent = data.message;
                err.classList.remove('hidden');
            }
        });

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
            
            // Geçmiş sekmesi seçilirse tabloyu doldur
            if(hedefId === 'view-gecmis') {
                gecmisTablosunuDoldur();
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

        function sinifKaydet() {
            const ad = document.getElementById('yeni_sinif_adi').value;
            if(!ad) return alert("Sınıf adı giriniz!");
            const tbody = document.getElementById('sinif-tablo-govde');
            tbody.innerHTML += `<tr><td class="p-3 border-t">${ad}</td><td class="p-3 border-t">0</td><td class="p-3 border-t text-blue-600 cursor-pointer">Düzenle</td></tr>`;
            modalKapat('sinifEkleModal');
            document.getElementById('yeni_sinif_adi').value = "";
        }

        function ogrenciKaydet() {
            const no = document.getElementById('yeni_ogr_no').value;
            const ad = document.getElementById('yeni_ogr_ad').value;
            const sinif = document.getElementById('yeni_ogr_sinif').value;
            if(!no || !ad) return alert("Bilgileri doldurunuz!");
            const tbody = document.getElementById('ogrenci-tablo-govde');
            tbody.innerHTML += `<tr><td class="p-3 border-t">${no}</td><td class="p-3 border-t">${ad}</td><td class="p-3 border-t">${sinif}</td><td class="p-3 border-t text-blue-600 cursor-pointer">Düzenle</td></tr>`;
            modalKapat('ogrenciEkleModal');
            document.getElementById('yeni_ogr_no').value = ""; document.getElementById('yeni_ogr_ad').value = "";
        }

        let aktifAdim = 1;
        let sihirbazSorular = [];

        function sihirbazGuncelle() {
            document.querySelectorAll('.sihirbaz-adim').forEach(a => a.classList.add('hidden'));
            document.getElementById(`adim-${aktifAdim}`).classList.remove('hidden');
            document.getElementById('sihirbaz-baslik').textContent = `Adım ${aktifAdim} / 5: ${adimBasligiGetir(aktifAdim)}`;
            
            if (aktifAdim === 5) {
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
            if(a===4) return "Soruları Tanımla";
            if(a===5) return "Önizleme ve Yayınla";
        }

        document.getElementById('wizBtnBack').addEventListener('click', () => {
            if (aktifAdim > 1) { aktifAdim--; sihirbazGuncelle(); }
            else { sekmeDegistir('view-answer-sheets'); }
        });

        document.getElementById('wizBtnNext').addEventListener('click', async () => {
            if (aktifAdim < 5) {
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

        async function publisFormuKaydet() {
            const formAdi = document.getElementById('wiz_form_name').value || "Ozel_Optik_Form";
            let toplamSoru = sihirbazSorular.reduce((acc, curr) => acc + curr.adet, 0);
            if (toplamSoru === 0) { alert("Lütfen en az bir soru bloğu ekleyin!"); aktifAdim = 4; sihirbazGuncelle(); return; }

            const hasId = document.getElementById('wiz_has_student_id').checked;
            const haneSayisi = hasId ? parseInt(document.getElementById('wiz_id_digits').value) : 0;

            let answers = {};
            let question_weights = {};
            let sayac = 1;
            sihirbazSorular.forEach(blok => {
                for(let i=0; i<blok.adet; i++) {
                    answers[sayac] = blok.etiket.charAt(0);
                    question_weights[sayac] = blok.puan;
                    sayac++;
                }
            });

            try {
                const res = await fetch("{{ route('panel.cevapkaydet') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ exam_name: formAdi, answers: answers, question_weights: question_weights })
                });
                const d = await res.json();
                if (d.success) {
                    alert("✓ Cevap Kağıdı Başarıyla Yayınlandı!");
                    sekmeDegistir('view-quizzes');
                } else {
                    console.warn("Kayıt uyarı: " + (d.message || "Bilinmeyen yanıt"));
                }
            } catch (err) {
                console.warn("Sunucu bağlantısı kurulamadı (Failed to fetch), ancak PDF oluşturma işlemi devam ediyor: " + err.message);
            }

            orijinalOptikFormOlustur(formAdi, sihirbazSorular, haneSayisi, 'pdf');
        }

        function standartPdfIndir(qCount) {
            // DÜZELTİLDİ: 20 soruluk, 50 soruluk ve 100 soruluk formların hepsi Öğrenci Numarasına sahip!
            let hane = (qCount === 100) ? 9 : 5;
            orijinalOptikFormOlustur(`ZipGrade_${qCount}_Question_Form`, [{ adet: qCount, etiket: "ABCDE" }], hane, 'pdf');
        }

        function standartPngAc(qCount) {
            // DÜZELTİLDİ: 20 soruluk, 50 soruluk ve 100 soruluk formların hepsi Öğrenci Numarasına sahip!
            let hane = (qCount === 100) ? 9 : 5;
            orijinalOptikFormOlustur(`ZipGrade_${qCount}_Question_Form`, [{ adet: qCount, etiket: "ABCDE" }], hane, 'png');
        }

        // ARKADAŞININ YAZDIĞI KUSURSUZ PDF/OPTİK MOTORU + SENİN PNG ÖNİZLEME SİSTEMİN
        function orijinalOptikFormOlustur(fileName, bloklar, haneSayisi = 5, format = 'pdf') {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            
            // 4 Köşe Siyah Referans Kareleri (Büyütülmüş: 9x9)
            doc.setFillColor(0, 0, 0);
            doc.rect(14, 14, 9, 9, 'F'); doc.rect(187, 14, 9, 9, 'F');
            doc.rect(14, 274, 9, 9, 'F'); doc.rect(187, 274, 9, 9, 'F');
            
            // Sol Dikey MarkVISION Yazısı
            doc.setFontSize(14); doc.setFont("helvetica", "bold"); doc.text("MARKVISION", 17, 170, { angle: 90 });

            // Üst Bilgi Kutusu
            doc.setLineWidth(0.4);
            doc.roundedRect(25, 14, 158, 14, 2, 2, 'S');
            doc.line(25, 21, 183, 21); doc.line(135, 14, 135, 28); doc.line(95, 21, 95, 28);
            doc.setFontSize(7.5); 
            doc.text("Ad Soyad:", 27, 18); doc.text("Sinif:", 27, 25);
            doc.text("Sinav Adi:", 97, 25); doc.text("Tarih:", 137, 18);

            // ÖĞRENCİ NO MATRİSİ
            if (haneSayisi > 0) {
                let idStartX = 25; let idStartY = 31;
                doc.setFontSize(7.5); doc.setFont("helvetica", "bold");
                doc.text("Ogrenci No (Student ID)", idStartX, idStartY);
                doc.setFont("helvetica", "normal");
                doc.setLineWidth(0.2);
                
                for(let i=0; i<haneSayisi; i++) {
                    let colX = idStartX + (i * 10.5); 
                    doc.rect(colX, idStartY + 2.5, 7.5, 3.8); 
                    for(let j=0; j<=9; j++) {
                        let bY = idStartY + 10.0 + (j * 4.2); 
                        doc.circle(colX + 3.75, bY, 1.9); 
                        doc.setFontSize(4.5); 
                        doc.text(j.toString(), colX + 2.5, bY + 0.7);
                    }
                }
            }

            // SORULAR BÖLÜMÜ
            let soruSayaci = 1;
            let startX = 25; 
            let startY = haneSayisi > 0 ? 88 : 38; 
            let colWidth = 56; 
            let maxPerColumn = 34; 

            doc.setFontSize(8.5);
            bloklar.forEach(blok => {
                let labels = blok.etiket.split('');
                for(let k=0; k<blok.adet; k++) {
                    let i = soruSayaci;
                    let colIndex = Math.floor((i - 1) / maxPerColumn);
                    let rowIndex = (i - 1) % maxPerColumn;

                    let qX = startX + (colIndex * colWidth);
                    let qY = startY + (rowIndex * 5.3); 

                    if (qY > 265 || colIndex >= 3) {
                        if (colIndex >= 3 && qY > 265) {
                            doc.addPage();
                            doc.setFillColor(0, 0, 0);
                            doc.rect(14, 14, 9, 9, 'F'); doc.rect(187, 14, 9, 9, 'F');
                            doc.rect(14, 274, 9, 9, 'F'); doc.rect(187, 274, 9, 9, 'F');
                            qY = 35;
                        }
                    }

                    doc.setFont("helvetica", "bold");
                    doc.text(i.toString() + ".", qX, qY);

                    labels.forEach((harf, idx) => {
                        let bx = qX + 9 + (idx * 5.8); 
                        doc.circle(bx, qY - 1, 2.3); 
                        doc.setFontSize(5.0); doc.setFont("helvetica", "normal");
                        doc.text(harf, bx - 0.8, qY + 0.4);
                    });
                    soruSayaci++;
                }
            });

            // SENİN YAPTIĞIN PNG ÖNİZLEME (IFRAME) SİSTEMİ
            if (format === 'png') {
                const pdfData = doc.output('datauristring');
                const newWindow = window.open();
                newWindow.document.write(`
                    <html>
                        <head><title>${fileName} - Önizleme</title></head>
                        <body style="margin:0; background:#555; display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh;">
                            <div style="background:white; padding:10px; margin-bottom:10px; border-radius:5px; font-family:sans-serif; font-size:14px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                                💡 <b>İpucu:</b> Bu sayfadaki forma sağ tıklayıp <b>"Resmi Farklı Kaydet"</b> diyerek veya ekran görüntüsü alarak hemen <b>test.jpg / test.png</b> olarak \`omr_scripts\` klasörüne kaydedebilirsiniz!
                            </div>
                            <iframe src="${pdfData}" style="width:800px; height:90vh; border:none; border-radius:4px; box-shadow:0 10px 25px rgba(0,0,0,0.5);"></iframe>
                        </body>
                    </html>
                `);
            } else {
                doc.save(fileName + ".pdf");
            }
        }
    </script>
</body>
</html>