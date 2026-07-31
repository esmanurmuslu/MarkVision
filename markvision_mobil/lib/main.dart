import 'package:flutter/material.dart';
import 'package:camera/camera.dart';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'dart:convert';

// =====================================================================
// NGROK API AYARI (KENDİ NGROK LINKIN ILE DEGISTIR)
// =====================================================================
// DİKKAT: Backend rotaları web.php'de olduğu için sonuna /api veya /v1 EKLENMEMELİDİR.
const String API_URL = "https://ducking-cartridge-emerald.ngrok-free.dev";
List<CameraDescription> cameras = [];

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  try {
    cameras = await availableCameras();
  } catch (e) {
    debugPrint('Kamera başlatılamadı: $e');
  }
  runApp(const MarkVisionApp());
}

class MarkVisionApp extends StatelessWidget {
  const MarkVisionApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MarkVision',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primaryColor: const Color(0xFF2C5E1A),
        scaffoldBackgroundColor: const Color(0xFFF2F2F2),
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF2C5E1A)),
      ),
      home: const GirisSayfasi(),
    );
  }
}

// =====================================================================
// GİRİŞ EKRANI (LOGIN)
// =====================================================================
class GirisSayfasi extends StatefulWidget {
  const GirisSayfasi({super.key});

  @override
  State<GirisSayfasi> createState() => _GirisSayfasiState();
}

class _GirisSayfasiState extends State<GirisSayfasi> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _isLoading = false;

  void _girisYap() async {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('E-posta ve şifre boş bırakılamaz.')));
      return;
    }

    setState(() => _isLoading = true);
    await Future.delayed(const Duration(seconds: 1));
    setState(() => _isLoading = false);

    if (mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => AnaNavigasyonSayfasi(girisMail: _emailController.text.trim())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.school, size: 80, color: Color(0xFF2C5E1A)),
              const SizedBox(height: 16),
              const Text('MARKVISION', style: TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Color(0xFF2C5E1A), letterSpacing: 2)),
              const SizedBox(height: 8),
              const Text('Öğretmen Giriş Paneli', style: TextStyle(color: Colors.grey)),
              const SizedBox(height: 40),
              TextField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(labelText: 'E-Posta Adresi', border: OutlineInputBorder(), prefixIcon: Icon(Icons.email_outlined)),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _passwordController,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Şifre', border: OutlineInputBorder(), prefixIcon: Icon(Icons.lock_outline)),
              ),
              const SizedBox(height: 24),
              _isLoading
                  ? const CircularProgressIndicator(color: Color(0xFF2C5E1A))
                  : SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2C5E1A),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  onPressed: _girisYap,
                  child: const Text('GİRİŞ YAP', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// =====================================================================
// ÖZEL KAMERA EKRANI (VIZORLU)
// =====================================================================
class KameraTaramaSayfasi extends StatefulWidget {
  final String formAdi;
  const KameraTaramaSayfasi({super.key, required this.formAdi});

  @override
  State<KameraTaramaSayfasi> createState() => _KameraTaramaSayfasiState();
}

class _KameraTaramaSayfasiState extends State<KameraTaramaSayfasi> {
  CameraController? _controller;
  bool _isCameraInitialized = false;

  @override
  void initState() {
    super.initState();
    _initCamera();
  }

  Future<void> _initCamera() async {
    if (cameras.isEmpty) return;
    CameraDescription? backCamera;
    for (var camera in cameras) {
      if (camera.lensDirection == CameraLensDirection.back) {
        backCamera = camera;
        break;
      }
    }
    backCamera ??= cameras.first;

    _controller = CameraController(backCamera, ResolutionPreset.high, enableAudio: false);

    try {
      await _controller!.initialize();
      if (!mounted) return;
      setState(() {
        _isCameraInitialized = true;
      });
    } catch (e) {
      debugPrint("Kamera başlatma hatası: $e");
    }
  }

  Future<void> _fotografCek() async {
    if (_controller == null || !_controller!.value.isInitialized) return;
    if (_controller!.value.isTakingPicture) return;

    try {
      final XFile photo = await _controller!.takePicture();
      if (mounted) {
        Navigator.pop(context, photo.path);
      }
    } catch (e) {
      debugPrint("Fotoğraf çekilirken hata: $e");
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: const Text('SCANNING', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1)),
        leading: IconButton(icon: const Icon(Icons.arrow_back, color: Colors.white), onPressed: () => Navigator.pop(context)),
        actions: [IconButton(icon: const Icon(Icons.settings_outlined, color: Colors.white), onPressed: () {})],
      ),
      body: !_isCameraInitialized
          ? const Center(child: CircularProgressIndicator(color: Colors.white))
          : Stack(
        children: [
          SizedBox(width: double.infinity, height: double.infinity, child: CameraPreview(_controller!)),
          Positioned(top: 40, left: 20, child: _vizorKaresi()),
          Positioned(top: 40, right: 20, child: _vizorKaresi()),
          Positioned(bottom: 150, left: 20, child: _vizorKaresi()),
          Positioned(bottom: 150, right: 20, child: _vizorKaresi()),
          Align(
            alignment: Alignment.bottomCenter,
            child: Padding(
              padding: const EdgeInsets.only(bottom: 30),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    decoration: BoxDecoration(color: Colors.white.withOpacity(0.7), borderRadius: BorderRadius.circular(8)),
                    child: Text(
                      'Vizörlerde kareleri hizalayın\n${widget.formAdi}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.black87),
                    ),
                  ),
                  const SizedBox(height: 20),
                  FloatingActionButton(
                    backgroundColor: const Color(0xFF2C5E1A),
                    onPressed: _fotografCek,
                    child: const Icon(Icons.camera_alt, color: Colors.white, size: 30),
                  )
                ],
              ),
            ),
          )
        ],
      ),
    );
  }

  Widget _vizorKaresi() {
    return Container(width: 70, height: 70, color: Colors.white.withOpacity(0.6));
  }
}

