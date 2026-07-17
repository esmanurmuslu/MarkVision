import cv2
import json
from anchor_detect import belgeyi_duzlestir

# 1. Kagidi duzlestir (anchor_detect modulu uzerinden)
gri, renkli = belgeyi_duzlestir("optik.png")

if renkli is None:
    print("Kagit duzlestirilemedi, referans kareleri bulunamadi!")
    exit()

# 2. JSON haritasini oku
try:
    with open("koordinat_haritasi_ornek.json", "r", encoding="utf-8") as f:
        harita = json.load(f)
except FileNotFoundError:
    print("koordinat_haritasi_ornek.json dosyasi bulunamadi!")
    exit()

# 3. Ogrenci numarasi noktalarini ciz (Mavi Yuvarlaklar)
for basamak, rakamlar in harita.get("ogrenci_no", {}).items():
    for rakam, koordinatlar in rakamlar.items():
        # JSON formatindaki listeyi x ve y olarak ayir
        x, y = koordinatlar
        cv2.circle(renkli, (int(x), int(y)), 12, (255, 0, 0), 2)

# 4. Soru seceneklerini ciz (Yesil Yuvarlaklar)
for soru in harita.get("sorular", []):
    for anahtar, deger in soru.items():
        if anahtar != "soru_no":
            x, y = deger
            cv2.circle(renkli, (int(x), int(y)), 12, (0, 255, 0), 2)

# 5. Ekranda goster
cv2.imshow("Koordinat Kayma Testi", renkli)
cv2.waitKey(0)
cv2.destroyAllWindows()
