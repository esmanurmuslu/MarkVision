"""
kalibrasyon_araci.py
----------------------
Gercek bir taramayi anchor_detect ile duzlestirdikten sonra, bu duz
goruntu uzerinde FARE ILE TIKLAYARAK her kabarcigin merkez koordinatini
toplamani saglayan basit bir arac.
"""

import cv2
import sys
import json

noktalar = []

def tiklama_yakala(event, x, y, flags, param):
    if event == cv2.EVENT_LBUTTONDOWN:
        noktalar.append([x, y])
        print(f"Nokta eklendi: ({x}, {y})  -- toplam: {len(noktalar)}")

def main():
    if len(sys.argv) < 2:
        print("Kullanim: python kalibrasyon_araci.py duz_kagit.jpg")
        return

    resim_yolu = sys.argv[1]
    img = cv2.imread(resim_yolu)
    if img is None:
        print(f"Hata: {resim_yolu} okunamadi.")
        return

    # PENCERE YENİDEN BOYUTLANDIRMA ÖZELLİĞİ (cv2.WINDOW_NORMAL) EKLENDİ
    cv2.namedWindow("Kalibrasyon - tikla, bitince 'q'", cv2.WINDOW_NORMAL)
    cv2.setMouseCallback("Kalibrasyon - tikla, bitince 'q'", tiklama_yakala)

    while True:
        gosterim = img.copy()
        for i, (x, y) in enumerate(noktalar):
            cv2.circle(gosterim, (x, y), 3, (0, 0, 255), -1)
            cv2.putText(gosterim, str(i + 1), (x + 5, y - 5),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.4, (0, 0, 255), 1)
            cv2.namedWindow("Kalibrasyon - tikla, bitince 'q'", cv2.WINDOW_NORMAL)
            cv2.resizeWindow("Kalibrasyon - tikla, bitince 'q'", 800, 1000) # İhtiyacına göre boyutları artırabilirsincv2.namedWindow("Kalibrasyon - tikla, bitince 'q'", cv2.WINDOW_NORMAL)
            cv2.resizeWindow("Kalibrasyon - tikla, bitince 'q'", 800, 1000) # İhtiyacına göre boyutları artırabilirsin 

        cv2.imshow("Kalibrasyon - tikla, bitince 'q'", gosterim)
        tus = cv2.waitKey(20) & 0xFF
        if tus == ord('q'):
            break

    cv2.destroyAllWindows()

    with open("koordinatlar.json", "w", encoding="utf-8") as f:
        json.dump(noktalar, f, ensure_ascii=False, indent=2)

    print(f"\n{len(noktalar)} nokta 'koordinatlar.json' dosyasina kaydedildi.")
    print("Simdi bu listeyi soru/secenek yapina gore grupla (bkz. koordinat_haritasi_ornek.json).")


if __name__ == "__main__":
    main()