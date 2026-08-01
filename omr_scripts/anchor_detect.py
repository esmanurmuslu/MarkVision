"""
anchor_detect.py
-----------------
Optik formun 4 kosesindeki dolu siyah kareleri (anchor/referans noktalari)
bulur ve bu 4 noktayi kullanarak kagidi kusbakisi (warp) hale getirir.

Kullanim:
    from anchor_detect import belgeyi_duzlestir
    duz_gri, duz_renkli = belgeyi_duzlestir("kagit.jpg")
"""

import cv2
import numpy as np
import os
import math

def apply_clahe(img_gri):
    """
    CLAHE uygulayarak görüntüdeki ışık dengesizliklerini 
    (flaş parlaması, bölgesel gölgeler) giderir.
    """
    # 8x8'lik karolara bölerek kontrastı sınırlar (clipLimit)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    return clahe.apply(img_gri)

def kagidi_bul_ve_kirp(img):
    """
    Görüntüdeki en büyük beyaz dikdörtgeni (kağıdı) tespit eder 
    ve etrafındaki gereksiz arka planı (masa vb.) budar.
    """
    # Kenar tespiti için geçici bir gri kopya oluştur
    gri = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gri = cv2.GaussianBlur(gri, (5, 5), 0)
    kenarlar = cv2.Canny(gri, 75, 200)
    
    # Kenarlardan şekilleri (konturları) çıkar ve alana göre en büyük 5'ini al
    konturlar, _ = cv2.findContours(kenarlar, cv2.RETR_LIST, cv2.CHAIN_APPROX_SIMPLE)
    konturlar = sorted(konturlar, key=cv2.contourArea, reverse=True)[:5]
    
    for k in konturlar:
        cevre = cv2.arcLength(k, True)
        yaklasik = cv2.approxPolyDP(k, 0.02 * cevre, True)
        
        # Eğer şeklin 4 köşesi varsa ve çok küçük bir leke değilse (resmin en az %20'si)
        if len(yaklasik) == 4 and cv2.contourArea(yaklasik) > (img.shape[0] * img.shape[1] * 0.2):
            x, y, w, h = cv2.boundingRect(yaklasik)
            
            # Siyah referans noktalarının (anchor) kesilmemesi için 15 piksel güvenlik payı bırak
            pad = 15
            y1 = max(0, y - pad)
            y2 = min(img.shape[0], y + h + pad)
            x1 = max(0, x - pad)
            x2 = min(img.shape[1], x + w + pad)
            
            # Sadece kağıdın olduğu alanı kes ve döndür
            return img[y1:y2, x1:x2]
            
    # Eğer düzgün bir kağıt şekli bulunamazsa, resmi olduğu gibi geri döndür
    return img

def is_blurry(image, threshold=150.0):
    if len(image.shape) == 3:
        gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    else:
        gray = image
    fm = cv2.Laplacian(gray, cv2.CV_64F).var()
    if fm < threshold:
        return True, fm
    return False, fm

def _esikle(img_gri, mod="sabit"):
    """
    Iki mod destekler:
      - "sabit"  : ORIJINAL davranis. Sabit esik (100) kullanir; pembeleri,
                    grileri ve beyazlari yoksayip sadece gercek siyahliklari
                    (referans kareleri) ayirir. Duzgun/normal isikta cekilmis
                    fotograflar icin en guvenilir ve HIZLI yontemdir, bu yuzden
                    varsayilan/ilk deneme hep budur.
      - "adaptif": Goruntuyu kucuk bolgelere ayirip HER bolge icin kendi yerel
                    esigini hesaplar (cv2.adaptiveThreshold). Boylece kagidin
                    bir yarisi golgede/karanlik, digeri aydinlik olsa bile
                    (esit olmayan isik) her bolgede "o bolgeye gore en koyu"
                    pikseller bulunur. Sabit esigin basarisiz oldugu durumlar
                    icin (golgeli/soluk fotograf) YEDEK (fallback) olarak
                    kullanilir -- normal sartlarda devreye girmez.
    """
    if mod == "adaptif":
        # blockSize buyuk tutuluyor (61) ki kucuk kabarciklara degil, sadece
        # buyuk anchor karelerine duyarli olsun; C, gurultuyu bastirmak icin.
        th = cv2.adaptiveThreshold(
            img_gri, 255,
            cv2.ADAPTIVE_THRESH_GAUSSIAN_C, cv2.THRESH_BINARY_INV,
            blockSize=61, C=15
        )
        return th

    _, th = cv2.threshold(img_gri, 100, 255, cv2.THRESH_BINARY_INV)
    return th

