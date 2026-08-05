"""
pipeline_main.py
------------------
anchor_detect + bubble_detect modullerini birlestirip, bir kagit
fotografini alip `exam_results` tablosuna DOGRUDAN yazilabilecek
bir JSON ureten uctan uca akis.

Veritabani semasi (markvision.sql) ile hizali calisir:
    - exams.total_questions  -> her sinavda soru sayisi farkli olabilir,
                                 bu yuzden SABIT 40 degil, disaridan verilir.
    - exams.answer_key       -> zaten {"1":"A","2":"B",...} formatinda,
                                 asagidaki sinav_bilgisi JSON'undaki
                                 "answer_key" alaniyla birebir eslesir.
    - exam_results.student_no, correct_count, wrong_count, blank_count,
      score, status -> bu scriptin ciktisindaki alanlarla birebir eslesir.

Kullanim:
    python pipeline_main.py kagit.jpg koordinat_haritasi.json sinav_bilgisi.json

sinav_bilgisi.json, Laravel'in `exams` tablosundan tek bir satiri
disa aktarmasiyla olusur (bkz. sinav_bilgisi_ornek.json):
    {
      "exam_id": 1,
      "total_questions": 10,
      "answer_key": {"1": "A", "2": "B", ...}
    }
"""
import os
from dotenv import load_dotenv
import cv2
import sys

# Scriptin bulunduğu klasöre geçiş yap
os.chdir(os.path.dirname(os.path.abspath(__file__)))
load_dotenv() # .env dosyasındaki bilgileri yükle
import sys
import json
import io
import contextlib

from anchor_detect import belgeyi_duzlestir, kenar_koyuluk_orani
from bubble_detect import soruyu_oku, coklu_soru_oku, kabarcik_doluluk_orani

# Sinav puanlamasinda yanlislarin dogrulari goturup goturmeyecegini
# belirleyen katsayi. Artik SABIT DEGIL: her sinav icin Laravel tarafinda
# (sinavlar.penalty_coef) ogretmenin girdigi deger, sinav_bilgisi.json
# icindeki "penalty_coef" alaniyla buraya tasinir (bkz. kagidi_isle).
# Bu sabit sadece sinav_bilgisi.json'da alan yoksa (eski/geriye donuk
# uyumluluk) kullanilacak VARSAYILAN degerdir:
#   0      -> yanlis, dogruyu goturmez (net puan = dogru sayisi)
#   0.25   -> 4 secenekli klasik "4 yanlis 1 dogruyu goturur" kurali
#   1/(N-1)-> N secenekli sinavlarda istatistiksel olarak "adil" ceza
VARSAYILAN_CEZA_KATSAYISI = 0


def _akilli_rakam_sec(img_gri, secenekler, yaricap=12, min_taban=0.25):
    en_iyi_rakam = "?"
    en_yuksek_oran = 0.0

    for rakam_str, merkez in secenekler.items():
        oran = kabarcik_doluluk_orani(img_gri, merkez, yaricap=yaricap)
        if oran > en_yuksek_oran:
            en_yuksek_oran = oran
            en_iyi_rakam = rakam_str

    if en_yuksek_oran < min_taban:
        return "?"

    return en_iyi_rakam

def ogrenci_no_oku(img_gri, harita):
    basamaklar = sorted(
        harita["ogrenci_no"].keys(),
        key=lambda s: int(s.split("_")[1])
    )
    
    rakamlar_listesi = []
    for basamak_adi in basamaklar:
        secenekler = harita["ogrenci_no"][basamak_adi]
        secenekler = {k: tuple(v) for k, v in secenekler.items()}
        
        rakam = _akilli_rakam_sec(img_gri, secenekler, yaricap=14, min_taban=0.15)
        
        if rakam == "?":
            if len(rakamlar_listesi) >= 2:
                break
            else:
                continue
        else:
            rakamlar_listesi.append(rakam)
            
    no = "".join(rakamlar_listesi)
    
    if len(no.strip()) < 1:
        return "?"
        
    return no

