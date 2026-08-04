<?php

namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResultsExport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Sinav;
use App\Models\OgrenciSonuc;
use App\Models\Obs\Exam as ObsExam;
use App\Models\Obs\Student as ObsStudent;
use App\Models\Obs\ExamResult as ObsExamResult;

class MarkVisionController extends Controller
{
   private string $pythonPath = 'python';

    private const AKTIF_SINAV_SESSION_KEY = 'aktif_sinav_id';

    public function index()
    {
        return view('markvision-panel');
    }

    public function login(Request $request)
    {
        try {
            $teacher = \App\Models\User::where('email', $request->email)->first();

            if ($teacher && \Illuminate\Support\Facades\Hash::check($request->password, $teacher->password)) {

                // --- YENİ EKLENDİ: oturumu gerçekten açıyoruz ---
                // Bu satır olmadan Auth::check() / Auth::id() panel* (Sınıflar,
                // Öğrenciler, Sınavlar -> "her öğretmen sadece kendi kaydını görsün")
                // uçlarında hep boş/401 dönüyordu; login() JSON success döndürse bile
                // session'da kimse "giriş yapmış" sayılmıyordu.
                Auth::login($teacher);

                // --- GİRİŞ BAŞARILI DÖNÜŞÜ ---
                return response()->json([
                    'success' => true,
                    'user' => [
                        'ad' => $teacher->name,
                        // YENİ EKLENDİ: Hesabım sekmesinde ad soyad + e-posta gösterebilmek için
                        'soyad' => $teacher->surname ?? null,
                        'email' => $teacher->email ?? null,
                        'rol' => 'Öğretmen / Akademisyen',
                    ]
                ]);
            }

            return response()->json(['success' => false, 'message' => 'E-posta veya şifre hatalı!'], 401);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Mobil (Flutter) uygulamadan giriş için JSON dönen stateless sürüm.
    // Arkadaşımın kodundan entegre edildi: login() ile birebir aynı
    // doğrulamayı (teachers tablosu + Hash::check) kullanır ama
    // Auth::login()/session çağırmaz. Şu an hiçbir route bu metodu
    // çağırmıyor (api rotalarımda /v1/login halen login()'e bağlı,
    // önceliğim öyle kaldığı için değiştirmedim) — istersen ileride
    // /v1/login'i buna yönlendirebiliriz.
    public function loginApi(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email', 'password' => 'required']);
            $teacher = DB::table('teachers')->where('email', $request->email)->first();

            if ($teacher && Hash::check($request->password, $teacher->password)) {
                return response()->json([
                    'success' => true,
                    'user' => [
                        'id'  => $teacher->id,
                        'ad'  => $teacher->name . ' ' . $teacher->surname,
                        'rol' => 'Öğretmen / Akademisyen',
                    ],
                ]);
            }
            return response()->json(['success' => false, 'message' => 'E-posta veya şifre hatalı!'], 401);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sifreDegistir(Request $request)
    {
        $request->validate([
            'eski_sifre' => 'required',
            'yeni_sifre' => 'required|min:6',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->eski_sifre, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Mevcut şifre yanlış.']);
        }

        $user->password = Hash::make($request->yeni_sifre);
        $user->save();

        return response()->json(['success' => true]);
    }

    // Hesabı Sil (YENİ EKLENDİ) — öğretmen şifresini doğrulayarak kendi hesabını
    // ve SADECE kendine ait verileri (sınavlar/sınıflar/öğrenciler) kalıcı
    // olarak siler. Başka bir öğretmenin kaydına asla dokunmaz.
    public function hesabiSil(Request $request)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $request->validate(['sifre' => 'required']);

        $user = Auth::user();

        if (!Hash::check($request->input('sifre'), $user->password)) {
            return response()->json(['success' => false, 'message' => 'Şifre yanlış.'], 422);
        }

        $teacherId = $user->id;

        DB::table('panel_students')->where('teacher_id', $teacherId)->delete();
        DB::table('siniflar')->where('teacher_id', $teacherId)->delete();
        Sinav::where('teacher_id', $teacherId)->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::table('teachers')->where('id', $teacherId)->delete();

        return response()->json(['success' => true, 'message' => 'Hesabınız kalıcı olarak silindi.']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    // Web panelinden yapılan kayıt: form -> redirect akışı (session/cookie tabanlı).
    public function registerStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:100',
                'surname'  => 'required|string|max:100',
                'email'    => 'required|email|max:150|unique:teachers,email',
                'tc_no'    => 'required|string|max:11|unique:teachers,tc_no',
                'password' => 'required|string|min:6',
            ], [
                'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
                'tc_no.unique' => 'Bu TC kimlik numarası zaten kayıtlı.',
            ]);

            $teacherId = DB::table('teachers')->insertGetId([
                'name'       => $validated['name'],
                'surname'    => $validated['surname'],
                'email'      => $validated['email'],
                'tc_no'      => $validated['tc_no'],
                'password'   => Hash::make($validated['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $teacher = DB::table('teachers')->find($teacherId);
            $userModel = User::find($teacher->id);
            if (!$userModel) {
                $userModel = new User();
                $userModel->forceFill((array) $teacher);
            }
            Auth::login($userModel);

            return redirect()->route('panel.index')->with('success', 'Kayıt başarılı! Hoş geldiniz.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput($request->except('password'));
        } catch (\Exception $e) {
            return back()
                ->withErrors(['general' => 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage()])
                ->withInput($request->except('password'));
        }
    }

    // Mobil (Flutter) uygulamadan kayıt için JSON dönen sürüm.
    // registerStore() ile aynı validasyonu kullanır ama redirect yerine
    // JSON döner ve Auth::login() çağırmaz (api.php rotaları stateless,
    // session/cookie tabanlı oturum açmanın mobilde bir karşılığı yok).
    public function registerApi(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:100',
                'surname'  => 'required|string|max:100',
                'email'    => 'required|email|max:150|unique:teachers,email',
                'tc_no'    => 'required|string|max:11|unique:teachers,tc_no',
                'password' => 'required|string|min:6',
            ], [
                'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
                'tc_no.unique' => 'Bu TC kimlik numarası zaten kayıtlı.',
            ]);

            $teacherId = DB::table('teachers')->insertGetId([
                'name'       => $validated['name'],
                'surname'    => $validated['surname'],
                'email'      => $validated['email'],
                'tc_no'      => $validated['tc_no'],
                'password'   => Hash::make($validated['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $teacher = DB::table('teachers')->find($teacherId);

            return response()->json([
                'success' => true,
                'message' => 'Kayıt başarılı.',
                'user'    => [
                    'id'  => $teacher->id,
                    'ad'  => $teacher->name . ' ' . $teacher->surname,
                    'rol' => 'Öğretmen / Akademisyen',
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

  public function saveAnswerKey(Request $request)
    {
        try {
            $request->validate([
                'exam_name'          => 'required|string|max:255',
                'ders_kodu'          => 'nullable|string|max:50',
                'obs_exam_id'        => 'nullable',
                'sinav_id'           => 'nullable|integer',
                'answers'            => 'required|array|min:1',
                'answers.*'          => 'required|string|in:A,B,C,D,E,F,G,H,I,J',
                'koordinat_haritasi' => 'nullable|array',
                'etiket_ad_soyad'    => 'nullable|string|max:50',
                'etiket_sinif'       => 'nullable|string|max:50',
                'etiket_sinav_adi'   => 'nullable|string|max:50',
                'penalty_coef'       => 'nullable|numeric|min:0', // C KİŞİSİ GÖREVİ: Ceza katsayısı validasyonu
            ]);

            $dersKodu = trim((string) $request->input('ders_kodu'));
            if ($dersKodu === '') {
                $dersKodu = strtoupper(Str::slug($request->input('exam_name'), '_'));
                // NOT: 'ders_kodu' kolonu veritabaninda kisa (ornegin VARCHAR(20))
                // olabilir -- "ZipGrade_50_Question_Form" gibi uzun otomatik
                // isimlerin slug'i (25+ karakter) SQLSTATE[22001] "Data too long"
                // hatasi verip sinav kaydini HIC OLUSTURMADAN patlatiyordu. 15
                // karaktere kirpmak, bilinen en kucuk kolon boyutlarina bile
                // guvenli sekilde sigar. Kalici cozum icin ayrica asagidaki
                // "DB QueryException fallback" da eklendi.
                if (strlen($dersKodu) > 15) {
                    $dersKodu = substr($dersKodu, 0, 15);
                }
                if ($dersKodu === '') {
                    $dersKodu = 'GEN_' . time();
                }
            } elseif (strlen($dersKodu) > 15) {
                $dersKodu = substr($dersKodu, 0, 15);
            }

            // OBS sınav ID boş veya "seçilmedi" ise null yapalım
            $obsExamId = $request->input('obs_exam_id');
            if (empty($obsExamId) || $obsExamId === 'null' || $obsExamId === '0') {
                $obsExamId = null;
            }

            // === ONEMLI DUZELTME: HER KAYITTA YENI SATIR OLUSTURMA HATASI ===
            // Eskiden burada KOSULSUZ "new Sinav()" cagriliyordu -- yani hem web
            // sihirbazindan hem mobil QUIZ KEY ekranindan "Kaydet" basildiginda,
            // ayni sinavi GUNCELLEMEK yerine HER SEFERINDE ayri, bagimsiz, YENI
            // bir sinav kaydi (yeni ID, yeni koordinat_<id>.json, yeni
            // form_<id>.pdf) olusturuluyordu. Sonuc: ayni isimde ("turkce" gibi)
            // birbirinden bagimsiz, FARKLI sik/hane sayisina sahip birden fazla
            // sinav kaydi birikiyor, mobil/web hangisini gosterdigine gore
            // "web'de G'ye kadar var ama mobilde E'de kaliyor" gibi tutarsizliklar
            // ortaya cikiyordu.
            //
            // Simdi: istekte 'sinav_id' geldiyse VE bu ID o ogretmene aitse,
            // YENI kayit ACILMIYOR -- var olan sinav GUNCELLENIYOR. 'sinav_id'
            // gelmediyse (ilk kez olusturuluyorsa) eskisi gibi yeni kayit acilir.
            $sinavId = $request->input('sinav_id');
            $sinav = null;
            if (!empty($sinavId)) {
                $sinav = \App\Models\Sinav::find($sinavId);
                if ($sinav) {
                    $sahibiDogrula = true;
                    if (Auth::check()) {
                        $sahibiDogrula = ((int) $sinav->teacher_id === (int) Auth::id());
                    } elseif ($request->has('email')) {
                        $teacher = DB::table('teachers')->where('email', $request->input('email'))->first();
                        $sahibiDogrula = $teacher && ((int) $sinav->teacher_id === (int) $teacher->id);
                    }
                    if (!$sahibiDogrula) {
                        $sinav = null; // baskasinin sinavini GUNCELLEMEYE izin verme, yeni kayit acilsin
                    }
                }
            }
            if ($sinav === null) {
                $sinav = new Sinav();
            }
            $sinav->sinav_adi = $request->input('exam_name');
            $sinav->ders_kodu = $dersKodu;
            $sinav->cevap_anahtari = $request->input('answers');
            $sinav->obs_exam_id = $obsExamId;
            $sinav->question_weights = $request->input('question_weights'); // YENİ EKLENDİ
            
            // C KİŞİSİ GÖREVİ: Panelden gelen ceza katsayısını veritabanına kaydediyoruz
            $sinav->penalty_coef = $request->input('penalty_coef', 0);

            // YENİ EKLENDİ: teacher_id atanmıyordu, bu yüzden "Sınavlar" sekmesi

            // (panelSinavlariGetir -> Sinav::where('teacher_id', Auth::id())) yeni
            // oluşturulan sınavı asla göremiyordu -- kayıt teacher_id=null olarak
            // düşüyordu. Sadece oturum açıkken (web panelinden) atanır; mobil/
            // stateless istekte Auth::id() zaten null döner, orada davranış değişmez.
            if (Auth::check()) {
                $sinav->teacher_id = Auth::id();
            } elseif ($request->has('email')) {
                $teacher = DB::table('teachers')->where('email', $request->input('email'))->first();
                if ($teacher) {
                    $sinav->teacher_id = $teacher->id;
                }
            } 
            try {
                $sinav->save();
            } catch (\Illuminate\Database\QueryException $e) {
                // Kolon hala cok kisa kalirsa (ornegin gercek kolon 10 karakterden
                // kisaysa) ya da baska bir "data too long" turu hatada, kullaniciyi
                // ham SQL hatasiyla karsi karsiya birakmak yerine cok kisa, garanti
                // sigacak bir kod ile TEK SEFER tekrar dene.
                if ((int) $e->getCode() === 22001 || str_contains($e->getMessage(), '1406')) {
                    $sinav->ders_kodu = 'G' . substr((string) time(), -8);
                    $sinav->save();
                } else {
                    throw $e;
                }
            }

            // === Bu sınava özel koordinat haritasını diske kaydet (varsa) ===
            $soruSayisi  = count($request->input('answers'));

            // GUVENLIK KATMANI: 'sik_harfleri' / 'hane_sayisi' istekte
            // gelmediyse (ornegin ileride baska bir ekran/entegrasyon bu
            // alanlari eklemeyi unutursa), sabit "ABCDE/9" varsayilanina
            // DUSMEDEN ONCE -- eger bu GUNCELLENEN mevcut bir sinavsa --
            // once o sinavin KENDI mevcut koordinat dosyasindan gercek
            // sik/hane sayisini okumayi dene. Boylece eksik bir alan,
            // var olan dogru sik/hane duzenini SESSIZCE 5 sik'e/9 haneye
            // dusurup ustune yazamaz.
            $eskiSikHarfleri = null;
            $eskiHaneSayisi = null;
            if (!empty($sinavId)) {
                $eskiKoordYolu = storage_path('app/omr_scripts/koordinat_' . $sinavId . '.json');
                if (file_exists($eskiKoordYolu)) {
                    $eskiJson = json_decode(file_get_contents($eskiKoordYolu), true);
                    if (isset($eskiJson['sorular'][0])) {
                        $harfler = [];
                        foreach ($eskiJson['sorular'][0] as $k => $v) {
                            if ($k !== 'soru_no') $harfler[] = $k;
                        }
                        if (count($harfler) > 0) {
                            sort($harfler);
                            $eskiSikHarfleri = implode('', $harfler);
                        }
                    }
                    if (isset($eskiJson['ogrenci_no'])) {
                        $eskiHaneSayisi = count($eskiJson['ogrenci_no']);
                    }
                }
            }

            $sikHarfleri = strtoupper(trim($request->input('sik_harfleri', $eskiSikHarfleri ?? 'ABCDE')));
            $haneSayisi  = (int) $request->input('hane_sayisi', $eskiHaneSayisi ?? 9);
            $penaltyCoef = $sinav->penalty_coef; // C kişisi katsayısı

            $omrDir   = base_path('omr_scripts');
            $koordDir = storage_path('app/omr_scripts');
            $formDir  = storage_path('app/public/optik_forms');
            foreach ([$koordDir, $formDir] as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
            }

            $koordCiktiYolu = $koordDir . DIRECTORY_SEPARATOR . 'koordinat_' . $sinav->id . '.json';
            $pdfCiktiYolu   = $formDir  . DIRECTORY_SEPARATOR . 'form_' . $sinav->id . '.pdf';

            $cmdKoordinat = sprintf(
                '"%s" "%s" --soru %d --sik "%s" --hane %d --cikti "%s" 2>&1',
                $this->pythonPath,
                $omrDir . DIRECTORY_SEPARATOR . 'koordinat_uretici.py',
                $soruSayisi, $sikHarfleri, $haneSayisi, $koordCiktiYolu
            );
            exec($cmdKoordinat, $koordCiktisi, $koordKodu);

            $cmdPdf = sprintf(
                '"%s" "%s" --soru %d --sik "%s" --hane %d --cikti "%s" --baslik "%s" --etiket-ad-soyad "%s" --etiket-sinif "%s" --etiket-sinav-adi "%s" 2>&1',
                $this->pythonPath,
                $omrDir . DIRECTORY_SEPARATOR . 'sablon_uret.py',
                $soruSayisi, $sikHarfleri, $haneSayisi, $pdfCiktiYolu,
                // PDF ic metadata basligi indirilen dosya adiyla (exam_name) AYNI olsun
                // diye -- yoksa sekme basligi ile indirilen dosya adi birbirini tutmuyor.
                addslashes($request->input('exam_name')),
                // Bu 3 alan sihirbazdaki checkbox'lardan geliyor: kullanici bir alani
                // KAPATTIYSA (checkbox isaretsizse) frontend bos string gonderir, biz de
                // burada varsayilan etikete DUSMEDEN oldugu gibi iletiyoruz ki PDF'te de
                // gercekten gizlensin (bkz. sablon_uret.py::basligi_ciz).
                addslashes((string) $request->input('etiket_ad_soyad', 'Ad Soyad')),
                addslashes((string) $request->input('etiket_sinif', 'Sinif')),
                addslashes((string) $request->input('etiket_sinav_adi', 'Sinav Adi'))
            );
            exec($cmdPdf, $pdfCiktisi, $pdfKodu);

            if ($koordKodu !== 0 || $pdfKodu !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Form üretilemedi: ' . implode(' ', array_merge($koordCiktisi, $pdfCiktisi)),
                ], 500);
            }

            // Koordinat JSON'u normalde SADECE sunucu icinde (storage/app/omr_scripts)
            // kalir -- tarama pipeline'i onu oradan kullanir. Ama eskiden "Yayinla"
            // dedikten sonra hem PDF hem koordinat JSON bilgisayara iniyordu; bu
            // davranisi geri getirmek icin JSON'u da public storage'a KOPYALIYORUZ
            // ki indirilebilir bir URL'si olsun (asil kullanilan kopya hala
            // storage/app/omr_scripts icinde, buradaki sadece indirme amacli kopya).
            $koordPublicYolu = $formDir . DIRECTORY_SEPARATOR . 'koordinat_' . $sinav->id . '.json';
            copy($koordCiktiYolu, $koordPublicYolu);

            $formUrl = asset('storage/optik_forms/form_' . $sinav->id . '.pdf');
            $koordUrl = asset('storage/optik_forms/koordinat_' . $sinav->id . '.json');

            // Web panelinde (session var) eskisi gibi "aktif sınav" session'a yazılır.
            // Mobil/api.php üzerinden gelen isteklerde session hiç yoktur (stateless),
            // bu durumda hasSession() false döner ve burada patlamadan geçilir.
            // Mobil taraf aktif sınavı bu response'taki 'sinav_id' değeriyle takip eder.
            if ($request->hasSession()) {
                $request->session()->put(self::AKTIF_SINAV_SESSION_KEY, $sinav->id);
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Cevap anahtarı başarıyla kaydedildi.',
                'sinav_id' => $sinav->id,
                'form_url' => $formUrl,
                'koordinat_url' => $koordUrl,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getLatestAnswerKey(Request $request)
    {
        try {
            // Session yerine son eklenen sınavı alalım ki hata vermesin:
            $sinav = Sinav::latest()->first();
            $sinavId = $sinav ? $sinav->id : null;

            if (!$sinavId) {
                return response()->json(['success' => false, 'message' => 'Bu oturumda henüz bir cevap anahtarı girilmedi.']);
            }

            $sinav = Sinav::find($sinavId);
            if (!$sinav) {
                if ($request->hasSession()) {
                    $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);
                }
                return response()->json(['success' => false, 'message' => 'Aktif cevap anahtarı bulunamadı.']);
            }

            return response()->json([
                'success'      => true,
                'exam_name'    => $sinav->sinav_adi,
                'ders_kodu'    => $sinav->ders_kodu,
                'answers'      => $sinav->cevap_anahtari,
                'penalty_coef' => $sinav->penalty_coef ?? 0, // C kişisi verisi eklendi
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function optikOkut(Request $request)
    {
        // EKLENDİ: python OCR pipeline'i (cv2 import + goruntu isleme) bazen
        // PHP'nin varsayilan max_execution_time suresinden uzun surebiliyor.
        // Bu durumda PHP script'i sessizce kesip baglantiyi hicbir HTTP
        // header'i donmeden kapatiyor -- Flutter tarafinda tam olarak
        // "Connection closed before full header was received" hatasina
        // sebep olan budur. 300 saniyeye cikararak bu erken kesilmeyi
        // engelliyoruz (asil darbogaz makine/agdaysa bu tek basina
        // yetmeyebilir, ama sebeplerden birini kesin olarak eler).
        set_time_limit(300);

        try {
            $request->validate([
                'image' => 'required|file|image|max:10240',
            ]);

            // Aktif sınavı bulma sırası:
            // 1) İstekle birlikte doğrudan sinav_id gelmiş mi (mobil bunu kullanacak,
            //    saveAnswerKey()'in döndürdüğü sinav_id'yi saklayıp burada geri gönderir)
            // 2) Session'da bir aktif sınav var mı (web paneli - eskisi gibi çalışır)
            // 3) exam_name gelmiş mi, o isme ait en güncel sınavı bul (mobil için
            //    sinav_id'yi saklamadıysa yedek yol)
            $sinavId = $request->input('sinav_id');
if (!$sinavId && $request->hasSession()) {
    $sinavId = $request->session()->get(self::AKTIF_SINAV_SESSION_KEY);
}

            if (!$sinavId && $request->filled('exam_name')) {
                $sinavByName = Sinav::where('sinav_adi', $request->input('exam_name'))
                    ->latest()
                    ->first();
                $sinavId = $sinavByName->id ?? null;
            }

            if (!$sinavId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Önce "Cevap Anahtarı" bölümünden bu sınav için bir cevap anahtarı kaydetmelisiniz.',
                ], 422);
            }

            $sinav = Sinav::find($sinavId);
            if (!$sinav || empty($sinav->cevap_anahtari)) {
                if ($request->hasSession()) {
                    $request->session()->forget(self::AKTIF_SINAV_SESSION_KEY);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Aktif cevap anahtarı bulunamadı. Lütfen "Cevap Anahtarı" bölümünden yeniden kaydedin.',
                ], 422);
            }

            $answerKey = $sinav->cevap_anahtari;
            $totalQuestions = count($answerKey);

            $publicDir = storage_path('app/public/optik_forms');
            if (!is_dir($publicDir)) mkdir($publicDir, 0777, true);

            $uploadedFile = $request->file('image');
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'jpg';
            $imageName = 'optik_' . time() . '_' . uniqid() . '.' . $extension;

            $uploadedFile->move($publicDir, $imageName);
            $imagePath = $publicDir . DIRECTORY_SEPARATOR . $imageName;

            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

            $sinavBilgisi = [
                'exam_id'         => $sinav->id,
                'total_questions' => $totalQuestions,
                'answer_key'      => $answerKey,
                'question_weights'=> $sinav->question_weights ?? [], // YENİ EKLENDİ
            ];
            $sinavPath = $tempDir . DIRECTORY_SEPARATOR . 'sinav_' . uniqid() . '.json';
            file_put_contents($sinavPath, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

            $omrDir = base_path('omr_scripts');
            $pipelinePath = $omrDir . DIRECTORY_SEPARATOR . 'pipeline_main.py';

            // === Bu sınava özel koordinat haritası var mı diye bak; yoksa
            // ANINDA (mathematically) üret. Eski sabit dosyaya sadece son
            // çare / geriye dönük uyumluluk olarak düşülür. ===
            $ozelKoordinatYolu = storage_path('app/omr_scripts/koordinat_' . $sinav->id . '.json');

            if (file_exists($ozelKoordinatYolu)) {
                $koordinatPath = $ozelKoordinatYolu;
            } else {
                $uretilenYol = $this->koordinatHaritasiUret(
                    $totalQuestions,
                    $sinav->sik_harfleri ?? 'ABCDE',
                    $sinav->ogrenci_no_hane ?? 9
                );
                $koordinatPath = $uretilenYol ?? ($omrDir . DIRECTORY_SEPARATOR . 'koordinat_haritasi.json');
            }

            // Windows izin sorununu tamamen ortadan kaldıran saf exec yöntemi
            $command = sprintf(
                '"%s" "%s" "%s" "%s" "%s" 2>&1',
                $this->pythonPath,
                $pipelinePath,
                $imagePath,
                $koordinatPath,
                $sinavPath
            );

            exec($command, $outputArray, $resultCode);
            $output = trim(implode("\n", $outputArray));
            $errorOutput = $resultCode !== 0 ? $output : '';

            @unlink($sinavPath);

            if (!$output) {
                return response()->json([
                    'success' => false,
                    'message' => 'Python işlemi hiçbir çıktı üretmedi. Detay: ' . $errorOutput,
                ], 500);
            }

            // ÇÖZÜM 2: Python fazladan hata metni bassa bile sadece saf JSON kısmını cımbızla çekiyoruz
            $jsonStart = strpos($output, '{');
            $jsonEnd = strrpos($output, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $cleanJson = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
                $sonuc = json_decode($cleanJson, true);
            } else {
                $sonuc = json_decode($output, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE || !isset($sonuc['basarili'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kağıt algılanamadı. Lütfen kamerayı kağıda tam tepeden (dik) ve yeşil köşelere oturacak şekilde tutun.',
                ], 422);
            }

            if (!$sonuc['basarili']) {
                return response()->json([
                    'success' => false,
                    'message' => $sonuc['hata'] ?? 'Optik form okunamadı (Köşeler bulunamadı veya açı çok yamuk).',
                ], 422);
            }

            // ÇÖZÜM 1: Öğrenci numarası boş okunursa veritabanını çökertmek yerine kullanıcıyı uyar
            $ogrenciNo = $sonuc['student_no'] ?? null;
            if (empty($ogrenciNo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hata: Öğrenci numarası okunamadı! Lütfen kodlamayı kontrol edip formu tekrar net bir şekilde okutun.',
                ], 422);
            }


            $ogrenciSonuc = OgrenciSonuc::create([
                'sinav_id'          => $sinav->id,
                'ogrenci_no'        => $ogrenciNo,
                'ogrenci_ad_soyad'  => null,
                'ogrenci_cevaplari' => $sonuc['student_answers'] ?? [],
                'dogru_sayisi'      => $sonuc['correct_count'] ?? 0,
                'yanlis_sayisi'     => $sonuc['wrong_count'] ?? 0,
                'bos_sayisi'        => $sonuc['blank_count'] ?? 0,
                'toplam_puan'       => $sonuc['score'] ?? 0,
                'gorsel_yolu'       => 'optik_forms/' . $imageName,
            ]);

            return response()->json([
                'success'          => true,
                'ogrenci_sonuc_id' => $ogrenciSonuc->id,
                'obs_hazir'        => !empty($sinav->obs_exam_id),
                'ogrenci_no'       => $ogrenciNo,
                'dogru'            => $sonuc['correct_count'] ?? 0,
                'yanlis'           => $sonuc['wrong_count'] ?? 0,
                'bos'              => $sonuc['blank_count'] ?? 0,
                'puan'             => number_format($sonuc['score'] ?? 0, 2),
                'status'           => $sonuc['status'] ?? 'success',
                
                // --- TELEFONDA GÖRÜNMESİ İÇİN BU İKİ SATIRI EKLEDİK ---
                'cevaplar'         => $sonuc['student_answers'] ?? [],
                'gorsel_yolu' => 'storage/optik_forms/' . $imageName,
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Hatası: ' . $e->getMessage()], 500);
        }
    }

    public function gecmisSonuclar()
    {
        try {
            $sinavlar = Sinav::all()->keyBy('id');

            $sonuclar = OgrenciSonuc::orderBy('id', 'desc')
                ->get()
                ->map(function ($s) use ($sinavlar) {
                    $sinav = $sinavlar[$s->sinav_id] ?? null;

                    return [
                        'id'               => $s->id,
                        'exam_name'        => $sinav->sinav_adi ?? 'Genel Optik Sınav',
                        'ogrenci_no'       => $s->ogrenci_no,
                        'correct_count'    => $s->dogru_sayisi,
                        'wrong_count'      => $s->yanlis_sayisi,
                        'empty_count'      => $s->bos_sayisi,
                        'total_score'      => $s->toplam_puan,
                        'created_at'       => $s->created_at,
                        'obs_kayit_edildi' => (bool) $s->obs_kayit_edildi,
                        'obs_hazir'        => (bool) ($sinav->obs_exam_id ?? false),
                    ];
                });

            return response()->json(['success' => true, 'data' => $sonuclar]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Taranan sonucu OBS'ye (exam_results tablosuna) kaydeder
    public function obsKaydet(Request $request)
    {
        try {
            $request->validate([
                'ogrenci_sonuc_id' => 'required|integer',
            ]);

            $ogrenciSonuc = OgrenciSonuc::find($request->ogrenci_sonuc_id);

            if (!$ogrenciSonuc) {
                return response()->json(['success' => false, 'message' => 'Sonuç bulunamadı.'], 404);
            }

            if ($ogrenciSonuc->obs_kayit_edildi) {
                return response()->json(['success' => false, 'message' => 'Bu sonuç zaten OBS\'ye kaydedilmiş.'], 422);
            }

            $sinav = Sinav::find($ogrenciSonuc->sinav_id);

            if (!$sinav || !$sinav->obs_exam_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu sınav için OBS eşleştirmesi yapılmamış. Lütfen "Cevap Anahtarı" ekranından bu sınavı OBS sınavıyla eşleştirin.',
                ], 422);
            }

            if (!$ogrenciSonuc->ogrenci_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenci numarası okunamadığı için OBS\'ye kaydedilemiyor.',
                ], 422);
            }

            $obsOgrenci = ObsStudent::find($ogrenciSonuc->ogrenci_no);

            if (!$obsOgrenci) {
                return response()->json([
                    'success' => false,
                    'message' => 'Öğrenci numarası "' . $ogrenciSonuc->ogrenci_no . '" OBS\'de kayıtlı değil.',
                ], 422);
            }

            // Aynı öğrenci ve sınav için kayıt varsa güncelle, yoksa yeni oluştur
            $examResult = ObsExamResult::updateOrCreate(
                [
                    'exam_id'    => $sinav->obs_exam_id,
                    'student_no' => $ogrenciSonuc->ogrenci_no,
                ],
                [
                    'score'           => $ogrenciSonuc->toplam_puan,
                    'correct_count'   => $ogrenciSonuc->dogru_sayisi,
                    'wrong_count'     => $ogrenciSonuc->yanlis_sayisi,
                    'blank_count'     => $ogrenciSonuc->bos_sayisi,
                    'student_answers' => $ogrenciSonuc->ogrenci_cevaplari,
                ]
            );

            $ogrenciSonuc->obs_exam_result_id = $examResult->id;
            $ogrenciSonuc->obs_kayit_edildi = true;
            $ogrenciSonuc->save();

            return response()->json([
                'success' => true,
                'message' => 'Sonuç OBS\'ye başarıyla kaydedildi.',
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportExcel()
    {
        return Excel::download(new ResultsExport, 'zipgrade_sonuclar.xlsx');
    }

    public function anahtarOku(Request $request)
    {
        // EKLENDİ: optikOkut() ile ayni sebep -- python pipeline uzun surerse
        // PHP'nin kendi zaman asimi baglantiyi header donmeden kesmesin diye.
        set_time_limit(300);

        try {
            $request->validate([
                'image'           => 'required|file|image|max:10240',
                'toplam_soru'     => 'required|integer|min:1|max:200',
                'sik_harfleri'    => 'nullable|string',   // örn: "ABCDE" -- yoksa varsayılan ABCDE
                'ogrenci_no_hane' => 'nullable|integer|min:1|max:15',
            ]);

            $publicDir = storage_path('app/public/optik_forms');
            if (!is_dir($publicDir)) mkdir($publicDir, 0777, true);

            $uploadedFile = $request->file('image');
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'jpg';
            $imageName = 'anahtar_' . time() . '_' . uniqid() . '.' . $extension;
            $uploadedFile->move($publicDir, $imageName);
            $imagePath = $publicDir . DIRECTORY_SEPARATOR . $imageName;

            $toplamSoru = (int) $request->input('toplam_soru');
            $sikHarfleri = $request->input('sik_harfleri', 'ABCDE');
            $ogrenciNoHane = (int) $request->input('ogrenci_no_hane', 9);

            $sinavBilgisi = [
                'exam_id' => 0,
                'total_questions' => $toplamSoru,
                'answer_key' => [],
            ];
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $sinavPath = $tempDir . DIRECTORY_SEPARATOR . 'sinav_anahtar_' . uniqid() . '.json';
            file_put_contents($sinavPath, json_encode($sinavBilgisi, JSON_UNESCAPED_UNICODE));

            // --- DİNAMİK KOORDİNAT: sabit dosya yerine, bu taramaya ÖZEL üretiliyor ---
            $koordinatPath = $this->koordinatHaritasiUret($toplamSoru, $sikHarfleri, $ogrenciNoHane);
            if ($koordinatPath === null) {
                return response()->json(['success' => false, 'message' => 'Koordinat haritası üretilemedi.'], 500);
            }

            $omrDir = base_path('omr_scripts');
            $pipelinePath = $omrDir . DIRECTORY_SEPARATOR . 'pipeline_main.py';

            $command = sprintf(
                '"%s" "%s" "%s" "%s" "%s" 2>&1',
                $this->pythonPath, $pipelinePath, $imagePath, $koordinatPath, $sinavPath
            );

            exec($command, $outputArray, $resultCode);
            $output = trim(implode("\n", $outputArray));
            @unlink($sinavPath);

            if (!$output) {
                return response()->json(['success' => false, 'message' => 'Python işlemi çıktı üretmedi.'], 500);
            }

            $jsonStart = strpos($output, '{');
            $jsonEnd = strrpos($output, '}');
            $cleanJson = ($jsonStart !== false && $jsonEnd !== false)
                ? substr($output, $jsonStart, $jsonEnd - $jsonStart + 1)
                : $output;
            $sonuc = json_decode($cleanJson, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($sonuc['basarili']) || !$sonuc['basarili']) {
                return response()->json([
                    'success' => false,
                    'message' => $sonuc['hata'] ?? 'Cevap anahtarı formu okunamadı.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'cevaplar' => $sonuc['student_answers'] ?? [],
                'message' => 'Cevap anahtarı başarıyla tarandı.',
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Sistem Hatası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verilen soru/şık/hane kombinasyonu için koordinat haritasını üretir
     * (ya da daha önce üretildiyse cache'den döner). Artık TEK bir sabit
     * dosya yerine, her form boyutu için matematiksel olarak doğru harita
     * üretiliyor -- bkz. koordinat_uretici.py.
     */
    private function koordinatHaritasiUret(int $toplamSoru, string $sikHarfleri, int $ogrenciNoHane): ?string
    {
        $cacheDir = storage_path('app/omr_scripts/uretilen_haritalar');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        // Öğrenci numarası hane sayısını form türüne göre sabitle
        if ($toplamSoru > 50) {
            $ogrenciNoHane = 9;
        } else {
            $ogrenciNoHane = 5;
        }

        // Aynı form için tekrar tekrar üretmemek adına önbellek dosya adı
        $cikti = $cacheDir . DIRECTORY_SEPARATOR . "koordinat_{$toplamSoru}s_{$sikHarfleri}_{$ogrenciNoHane}h.json";
        if (file_exists($cikti)) {
            return $cikti;
        }

        // Web panelindeki PDF çizim matrisinin (milimetre) piksele dönüştürülmüş BİREBİR kopyası
        $omrOutW = 1000;
        $omrOutH = 1400;
        $omrPad  = 40; // anchor_detect.py kenar boşluğu

        $anchorSize = 9.0;
        $anchorTlX = 14.0 + $anchorSize / 2; // 18.5
        $anchorTrX = 187.0 + $anchorSize / 2; // 191.5
        $anchorTlY = 14.0 + $anchorSize / 2; // 18.5
        $anchorBlY = 274.0 + $anchorSize / 2; // 278.5

        $mmToPx = function($xmm, $ymm) use ($omrPad, $omrOutW, $omrOutH, $anchorTlX, $anchorTrX, $anchorTlY, $anchorBlY) {
            $px = $omrPad + ($xmm - $anchorTlX) / ($anchorTrX - $anchorTlX) * ($omrOutW - 2 * $omrPad);
            $py = $omrPad + ($ymm - $anchorTlY) / ($anchorBlY - $anchorTlY) * ($omrOutH - 2 * $omrPad);
            return [round($px), round($py)];
        };

        $harita = [
            'ogrenci_no' => [],
            'sorular'    => []
        ];

        // Öğrenci Numarası Matrisi
        if ($ogrenciNoHane > 0) {
            $idStartX = 25.0;
            $idStartY = 31.0;
            for ($i = 0; $i < $ogrenciNoHane; $i++) {
                $colX = $idStartX + ($i * 10.5);
                $basamakAdi = "basamak_" . ($i + 1);
                $harita['ogrenci_no'][$basamakAdi] = [];

                for ($j = 0; $j <= 9; $j++) {
                    $bY = $idStartY + 10.0 + ($j * 4.2);
                    $bX = $colX + 3.75;
                    $harita['ogrenci_no'][$basamakAdi][(string)$j] = $mmToPx($bX, $bY);
                }

                // ÖNEMLİ DÜZELTME: $harita['ogrenci_no'][$basamakAdi] içindeki anahtarlar
                // "0","1",...,"9" -- yani sıralı sayısal string'ler. PHP'nin json_encode()
                // fonksiyonu, TÜM anahtarları 0'dan başlayan sıralı tamsayı olan bir diziyi
                // JSON OBJECT değil, JSON ARRAY olarak yazar (ör. {"0":[..],"1":[..]} yerine
                // [[..],[..]]). Python tarafında (pipeline_main.py -> ogrenci_no_oku ->
                // .items()) bu alan MUTLAKA bir dict/obje bekleniyor; array olarak gelince
                // "'list' object has no attribute 'items'" hatasi ile cakiliyor -- ekran
                // goruntusundeki "ANAHTAR TARA" hatasinin birebir sebebi budur. (object)
                // cast'i, anahtarlari korkarak JSON object olarak yazilmasini garanti eder.
                $harita['ogrenci_no'][$basamakAdi] = (object) $harita['ogrenci_no'][$basamakAdi];
            }
        }

        // Soru Matrisi
        $startX = 25.0;
        $startY = $ogrenciNoHane > 0 ? 88.0 : 38.0;
        $satirYuksekligi = 5.3;
        $maksSatir = 34;
        $sikAraligi = 5.8;
        $ilkSikOfseti = 9.0;
        
        $maxSik = strlen($sikHarfleri);
        $colWidth = $ilkSikOfseti + ($maxSik - 1) * $sikAraligi + 7.0;
        $labels = str_split($sikHarfleri);

        for ($i = 1; $i <= $toplamSoru; $i++) {
            $colIndex = (int) floor(($i - 1) / $maksSatir);
            $rowIndex = ($i - 1) % $maksSatir;

            $qX = $startX + ($colIndex * $colWidth);
            $qY = $startY + ($rowIndex * $satirYuksekligi);

            $soruHarita = ['soru_no' => $i];
            foreach ($labels as $idx => $harf) {
                $bx = $qX + $ilkSikOfseti + ($idx * $sikAraligi);
                $by = $qY - 1.0;
                $soruHarita[$harf] = $mmToPx($bx, $by);
            }
            $harita['sorular'][] = $soruHarita;
        }

        // Sınava özel hatasız JSON dosyasını oluştur ve kaydet
        file_put_contents($cikti, json_encode($harita, JSON_UNESCAPED_UNICODE));
        return $cikti;
    }



    public function apiSiniflariGetir(Request $request)
    {
        $query = DB::table('siniflar')->orderBy('class_name');
        if ($request->has('email')) {
            $teacher = DB::table('teachers')->where('email', $request->input('email'))->first();
            if ($teacher) $query->where('teacher_id', $teacher->id);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function apiOgrencileriGetir(Request $request)
    {
        $query = DB::table('panel_students')->orderBy('id', 'desc');
        if ($request->has('email')) {
            $teacher = DB::table('teachers')->where('email', $request->input('email'))->first();
            if ($teacher) $query->where('teacher_id', $teacher->id);
        }
        return response()->json(['success' => true, 'data' => $query->get()]);
    }
    public function apiOgrenciKaydet(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'       => 'required|string|max:150',
                'student_no' => 'required|string|max:50',
                'class_id'   => 'nullable|integer',
            ]);

            $id = DB::table('students')->insertGetId([
                'name'       => $validated['name'],
                'student_no' => $validated['student_no'],
                'class_id'   => $validated['class_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'id' => $id, 'message' => 'Öğrenci eklendi']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Var olan bir öğrencinin adını / sınıfını düzenlemek için (özellikle
    // OBS'den sadece numarasıyla aktarılmış, adı boş kalmış kayıtları
    // doldurmak amacıyla eklendi).
    public function apiOgrenciGuncelle(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name'       => 'sometimes|required|string|max:150',
                'student_no' => 'sometimes|required|string|max:50',
                'class_id'   => 'nullable|integer',
            ]);

            $ogrenci = DB::table('students')->where('id', $id)->first();
            if (!$ogrenci) {
                return response()->json(['success' => false, 'message' => 'Öğrenci bulunamadı.'], 404);
            }

            $guncelleme = array_intersect_key($validated, array_flip(['name', 'student_no', 'class_id']));
            $guncelleme['updated_at'] = now();

            DB::table('students')->where('id', $id)->update($guncelleme);

            return response()->json(['success' => true, 'message' => 'Öğrenci güncellendi.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri gönderildi.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Sınıf silme (örn. test amaçlı eklenen sınıfları temizlemek için).
    // Not: O sınıfa bağlı öğrenciler silinmiyor, sadece class_id'leri
    // null'a çekilebilir istenirse -- şu an basitçe sınıf kaydı siliniyor.
    public function apiSinifSil($id)
    {
        try {
            $silindi = DB::table('siniflar')->where('id', $id)->delete();
            if (!$silindi) {
                return response()->json(['success' => false, 'message' => 'Sınıf bulunamadı.'], 404);
            }
            return response()->json(['success' => true, 'message' => 'Sınıf silindi.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
  public function apiSinavlariGetir(Request $request)
    {
        try {
            $query = \App\Models\Sinav::orderBy('id', 'desc');
            
            if ($request->has('email')) {
                $teacher = DB::table('teachers')->where('email', $request->input('email'))->first();
                if ($teacher) {
                    $query->where('teacher_id', $teacher->id);
                } else {
                    return response()->json(['success' => true, 'data' => []]);
                }
            }
            $sinavlar = $query->get();

            $sinavlar->transform(function ($sinav) {
                $sinav->id = (int) $sinav->id;
                
                $soruSayisi = 20;
                if (!empty($sinav->cevap_anahtari)) {
                    $decoded = is_string($sinav->cevap_anahtari) ? json_decode($sinav->cevap_anahtari, true) : $sinav->cevap_anahtari;
                    if (is_array($decoded)) {
                        $soruSayisi = count($decoded);
                    }
                }
                $sinav->soru_sayisi = $soruSayisi;

                // --- SİHİRLİ KISIM: Veritabanına dokunmadan koordinat dosyasından şık ve hane sayısını okuyoruz! ---
                $siklar = 'ABCDE';
                $hane = 9;
                $koordDosya = storage_path('app/public/optik_forms/koordinat_' . $sinav->id . '.json');
                
                if (file_exists($koordDosya)) {
                    $json = json_decode(file_get_contents($koordDosya), true);
                    if (isset($json['sorular'][0])) {
                        $harfler = [];
                        foreach ($json['sorular'][0] as $k => $v) {
                            if ($k !== 'soru_no') $harfler[] = $k;
                        }
                        if (count($harfler) > 0) {
                            sort($harfler);
                            $siklar = implode('', $harfler);
                        }
                    }
                    if (isset($json['ogrenci_no'])) {
                        $hane = count($json['ogrenci_no']);
                    }
                }
                $sinav->sik_harfleri = $siklar;
                $sinav->hane_sayisi = $hane;

                return $sinav;
            });

            return response()->json(['success' => true, 'data' => $sinavlar]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =====================================================================
    // WEB PANELİ İÇİN: SINAVLAR / SINIFLAR / ÖĞRENCİLER (kullanıcıya özel)
    // Arkadaşımın kodundan hiç dokunmadan entegre edildi. apiSiniflariGetir /
    // apiOgrencileriGetir vb. (yukarıdaki) mobil tarafın herkese açık genel
    // listeleridir; buradakiler ise Auth::id() ile öğretmene özel filtrelenmiş,
    // panelYetkiKontrol() ile korunan ayrı bir set. İkisi çakışmıyor.
    // =====================================================================
    private function panelYetkiKontrol()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Oturum açmanız gerekiyor, lütfen tekrar giriş yapın.'], 401);
        }
        return null;
    }

    // --- SINAVLAR ---
    public function panelSinavlariGetir()
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $sinavlar = Sinav::where('teacher_id', Auth::id())
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'          => $s->id,
                    'sinav_adi'   => $s->sinav_adi,
                    'ders_kodu'   => $s->ders_kodu,
                    'soru_sayisi' => is_array($s->cevap_anahtari) ? count($s->cevap_anahtari) : 0,
                    'tarih'       => optional($s->created_at)->format('Y-m-d'),
                ];
            });

        return response()->json(['success' => true, 'data' => $sinavlar]);
    }

    // "Seçilenleri Sil" butonu artık bunu çağırıyor — sadece isteği
    // atan öğretmenin KENDİ sınavları silinebilir (whereIn + teacher_id
    // filtresi birlikte), başka bir öğretmenin sınavı silinemez.
    public function panelSinavSil(Request $request)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $silinen = Sinav::where('teacher_id', Auth::id())
            ->whereIn('id', $request->input('ids'))
            ->delete();

        return response()->json(['success' => true, 'message' => $silinen . ' sınav silindi.']);
    }

    // --- SINIFLAR ---
    public function panelSiniflariGetir()
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $siniflar = DB::table('siniflar')
            ->where('teacher_id', Auth::id())
            ->orderBy('class_name')
            ->get()
            ->map(function ($sinif) {
                $ogrenciSayisi = DB::table('panel_students')->where('class_id', $sinif->id)->count();
                return [
                    'id'             => $sinif->id,
                    'class_name'     => $sinif->class_name,
                    'ogrenci_sayisi' => $ogrenciSayisi,
                ];
            });

        return response()->json(['success' => true, 'data' => $siniflar]);
    }

    public function panelSinifEkle(Request $request)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        try {
            $validated = $request->validate(['class_name' => 'required|string|max:100']);

            $id = DB::table('siniflar')->insertGetId([
                'class_name' => $validated['class_name'],
                'teacher_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'id' => $id, 'message' => 'Sınıf eklendi.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri.'], 422);
        }
    }

    public function panelSinifGuncelle(Request $request, $id)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $sinif = DB::table('siniflar')->where('id', $id)->where('teacher_id', Auth::id())->first();
        if (!$sinif) {
            return response()->json(['success' => false, 'message' => 'Sınıf bulunamadı ya da bu sınıf size ait değil.'], 404);
        }

        try {
            $validated = $request->validate(['class_name' => 'required|string|max:100']);

            DB::table('siniflar')->where('id', $id)->update([
                'class_name' => $validated['class_name'],
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Sınıf güncellendi.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri.'], 422);
        }
    }

    public function panelSinifSil($id)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $sinif = DB::table('siniflar')->where('id', $id)->where('teacher_id', Auth::id())->first();
        if (!$sinif) {
            return response()->json(['success' => false, 'message' => 'Sınıf bulunamadı ya da bu sınıf size ait değil.'], 404);
        }

        DB::table('panel_students')->where('class_id', $id)->update(['class_id' => null]);
        DB::table('siniflar')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Sınıf silindi.']);
    }

    // --- ÖĞRENCİLER (panel) ---
    public function panelOgrencileriGetir()
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $ogrenciler = DB::table('panel_students')
            ->leftJoin('siniflar', 'panel_students.class_id', '=', 'siniflar.id')
            ->where('panel_students.teacher_id', Auth::id())
            ->orderBy('panel_students.id', 'desc')
            ->get(['panel_students.id', 'panel_students.student_no', 'panel_students.name', 'panel_students.class_id', 'siniflar.class_name']);

        return response()->json(['success' => true, 'data' => $ogrenciler]);
    }

    public function panelOgrenciEkle(Request $request)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        try {
            $validated = $request->validate([
                'student_no' => 'required|string|max:50',
                'name'       => 'required|string|max:150',
                'class_id'   => 'nullable|integer',
            ]);

            $id = DB::table('panel_students')->insertGetId([
                'student_no' => $validated['student_no'],
                'name'       => $validated['name'],
                'class_id'   => $validated['class_id'] ?? null,
                'teacher_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'id' => $id, 'message' => 'Öğrenci eklendi.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri.'], 422);
        }
    }

    public function panelOgrenciGuncelle(Request $request, $id)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $ogrenci = DB::table('panel_students')->where('id', $id)->where('teacher_id', Auth::id())->first();
        if (!$ogrenci) {
            return response()->json(['success' => false, 'message' => 'Öğrenci bulunamadı ya da size ait değil.'], 404);
        }

        try {
            $validated = $request->validate([
                'student_no' => 'required|string|max:50',
                'name'       => 'required|string|max:150',
                'class_id'   => 'nullable|integer',
            ]);

            DB::table('panel_students')->where('id', $id)->update([
                'student_no' => $validated['student_no'],
                'name'       => $validated['name'],
                'class_id'   => $validated['class_id'] ?? null,
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Öğrenci güncellendi.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first() ?? 'Geçersiz veri.'], 422);
        }
    }

    public function panelOgrenciSil($id)
    {
        if ($hata = $this->panelYetkiKontrol()) return $hata;

        $silinen = DB::table('panel_students')->where('id', $id)->where('teacher_id', Auth::id())->delete();

        if (!$silinen) {
            return response()->json(['success' => false, 'message' => 'Öğrenci bulunamadı ya da size ait değil.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Öğrenci silindi.']);
    }
}