def _kare_mi(kontur, min_alan=50, max_alan=None, min_doluluk=0.40):
    """
    max_alan None ise, cagiran kod (_anchor_adaylarini_bul) resmin
    boyutuna gore dinamik bir ust sinir hesaplar (bkz. asagida).
    """
    if max_alan is None:
        max_alan = 5000000
    """
    Bir konturun 'dolu siyah kare' olup olmadigini kontrol eder.
    Dijital resimler icin sinirlar maksimum seviyede esnetilmistir.
    """
    alan = cv2.contourArea(kontur)
    if alan < min_alan or alan > max_alan:
        return False

    x, y, w, h = cv2.boundingRect(kontur)
    if h == 0:
        return False
    
    en_boy = w / float(h)
    if en_boy < 0.2 or en_boy > 5.0:
        return False

    doluluk = alan / float(w * h)
    if doluluk < min_doluluk:
        return False

    return True

def _kose_bolgesi_maskele(img_gri, oran=0.22):
    """
    Adaptif mod icin: goruntunun SADECE 4 kosesine yakin kenar seritlerini
    birakip orta govdeyi (yazi/tablo alanlarini) beyazla maskeler. Anchor
    kareler zaten fiziksel olarak kagidin koselerinde olacagi icin, arama
    alanini kositlemek adaptiveThreshold'un govde metnini/tablo cizgilerini
    "sahte kare" olarak yakalamasini onler.
    """
    h, w = img_gri.shape[:2]
    maskeli = np.full_like(img_gri, 255)
    kenar_w = int(w * oran)
    kenar_h = int(h * oran)
    # 4 kose seridi: sol-ust, sag-ust, sol-alt, sag-alt kutulari
    maskeli[0:kenar_h, 0:kenar_w] = img_gri[0:kenar_h, 0:kenar_w]
    maskeli[0:kenar_h, w - kenar_w:w] = img_gri[0:kenar_h, w - kenar_w:w]
    maskeli[h - kenar_h:h, 0:kenar_w] = img_gri[h - kenar_h:h, 0:kenar_w]
    maskeli[h - kenar_h:h, w - kenar_w:w] = img_gri[h - kenar_h:h, w - kenar_w:w]
    return maskeli

