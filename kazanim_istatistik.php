<?php
session_start();
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

$sinav_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sinav_id == 0) { die("Hata: Sınav seçilmedi."); }

// 1. Sınav bilgilerini ve kazanımları çek
$sinav_sorgu = mysqli_query($baglanti, "SELECT * FROM denemeler WHERE id = $sinav_id");
$sinav_bilgi = mysqli_fetch_assoc($sinav_sorgu);

$kazanimlar_sorgu = mysqli_query($baglanti, "SELECT * FROM soru_kazanimlari WHERE deneme_id = $sinav_id");
$toplam_ogrenci = mysqli_num_rows(mysqli_query($baglanti, "SELECT id FROM optik_sonuclar WHERE deneme_id = $sinav_id"));

$istatistik = [];

while($k = mysqli_fetch_assoc($kazanimlar_sorgu)) {
    $soru_no = $k['soru_no'];
    $dogru_cevap = $k['dogru_cevap'];
    
    // Doğru yapanları say (SUBSTRING ile optikten tam yerini buluyoruz)
    $dogru_sayisi = mysqli_num_rows(mysqli_query($baglanti, "SELECT id FROM optik_sonuclar WHERE deneme_id = $sinav_id AND SUBSTRING(cevaplar, $soru_no, 1) = '$dogru_cevap'"));
    
    // Yanlış yapanları say (Boşlar hariç, doğru olmayanlar)
    $yanlis_sayisi = mysqli_num_rows(mysqli_query($baglanti, "SELECT id FROM optik_sonuclar WHERE deneme_id = $sinav_id AND SUBSTRING(cevaplar, $soru_no, 1) != '$dogru_cevap' AND SUBSTRING(cevaplar, $soru_no, 1) != ' '"));

    $basari_orani = ($toplam_ogrenci > 0) ? ($dogru_sayisi / $toplam_ogrenci) * 100 : 0;
    $hata_orani = ($toplam_ogrenci > 0) ? (($toplam_ogrenci - $dogru_sayisi) / $toplam_ogrenci) * 100 : 0;

    $istatistik[] = [
        'ders' => $k['ders_adi'],
        'kod' => $k['kazanim_kodu'],
        'aciklama' => $k['kazanim_aciklamasi'],
        'basari' => $basari_orani,
        'hata' => $hata_orani
    ];
}

// KRİTİK SIRALAMA: Başarı oranına göre Küçükten Büyüye (En zayıf halkalar en üste)
usort($istatistik, function($a, $b) {
    return $a['basari'] <=> $b['basari'];
});
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınıf Kazanım İstatistikleri</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 30px; }
        .report-box { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #334155; color: white; padding: 12px; text-align: left; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; }
        .low-success { color: #ef4444; font-weight: bold; }
        .progress { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-top: 5px; }
        .progress-bar { height: 100%; background: #22c55e; }
    </style>
</head>
<body>
<div class="report-box">
    <h2 style="color:#1a73e8; margin-bottom:5px;">📊 Sınıf Kazanım Başarı Oranları</h2>
    <p style="color:#64748b; margin-top:0;">Sınav: <strong><?php echo $sinav_bilgi['deneme_adi']; ?></strong> | Katılım: <?php echo $toplam_ogrenci; ?> Öğrenci</p>
    
    <table>
        <thead>
            <tr>
                <th>Ders</th>
                <th>Kazanım Kodu</th>
                <th>Kazanım Açıklaması</th>
                <th style="text-align:center;">Doğru %</th>
                <th style="text-align:center;">Hata %</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($istatistik as $i): ?>
            <tr>
                <td><span style="background:#dbeafe; color:#1e40af; padding:3px 8px; border-radius:4px; font-size:12px;"><?php echo $i['ders']; ?></span></td>
                <td><strong><?php echo $i['kod']; ?></strong></td>
                <td style="color:#475569;"><?php echo $i['aciklama']; ?></td>
                <td align="center">
                    <span class="<?php echo ($i['basari'] < 40) ? 'low-success' : ''; ?>">%<?php echo round($i['basari']); ?></span>
                    <div class="progress"><div class="progress-bar" style="width:<?php echo $i['basari']; ?>%;"></div></div>
                </td>
                <td align="center" style="color:#94a3b8;">%<?php echo round($i['hata']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br><a href="ogretmen_analiz.php" style="color:#64748b; text-decoration:none;">← Panele Geri Dön</a>
</div>
</body>
</html>