// =====================================================================
// VERİ MODELLERİ
// =====================================================================
class PaperResult {
  final String ogrenciNo;
  final int dogru;
  final int yanlis;
  final int bos;
  final double puan;
  final double tamPuan;
  final Map<int, String> cevaplar;
  final DateTime tarih;

  PaperResult({
    required this.ogrenciNo,
    required this.dogru,
    required this.yanlis,
    required this.bos,
    required this.puan,
    required this.tamPuan,
    required this.cevaplar,
    DateTime? tarih,
  }) : tarih = tarih ?? DateTime.now();
}

class TestModel {
  String isim;
  String cevapKagidi;
  String tarih;
  String folder;
  int soruSayisi;
  Map<String, Map<int, String>> anahtarVersiyonlari;
  String aktifVersiyon;
  Map<int, double> agirliklar;
  List<PaperResult> kagitlar;
  String? sinavId; // SUNUCUDAN GELEN SINAV ID'SI (P0-3 DİNAMİK KOORDİNAT İÇİN)[cite: 1]

  TestModel({
    required this.isim,
    required this.cevapKagidi,
    required this.tarih,
    required this.folder,
    int? soruSayisi,
    Map<String, Map<int, String>>? anahtarVersiyonlari,
    String? aktifVersiyon,
    Map<int, double>? agirliklar,
    List<PaperResult>? kagitlar,
    this.sinavId,
  })  : soruSayisi = soruSayisi ?? _soruSayisindanCikar(cevapKagidi),
        anahtarVersiyonlari = anahtarVersiyonlari ?? {'A': {}},
        aktifVersiyon = aktifVersiyon ?? 'A',
        agirliklar = agirliklar ?? {},
        kagitlar = kagitlar ?? [];

  static int _soruSayisindanCikar(String form) {
    if (form.contains('100')) return 100;
    if (form.contains('50')) return 50;
    return 20;
  }

  Map<int, String> get cevaplar => anahtarVersiyonlari.putIfAbsent(aktifVersiyon, () => {});
  set cevaplar(Map<int, String> value) { anahtarVersiyonlari[aktifVersiyon] = value; }
  double agirlik(int soruNo) => agirliklar[soruNo] ?? 1.0;

  double get toplamPuan {
    double t = 0;
    for (int i = 1; i <= soruSayisi; i++) {
      t += agirlik(i);
    }
    return t;
  }
}

class ClassModel { String isim; ClassModel({required this.isim}); }
class StudentModel { String ad; String numara; String sinif; StudentModel({required this.ad, required this.numara, required this.sinif}); }
class FolderModel { String isim; FolderModel({required this.isim}); }

// =====================================================================
// ANA NAVİGASYON (BOTTOM BAR)
// =====================================================================
class AnaNavigasyonSayfasi extends StatefulWidget {
  final String girisMail;
  const AnaNavigasyonSayfasi({super.key, required this.girisMail});

  @override
  State<AnaNavigasyonSayfasi> createState() => _AnaNavigasyonSayfasiState();
}

class _AnaNavigasyonSayfasiState extends State<AnaNavigasyonSayfasi> {
  int _seciliSekme = 0;

  final List<TestModel> testler = [
    TestModel(isim: 'Fizik Vize Sınavı', cevapKagidi: '20 Question Form (1)', tarih: '2026-07-30', folder: 'Main Folder')
  ];
  final List<FolderModel> klasorler = [FolderModel(isim: 'Main Folder'), FolderModel(isim: 'Arşiv')];
  final List<ClassModel> siniflar = [];
  final List<StudentModel> ogrenciler = [];

  @override
  Widget build(BuildContext context) {
    final sayfalar = [
      TestlerSekmesi(testler: testler, klasorler: klasorler),
      SiniflarSekmesi(siniflar: siniflar, ogrenciler: ogrenciler),
      OgrencilerSekmesi(ogrenciler: ogrenciler, siniflar: siniflar),
      KlasorlerSekmesi(klasorler: klasorler, testler: testler),
      BulutSekmesi(girisMail: widget.girisMail),
    ];
    return Scaffold(
      body: sayfalar[_seciliSekme],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _seciliSekme,
        selectedItemColor: const Color(0xFF2C5E1A),
        unselectedItemColor: Colors.grey,
        type: BottomNavigationBarType.fixed,
        onTap: (index) => setState(() => _seciliSekme = index),
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.check_box_outlined), label: 'Testler'),
          BottomNavigationBarItem(icon: Icon(Icons.groups_outlined), label: 'Sınıflar'),
          BottomNavigationBarItem(icon: Icon(Icons.person_outline), label: 'Öğrenciler'),
          BottomNavigationBarItem(icon: Icon(Icons.folder_outlined), label: 'Klasörler'),
          BottomNavigationBarItem(icon: Icon(Icons.person), label: 'Hesap'),
        ],
      ),
    );
  }
}

// =====================================================================
// 1. TESTLER EKRANI
// =====================================================================
class TestlerSekmesi extends StatefulWidget {
  final List<TestModel> testler;
  final List<FolderModel> klasorler;
  const TestlerSekmesi({super.key, required this.testler, required this.klasorler});

  @override
  State<TestlerSekmesi> createState() => _TestlerSekmesiState();
}

class _TestlerSekmesiState extends State<TestlerSekmesi> {
  String _arama = '';
  bool _tarihAzalan = true;

  List<TestModel> get _gorunenListe {
    var liste = widget.testler.where((t) => t.isim.toLowerCase().contains(_arama.toLowerCase())).toList();
    liste.sort((a, b) => _tarihAzalan ? b.tarih.compareTo(a.tarih) : a.tarih.compareTo(b.tarih));
    return liste;
  }

