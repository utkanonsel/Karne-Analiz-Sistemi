import cv2
import numpy as np
import tkinter as tk
from tkinter import filedialog
import sys
import os

# --- DOSYA SEÇİMİ ---
try:
    root = tk.Tk()
    root.withdraw() # Ana pencereyi gizle
    dosya_yolu = filedialog.askopenfilename(title="Optik Form Görselini Seç", 
                                            filetypes=[("Image", "*.jpg *.png *.jpeg")])
except Exception as e:
    print(f"Dosya seçim penceresi açılamadı: {e}")
    sys.exit()

if not dosya_yolu:
    print("Dosya seçilmedi, çıkılıyor.")
    sys.exit()

# Görseli yükle (Türkçe karakter içeren yollar için güvenli yöntem)
try:
    # np.fromfile ve cv2.imdecode kullanarak yolu okuyoruz
    img_array = np.fromfile(dosya_yolu, np.uint8)
    img = cv2.imdecode(img_array, cv2.IMREAD_COLOR)
except Exception as e:
    print(f"Görsel okunamadı: {e}")
    sys.exit()

if img is None:
    print("Hata: Görüntü dosyası bozuk veya okunamadı.")
    sys.exit()

# Ekrana sığdırma (Çok büyük görseller için)
max_height = 800
if img.shape[0] > max_height:
    scale = max_height / img.shape[0]
    img = cv2.resize(img, (int(img.shape[1] * scale), max_height))

orijinal_kopya = img.copy()
noktalar = []
warped_img = None 

def sirala_noktalar(pts):
    pts = np.array(pts, dtype="float32")
    s = pts.sum(axis=1); diff = np.diff(pts, axis=1)
    rect = np.zeros((4, 2), dtype="float32")
    rect[0] = pts[np.argmin(s)]   # Sol Üst
    rect[2] = pts[np.argmax(s)]   # Sağ Alt
    rect[1] = pts[np.argmin(diff)] # Sağ Üst
    rect[3] = pts[np.argmax(diff)] # Sol Alt
    return rect

def tikla(event, x, y, flags, param):
    global img, warped_img
    if event == cv2.EVENT_LBUTTONDOWN:
        # Eğer henüz warp yapılmadıysa (İlk 4 nokta - Markerlar)
        if len(noktalar) < 4:
            noktalar.append([x, y])
            cv2.circle(img, (x, y), 5, (0, 0, 255), -1)
            cv2.putText(img, str(len(noktalar)), (x+10, y), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0,0,255), 2)
            cv2.imshow("ZIVER MAPPER", img)
            
            # 4. noktayı koyunca WARP işlemini yap
            if len(noktalar) == 4:
                print("Markerlar seçildi. Düzeltiliyor...")
                try:
                    src_pts = sirala_noktalar(noktalar)
                    dst_pts = np.array([[0,0], [1000,0], [1000,1000], [0,1000]], dtype="float32")
                    M = cv2.getPerspectiveTransform(src_pts, dst_pts)
                    warped_img = cv2.warpPerspective(orijinal_kopya, M, (1000, 1000))
                    cv2.imshow("ZIVER MAPPER", warped_img) 
                    print("Görüntü düzeltildi. Şimdi cevap bloklarını işaretleyin.")
                except Exception as e:
                    print(f"Hata: Perspektif düzeltme yapılamadı. ({e})")

        # Warp yapıldıktan sonraki noktalar (5,6,7,8 - Cevap Blokları)
        elif len(noktalar) < 8 and warped_img is not None:
            noktalar.append([x, y]) 
            cv2.circle(warped_img, (x, y), 5, (255, 0, 0), -1)
            cv2.putText(warped_img, str(len(noktalar)), (x+10, y), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255,0,0), 2)
            cv2.imshow("ZIVER MAPPER", warped_img)

cv2.namedWindow("ZIVER MAPPER")
cv2.setMouseCallback("ZIVER MAPPER", tikla)

# --- İŞTE EKSİK OLAN PARÇA: PENCEREYİ İLK AÇILIŞTA GÖSTER ---
cv2.imshow("ZIVER MAPPER", img)

print("--- ADIM 1: MARKERLAR ---")
print("Sırayla tıkla: 1.Sol-Üst -> 2.Sağ-Üst -> 3.Sağ-Alt -> 4.Sol-Alt")

while True:
    k = cv2.waitKey(1) & 0xFF
    if k == 27: break # ESC ile çıkış
    if len(noktalar) == 8:
        # Haritayı hesapla
        p5, p6 = noktalar[4], noktalar[5] # Sol Blok
        p7, p8 = noktalar[6], noktalar[7] # Sağ Blok
        
        sol_x = p5[0] / 1000.0
        sol_y = p5[1] / 1000.0
        sol_w = (p6[0] - p5[0]) / 1000.0
        sol_h = (p6[1] - p5[1]) / 1000.0
        
        sag_x = p7[0] / 1000.0
        sag_y = p7[1] / 1000.0
        sag_w = (p8[0] - p7[0]) / 1000.0
        sag_h = (p8[1] - p7[1]) / 1000.0
        
        print("\n" + "="*40)
        print("YENİ HARİTA (main.py içine yapıştır):")
        print(f'HARITA = {{')
        print(f'    "sol_blok": {{"x": {sol_x:.3f}, "y": {sol_y:.3f}, "w": {sol_w:.3f}, "h": {sol_h:.3f}}},')
        print(f'    "sag_blok": {{"x": {sag_x:.3f}, "y": {sag_y:.3f}, "w": {sag_w:.3f}, "h": {sag_h:.3f}}},')
        print(f'    "satir_sayisi": 15, "sik_sayisi": 5')
        print(f'}}')
        print("="*40)
        break

cv2.destroyAllWindows()