def _anchor_adaylarini_bul(img_gri, mod="sabit"):
    """Goruntudeki tum siyah bolgelerin merkezlerini dondurur."""
    if mod == "adaptif":
        img_gri = _kose_bolgesi_maskele(img_gri)

    h, w = img_gri.shape[:2]
    # ONEMLI GUVENLIK SINIRI: gercek anchor kareler kagidin kucuk bir
    # kosesidir, asla goruntunun buyuk bir kismini kaplamaz. Arka planda
    # kalan siyah nesneler (klavye, kiyafet, letterbox/siyah serit vb.)
    # bazen gercek anchor'lardan cok daha buyuk siyah konturlar
    # olusturup yanlislikla "anchor" sanilabiliyor. Bunu onlemek icin
    # ust siniri goruntu alaninin sadece %2'siyle sinirliyoruz --
    # gercek anchor kareler bunun cok altinda kalir, arka plan
    # gurultusu ise cogunlukla bunun cok ustunde olur.
    dinamik_max_alan = h * w * 0.02

    th = _esikle(img_gri, mod=mod)
    konturlar, _ = cv2.findContours(th, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    if mod == "adaptif":
        kare_kriterleri = dict(min_alan=150, max_alan=min(20000, dinamik_max_alan), min_doluluk=0.55)
    else:
        kare_kriterleri = dict(max_alan=dinamik_max_alan)

    adaylar = []
    for k in konturlar:
        if _kare_mi(k, **kare_kriterleri):
            x, y, w, h = cv2.boundingRect(k)
            if mod == "adaptif" and (w / float(h) < 0.6 or w / float(h) > 1.7):
                continue  # gercek anchor kareye yakin en/boy olmali
            merkez = (x + w / 2.0, y + h / 2.0)
            adaylar.append({"merkez": merkez, "alan": cv2.contourArea(k)})

    return adaylar

def _dort_koseyi_sec(adaylar, genislik, yukseklik):
    """Goruntuyu 4 ceyrege ayirip her bolgeden en buyuk alana sahip siyahligi secer."""
    orta_x, orta_y = genislik / 2.0, yukseklik / 2.0
    bolgeler = {"sol_ust": None, "sag_ust": None, "sol_alt": None, "sag_alt": None}

    for aday in adaylar:
        cx, cy = aday["merkez"]
        if cx < orta_x and cy < orta_y:
            key = "sol_ust"
        elif cx >= orta_x and cy < orta_y:
            key = "sag_ust"
        elif cx < orta_x and cy >= orta_y:
            key = "sol_alt"
        else:
            key = "sag_alt"

        if bolgeler[key] is None or aday["alan"] > bolgeler[key]["alan"]:
            bolgeler[key] = aday

    if any(v is None for v in bolgeler.values()):
        return None  

    return bolgeler

def _dortgen_gecerli_mi(koseler, tolerans=0.35):
    """
    4 kosenin GERCEKTEN bir kagidin 4 kosesi gibi durup durmadigini kontrol
    eder (bir 'kedi fotografi' okutuldugunda ya da anchor'lar yanlis
    eslestiginde, 4 siyah blok bulunsa bile bunlar duzgun bir dortgen
    OLUSTURMAYABILIR -- bu fonksiyon boyle sahte eslesmeleri eler).

    Kontroller:
      1) Ust kenar (sol_ust-sag_ust) ile alt kenar (sol_alt-sag_alt)
         uzunluklari birbirine yakin olmali (kagit dikdortgen, yamuk degil).
      2) Sol kenar ile sag kenar uzunluklari da birbirine yakin olmali.
      3) Iki kosegen de birbirine yakin uzunlukta olmali (gercek bir
         dikdortgende kosegenler esittir; asiri caprazlik/yamukluk
         genelde yanlis eslesme ya da alakasiz nesne anlamina gelir).

    Not: Perspektiften kaynakli hafif farklar (telefonla acili cekim)
    normaldir, bu yuzden tolerans genis tutuldu (varsayilan %35).
    """
    import math

    def uzaklik(p1, p2):
        return math.hypot(p1[0] - p2[0], p1[1] - p2[1])

    su = koseler["sol_ust"]["merkez"]
    sa = koseler["sag_ust"]["merkez"]
    al = koseler["sol_alt"]["merkez"]
    ar = koseler["sag_alt"]["merkez"]

    ust_kenar = uzaklik(su, sa)
    alt_kenar = uzaklik(al, ar)
    sol_kenar = uzaklik(su, al)
    sag_kenar = uzaklik(sa, ar)
    kosegen1 = uzaklik(su, ar)
    kosegen2 = uzaklik(sa, al)

    def yakin_mi(a, b, tol):
        if min(a, b) == 0:
            return False
        return abs(a - b) / max(a, b) <= tol

    if not yakin_mi(ust_kenar, alt_kenar, tolerans):
        return False, f"ust kenar ({ust_kenar:.0f}px) ile alt kenar ({alt_kenar:.0f}px) uyusmuyor"
    if not yakin_mi(sol_kenar, sag_kenar, tolerans):
        return False, f"sol kenar ({sol_kenar:.0f}px) ile sag kenar ({sag_kenar:.0f}px) uyusmuyor"
    if not yakin_mi(kosegen1, kosegen2, tolerans):
        return False, f"kosegenler ({kosegen1:.0f}px / {kosegen2:.0f}px) esit degil -- yamuk/hatali eslesme"

    return True, None

def kenar_koyuluk_orani(img_gri, serit_orani=0.08):
    """
    Duzlestirilmis goruntunun SOL kenar seridindeki koyu piksel sayisinin,
    SAG kenar seridindekine oranini dondurur.

    Bu formda (bkz. optik ornegi) sol kenar boyunca spiral cilt/delik
    izleri var, sag kenarda yok -- yani normal cekimde sol/sag orani
    belirgin sekilde 1'den BUYUK cikar. Kagit 180 derece ters
    okutulmussa (bas asagi), bu izler sag tarafa gecer ve oran
    KUCULUR (1'in altina duser). Bu, sadece 4 anchor karesine bakarak
    YAKALANAMAYACAK bir hata turudur (kareler her yonden simetrik
    gorundugu icin salt kose-mesafesi kontrolu ters cevrilmeyi ayirt
    edemez); bu yuzden ayrica icerik tabanli bu kontrolu ekliyoruz.

    Donus: oran (sol_koyu_piksel / sag_koyu_piksel). ~1.7 civari normal,
    1.0'in belirgin altinda ise ters okutulmus olabilecegi anlamina gelir.
    """
    h, w = img_gri.shape[:2]
    serit_px = max(1, int(w * serit_orani))
    sol = img_gri[:, 0:serit_px]
    sag = img_gri[:, w - serit_px:w]
    sol_koyu = int(np.sum(sol < 150))
    sag_koyu = int(np.sum(sag < 150))
    if sag_koyu == 0:
        return float("inf")
    return sol_koyu / float(sag_koyu)

def belgeyi_duzlestir(resim_yolu, cikti_genislik=1000, cikti_yukseklik=1400, kenar_bosluk=40):
    if not os.path.exists(resim_yolu):
        import sys
        print(f"Hata: {resim_yolu} bulunamadi.", file=sys.stderr)
        return None, None

    # ÖNCE RESMİ OKUYORUZ
    # Resmin transparan (saydam) olma ihtimaline karsi 4 kanalli (UNCHANGED) okuyoruz
    img_orijinal = cv2.imread(resim_yolu, cv2.IMREAD_UNCHANGED)
    
    if img_orijinal is None:
        import sys
        print(f"Hata: {resim_yolu} okunamadi, bozuk olabilir.", file=sys.stderr)
        return None, None

    # RESMİ OKUDUKTAN SONRA BULANIKLIK KONTROLÜ YAPIYORUZ
    blurry, score = is_blurry(img_orijinal)
    if blurry:
        import sys
        print(f"Hata: Görüntü çok bulanık (Skor: {score:.2f}). Lütfen daha net çekin.", file=sys.stderr)
        return None, None  # Bulaniksa islemi iptal et ve None dondur

    # Eger resim PNG formatinda ve saydamsa, arka planini beyaza boyuyoruz
    if len(img_orijinal.shape) == 3 and img_orijinal.shape[2] == 4:
        alpha_kanal = img_orijinal[:, :, 3]
        beyaz_arka_plan = np.ones_like(img_orijinal[:, :, :3]) * 255
        alpha_maske = alpha_kanal[:, :, np.newaxis] / 255.0
        img = (img_orijinal[:, :, :3] * alpha_maske + beyaz_arka_plan * (1 - alpha_maske)).astype(np.uint8)
    else:
        img = img_orijinal

    img = kagidi_bul_ve_kirp(img)

    img_gri = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # ---CLAHE IŞIK DENGELEME ---
    img_gri = apply_clahe(img_gri)
    # ------------------------------------------

    img_gri = cv2.GaussianBlur(img_gri, (5, 5), 1)

    h, w = img_gri.shape[:2]

    # ONCE orijinal/sabit esikle dene (normal isikta cekilmis fotograflar
    # icin en hizli ve en guvenilir yol -- davranis eskisiyle birebir ayni).
    # Basarisiz olursa (golge, dusuk kontrast, soluk kalem/tarama vs.)
    # adaptif esiklemeye gec.
    adaylar = _anchor_adaylarini_bul(img_gri, mod="sabit")
    kullanilan_mod = "sabit"
    if len(adaylar) < 4:
        adaylar = _anchor_adaylarini_bul(img_gri, mod="adaptif")
        kullanilan_mod = "adaptif"

    if len(adaylar) < 4:
        import sys
        print(
            f"Hata: Sadece {len(adaylar)} anchor karesi bulundu, 4 tane "
            f"gerekiyor (sabit ve adaptif esikleme ikisi de denendi). "
            f"Bu genelde optik disi bir gorsel okutuldugunda ya da kagit "
            f"cok karanlik/bulanik cekildiginde olur.",
            file=sys.stderr,
        )
        return None, None

    koseler = _dort_koseyi_sec(adaylar, w, h)
    if koseler is None:
        import sys
        print(
            "Hata: 4 kosenin hepsinde bir anchor karesi bulunamadi "
            "(kagidin sadece bir kismi kadraja girmis olabilir).",
            file=sys.stderr,
        )
        return None, None

    gecerli, sebep = _dortgen_gecerli_mi(koseler)
    if not gecerli:
        import sys
        print(
            f"Hata: Bulunan 4 kose duzgun bir kagit gibi durmuyor ({sebep}). "
            f"Optik disi bir fotograf okutulmus olabilir ya da kagit "
            f"ters/yanlis acidan cekilmis olabilir.",
            file=sys.stderr,
        )
        return None, None

    if kullanilan_mod == "adaptif":
        import sys
        print("Bilgi: Anchor kareler adaptif esikleme ile bulundu (dusuk kontrast/golge tespit edildi).", file=sys.stderr)

    # === KUSURSUZ VE KIRILMAZ YÖN BULMA ALGORİTMASI ===
    def _rol_dondur(koseler_dict, adim):
        sira = ["sol_ust", "sag_ust", "sag_alt", "sol_alt"]
        n = len(sira)
        return {rol: koseler_dict[sira[(i - adim) % n]] for i, rol in enumerate(sira)}

    def _yon_skoru(img_bgr, kaynak_noktalar):
        su = kaynak_noktalar[0]
        sa = kaynak_noktalar[1]
        al = kaynak_noktalar[3]
        
        # 1. GEOMETRİK KONTROL (Yan Yatma Engeli)
        genislik = math.hypot(su[0] - sa[0], su[1] - sa[1])
        yukseklik = math.hypot(su[0] - al[0], su[1] - al[1])
        
        if genislik > yukseklik:
            return -float('inf') 
            
        # 2. SOL-SAĞ KONTROLÜ (Ters Dönme Engeli)
        gri_test = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2GRAY)
        hh, ww = gri_test.shape[:2]
        
        sol_serit = gri_test[int(hh*0.1):int(hh*0.9), 0:int(ww*0.08)]
        sag_serit = gri_test[int(hh*0.1):int(hh*0.9), int(ww*0.92):ww]
        
        sol_koyu = np.sum(sol_serit < 150)
        sag_koyu = np.sum(sag_serit < 150)
        
        return sol_koyu - sag_koyu

    en_iyi_skor = -float('inf')
    en_iyi_warped = None
    en_iyi_gri = None

    hedef_noktalar = np.float32([
        [kenar_bosluk, kenar_bosluk],
        [cikti_genislik - kenar_bosluk, kenar_bosluk],
        [cikti_genislik - kenar_bosluk, cikti_yukseklik - kenar_bosluk],
        [kenar_bosluk, cikti_yukseklik - kenar_bosluk]
    ])

    for adim in range(4):
        deneme_koseler = _rol_dondur(koseler, adim)
        kaynak_noktalar = np.float32([
            deneme_koseler["sol_ust"]["merkez"],
            deneme_koseler["sag_ust"]["merkez"],
            deneme_koseler["sag_alt"]["merkez"],
            deneme_koseler["sol_alt"]["merkez"]
        ])
        
        M = cv2.getPerspectiveTransform(kaynak_noktalar, hedef_noktalar)
        warped = cv2.warpPerspective(img, M, (cikti_genislik, cikti_yukseklik))
        
        skor = _yon_skoru(warped, kaynak_noktalar)
        if skor > en_iyi_skor:
            en_iyi_skor = skor
            en_iyi_warped = warped
            en_iyi_gri = cv2.cvtColor(warped, cv2.COLOR_BGR2GRAY)

    return en_iyi_gri, en_iyi_warped