  void _yeniTestModalGoster() {
    String isimStr = '';
    String secilenForm = '20 Question Form (1)';
    String tarihStr = '2026-07-30';
    String secilenFolder = widget.klasorler.isNotEmpty ? widget.klasorler.first.isim : 'Main Folder';

    showDialog(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(builder: (dialogContext, setDialogState) {
          return AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            title: const Center(child: Text('Yeni Test', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.black54))),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(onChanged: (val) => isimStr = val, decoration: const InputDecoration(labelText: 'İsim', border: OutlineInputBorder(), isDense: true)),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: secilenForm,
                    decoration: const InputDecoration(labelText: 'Cevap Kağıdı', border: OutlineInputBorder(), isDense: true),
                    items: ['20 Question Form (1)', '50 Question Form', '100 Question Form'].map((f) => DropdownMenuItem(value: f, child: Text(f, style: const TextStyle(fontSize: 13)))).toList(),
                    onChanged: (val) => setDialogState(() => secilenForm = val!),
                  ),
                  const SizedBox(height: 12),
                  TextField(controller: TextEditingController(text: tarihStr), decoration: const InputDecoration(labelText: 'Tarih', border: OutlineInputBorder(), isDense: true), onChanged: (val) => tarihStr = val),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: secilenFolder,
                    decoration: const InputDecoration(labelText: 'Folder', border: OutlineInputBorder(), isDense: true, prefixIcon: Icon(Icons.folder_open, size: 20)),
                    items: widget.klasorler.map((f) => DropdownMenuItem(value: f.isim, child: Text(f.isim, style: const TextStyle(fontSize: 13)))).toList(),
                    onChanged: (val) => setDialogState(() => secilenFolder = val!),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(onPressed: () => Navigator.pop(dialogContext), child: const Text('İPTAL', style: TextStyle(color: Colors.redAccent, fontWeight: FontWeight.bold))),
              TextButton(
                onPressed: () {
                  if (isimStr.trim().isNotEmpty) {
                    setState(() { widget.testler.insert(0, TestModel(isim: isimStr, cevapKagidi: secilenForm, tarih: tarihStr, folder: secilenFolder)); });
                    Navigator.pop(dialogContext);
                  }
                },
                child: const Text('KAYDET', style: TextStyle(color: Color(0xFF2C5E1A), fontWeight: FontWeight.bold)),
              ),
            ],
          );
        });
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final liste = _gorunenListe;
    return Scaffold(
      appBar: AppBar(backgroundColor: const Color(0xFF2C5E1A), title: const Text('TESTLER', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.2)), elevation: 0),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(8.0),
            color: Colors.white,
            child: Row(
              children: [
                InkWell(
                  onTap: () => setState(() => _tarihAzalan = !_tarihAzalan),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade400), borderRadius: BorderRadius.circular(4)),
                    child: Row(children: [Text('Sırala\nTarih ${_tarihAzalan ? "↓" : "↑"}', style: const TextStyle(fontSize: 11, color: Colors.grey)), const SizedBox(width: 8), const Icon(Icons.arrow_drop_down, color: Colors.black54)]),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(child: TextField(onChanged: (val) => setState(() => _arama = val), decoration: InputDecoration(hintText: 'Arama', isDense: true, contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12), border: OutlineInputBorder(borderRadius: BorderRadius.circular(4), borderSide: BorderSide(color: Colors.grey.shade400))))),
              ],
            ),
          ),
          Expanded(
            child: liste.isEmpty ? const Center(child: Text('Test bulunamadı', style: TextStyle(color: Colors.grey))) : ListView.builder(
              itemCount: liste.length,
              itemBuilder: (context, index) {
                final test = liste[index];
                return InkWell(
                  onTap: () async {
                    await Navigator.push(context, MaterialPageRoute(builder: (context) => TestDetaySayfasi(test: test)));
                    setState(() {});
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    decoration: BoxDecoration(border: Border(bottom: BorderSide(color: Colors.grey.shade300)), color: Colors.white),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(child: Text(test.isim, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87))),
                        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [Text('Kağıtlar: ${test.kagitlar.length}', style: const TextStyle(fontSize: 11, color: Colors.grey)), Text(test.tarih, style: const TextStyle(fontSize: 11, color: Colors.grey))]),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(backgroundColor: const Color(0xFF2C5E1A), onPressed: _yeniTestModalGoster, icon: const Icon(Icons.add, color: Colors.white), label: const Text('YENİ TEST', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold))),
    );
  }
}

// =====================================================================
// SINIFLAR, ÖĞRENCİLER, KLASÖRLER
// =====================================================================
class SiniflarSekmesi extends StatefulWidget {
  final List<ClassModel> siniflar;
  final List<StudentModel> ogrenciler;
  const SiniflarSekmesi({super.key, required this.siniflar, required this.ogrenciler});
  @override State<SiniflarSekmesi> createState() => _SiniflarSekmesiState();
}

class _SiniflarSekmesiState extends State<SiniflarSekmesi> {
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _siniflariGetir();
  }

  Future<void> _siniflariGetir() async {
    setState(() => _isLoading = true);
    try {
      var response = await http.get(
        Uri.parse('$API_URL/obs-sinavlari'), // Veya Kişi A'nın tanımladığı sınıf endpoint'i
        headers: {'ngrok-skip-browser-warning': 'true'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        var data = jsonDecode(response.body);
        if (data is List) {
          setState(() {
            widget.siniflar.clear();
            for (var item in data) {
              widget.siniflar.add(ClassModel(isim: item['name'] ?? item['isim'] ?? 'Sınıf'));
            }
          });
        }
      }
    } catch (e) {
      debugPrint("Sınıf çekme hatası: $e");
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: const Text('SINIFLAR', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: _siniflariGetir,
          )
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2C5E1A)))
          : widget.siniflar.isEmpty
          ? const Center(child: Text('Henüz sınıf bulunamadı.', style: TextStyle(color: Colors.grey)))
          : ListView.builder(
        itemCount: widget.siniflar.length,
        itemBuilder: (context, index) {
          final sinif = widget.siniflar[index];
          final sinifMevcudu = widget.ogrenciler.where((o) => o.sinif == sinif.isim).length;
          return ListTile(
            leading: const Icon(Icons.class_, color: Color(0xFF2C5E1A)),
            title: Text(sinif.isim, style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text('$sinifMevcudu Öğrenci'),
            trailing: const Icon(Icons.chevron_right),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: const Color(0xFF2C5E1A),
        onPressed: _siniflariGetir,
        child: const Icon(Icons.sync, color: Colors.white),
      ),
    );
  }
}
class OgrencilerSekmesi extends StatefulWidget {
  final List<StudentModel> ogrenciler;
  final List<ClassModel> siniflar;
  const OgrencilerSekmesi({super.key, required this.ogrenciler, required this.siniflar});
  @override State<OgrencilerSekmesi> createState() => _OgrencilerSekmesiState();
}

