<?php
// --- ZİVER SİSTEMİ: GELİŞMİŞ EXCEL SINAV YÜKLEYİCİ (BACKEND) ---

// 1. Gerekli Kütüphaneleri Dahil Et
require 'vendor/autoload.php'; // PhpSpreadsheet için composer autoloader
use PhpOffice\PhpSpreadsheet\IOFactory;

// Veritabanı Bağlantısı (PDO Kullanımı)
$host = 'localhost';
$db   = 'analiz_sistemi';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// ------------------------------------------------------------------
// FONKSİYON: Excel Dosyasını İşle ve Kaydet
// ------------------------------------------------------------------
function excelSinavYukle($dosya_yolu, $pdo) {
    try {
        // A. Dosyayı Yükle ve Okuyucu Başlat
        $spreadsheet = IOFactory::load($dosya_yolu);
        
        // İlk sayfayı aktif et (Varsayılan)
        $sheet = $spreadsheet->getActiveSheet();
        $sayfa_adi = $sheet->getTitle(); // Excel sekme adı

        // B. Sabit Bilgileri Oku (Header Kısmı)
        // B1: Kodu, B2: Adı, B3: Ders/Tür, B4: Tarih
        $deneme_kodu = trim($sheet->getCell('B1')->getValue());
        $deneme_adi = trim($sheet->getCell('B2')->getValue());
        $ders_tur = trim($sheet->getCell('B3')->getValue());
        
        // Tarih hücresini formatla (Excel tarih formatı sorunu yaşamamak için)
        $tarih_ham = $sheet->getCell('B4')->getValue();
        if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($sheet->getCell('B4'))) {
            $deneme_tarihi = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tarih_ham)->format('Y-m-d');
        } else {
            $deneme_tarihi = date('Y-m-d', strtotime($tarih_ham));
        }

        // C. Güvenlik Kontrolleri
        // 1. Sayfa Adı ile Deneme Adı Eşleşiyor mu?
        if (strcasecmp($sayfa_adi, $deneme_adi) !== 0) {
            throw new Exception("HATA: Excel sayfa adı ('$sayfa_adi') ile B2 hücresindeki Deneme Adı ('$deneme_adi') uyuşmuyor!");
        }

        // 2. Bu deneme kodu daha önce eklenmiş mi?
        $stmt = $pdo->prepare("SELECT id FROM denemeler WHERE deneme_adi = ? OR tarih = ?"); 
        // Not: Senin 'deneme_kodu' sütunun olmadığı için Ad ve Tarih kontrolü yapıyorum.
        // Eğer 'deneme_kodu' sütunu eklersen: WHERE deneme_kodu = ?
        $stmt->execute([$deneme_adi, $deneme_tarihi]);
        if ($stmt->fetch()) {
            throw new Exception("UYARI: Bu deneme ('$deneme_adi') sistemde zaten kayıtlı.");
        }

        // D. Soruları Okumaya Başla
        // Sabit alanlardan sonra 1 satır boşluk var (Satır 5 boş). Tablo başlığı Satır 6. Veri Satır 7'den başlar.
        $baslangic_satiri = 7;
        $en_yuksek_satir = $sheet->getHighestRow();
        
        $cevap_anahtari_string = ""; // Eski sistem için (ABCDE...)
        $sorular_dizisi = [];

        for ($row = $baslangic_satiri; $row <= $en_yuksek_satir; $row++) {
            // Sütunlar: A=SoruNo, B=Ders, C=KazanımKodu, D=KazanımAdı, E=DoğruCevap
            $s_no = $sheet->getCell('A' . $row)->getValue();
            $s_ders = $sheet->getCell('B' . $row)->getValue();
            $s_kkodu = $sheet->getCell('C' . $row)->getValue();
            $s_kadi = $sheet->getCell('D' . $row)->getValue();
            $s_cevap = strtoupper(trim($sheet->getCell('E' . $row)->getValue()));

            // Boş satıra geldiysek döngüyü bitir
            if (empty($s_no) || empty($s_cevap)) break;

            // Cevap anahtarı stringini oluştur (Eski sistem uyumluluğu)
            $cevap_anahtari_string .= $s_cevap;

            // Detaylı veriyi diziye at (Yeni sistem için)
            $sorular_dizisi[] = [
                'no' => $s_no,
                'ders' => $s_ders,
                'k_kodu' => $s_kkodu,
                'k_adi' => $s_kadi,
                'cevap' => $s_cevap
            ];
        }

        // E. Veritabanına Kayıt İşlemi (Transaction ile güvenli kayıt)
        $pdo->beginTransaction();

        // 1. Denemeler Tablosuna Ekle (Eski sisteme uyumlu)
        // Senin tablonun yapısına göre sütun adlarını kontrol et: (deneme_adi, tarih, cevap_anahtari)
        $sql_deneme = "INSERT INTO denemeler (deneme_adi, tarih, cevap_anahtari) VALUES (?, ?, ?)";
        $stmt_d = $pdo->prepare($sql_deneme);
        $stmt_d->execute([$deneme_adi, $deneme_tarihi, $cevap_anahtari_string]);
        
        $yeni_deneme_id = $pdo->lastInsertId(); // Yeni eklenen ID'yi al (Örn: 15)

        // 2. Sorular Tablosuna Detayları Ekle (Yeni analiz sistemi için)
        $sql_soru = "INSERT INTO sorular (deneme_id, soru_no, ders, kazanim_kodu, kazanim_adi, dogru_cevap) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_s = $pdo->prepare($sql_soru);

        foreach ($sorular_dizisi as $soru) {
            $stmt_s->execute([
                $yeni_deneme_id,
                $soru['no'],
                $soru['ders'],
                $soru['k_kodu'],
                $soru['k_adi'],
                $soru['cevap']
            ]);
        }

        // Her şey yolundaysa onayla
        $pdo->commit();

        return [
            'durum' => true, 
            'mesaj' => "BAŞARILI: '$deneme_adi' sisteme eklendi. (ID: $yeni_deneme_id, Soru Sayısı: " . count($sorular_dizisi) . ")"
        ];

    } catch (Exception $e) {
        // Hata varsa işlemleri geri al
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['durum' => false, 'mesaj' => $e->getMessage()];
    }
}

// --- DOSYA YÜKLEME İŞLEYİCİSİ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_dosyasi'])) {
    if ($_FILES['excel_dosyasi']['error'] === UPLOAD_ERR_OK) {
        
        $gecici_yol = $_FILES['excel_dosyasi']['tmp_name'];
        $orijinal_ad = $_FILES['excel_dosyasi']['name'];
        
        // Sadece xlsx veya xls kabul et
        $uzanti = strtolower(pathinfo($orijinal_ad, PATHINFO_EXTENSION));
        if ($uzanti != 'xlsx' && $uzanti != 'xls') {
            die("HATA: Sadece Excel dosyaları (.xlsx, .xls) yüklenebilir.");
        }

        // Fonksiyonu çalıştır
        $sonuc = excelSinavYukle($gecici_yol, $pdo);

        // Sonucu ekrana bas (JSON veya Basit Metin)
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($sonuc, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;

    } else {
        die("Dosya yükleme hatası: Kod " . $_FILES['excel_dosyasi']['error']);
    }
}
?>