# ======================================================================
# TEST MOTORU: omr_form_geometry.py dosyanizla %100 UYUMLU calisan,
# hicbir seyi eksiltmeyen test blogu.
# ======================================================================
if __name__ == "__main__":
    import sys
    import glob
    try:
        from omr_form_geometry import form_planla, mm_to_px
    except ImportError:
        print("HATA: Lutfen 'omr_form_geometry.py' dosyasini bu script ile ayni klasore koyun.")
        sys.exit(1)

    # Arguman verilerek calistirilirsa onu kullanir, yoksa klasordeki jpg'yi bulur
    if len(sys.argv) > 1 and sys.argv[1].endswith(('.jpg', '.jpeg', '.png')):
        secilen = sys.argv[1]
    else:
        resimler = glob.glob("*.jpg") + glob.glob("*.jpeg") + glob.glob("*.png")
        if not resimler:
            print("Klasörde resim bulunamadı.")
            sys.exit()
        secilen = "test.jpeg" if "test.jpeg" in resimler else resimler[0]
        
    print(f"Test ediliyor: {secilen}")
    
    gri, renkli = belgeyi_duzlestir(secilen)
    
    if renkli is None:
        print("Başarısız: Kağıt algılanamadı veya bulanık.")
        sys.exit()

    print("Kağıt düzleştirildi. omr_form_geometry.py'den GERÇEK koordinatlar alınıyor...")

    # Form algilama zekasi (Dosya adina gore form turunu anlar veya arguman alir)
    toplamSoru = 100
    siklar = "ABCDE"
    haneSayisi = 9

    if "15" in secilen:
        toplamSoru = 15
        siklar = "ABCDEFGH"
    elif "40" in secilen:
        toplamSoru = 40
        siklar = "ABC"

    # Eger terminalden "python anchor_detect.py test.jpeg 40 ABC" yazildiysa ayarlari ezer
    if len(sys.argv) >= 3:
        toplamSoru = int(sys.argv[2])
    if len(sys.argv) >= 4:
        siklar = sys.argv[3].upper()

    print(f"Secilen Format: {toplamSoru} soru, {len(siklar)} sik ({siklar}), {haneSayisi} haneli ogrenci no.")

    try:
        plan = form_planla(toplamSoru, siklar, haneSayisi)
    except Exception as e:
        print(f"Geometri Plani Hatasi: {e}")
        sys.exit()

    # Öğrenci Numarasını Çiz (Kırmızı)
    for basamak_adi, rakamlar in plan["ogrenci_no"]["basamaklar"].items():
        for rakam_str, (x_mm, y_mm) in rakamlar.items():
            px, py = mm_to_px(x_mm, y_mm)
            cv2.circle(renkli, (px, py), 8, (0, 0, 255), 2)

    # Soruları Çiz (Mavi)
    for soru in plan["sorular"]["sorular"]:
        for k, v in soru.items():
            if k != "soru_no":
                px, py = mm_to_px(v[0], v[1])
                cv2.circle(renkli, (px, py), 8, (255, 0, 0), 2)

    cikti_adi = "MUKEMMEL_SONUC.jpeg"
    cv2.imwrite(cikti_adi, renkli)
    print(f"\n>>> HARİKA! Test tamamlandı. Lütfen '{cikti_adi}' dosyasını açıp piksellerin NASIL CUK OTURDUĞUNU görün! <<<")