class _OgrencilerSekmesiState extends State<OgrencilerSekmesi> {
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _ogrencileriGetir();
  }

  Future<void> _ogrencileriGetir() async {
    setState(() => _isLoading = true);
    try {
      var response = await http.get(
        Uri.parse('$API_URL/obs/students'), // Laravel OBS StudentController rotası
        headers: {'ngrok-skip-browser-warning': 'true', 'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        var data = jsonDecode(response.body);
        var list = data['data'] ?? data;
        if (list is List) {
          setState(() {
            widget.ogrenciler.clear();
            for (var item in list) {
              widget.ogrenciler.add(StudentModel(
                ad: item['name'] ?? item['ad'] ?? 'Öğrenci',
                numara: (item['student_no'] ?? item['numara'] ?? '0').toString(),
                sinif: item['class_name'] ?? item['sinif'] ?? 'Genel',
              ));
            }
          });
        }
      }
    } catch (e) {
      debugPrint("Öğrenci çekme hatası: $e");
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: const Text('ÖĞRENCİLER', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: _ogrencileriGetir,
          )
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2C5E1A)))
          : widget.ogrenciler.isEmpty
          ? const Center(child: Text('Henüz öğrenci bulunamadı.', style: TextStyle(color: Colors.grey)))
          : ListView.builder(
        itemCount: widget.ogrenciler.length,
        itemBuilder: (context, index) {
          final ogrenci = widget.ogrenciler[index];
          return ListTile(
            leading: CircleAvatar(
              backgroundColor: const Color(0xFF2C5E1A),
              child: Text(ogrenci.ad.isNotEmpty ? ogrenci.ad[0].toUpperCase() : '?', style: const TextStyle(color: Colors.white)),
            ),
            title: Text(ogrenci.ad, style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text('No: ${ogrenci.numara} - Sınıf: ${ogrenci.sinif}'),
            trailing: const Icon(Icons.more_vert),
          );
        },
      ),
      floatingActionButton: FloatingActionButton(
        backgroundColor: const Color(0xFF2C5E1A),
        onPressed: _ogrencileriGetir,
        child: const Icon(Icons.sync, color: Colors.white),
      ),
    );
  }
}

class KlasorlerSekmesi extends StatefulWidget {
  final List<FolderModel> klasorler;
  final List<TestModel> testler;
  const KlasorlerSekmesi({super.key, required this.klasorler, required this.testler});
  @override State<KlasorlerSekmesi> createState() => _KlasorlerSekmesiState();
}
class _KlasorlerSekmesiState extends State<KlasorlerSekmesi> {
  @override Widget build(BuildContext context) {
    return Scaffold(
        appBar: AppBar(backgroundColor: const Color(0xFF2C5E1A), title: const Text('KLASÖRLER', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.2))),
        body: const Center(child: Text('Klasör yönetimi eklenecek.'))
    );
  }
}

// =====================================================================
// BULUT SEKMESİ (HESAP)
// =====================================================================
class BulutSekmesi extends StatefulWidget {
  final String girisMail;
  const BulutSekmesi({super.key, required this.girisMail});

  @override
  State<BulutSekmesi> createState() => _BulutSekmesiState();
}

class _BulutSekmesiState extends State<BulutSekmesi> {
  bool _hesapSilmeEkranindaMi = false;
  final TextEditingController _mailKontrolcusu = TextEditingController();

