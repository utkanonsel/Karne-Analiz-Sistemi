import cv2
import numpy as np
import requests
import itertools
import time
import sys # Sadece bunu ekledim (ID'yi almak için)

# --- SENİN HARİTAN ---
HARITA = {
    "sol_blok": {"x": 0.197, "y": 0.235, "w": 0.236, "h": 0.642},
    "sag_blok": {"x": 0.610, "y": 0.234, "w": 0.237, "h": 0.646},
    "satir_sayisi": 15, "sik_sayisi": 5
}

IP_ADRES = "192.168.1.173:8080"
video_adresi = f"http://{IP_ADRES}/video"
PHP_URL = "http://localhost/analiz/verileri-kaydet.php"

# --- HASSASİYET AYARLARI ---
KOYULUK_ESIGI = 128  # 5/10 kuralı
FARK_MARJI = 40      # Silgi izi ayrımı

cv2.namedWindow("ZIVER PRO", cv2.WINDOW_NORMAL)
cv2.resizeWindow("ZIVER PRO", 600, 850)
cv2.namedWindow("ZIVER DEBUG", cv2.WINDOW_NORMAL)
cv2.resizeWindow("ZIVER DEBUG", 400, 400)

kamera = cv2.VideoCapture(video_adresi)
qr_tarayici = cv2.QRCodeDetector() # QR Okuyucu (OpenCV)

# --- YARDIMCI FONKSİYONLAR (Senin Orijinal Fonksiyonların) ---
def get_center(cnt):
    M = cv2.moments(cnt)
    if M["m00"] != 0:
        return (int(M["m10"] / M["m00"]), int(M["m01"] / M["m00"]))
    return (0,0)

def sirala_markerlar(pts):
    pts = np.array(pts, dtype="float32")
    s = pts.sum(axis=1); diff = np.diff(pts, axis=1)
    rect = np.zeros((4, 2), dtype="float32")
    rect[0] = pts[np.argmin(s)]   # TL
    rect[2] = pts[np.argmax(s)]   # BR
    rect[1] = pts[np.argmin(diff)] # TR
    rect[3] = pts[np.argmax(diff)] # BL
    return rect

def geometrik_dogrulama(aday_grubu):
    pts = np.array([p['center'] for p in aday_grubu])
    areas = np.array([p['area'] for p in aday_grubu])
    
    if np.max(areas) / np.min(areas) > 2.0: return None

    rect = sirala_markerlar(pts)
    (tl, tr, br, bl) = rect
    
    widthA = np.linalg.norm(br - bl); widthB = np.linalg.norm(tr - tl)
    heightA = np.linalg.norm(tr - br); heightB = np.linalg.norm(tl - bl)
    
    if abs(widthA - widthB) > 50 or abs(heightA - heightB) > 50: return None
        
    avg_w = (widthA + widthB) / 2
    avg_h = (heightA + heightB) / 2
    if avg_h == 0: return None
    ar = avg_w / avg_h
    
    if 0.5 < ar < 0.95: return rect
    return None

def markerlari_bul_takim(img):
    """ Mevcut çalışan marker bulma sistemi (Dokunulmadı) """
    hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    mask = cv2.inRange(hsv, np.array([0,0,0]), np.array([180,255,100]))
    kernel = np.ones((3,3), np.uint8)
    mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, kernel)
    cv2.imshow("ZIVER DEBUG", mask)

    cnts, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    tum_adaylar = []
    
    for c in cnts:
        area = cv2.contourArea(c)
        if 150 < area < 10000:
            peri = cv2.arcLength(c, True)
            approx = cv2.approxPolyDP(c, 0.04 * peri, True)
            if len(approx) == 4:
                center = get_center(c)
                tum_adaylar.append({'center': center, 'area': area, 'cnt': c})

    tum_adaylar = sorted(tum_adaylar, key=lambda x: x['area'], reverse=True)[:8]
    
    if len(tum_adaylar) >= 4:
        for combo in itertools.combinations(tum_adaylar, 4):
            sonuc = geometrik_dogrulama(combo)
            if sonuc is not None:
                return sonuc
    return None

def analitik_okuma(img_duz):
    vizor = img_duz.copy()
    gray = cv2.cvtColor(img_duz, cv2.COLOR_BGR2GRAY)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8,8))
    gray = clahe.apply(gray)
    
    cevaplar = []
    for blok_ad in ["sol_blok", "sag_blok"]:
        b = HARITA[blok_ad]
        bx, by = int(b["x"]*1000), int(b["y"]*1000)
        bw, bh = int(b["w"]*1000), int(b["h"]*1000)
        ay = bh / (HARITA["satir_sayisi"]-1); ax = bw / (HARITA["sik_sayisi"]-1)
        
        for i in range(HARITA["satir_sayisi"]):
            vals = []; coords = []
            for j in range(HARITA["sik_sayisi"]):
                cx, cy = int(bx+(j*ax)), int(by+(i*ay))
                kutu = gray[cy-7:cy+7, cx-7:cx+7]
                val = np.percentile(kutu, 20) if kutu.size > 0 else 255
                vals.append(val); coords.append((cx, cy))
                cv2.circle(vizor, (cx, cy), 2, (255,0,0), -1)

            isaretli_indeksler = [idx for idx, v in enumerate(vals) if v < KOYULUK_ESIGI]
            
            if len(isaretli_indeksler) == 0:
                cevaplar.append("X") 
            elif len(isaretli_indeksler) > 1:
                cevaplar.append("M") # Multiple (Çoklu işaretleme)
                for idx in isaretli_indeksler:
                    cv2.circle(vizor, coords[idx], 10, (0, 0, 255), 2)
            else:
                en_koyu_idx = isaretli_indeksler[0]
                en_koyu_val = vals[en_koyu_idx]
                digerleri = [v for k, v in enumerate(vals) if k != en_koyu_idx]
                ikinci_val = min(digerleri)
                
                if (ikinci_val - en_koyu_val) > FARK_MARJI:
                    cevaplar.append(chr(65+en_koyu_idx))
                    cv2.circle(vizor, coords[en_koyu_idx], 10, (0, 255, 0), 2)
                else:
                    cevaplar.append("X") 
                    
    return cevaplar, vizor

