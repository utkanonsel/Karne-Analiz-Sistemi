import cv2
import numpy as np
import tkinter as tk
from tkinter import filedialog
import sys

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

# Görseli yükle
img = cv2.imread(dosya_yolu)
if img is None:
    print("Hata: Görüntü dosyası okunamadı.")
    sys.exit()

# Ekrana sığdırma (Çok büyük görseller için)
max_height = 800
if img.shape[0] > max_height:
    scale = max_height / img.shape[0]
    img = cv2.resize(img, (int(img.shape[1] * scale), max_height))

orijinal_kopya = img.copy()
noktalar = []
warped_img = None # Python'da 'null' yerine 'None' kullanılır

def sirala_noktalar(pts):
    pts = np.array(pts, dtype="float32")
    s = pts.sum(axis=1); diff = np.diff(pts, axis=1)
    # TL, TR, BR, BL sırası (OpenCV standardı)
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
                    # 1000x1000'lik standart bir kareye dönüştür
                    dst_pts = np.array([[0,0], [1000,0], [1000,1000], [0,1000]], dtype="float32")
                    M = cv2.getPerspectiveTransform(src_pts, dst_pts)
                    warped_img = cv2.warpPerspective(orijinal_kopya, M, (1000, 1000))
                    cv2.imshow("ZIVER MAPPER", warped_img) # Artık düz görüntüdeyiz
                    print("Görüntü düzeltildi. Şimdi cevap bloklarını işaretleyin.")
                except Exception as e:
                    print(f"Hata: Perspektif düzeltme yapılamadı. Noktaları kontrol edin. ({e})")

        # Warp yapıldıktan sonraki noktalar (5,6,7,8 - Cevap Blokları)
        elif len(noktalar) < 8 and warped_img is not None:
            # Warp edilmiş koordinata göre ekle
            noktalar.append([x, y]) 
            # Görselleştirme (Warped üzerinde)
            cv2.circle(warped_img, (x, y), 5, (255, 0, 0), -1)
            cv2.putText(warped_img, str(len(noktalar)), (x+10, y), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255,0,0), 2)
            cv2.imshow("ZIVER MAPPER", warped_img)

cv2.namedWindow("ZIVER MAPPER")
cv2.setMouseCallback("ZIVER MAPPER", tikla)

print("--- ADIM 1: MARKERLAR ---")
print("Sırayla tıkla: 1.Sol-Üst -> 2.Sağ-Üst -> 3.Sağ-Alt -> 4.Sol-Alt")

while True:
    k = cv2.waitKey(1) & 0xFF
    if k == 27: break # ESC ile çıkış
    if len(noktalar) == 8:
        # Haritayı hesapla (Warp düzlemine göre oranlar)
        # Not: Noktalar listesindeki 4,5,6,7. elemanlar (index 4-7) cevap bloklarıdır.
        # Warp resminde tıkladığın için koordinatlar zaten 0-1000 arasındadır.
        
        p5, p6 = noktalar[4], noktalar[5] # Sol Blok Baş-Son
        p7, p8 = noktalar[6], noktalar[7] # Sağ Blok Baş-Son
        
        # Koordinatları normalize et (0.0 - 1.0 arası)
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