  void _sifreDegistirModalGoster() {
    final TextEditingController eskiSifreController = TextEditingController();
    final TextEditingController yeniSifreController = TextEditingController();

    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text("Şifre Değiştir", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2C5E1A))),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(controller: eskiSifreController, obscureText: true, decoration: const InputDecoration(labelText: 'Mevcut Şifre', border: OutlineInputBorder())),
                const SizedBox(height: 12),
                TextField(controller: yeniSifreController, obscureText: true, decoration: const InputDecoration(labelText: 'Yeni Şifre', border: OutlineInputBorder())),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("İPTAL", style: TextStyle(color: Colors.grey))),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A)),
              onPressed: () {
                if (eskiSifreController.text.isEmpty || yeniSifreController.text.isEmpty) {
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Alanlar boş bırakılamaz.'), backgroundColor: Colors.red));
                  return;
                }
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Şifreniz değiştirildi. Lütfen tekrar giriş yapın.')));
                Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const GirisSayfasi()));
              },
              child: const Text("KAYDET", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  void _cikisYapOnayiGoster() {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text("Çıkış Yap", style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2C5E1A))),
          content: const Text("Hesabınızdan çıkış yapmak istediğinize emin misiniz?"),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("İPTAL", style: TextStyle(color: Colors.grey))),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A)),
              onPressed: () {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Çıkış yapıldı.')));
                Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const GirisSayfasi()));
              },
              child: const Text("ÇIKIŞ YAP", style: TextStyle(color: Colors.white)),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: Text(
          _hesapSilmeEkranindaMi ? "HESABI SİL" : "HESAP BİLGİSİ",
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1.2),
        ),
        leading: _hesapSilmeEkranindaMi
            ? IconButton(icon: const Icon(Icons.arrow_back, color: Colors.white), onPressed: () => setState(() => _hesapSilmeEkranindaMi = false))
            : null,
      ),
      body: _hesapSilmeEkranindaMi ? _hesapSilmeSayfasiOlustur() : _hesapBilgiSayfasiOlustur(),
    );
  }

  Widget _hesapBilgiSayfasiOlustur() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.15), spreadRadius: 3, blurRadius: 8, offset: const Offset(0, 3))]),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.cloud_circle, color: Color(0xFF2C5E1A), size: 28),
                const SizedBox(width: 10),
                const Text("MarkVision Sunucu Hesabı", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2C5E1A))),
              ],
            ),
            const Divider(height: 24, thickness: 1),
            _bilgiSatiri(Icons.person_outline, "Kullanıcı", widget.girisMail),
            const SizedBox(height: 16),
            _bilgiSatiri(Icons.access_time, "Son Veri Eşitlemesi", "Biraz önce"),
            const SizedBox(height: 16),
            _bilgiSatiri(Icons.check_circle_outline, "Eşitleme Durumu", "Senkronize Edildi (Eşitlendi)"),
            const SizedBox(height: 30),
            Wrap(
              spacing: 8,
              runSpacing: 10,
              alignment: WrapAlignment.spaceBetween,
              children: [
                OutlinedButton.icon(
                  onPressed: () => setState(() => _hesapSilmeEkranindaMi = true),
                  style: OutlinedButton.styleFrom(foregroundColor: Colors.red.shade700, side: BorderSide(color: Colors.red.shade300), padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10)),
                  icon: const Icon(Icons.delete_outline, size: 16),
                  label: const Text("Hesabı sil", style: TextStyle(fontSize: 12)),
                ),
                OutlinedButton.icon(
                  onPressed: _sifreDegistirModalGoster,
                  style: OutlinedButton.styleFrom(foregroundColor: const Color(0xFF2C5E1A), side: const BorderSide(color: Color(0xFF2C5E1A)), padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10)),
                  icon: const Icon(Icons.lock_reset, size: 16),
                  label: const Text("Şifre Değiştir", style: TextStyle(fontSize: 12)),
                ),
                OutlinedButton.icon(
                  onPressed: _cikisYapOnayiGoster,
                  style: OutlinedButton.styleFrom(foregroundColor: Colors.black87, side: BorderSide(color: Colors.grey.shade400), padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10)),
                  icon: const Icon(Icons.logout, size: 16),
                  label: const Text("Çıkış Yap", style: TextStyle(fontSize: 12)),
                ),
                ElevatedButton.icon(
                  onPressed: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Veriler başarıyla eşitlendi.'))),
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A), padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10)),
                  icon: const Icon(Icons.sync, color: Colors.white, size: 16),
                  label: const Text("Eşitle", style: TextStyle(color: Colors.white, fontSize: 12)),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _bilgiSatiri(IconData ikon, String baslik, String deger) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(ikon, size: 20, color: Colors.grey.shade600),
        const SizedBox(width: 12),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(baslik, style: TextStyle(color: Colors.grey.shade600, fontSize: 12, fontWeight: FontWeight.w500)),
            const SizedBox(height: 2),
            Text(deger, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: Colors.black87)),
          ],
        ),
      ],
    );
  }

  Widget _hesapSilmeSayfasiOlustur() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.15), spreadRadius: 3, blurRadius: 8, offset: const Offset(0, 3))]),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text("Sunucu Hesabını Sil", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.red)),
            const SizedBox(height: 12),
            const Text("MarkVision hesabınızı silmek, hesabınızı ve sunucularda depolanan tüm verileri kaldıracaktır. Silme işlemini onaylamak için lütfen giriş yaptığınız e-posta adresinizi girin.", style: TextStyle(fontSize: 14, color: Colors.black87, height: 1.4)),
            const SizedBox(height: 20),
            TextField(controller: _mailKontrolcusu, decoration: InputDecoration(labelText: "Kullanıcı adı (E-posta)", hintText: widget.girisMail, border: const OutlineInputBorder(), prefixIcon: const Icon(Icons.email_outlined))),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () {
                  String girilen = _mailKontrolcusu.text.trim().toLowerCase();
                  String gercekMail = widget.girisMail.trim().toLowerCase();
                  if (girilen == gercekMail) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Hesap silme talebi başarıyla gerçekleştirildi.")));
                    Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => const GirisSayfasi()));
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Girilen e-posta adresi eşleşmiyor!"), backgroundColor: Colors.red));
                  }
                },
                style: ElevatedButton.styleFrom(backgroundColor: Colors.red.shade700, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6))),
                icon: const Icon(Icons.warning_amber_rounded, color: Colors.white),
                label: const Text("Hesabımı Kalıcı Olarak Sil", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// =====================================================================
// TEST DETAY SAYFASI (DETAYLAR VE İSTATİSTİK SEKMELERİ İLE)
// =====================================================================
class TestDetaySayfasi extends StatefulWidget {
  final TestModel test;
  const TestDetaySayfasi({super.key, required this.test});

  @override
  State<TestDetaySayfasi> createState() => _TestDetaySayfasiState();
}

class _TestDetaySayfasiState extends State<TestDetaySayfasi> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _kameraIleTara() async {
    final String? imagePath = await Navigator.push(
        context,
        MaterialPageRoute(builder: (context) => KameraTaramaSayfasi(formAdi: widget.test.cevapKagidi))
    );

    if (imagePath == null) return;

    showDialog(context: context, barrierDismissible: false, builder: (context) => const Center(child: CircularProgressIndicator(color: Color(0xFF2C5E1A))));

    try {
      var request = http.MultipartRequest('POST', Uri.parse('$API_URL/optik-okut'));
      request.headers['ngrok-skip-browser-warning'] = 'true';
      request.fields['exam_name'] = widget.test.isim;

      // SINAV ID KONTROLÜ VE EKLENMESİ (KİŞİ A/D TALEBİ)
      if (widget.test.sinavId != null) {
        request.fields['sinav_id'] = widget.test.sinavId!;
      } else {
        if (mounted) Navigator.pop(context);
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
              content: Text('Lütfen önce cevap anahtarını kaydedin! (Sınav ID bulunamadı)'),
              backgroundColor: Colors.orange
          ));
        }
        return;
      }

      request.files.add(await http.MultipartFile.fromPath('image', imagePath));

      // 15 saniyelik Zaman Aşımı
      var response = await request.send().timeout(const Duration(seconds: 15));
      var responseData = await response.stream.bytesToString();

      if (mounted) Navigator.pop(context);

      var data = jsonDecode(responseData);

      if (response.statusCode == 200 && data['success'] == true) {
        final ogrenciNo = (data['ogrenci_no'] ?? '').toString();
        final dogru = ((data['dogru'] ?? 0) as num).toInt();
        final yanlis = ((data['yanlis'] ?? 0) as num).toInt();
        final bos = ((data['bos'] ?? 0) as num).toInt();
        final puan = ((data['puan'] ?? 0) as num).toDouble();
        final cevaplarRaw = (data['cevaplar'] ?? {}) as Map;
        final Map<int, String> cevaplar = {};
        cevaplarRaw.forEach((k, v) => cevaplar[int.tryParse(k.toString()) ?? 0] = v.toString());

        setState(() {
          widget.test.kagitlar.add(PaperResult(
            ogrenciNo: ogrenciNo, dogru: dogru, yanlis: yanlis, bos: bos, puan: puan, tamPuan: widget.test.toplamPuan, cevaplar: cevaplar,
          ));
        });
        _sonucGoster(ogrenciNo, dogru, yanlis, bos, puan);
      } else {
        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Hata: ${data['message'] ?? 'Okuma başarısız.'}'), backgroundColor: Colors.red)); }
      }
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Bağlantı Hatası veya Zaman Aşımı!\nNgrok linkini ve internetinizi kontrol edin.'), backgroundColor: Colors.red)); }
    }
  }

  void _sonucGoster(String ogrenciNo, int dogru, int yanlis, int bos, double puan) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Sınav Sonucu', style: TextStyle(color: Color(0xFF2C5E1A), fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Öğrenci No: $ogrenciNo', style: const TextStyle(fontWeight: FontWeight.bold)),
            const Divider(),
            Text('Doğru Sayısı: $dogru', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
            Text('Yanlış Sayısı: $yanlis', style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
            Text('Boş Sayısı: $bos'),
            const Divider(),
            Text('TOPLAM PUAN: $puan', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.blue)),
          ],
        ),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('KAPAT', style: TextStyle(color: Color(0xFF2C5E1A))))],
      ),
    );
  }

  Widget _yesilButon(String yazi, IconData ikon, VoidCallback fonksiyon) {
    return SizedBox(width: double.infinity, child: ElevatedButton.icon(style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A), padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4))), onPressed: fonksiyon, icon: Icon(ikon, color: Colors.white, size: 20), label: Text(yazi, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14))));
  }
  Widget _griButon(String yazi, IconData ikon, VoidCallback fonksiyon) {
    return SizedBox(width: double.infinity, child: ElevatedButton.icon(style: ElevatedButton.styleFrom(backgroundColor: Colors.grey.shade300, foregroundColor: Colors.black54, elevation: 0, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4))), onPressed: fonksiyon, icon: Icon(ikon, size: 20, color: Colors.black54), label: Text(yazi, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.black54))));
  }

  @override
  Widget build(BuildContext context) {
    final test = widget.test;
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: Text('Test: ${test.isim}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
        leading: IconButton(icon: const Icon(Icons.arrow_back, color: Colors.white), onPressed: () => Navigator.pop(context)),
        actions: [IconButton(icon: const Icon(Icons.edit, color: Colors.white), onPressed: () {})],
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'DETAYLAR'),
            Tab(text: 'İSTATİSTİK'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // 1. SEKME: DETAYLAR
          SingleChildScrollView(
            child: Column(
              children: [
                Container(color: Colors.white, padding: const EdgeInsets.all(12), width: double.infinity, child: Text('İsim: ${test.isim}', style: const TextStyle(color: Colors.black54, fontWeight: FontWeight.bold))),
                Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Container(
                    decoration: BoxDecoration(color: Colors.white, border: Border.all(color: Colors.green.shade800), borderRadius: BorderRadius.circular(4)),
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      children: [
                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Cevap Kağıdı', style: TextStyle(color: Colors.grey, fontSize: 13)), Row(children: [Text(test.cevapKagidi, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.black54)), const SizedBox(width: 8), const Icon(Icons.print, color: Color(0xFF2C5E1A), size: 24)])]),
                        const SizedBox(height: 12),
                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Tarih', style: TextStyle(color: Colors.grey)), Text(test.tarih, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black54))]),
                        const SizedBox(height: 12),
                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Kağıtlar', style: TextStyle(color: Colors.grey)), Text('${test.kagitlar.length}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black54))]),
                        const SizedBox(height: 12),
                        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [const Text('Sorular', style: TextStyle(color: Colors.grey)), Text('${test.soruSayisi}', style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black54))]),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24.0),
                  child: Column(
                    children: [
                      _yesilButon('ANAHTARI DÜZENLE', Icons.vpn_key_outlined, () async { await Navigator.push(context, MaterialPageRoute(builder: (context) => QuizKeySayfasi(test: test))); setState(() {}); }),
                      const SizedBox(height: 16),
                      _yesilButon('KAĞITLARI TARA', Icons.camera_alt_outlined, _kameraIleTara),
                      const SizedBox(height: 16),
                      _griButon('KAĞITLARI İNCELE', Icons.search, () { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Yakında eklenecek.'))); }),
                      const SizedBox(height: 16),
                      _griButon('ÖĞE ANALİZİ', Icons.bar_chart, () { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Öğe analizi yakında eklenecek.'))); }),
                      const SizedBox(height: 16),
                      _griButon('ETİKET RAPORU', Icons.label_outline, () { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Etiket raporu yakında eklenecek.'))); }),
                    ],
                  ),
                ),
              ],
            ),
          ),

          // 2. SEKME: İSTATİSTİK
          SingleChildScrollView(
            padding: const EdgeInsets.all(16.0),
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8), boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.2), spreadRadius: 2, blurRadius: 5)]),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Sınav İstatistik Özeti", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2C5E1A))),
                  const Divider(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Okunan Toplam Kağıt:", style: TextStyle(color: Colors.grey, fontSize: 14)),
                      Text("${test.kagitlar.length}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Sınıf Ortalama Puanı:", style: TextStyle(color: Colors.grey, fontSize: 14)),
                      Text(
                        test.kagitlar.isEmpty
                            ? "0.0"
                            : (test.kagitlar.map((e) => e.puan).reduce((a, b) => a + b) / test.kagitlar.length).toStringAsFixed(1),
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2C5E1A)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Maksimum Puan:", style: TextStyle(color: Colors.grey, fontSize: 14)),
                      Text(
                        test.kagitlar.isEmpty ? "0.0" : "${test.kagitlar.map((e) => e.puan).reduce((a, b) => a > b ? a : b)}",
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.green),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text("Minimum Puan:", style: TextStyle(color: Colors.grey, fontSize: 14)),
                      Text(
                        test.kagitlar.isEmpty ? "0.0" : "${test.kagitlar.map((e) => e.puan).reduce((a, b) => a < b ? a : b)}",
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.red),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// =====================================================================
// QUIZ KEY (CEVAP ANAHTARI) EKRANI
// =====================================================================
class QuizKeySayfasi extends StatefulWidget {
  final TestModel test;
  const QuizKeySayfasi({super.key, required this.test});

  @override
  State<QuizKeySayfasi> createState() => _QuizKeySayfasiState();
}

class _QuizKeySayfasiState extends State<QuizKeySayfasi> {
  Future<void> _cevapAnahtariniKaydet() async {
    showDialog(context: context, barrierDismissible: false, builder: (context) => const Center(child: CircularProgressIndicator(color: Color(0xFF2C5E1A))));
    try {
      Map<String, String> answersStringMap = {};
      widget.test.cevaplar.forEach((key, value) => answersStringMap[key.toString()] = value);
      var response = await http.post(
        Uri.parse('$API_URL/cevap-anahtari-kaydet'),
        headers: {'Content-Type': 'application/json', 'ngrok-skip-browser-warning': 'true'},
        body: jsonEncode({'exam_name': widget.test.isim, 'versiyon': widget.test.aktifVersiyon, 'answers': answersStringMap, 'question_weights': {for (var i = 1; i <= widget.test.soruSayisi; i++) '$i': widget.test.agirlik(i)}}),
      );
      if (mounted) Navigator.pop(context);
      var data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {

        // SUNUCUDAN DÖNEN SINAV_ID'NİN MODELE KAYDEDİLMESİ
        if (data['sinav_id'] != null) {
          widget.test.sinavId = data['sinav_id'].toString();
        }

        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Cevap Anahtarı Başarıyla Kaydedildi!'), backgroundColor: Colors.green)); }
      } else {
        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Hata: ${data['message']}'), backgroundColor: Colors.red)); }
      }
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('API Bağlantı Hatası: $e'), backgroundColor: Colors.red)); }
    }
  }

  Future<void> _anahtarTara() async {
    final String? imagePath = await Navigator.push(
        context,
        MaterialPageRoute(builder: (context) => const KameraTaramaSayfasi(formAdi: "Sınav Anahtarı"))
    );
    if (imagePath == null) return;

    showDialog(context: context, barrierDismissible: false, builder: (context) => const Center(child: CircularProgressIndicator(color: Color(0xFF2C5E1A))));
    try {
      var request = http.MultipartRequest('POST', Uri.parse('$API_URL/optik-anahtar-oku'));
      request.headers['ngrok-skip-browser-warning'] = 'true';
      request.files.add(await http.MultipartFile.fromPath('image', imagePath));
      var response = await request.send();
      var responseData = await response.stream.bytesToString();
      var data = jsonDecode(responseData);
      if (mounted) Navigator.pop(context);
      if (response.statusCode == 200 && data['success'] == true) {
        final cevaplarRaw = (data['cevaplar'] ?? {}) as Map;
        final Map<int, String> yeniCevaplar = {};
        cevaplarRaw.forEach((k, v) => yeniCevaplar[int.tryParse(k.toString()) ?? 0] = v.toString());
        setState(() => widget.test.cevaplar = yeniCevaplar);
        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Anahtar taranan kağıttan dolduruldu.'), backgroundColor: Color(0xFF2C5E1A))); }
      } else {
        if (mounted) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Hata: ${data['message'] ?? 'Okuma başarısız.'}'), backgroundColor: Colors.red)); }
      }
    } catch (e) {
      if (mounted) Navigator.pop(context);
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Bağlantı Hatası! Ngrok linkini kontrol edin.\nHata: $e'), backgroundColor: Colors.red)); }
    }
  }

  @override
  Widget build(BuildContext context) {
    final test = widget.test;
    final versiyonlar = test.anahtarVersiyonlari.keys.toList()..sort();
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: const Text('QUIZ KEY', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1)),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white),
          onPressed: () async {
            if (widget.test.cevaplar.isNotEmpty) await _cevapAnahtariniKaydet();
            if (mounted) Navigator.pop(context);
          },
        ),
        actions: [IconButton(icon: const Icon(Icons.save, color: Colors.white), onPressed: _cevapAnahtariniKaydet)],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Text('İsim: ${test.isim}   •   Toplam Puan: ${test.toplamPuan}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.black54)),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12.0),
            child: Wrap(
              spacing: 8,
              children: versiyonlar.map((v) {
                final secili = v == test.aktifVersiyon;
                return ChoiceChip(
                  label: Text(v == versiyonlar.first ? '$v: BİRİNCİL' : v),
                  selected: secili,
                  selectedColor: const Color(0xFF2C5E1A),
                  labelStyle: TextStyle(color: secili ? Colors.white : Colors.black87, fontWeight: FontWeight.bold),
                  onSelected: (_) => setState(() => test.aktifVersiyon = v),
                );
              }).toList(),
            ),
          ),
          const Divider(thickness: 2, color: Color(0xFF2C5E1A)),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 8.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                ElevatedButton.icon(style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A)), onPressed: _anahtarTara, icon: const Icon(Icons.camera_alt, color: Colors.white, size: 16), label: const Text('ANAHTAR\nTARA', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold), textAlign: TextAlign.center)),
                ElevatedButton.icon(style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A)), onPressed: (){}, icon: const Icon(Icons.add, color: Colors.white, size: 16), label: const Text('ANAHTAR\nEKLE', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold), textAlign: TextAlign.center)),
              ],
            ),
          ),
          Expanded(
            child: ListView.builder(
              itemCount: test.soruSayisi,
              itemBuilder: (context, index) {
                int soruNo = index + 1;
                String? seciliSik = test.cevaplar[soruNo];

                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(border: Border(bottom: BorderSide(color: Colors.grey.shade200))),
                  child: Row(
                    children: [
                      SizedBox(width: 35, child: Text('$soruNo:', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.black54))),
                      Expanded(
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                          children: ['A', 'B', 'C', 'D', 'E'].map((harf) {
                            bool secili = (seciliSik == harf);
                            return GestureDetector(
                              onTap: () {
                                setState(() {
                                  if (secili) { test.cevaplar.remove(soruNo); } else { test.cevaplar[soruNo] = harf; }
                                });
                              },
                              child: Container(
                                width: 35,
                                height: 35,
                                decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: Colors.black54, width: 1), color: secili ? const Color(0xFF2C5E1A) : Colors.transparent),
                                alignment: Alignment.center,
                                child: Text(harf, style: TextStyle(color: secili ? Colors.white : Colors.black87, fontWeight: FontWeight.bold, fontSize: 16)),
                              ),
                            );
                          }).toList(),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text('${test.agirlik(soruNo)}pt', style: const TextStyle(color: Colors.grey, fontSize: 12)),
                      const SizedBox(width: 8),
                      InkWell(
                          onTap: () async {
                            await Navigator.push(context, MaterialPageRoute(builder: (context) => CevapDetaySayfasi(test: test, soruNo: soruNo)));
                            setState(() {});
                          },
                          child: const Icon(Icons.info_outline, color: Colors.black54, size: 22)
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

// =====================================================================
// CEVAP DETAY (ANSWER DETAIL) EKRANI
// =====================================================================
class CevapDetaySayfasi extends StatefulWidget {
  final TestModel test;
  final int soruNo;
  const CevapDetaySayfasi({super.key, required this.test, required this.soruNo});

  @override
  State<CevapDetaySayfasi> createState() => _CevapDetaySayfasiState();
}

class _CevapDetaySayfasiState extends State<CevapDetaySayfasi> {
  late TextEditingController _puanController;

  @override
  void initState() {
    super.initState();
    _puanController = TextEditingController(text: widget.test.agirlik(widget.soruNo).toString());
  }

  @override
  void dispose() {
    _puanController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    String seciliSik = widget.test.cevaplar[widget.soruNo] ?? '';
    double puan = widget.test.agirlik(widget.soruNo);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: const Color(0xFF2C5E1A),
        title: const Text('ANSWER DETAIL', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 1)),
        leading: IconButton(
            icon: const Icon(Icons.arrow_back, color: Colors.white),
            onPressed: () {
              final parsed = double.tryParse(_puanController.text.replaceAll(',', '.'));
              if (parsed != null && parsed >= 0) {
                widget.test.agirliklar[widget.soruNo] = parsed;
              }
              Navigator.pop(context);
            }
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Soru ${widget.soruNo}', style: const TextStyle(fontSize: 16, color: Colors.grey)),
                Text('Birincil Puan. $puan', style: const TextStyle(fontSize: 16, color: Colors.grey)),
                Text('Maksimum Puan. $puan', style: const TextStyle(fontSize: 16, color: Colors.grey)),
              ],
            ),
          ),
          const Divider(thickness: 1),
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Yanıtlar', style: TextStyle(color: Colors.grey)),
                    Row(
                      children: [
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4))),
                          onPressed: () {},
                          icon: const Icon(Icons.add, color: Colors.white, size: 16),
                          label: const Text('ÇÖZÜLMÜŞ,\nANCAK YANLIŞ', style: TextStyle(color: Colors.white, fontSize: 10), textAlign: TextAlign.center),
                        ),
                        const SizedBox(width: 8),
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2C5E1A), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4))),
                          onPressed: () {},
                          icon: const Icon(Icons.add_box_outlined, color: Colors.white, size: 16),
                          label: const Text('CEVAP\nEKLE', style: TextStyle(color: Colors.white, fontSize: 10), textAlign: TextAlign.center),
                        ),
                      ],
                    )
                  ],
                ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    const Text('Cvp', style: TextStyle(color: Colors.grey, fontSize: 16)),
                    const SizedBox(width: 16),
                    Container(
                      width: 100,
                      height: 40,
                      decoration: BoxDecoration(color: const Color(0xFF2C5E1A), borderRadius: BorderRadius.circular(4)),
                      alignment: Alignment.center,
                      child: Text(seciliSik, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
                    ),
                    const Spacer(),
                    const Text('Puan', style: TextStyle(color: Colors.grey, fontSize: 16)),
                    const SizedBox(width: 16),
                    Container(
                      width: 60,
                      decoration: const BoxDecoration(border: Border(bottom: BorderSide(color: Colors.grey))),
                      child: TextField(
                        controller: _puanController,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        textAlign: TextAlign.center,
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        decoration: const InputDecoration(border: InputBorder.none, isDense: true, contentPadding: EdgeInsets.zero),
                      ),
                    ),
                  ],
                )
              ],
            ),
          ),
          const Spacer(),
          const Divider(thickness: 1),
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('Etiketler (0)', style: TextStyle(color: Colors.grey)),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(side: const BorderSide(color: Colors.grey)),
                  onPressed: () {},
                  icon: const Icon(Icons.add, color: Color(0xFF2C5E1A), size: 16),
                  label: const Text('ETİKET EKLE', style: TextStyle(color: Color(0xFF2C5E1A), fontWeight: FontWeight.bold)),
                )
              ],
            ),
          )
        ],
      ),
    );
  }
}
