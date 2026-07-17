import cv2
from anchor_detect import belgeyi_duzlestir

# Yeni kusursuz taslagini sisteme veriyoruz
gri, renkli = belgeyi_duzlestir("taslak.jpg") # Burayı güncelledik

if renkli is not None:
    cv2.imwrite("ana_sablon_duz.jpg", renkli)
    print("Harika! Ana sablon duzlestirildi ve kaydedildi.")