def sorulari_oku(img_gri, harita, toplam_soru):
    """
    Optik kagitta fiziksel olarak 40 soruluk yer olsa da, sinava gore
    sadece ilk `toplam_soru` kadari kullanilir (exams.total_questions).
    """
    if toplam_soru > len(harita["sorular"]):
        raise ValueError(
            f"Sinavda {toplam_soru} soru var ama koordinat haritasinda "
            f"sadece {len(harita['sorular'])} sorunun yeri tanimli."
        )

    sorular_tuple = []
    for soru in harita["sorular"][:toplam_soru]:
        soru_tuple = {"soru_no": soru["soru_no"]}
        for k, v in soru.items():
            if k != "soru_no":
                soru_tuple[k] = tuple(v)  # [x,y] -> (x,y)
        sorular_tuple.append(soru_tuple)

    return coklu_soru_oku(img_gri, sorular_tuple, bagil_yedek=True)


def puanla(cevaplar, answer_key, question_weights=None, ceza_katsayisi=VARSAYILAN_CEZA_KATSAYISI):
    dogru = yanlis = bos = 0
    gecersiz_sorular = []
    
    toplam_agirlikli_puan = 0.0
    alinan_agirlikli_puan = 0.0

    if question_weights is None:
        question_weights = {}

    try:
        ceza_katsayisi = float(ceza_katsayisi)
    except (TypeError, ValueError):
        ceza_katsayisi = VARSAYILAN_CEZA_KATSAYISI

    # Eğer answer_key liste gelirse güvenli şekilde sözlüğe çevirelim
    if isinstance(answer_key, list):
        answer_key = {str(i + 1): val for i, val in enumerate(answer_key)}

    # Eğer cevaplar liste gelirse sözlüğe çevirelim
    if isinstance(cevaplar, list):
        cevaplar = {str(i + 1): val for i, val in enumerate(cevaplar)}

    for soru_no, verilen in cevaplar.items():
        soru_no_str = str(soru_no)
        gercek = answer_key.get(soru_no_str)
        
        agirlik = float(question_weights.get(soru_no_str, 1.0))
        toplam_agirlikli_puan += agirlik

        if verilen == "BOS" or not verilen:
            bos += 1
        elif verilen == "GECERSIZ":
            yanlis += 1
            gecersiz_sorular.append(soru_no)
        elif verilen == gercek:
            dogru += 1
            alinan_agirlikli_puan += agirlik
        else:
            yanlis += 1
            alinan_agirlikli_puan -= (agirlik * ceza_katsayisi)

    alinan_agirlikli_puan = max(alinan_agirlikli_puan, 0.0)
    
    if toplam_agirlikli_puan > 0:
        score = round((alinan_agirlikli_puan / toplam_agirlikli_puan) * 100, 2)
    else:
        score = 0.0

    return {
        "correct_count": dogru,
        "wrong_count": yanlis,
        "blank_count": bos,
        "score": score,
        "gecersiz_sorular": gecersiz_sorular,
    }

