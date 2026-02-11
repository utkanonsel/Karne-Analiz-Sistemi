<?php
// --- ZİVER SİSTEMİ: VERİ KARŞILAMA VE NET HESAPLAMA MOTORU ---

$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "analiz_sistemi";

// Hata Raporlama
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

// ---------------------------------------------------------
// FONKSİYON: NET HESAPLAMA (4 Yanlış 1 Doğruyu Götürür)
// ---------------------------------------------------------
function netHesapla($ogrenci_cevaplari, $cevap_anahtari) {
    // Virgülle ayrılmış stringi diziye çevir (A,B,C -> [A,B,C])
    $ogr_dizi = explode(',', $ogrenci_cevaplari);
    
    // Cevap anahtarı veritabanında bitişik string olabilir (ABCDE...) -> Diziye çevir
    // Eğer cevap anahtarı da virgüllü ise explode kullanın.
    // Güvenlik için trim yapıyoruz.
    $cevap_anahtari = trim($cevap_anahtari);
    if (strpos($cevap_anahtari, ',') !== false) {
        $anahtar_dizi = explode(',', $cevap_anahtari);
    } else {
        $anahtar_dizi = str_split($cevap_anahtari);
    }
    
    $dogru = 0;
    $yanlis = 0;
    $bos = 0;
    
    // Döngü sayısı, en kısa olan dizi kadardır
    $soru_sayisi = min(count($ogr_dizi), count($anahtar_dizi));
    
    for ($i = 0; $i < $soru_sayisi; $i++) {
        $o_cvp = trim($ogr_dizi[$i]); // Öğrenci cevabı
        $d_cvp = $anahtar_dizi[$i];   // Doğru cevap
        
        if ($o_cvp == 'X' || $o_cvp == '' || $o_cvp == ' ') {
            $bos++;
        } elseif ($o_cvp == 'M') { // Çift işaretleme (Hatalı) -> Yanlış kabul ediyoruz
            $yanlis++;
        } elseif ($o_cvp == $d_cvp) {
            $dogru++;
        } else {
            $yanlis++;
        }
    }
    
    // Net Hesabı: Doğru - (Yanlış / 4)
    $net = $dogru - ($yanlis / 4.0);
    return max(0, $net); // Eksi net olmasın dersen 0 döndür
}

// ---------------------------------------------------------
// ANA İŞLEM: PYTHON'DAN GELEN VERİYİ KARŞILA
// ---------------------------------------------------------
if (isset($_POST['cevaplar']) && isset($_POST['deneme_id'])) {
    
    $ham_qr = $_POST['deneme_id']; // Örn: "ZIVER_15"
    $cevaplar = $_POST['cevaplar']; // Örn: "A,B,X,C..."
    
    // Sabit öğrenci verileri (İleride burası dinamikleşecek)
    $ogrenci_id = $_POST['ogrenci_id'] ?? 1;
    // Eğer öğrenci adı gönderilmediyse veritabanından çekmek daha doğru olabilir ama şimdilik böyle kalsın.
    $ad_soyad   = $_POST['ogrenci_ad_soyad'] ?? 'Mobil Okuma';

    // 1. QR KOD AYRIŞTIRMA
    if (strpos($ham_qr, 'ZIVER_') === 0) {
        $sinav_id = (int) str_replace('ZIVER_', '', $ham_qr);
    } else {
        $sinav_id = (int) $ham_qr;
    }

    try {
        // 2. SINAV KONTROLÜ VE CEVAP ANAHTARINI ÇEKME
        $sorgu = $conn->prepare("SELECT id, deneme_adi, cevap_anahtari FROM denemeler WHERE id = ?");
        $sorgu->bind_param("i", $sinav_id);
        $sorgu->execute();
        $sonuc = $sorgu->get_result();

        if ($sonuc->num_rows > 0) {
            $sinav = $sonuc->fetch_assoc();
            
            // 3. NET HESAPLAMA
            $hesaplanan_net = netHesapla($cevaplar, $sinav['cevap_anahtari']);
            
            // 4. KAYIT (optik_sonuclar tablosu)
            // 'puan' sütununa NET değerini yazıyoruz.
            $optik_no = "MOBIL-" . time(); 
            
            // Eğer daha önce bu öğrenci bu sınava girmişse güncelle, yoksa ekle (Opsiyonel Güvenlik)
            // Şimdilik direkt INSERT yapıyoruz, senin sisteminde mükerrer kayıt kontrolü varsa buraya eklenebilir.
            
            $ekle = $conn->prepare("INSERT INTO optik_sonuclar (ogrenci_id, deneme_id, optik_no, ogrenci_ad_soyad, cevaplar, puan, durum) VALUES (?, ?, ?, ?, ?, ?, 1)");
            
            $ekle->bind_param("iisssd", $ogrenci_id, $sinav_id, $optik_no, $ad_soyad, $cevaplar, $hesaplanan_net);
            
            if ($ekle->execute()) {
                // Python terminaline dönecek mesaj
                echo "BAŞARILI: '" . $sinav['deneme_adi'] . "' sınavı kaydedildi. | NET: " . $hesaplanan_net;
            } else {
                echo "KAYIT HATASI: " . $conn->error;
            }
            $ekle->close();
            
        } else {
            echo "HATA: Okunan QR Kod ($ham_qr) sistemde tanımlı değil!";
        }
        $sorgu->close();

    } catch (Exception $e) {
        echo "Sistem Hatası: " . $e->getMessage();
    }
} else {
    echo "Hata: Veri paketi eksik.";
}

$conn->close();
?>