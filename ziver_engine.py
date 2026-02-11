import cv2
import numpy as np
import sys
import json

# --- HARİTA (SENİN ONAYLADIĞIN) ---
HARITA = {
    "sol_blok": {"x": 0.197, "y": 0.235, "w": 0.236, "h": 0.642},
    "sag_blok": {"x": 0.610, "y": 0.234, "w": 0.237, "h": 0.646},
    "satir_sayisi": 15, "sik_sayisi": 5
}

# Hassasiyet
KOYULUK_ESIGI = 145 
FARK_MARJI = 30 

def marker_bul_hsv(img):
    hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    mask = cv2.inRange(hsv, np.array([0,0,0]), np.array([180,255,90]))
    kernel = np.ones((3,3), np.uint8)
    mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, kernel)
    mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel)
    cnts, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    adaylar = []
    for c in cnts:
        if 100 < cv2.contourArea(c) < 15000: # Fotoğraf boyutuna göre değişebilir
            peri = cv2.arcLength(c, True)
            approx = cv2.approxPolyDP(c, 0.04 * peri, True)
            if len(approx) == 4:
                M = cv2.moments(c)
                if M["m00"] != 0:
                    adaylar.append([int(M["m10"]/M["m00"]), int(M["m01"]/M["m00"])])
    
    if len(adaylar) >= 4:
        pts = np.array(adaylar)
        s = pts.sum(axis=1); diff = np.diff(pts, axis=1)
        rect = np.zeros((4, 2), dtype="float32")
        rect[0] = pts[np.argmin(s)]; rect[2] = pts[np.argmax(s)]
        rect[1] = pts[np.argmin(diff)]; rect[3] = pts[np.argmax(diff)]
        return rect
    return None

def analitik_okuma(img_duz):
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
            vals = []
            for j in range(HARITA["sik_sayisi"]):
                cx, cy = int(bx+(j*ax)), int(by+(i*ay))
                kutu = gray[cy-7:cy+7, cx-7:cx+7]
                val = np.percentile(kutu, 25) if kutu.size > 0 else 255
                vals.append(val)
            s_vals = sorted(vals)
            if s_vals[0] < KOYULUK_ESIGI and (s_vals[1]-s_vals[0]) > FARK_MARJI:
                cevaplar.append(chr(65 + vals.index(s_vals[0])))
            else:
                cevaplar.append("X")
    return cevaplar

# --- ANA İŞLEM ---
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Dosya yolu verilmedi"})); sys.exit()
        
    resim_yolu = sys.argv[1]
    img = cv2.imread(resim_yolu)
    
    if img is None:
        print(json.dumps({"error": "Resim okunamadi"})); sys.exit()

    # 1. QR Kod Oku
    qr = cv2.QRCodeDetector()
    data, _, _ = qr.detectAndDecode(img)
    sinav_id = data if data else None

    # 2. Marker Bul ve Oku
    pts = marker_bul_hsv(img)
    sonuc_cevaplar = ""
    
    if pts is not None:
        try:
            M = cv2.getPerspectiveTransform(pts, np.float32([[0,0],[1000,0],[1000,1000],[0,1000]]))
            duz = cv2.warpPerspective(img, M, (1000, 1000))
            cv_list = analitik_okuma(duz)
            sonuc_cevaplar = ",".join(cv_list)
        except:
            pass
    
    # 3. Sonucu JSON olarak PHP'ye ver
    output = {
        "success": True if pts is not None else False,
        "qr_code": sinav_id,
        "answers": sonuc_cevaplar
    }
    print(json.dumps(output))