def kagidi_isle(resim_yolu, koordinat_dosyasi, sinav_bilgisi_dosyasi):
    with open(koordinat_dosyasi, encoding="utf-8") as f:
        harita = json.load(f)
    with open(sinav_bilgisi_dosyasi, encoding="utf-8") as f:
        sinav = json.load(f)

    exam_id = sinav["exam_id"]
    toplam_soru = sinav["total_questions"]
    answer_key = sinav["answer_key"]
    question_weights = sinav.get("question_weights") or {}
    ceza_katsayisi = sinav.get("penalty_coef", VARSAYILAN_CEZA_KATSAYISI)

    stderr_yakalayici = io.StringIO()
    with contextlib.redirect_stderr(stderr_yakalayici):
        img_gri, img_renkli = belgeyi_duzlestir(resim_yolu)
    if img_gri is None:
        gercek_sebep = stderr_yakalayici.getvalue().strip()
        return {
            "basarili": False,
            "status": "failed",
            "exam_id": exam_id,
            "hata": "Kagit duzlestirilemedi (anchor bulunamadi)."
                    + (f" Detay: {gercek_sebep}" if gercek_sebep else ""),
        }

    ogrenci_no_str = ogrenci_no_oku(img_gri, harita)
    if ogrenci_no_str.count("?") > 2 or len(ogrenci_no_str.strip()) < 5:
        return {
            "basarili": False,
            "status": "failed",
            "hata": "Öğrenci numarası net okunamadı. Lütfen formu kameraya tam ve dik tutun."
        }

    cevaplar = sorulari_oku(img_gri, harita, toplam_soru)

    if "?" in ogrenci_no_str:
        harita_klasoru = os.path.dirname(os.path.abspath(koordinat_dosyasi))
        mevcut_ad = os.path.basename(koordinat_dosyasi)
        bilinen_haritalar = ["koordinat_haritasi.json", "koordinat_kamera.json"]
        alternatif_adlar = [ad for ad in bilinen_haritalar if ad != mevcut_ad]

        for alternatif_ad in alternatif_adlar:
            alternatif_yol = os.path.join(harita_klasoru, alternatif_ad)
            if not os.path.exists(alternatif_yol):
                continue
            try:
                with open(alternatif_yol, encoding="utf-8") as f:
                    alt_harita = json.load(f)
            except Exception:
                continue

            alt_ogrenci_no_str = ogrenci_no_oku(img_gri, alt_harita)
            if "?" not in alt_ogrenci_no_str:
                harita = alt_harita
                ogrenci_no_str = alt_ogrenci_no_str
                cevaplar = sorulari_oku(img_gri, harita, toplam_soru)
                break

    puanlar = puanla(cevaplar, answer_key, question_weights=question_weights, ceza_katsayisi=ceza_katsayisi)

    try:
        for basamak, secenekler in harita["ogrenci_no"].items():
            for rakam, kord in secenekler.items():
                cv2.circle(img_renkli, (int(kord[0]), int(kord[1])), 10, (255, 0, 0), 2)

        guvenli_answer_key = answer_key
        if isinstance(answer_key, list):
            guvenli_answer_key = {str(i + 1): val for i, val in enumerate(answer_key)}

        for soru in harita["sorular"][:toplam_soru]:
            soru_no_str = str(soru["soru_no"])
            ogrenci_cvp = cevaplar.get(soru_no_str, "BOS")
            dogru_cvp = guvenli_answer_key.get(soru_no_str, "")

            for k, v in soru.items():
                if k == "soru_no": continue
                x, y = int(v[0]), int(v[1])
                
                if k == ogrenci_cvp:
                    if ogrenci_cvp == dogru_cvp:
                        cv2.circle(img_renkli, (x, y), 12, (0, 255, 0), 3)
                    else:
                        cv2.circle(img_renkli, (x, y), 12, (0, 0, 255), 3)
                
                elif ogrenci_cvp == "BOS" and k == dogru_cvp:
                    cv2.circle(img_renkli, (x, y), 12, (0, 255, 255), 3)

        cv2.imwrite(resim_yolu, img_renkli)
    except Exception as e:
        pass 

    return {
        "basarili": True,
        "exam_id": int(exam_id),
        "student_no": ogrenci_no_str.replace("?", ""),
        "student_answers": {str(k): str(v) for k, v in cevaplar.items()},
        "correct_count": int(puanlar["correct_count"]),
        "wrong_count": int(puanlar["wrong_count"]),
        "blank_count": int(puanlar["blank_count"]),
        "score": float(puanlar["score"]),
        "status": "success",
        "gecersiz_sorular": [],
    }

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print(json.dumps({
            "basarili": False,
            "status": "failed",
            "hata": "Kullanim: python pipeline_main.py kagit.jpg koordinat_haritasi.json sinav_bilgisi.json"
        }, ensure_ascii=False))
        sys.exit(1)

    try:
        sonuc = kagidi_isle(sys.argv[1], sys.argv[2], sys.argv[3])
    except Exception as e:
        sonuc = {"basarili": False, "status": "failed", "hata": str(e)}

    print(json.dumps(sonuc, ensure_ascii=False))
    sys.exit(0 if sonuc.get("basarili") else 1)