# --- ANA PROGRAM BAŞLANGICI ---

# 1. ID KONTROLÜ (Yama Burası)
aktif_ogrenci_id = sys.argv[1] if len(sys.argv) > 1 else "1"
print(f"[SISTEM] Program Baslatildi. Ogrenci ID: {aktif_ogrenci_id}")

print("ZİVER QR + OPTİK MOD BAŞLATILDI.")
print("1. Önce QR okutun.")
print("2. Sonra formu gösterip 's' tuşuna basın.")
print("'r' -> Reset (Başa döner)")

donduruldu = False
sonuc_resmi = None
aktif_sinav_id = None # QR'dan gelecek ID

while True:
    if not donduruldu:
        ret, frame = kamera.read()
        if not ret: cv2.waitKey(100); continue
        
        # EKRAN DÖNDÜRME (Senin Orijinal Ayarın)
        frame = cv2.rotate(frame, cv2.ROTATE_90_CLOCKWISE)
        gosterim = frame.copy()
        
        # --- AŞAMA 1: QR KOD ARAMA ---
        if aktif_sinav_id is None:
            cv2.putText(gosterim, "LUTFEN QR KODU OKUTUN", (50, 400), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 0, 255), 2)
            try:
                # QR Tara (Senin Orijinal Dedektörün)
                data, bbox, _ = qr_tarayici.detectAndDecode(frame)
                if data:
                    aktif_sinav_id = data
                    print(f"SINAV TANIMLANDI: {aktif_sinav_id}")
                    cv2.putText(gosterim, f"ONAYLANDI: {data}", (50, 350), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)
                    cv2.imshow("ZIVER PRO", gosterim)
                    cv2.waitKey(1500) 
            except: pass
            
            pts = None 

        # --- AŞAMA 2: MARKER ARAMA ---
        else:
            cv2.putText(gosterim, f"SINAV: {aktif_sinav_id}", (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 0, 0), 2)
            
            pts = markerlari_bul_takim(frame)
            
            if pts is not None:
                cv2.polylines(gosterim, [pts.astype(int)], True, (0,255,0), 3)
                cv2.putText(gosterim, "KILITLENDI ('s' BAS)", (20, 80), 1, 2, (0,255,0), 3)
            else:
                cv2.putText(gosterim, "FORM ARANIYOR...", (20, 80), 1, 1.5, (0,0,255), 3)
        
        cv2.imshow("ZIVER PRO", gosterim)
        
    else:
        cv2.imshow("ZIVER PRO", sonuc_resmi)

    k = cv2.waitKey(1) & 0xFF
    if k == ord('q'): break
    
    # RESET
    if k == ord('r'): 
        donduruldu = False
        aktif_sinav_id = None
        print("Sıfırlandı. QR bekleniyor...")
    
    # KAYDET (ID Yaması Burada)
    if k == ord('s') and pts is not None and not donduruldu and aktif_sinav_id is not None:
        try:
            M = cv2.getPerspectiveTransform(pts, np.float32([[0,0],[1000,0],[1000,1000],[0,1000]]))
            duz = cv2.warpPerspective(frame, M, (1000, 1000))
            if np.mean(duz) < 30: donduruldu = False; continue
            
            cv_list, resim = analitik_okuma(duz)
            veri = ",".join(cv_list)
            
            sonuc_resmi = resim
            donduruldu = True
            
            print("\n" + "="*40)
            print(f"--- {aktif_sinav_id} SONUÇLARI ---")
            for i, cevap in enumerate(cv_list):
                print(f"{i+1:02d}. Soru: {cevap}")
            print("="*40)
            
            print(f"Sunucuya gönderiliyor... (Öğrenci ID: {aktif_ogrenci_id})")
            
            # BURASI DEĞİŞTİ: Artık gelen ID'yi kullanıyor
            requests.post(PHP_URL, data={
                "ogrenci_id": aktif_ogrenci_id, 
                "deneme_id": aktif_sinav_id, 
                "cevaplar": veri, 
                "durum": 1
            })
            print("BAŞARILI.")
            
        except Exception as e:
            print(f"Hata: {e}")
            donduruldu = False

kamera.release()
cv2.destroyAllWindows()