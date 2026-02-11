import cv2
import numpy as np

# --- AYARLAR ---
video_adresi = "http://192.168.1.173:8080/video" 

noktalar = []

def tiklama_olayi(event, x, y, flags, param):
    if event == cv2.EVENT_LBUTTONDOWN:
        print(f"Nokta {len(noktalar)+1} alındı: {x}, {y}")
        noktalar.append((x, y))
        # Tıklanan yeri işaretle
        cv2.circle(img_kopya, (x, y), 5, (0, 255, 0), -1)
        
        # Görsel rehberlik (Hangi noktada olduğunu hatırlatmak için)
        if len(noktalar) == 4: print(">>> Şimdi SOL SÜTUN'a geçiyoruz...")
        if len(noktalar) == 7: print(">>> Şimdi SAĞ SÜTUN'a geçiyoruz...")
        
        cv2.imshow("Kalibrasyon v2", img_kopya)

print("--- ZİVER KALİBRASYON v2 ---")
kamera = cv2.VideoCapture(video_adresi)

while True:
    success, img = kamera.read()
    if not success: break
    
    img = cv2.rotate(img, cv2.ROTATE_90_CLOCKWISE) 
    cv2.imshow("Kalibrasyon v2", img)
    
    if cv2.waitKey(1) & 0xFF == ord('d'):
        img_kopya = img.copy()
        print("\n--- GÖRÜNTÜ DONDURULDU ---")
        print("Sırasıyla şu 10 noktaya tıkla:")
        print("1. Sol Üst Marker")
        print("2. Sağ Üst Marker")
        print("3. Sol Alt Marker")
        print("4. Sağ Alt Marker")
        print("---------------------------")
        print("5. Sol Sütun - 1. Soru A Şıkkı (Sol Üst)")
        print("6. Sol Sütun - 1. Soru E Şıkkı (Sağ Üst)")
        print("7. Sol Sütun - 15. Soru A Şıkkı (Sol Alt)")
        print("---------------------------")
        print("8. Sağ Sütun - 16. Soru A Şıkkı (Sol Üst)")
        print("9. Sağ Sütun - 16. Soru E Şıkkı (Sağ Üst)")
        print("10. Sağ Sütun - 30. Soru A Şıkkı (Sol Alt)")
        
        cv2.setMouseCallback("Kalibrasyon v2", tiklama_olayi)
        cv2.waitKey(0)
        break

kamera.release()
cv2.destroyAllWindows()

# --- ORANLARI HESAPLA ---
if len(noktalar) == 10:
    pts1 = np.float32(noktalar[:4])
    width, height = 1000, 1000 # 1000x1000'lik sanal kağıt
    pts2 = np.float32([[0, 0], [width, 0], [0, height], [width, height]])
    
    matrix = cv2.getPerspectiveTransform(pts1, pts2)
    
    # Soruların koordinatlarını dönüştür
    soru_ham = np.float32(noktalar[4:]).reshape(-1, 1, 2)
    soru_yeni = cv2.perspectiveTransform(soru_ham, matrix)
    
    # Oranları Çıkar
    s1 = soru_yeni # Kısa isim
    
    print("\n\n--- ANA KOD İÇİN GEREKLİ VERİLER (BUNLARI KOPYALA) ---")
    print(f"SOL_SUTUN_BAS_X = {s1[0][0][0]/width:.5f}")
    print(f"SOL_SUTUN_BAS_Y = {s1[0][0][1]/height:.5f}")
    print(f"SOL_SUTUN_GENISLIK = {(s1[1][0][0] - s1[0][0][0])/width:.5f}  # A'dan E'ye mesafe")
    print(f"SOL_SUTUN_YUKSEKLIK = {(s1[2][0][1] - s1[0][0][1])/height:.5f} # 1. Sorudan 15. Soruya mesafe")
    print("-" * 30)
    print(f"SAG_SUTUN_BAS_X = {s1[3][0][0]/width:.5f}")
    print(f"SAG_SUTUN_BAS_Y = {s1[3][0][1]/height:.5f}")
    print(f"SAG_SUTUN_GENISLIK = {(s1[4][0][0] - s1[3][0][0])/width:.5f}")
    print(f"SAG_SUTUN_YUKSEKLIK = {(s1[5][0][1] - s1[3][0][1])/height:.5f}")
    print("------------------------------------------------------")
else:
    print("Eksik tıklama! 10 nokta gerekli.")