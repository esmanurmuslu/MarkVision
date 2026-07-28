import cv2
from anchor_detect import belgeyi_duzlestir

# Yeni kusursuz taslagini sisteme veriyoruz
# taslak.jpg yerine telefon fotoğrafını yazıyoruz
gri, renkli = belgeyi_duzlestir("telefon_kagit.jpeg")

if renkli is not None:
    cv2.imwrite("ana_sablon_duz.jpg", renkli)
    print("Harika! Ana sablon duzlestirildi ve kaydedildi.")
