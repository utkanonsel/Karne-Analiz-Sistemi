<?php
// 1. OTURUM KONTROLÜ (Düğmenin çalışmama sebebini düzelttik)
include 'oturum_kontrol.php';

// Güvenlik: ogretmen_id yoksa login sayfasına at
if (!isset($_SESSION['ogretmen_id'])) { 
    header("Location: login_ogretmen.php"); 
    exit(); 
}

$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

// 2. ÖĞRENCİ BİLGİSİNİ ÇEK (Yeni sisteme uygun: ad ve soyad ayrı)
$ogr_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($ogr_id == 0) { die("Hata: Öğrenci seçilmedi."); }

$ogrenci_sorgu = mysqli_query($baglanti, "SELECT ad, soyad FROM ogrenciler WHERE id = $ogr_id");
$ogrenci = mysqli_fetch_assoc($ogrenci_sorgu);

if (!$ogrenci) { die("Hata: Öğrenci bulunamadı."); }

// Sınav sonuçlarını çek
$sonuclar = mysqli_query($baglanti, "SELECT os.*, d.deneme_adi, d.cevap_anahtari 
                                     FROM optik_sonuclar os 
                                     JOIN denemeler d ON os.deneme_id = d.id 
                                     WHERE os.ogrenci_id = $ogr_id 
                                     ORDER BY os.id DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $ogrenci['ad'] . " " . $ogrenci['soyad']; ?> - Gelişim Raporu</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f9; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; color: #64748b; }
        .btn { padding: 6px 12px; background: #1a73e8; color: white; text-decoration: none; border-radius: 5px; font-size: 13px; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #666; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <a href="ogretmen_analiz.php" class="back-btn">← Panele Dön</a>
    
    <h2 style="margin:0;">📊 <?php echo $ogrenci['ad'] . " " . $ogrenci['soyad']; ?></h2>
    <p style="color:#666;">Öğrencinin bugüne kadar girdiği denemeler ve başarı grafiği.</p>

    <table>
        <thead>
            <tr>
                <th style="text-align:left;">Sınav Adı</th>
                <th>Doğru</th>
                <th>Yanlış</th>
                <th>Boş</th>
                <th>Net</th>
                <th>İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php while($satir = mysqli_fetch_assoc($sonuclar)): 
                $d = 0; $y = 0; $b = 0;
                $ogr_c = str_split(trim($satir['cevaplar']));
                $dog_c = str_split(trim($satir['cevap_anahtari']));
                
                foreach($dog_c as $i => $dogru) {
                    $ogr = strtoupper($ogr_c[$i] ?? "-");
                    if($ogr == "-" || $ogr == " ") $b++;
                    elseif($ogr == strtoupper($dogru)) $d++;
                    else $y++;
                }
                $net = $d - ($y * 0.25);
            ?>
            <tr>
                <td style="text-align:left;"><strong><?php echo $satir['deneme_adi']; ?></strong></td>
                <td style="color:green; font-weight:bold;"><?php echo $d; ?></td>
                <td style="color:red; font-weight:bold;"><?php echo $y; ?></td>
                <td><?php echo $b; ?></td>
                <td style="background:#f9f9f9; font-weight:bold;"><?php echo number_format($net, 2); ?></td>
                <td>
                    <a href="analiz_karne.php?id=<?php echo $satir['id']; ?>" class="btn">Detaylı Karne</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($sonuclar) == 0): ?>
                <tr><td colspan="6">Bu öğrenciye ait sınav sonucu bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>