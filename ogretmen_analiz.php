<?php
include 'oturum_kontrol.php';
// Güvenlik: Giriş yoksa at
if (!isset($_SESSION['ogretmen_id'])) { header("Location: login_ogretmen.php"); exit(); }

$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

// Öğretmenin Bilgilerini Al (Okul ID dahil)
$ogretmen_id = $_SESSION['ogretmen_id'];
$sorgu = mysqli_query($baglanti, "SELECT * FROM ogretmenler WHERE id = '$ogretmen_id'");
$ogretmen = mysqli_fetch_assoc($sorgu);

$okul_id = $ogretmen['okul_id']; // Öğretmenin okulu (Örn: 34)

// Okul Adını da şıklık olsun diye çekelim
$okul_sorgu = mysqli_query($baglanti, "SELECT okul_adi FROM okullar WHERE id = '$okul_id'");
$okul_veri = mysqli_fetch_assoc($okul_sorgu);
$okul_adi = $okul_veri['okul_adi'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğretmen Paneli</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; }
        .card { background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .btn { padding: 8px 12px; background: #1a73e8; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>Sayın <?php echo $ogretmen['ad'] . " " . $ogretmen['soyad']; ?></h2>
            <small><?php echo $okul_adi; ?></small>
        </div>
        <a href="index.php" style="color:red; text-decoration:none;">Çıkış</a>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
        
        <div class="card">
            <h3>📝 Deneme Sınavları</h3>
            <table>
                <tr><th>Sınav Adı</th><th>İşlem</th></tr>
                <?php
                $denemeler = mysqli_query($baglanti, "SELECT * FROM denemeler ORDER BY id DESC LIMIT 10");
                while($d = mysqli_fetch_assoc($denemeler)):
                ?>
                <tr>
                    <td><?php echo $d['deneme_adi']; ?></td>
                    <td><a href="kazanim_istatistik.php?id=<?php echo $d['id']; ?>" class="btn">Analiz Et</a></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="card">
            <h3>👨‍🎓 Öğrenci Listesi</h3>
            <table>
                <tr><th>Ad Soyad</th><th>Telefon</th><th>İşlem</th></tr>
                <?php
                // İŞTE PROFESYONEL SORGU BU: Sadece ID eşleşenleri getir
                $ogrenciler = mysqli_query($baglanti, "SELECT * FROM ogrenciler WHERE okul_id = '$okul_id' ORDER BY ad ASC");
                
                if(mysqli_num_rows($ogrenciler) > 0):
                    while($ogr = mysqli_fetch_assoc($ogrenciler)):
                ?>
                <tr>
                    <td><?php echo $ogr['ad'] . " " . $ogr['soyad']; ?></td>
                    <td><?php echo $ogr['telefon']; ?></td>
                    <td><a href="ogrenci_ozel_analiz.php?id=<?php echo $ogr['id']; ?>" class="btn" style="background:#28a745;">Karne</a></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="3">Henüz bu okula kayıtlı öğrenci yok.</td></tr>
                <?php endif; ?>
            </table>
        </div>

    </div>
</div>

</body>
</html>