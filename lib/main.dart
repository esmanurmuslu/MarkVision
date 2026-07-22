import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kDebugMode;
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:file_picker/file_picker.dart';

// =====================================================================
// EMÜLATÖRDE TEST EDERKEN: önce terminalde "adb reverse tcp:8000 tcp:8000"
// çalıştırın, sonra burada "localhost" kullanın. Bu sayede kamera izni
// (getUserMedia) "güvenli origin" sayılıp çalışır.
//
// GERÇEK TELEFONDA TEST EDERKEN (aynı WiFi): bilgisayarınızın yerel IP'sini
// yazın, örn. 'http://192.168.1.34:8000' ve sunucuyu
// "php artisan serve --host=0.0.0.0 --port=8000" ile başlatın.
//
// YAYINA ALIRKEN: 'https://sizin-domaininiz.com' yapın ve
// AndroidManifest.xml'de usesCleartextTraffic="false" yapın.
// =====================================================================
const String kSunucuAdresi = 'http://localhost:8000';

void main() => runApp(const MaterialApp(
  debugShowCheckedModeBanner: false,
  home: WebViewApp(),
));

class WebViewApp extends StatefulWidget {
  const WebViewApp({super.key});
  @override
  State<WebViewApp> createState() => _WebViewAppState();
}

class _WebViewAppState extends State<WebViewApp> {
  WebViewController? controller;

  bool isLoading = true;
  bool hasError = false;
  String hataMesaji = '';
  double yuklemeYuzdesi = 0;

  @override
  void initState() {
    super.initState();
    initApp();
  }

  Future<void> initApp() async {
    await [
      Permission.camera,
      Permission.microphone,
    ].request();

    late final PlatformWebViewControllerCreationParams params;
    if (WebViewPlatform.instance is AndroidWebViewPlatform) {
      params = AndroidWebViewControllerCreationParams();
      if (kDebugMode) {
        AndroidWebViewController.enableDebugging(true);
      }
    } else {
      params = const PlatformWebViewControllerCreationParams();
    }

    final WebViewController localController =
    WebViewController.fromPlatformCreationParams(params);

    localController
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFFF1F5F9))
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (_) {
            if (mounted) setState(() { isLoading = true; hasError = false; });
          },
          onProgress: (progress) {
            if (mounted) setState(() => yuklemeYuzdesi = progress / 100);
          },
          onPageFinished: (_) {
            if (mounted) setState(() => isLoading = false);
          },
          onWebResourceError: (error) {
            if (error.isForMainFrame ?? true) {
              if (mounted) {
                setState(() {
                  isLoading = false;
                  hasError = true;
                  hataMesaji = error.description;
                });
              }
            }
          },
          onNavigationRequest: (request) => NavigationDecision.navigate,
        ),
      );

    if (localController.platform is AndroidWebViewController) {
      final androidController =
      localController.platform as AndroidWebViewController;

      await androidController.setMediaPlaybackRequiresUserGesture(false);
      androidController.setOnShowFileSelector(_dosyaSeciciAc);
      await androidController.setOnPlatformPermissionRequest(
            (request) => request.grant(),
      );
    }

    await localController.loadRequest(Uri.parse(kSunucuAdresi));

    if (mounted) {
      setState(() {
        controller = localController;
      });
    }
  }

  Future<List<String>> _dosyaSeciciAc(FileSelectorParams params) async {
    try {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.image,
        allowMultiple: false,
      );
      final path = result?.files.single.path;
      if (path == null) return [];
      return ['file://$path'];
    } catch (_) {
      return [];
    }
  }

  Future<void> _yenidenDene() async {
    setState(() { hasError = false; isLoading = true; });
    if (controller != null) {
      await controller!.loadRequest(Uri.parse(kSunucuAdresi));
    } else {
      await initApp();
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) async {
        if (didPop) return;
        if (controller != null && await controller!.canGoBack()) {
          controller!.goBack();
        } else {
          if (mounted) Navigator.of(context).maybePop();
        }
      },
      child: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: SafeArea(
          child: Stack(
            children: [
              if (hasError)
                _HataEkrani(mesaj: hataMesaji, onTekrarDene: _yenidenDene)
              else if (controller != null)
                WebViewWidget(controller: controller!)
              else
                const Center(child: CircularProgressIndicator()),

              if (isLoading && !hasError && controller != null)
                Align(
                  alignment: Alignment.topCenter,
                  child: LinearProgressIndicator(
                    value: yuklemeYuzdesi == 0 ? null : yuklemeYuzdesi,
                    minHeight: 3,
                    backgroundColor: Colors.transparent,
                    color: const Color(0xFF2563EB),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HataEkrani extends StatelessWidget {
  final String mesaj;
  final VoidCallback onTekrarDene;
  const _HataEkrani({required this.mesaj, required this.onTekrarDene});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFF0F172A),
      alignment: Alignment.center,
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.wifi_off_rounded, color: Colors.white54, size: 56),
          const SizedBox(height: 16),
          const Text(
            'Sunucuya bağlanılamadı',
            style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'İnternet bağlantını kontrol et. (Detay: $mesaj)',
            textAlign: TextAlign.center,
            style: const TextStyle(color: Colors.white54, fontSize: 12),
          ),
          const SizedBox(height: 20),
          ElevatedButton(
            onPressed: onTekrarDene,
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text('Tekrar Dene'),
          ),
        ],
      ),
    );
  }
}