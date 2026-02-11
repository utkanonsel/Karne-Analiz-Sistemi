<?php
session_start();
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

// Güvenlik: Giriş yapmamış öğretmenleri engelle (İsteğe bağlı)
$ogr_id = isset($_GET['ogrenci_id']) ? (int)$_GET['ogrenci_id'] : 0;
if ($ogr_id == 0) { die("Hata: Öğrenci seçilmedi."); }

// 1. Öğrenci Bilgilerini Al
$ogr_sorgu = mysqli_query($baglanti, "SELECT * FROM ogrenciler WHERE id = $ogr_id");
$ogr_bilgi = mysqli_fetch_assoc($ogr_sorgu);

// 2. Öğrencinin Girdiği Tüm Sınav Sonuçlarını Çek
$sonuclar_sorgu = "SELECT os.cevaplar, d.deneme_adi, d.cevap_anahtari, d.id as deneme_id, os.okunma_tarihi
                  FROM optik_sonuclar os 
                  JOIN denemeler d ON os.deneme_id = d.id 
                  WHERE os.ogrenci_id = $ogr_id
                  ORDER BY os.okunma_tarihi DESC";
$sonuclar_res = mysqli_query($baglanti, $sonuclar_sorgu);

$ders_analizi = [];
$sinav_gecmisi = [];

while($s = mysqli_fetch_assoc($sonuclar_res)) {
    $c = str_split(trim($s['cevaplar']));
    $a = str_split(trim($s['cevap_anahtari']));
    $deneme_id = $s['deneme_id'];
    
    // Her sınavın kendi içindeki soru-ders eşleşmesini çek (Hatasız ayrıştırma için)
    $kazanim_res = mysqli_query($baglanti, "SELECT soru_no, ders_adi FROM soru_kazanimlari WHERE deneme_id = $deneme_id");
    $soru_ders_map = [];
    while($k = mysqli_fetch_assoc($kazanim_res)) {
        $soru_ders_map[$k['soru_no']] = $k['ders_adi'];
    }

    $sinav_d = 0; $sinav_y = 0; $sinav_b = 0;
    
    foreach($a as $index => $dogru_cevap) {
        $soru_no = $index + 1;
        $ders = isset($soru_ders_map[$soru_no]) ? $soru_ders_map[$soru_no] : "Tanımsız";
        $ogr_cevap = isset($c[$index]) ? $c[$index] : " ";

        if(!isset($ders_analizi[$ders])) {
            $ders_analizi[$ders] = ['d' => 0, 'y' => 0, 'b' => 0, 'toplam_soru' => 0];
        }

        $ders_analizi[$ders]['toplam_soru']++;
        if($ogr_cevap == $dogru_cevap) {
            $ders_analizi[$ders]['d']++; $sinav_d++;
        } elseif($ogr_cevap != " " && $ogr_cevap != "") {
            $ders_analizi[$ders]['y']++; $sinav_y++;
        } else {
            $ders_analizi[$ders]['b']++; $sinav_b++;
        }
    }
    
    $sinav_net = $sinav_d - ($sinav_y * 0.25);
    $sinav_gecmisi[] = [
        'ad' => $s['deneme_adi'], 
        'tarih' => $s['okunma_tarihi'],
        'd' => $sinav_d, 
        'y' => $sinav_y, 
        'net' => $sinav_net
    ];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $ogr_bilgi['ad_soyad']; ?> - Gelişim Analizi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 30px; color: #1e293b; }
        .container { max-width: 1000px; margin: auto; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3b82f6; padding-bottom: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f8fafc; padding: 12px; text-align: left; color: #64748b; font-size: 13px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .progress-container { width: 100%; background: #f1f5f9; border-radius: 10px; height: 10px; margin-top: 8px; overflow: hidden; }
        .progress-bar { height: 100%; background: #3b82f6; border-radius: 10px; }
        .badge-d { color: #166534; background: #dcfce7; padding: 3px 8px; border-radius: 5px; font-weight: bold; }
        .badge-y { color: #991b1b; background: #fee2e2; padding: 3px 8px; border-radius: 5px; font-weight: bold; }
        .btn-back { text-decoration: none; color: #64748b; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .btn-back:hover { color: #1e293b; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <div>
                <h1 style="margin:0; color:#1e293b;"><?php echo htmlspecialchars($ogr_bilgi['ad_soyad']); ?></h1>
                <p style="margin:5px 0 0 0; color:#64748b;">Öğrenci Genel Gelişim Raporu</p>
            </div>
            <a href="ogretmen_analiz.php" class="btn-back">← Listeye Dön</a>
        </div>

        <h3>📚 Ders Bazlı Kümülatif Başarı (Tüm Sınavlar)</h3>
        <p style="color:#94a3b8; font-size:13px; margin-top:-10px;">Öğrencinin geçmişten bugüne tüm sorular içindeki ders performansı.</p>
        <table>
            <thead>
                <tr>
                    <th>Ders Adı</th>
                    <th>Toplam Soru</th>
                    <th style="text-align:center;">D / Y / B</th>
                    <th width="250">Başarı Yüzdesi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ders_analizi as $ders => $v): 
                    $basari_yuzde = ($v['toplam_soru'] > 0) ? ($v['d'] / $v['toplam_soru']) * 100 : 0;
                ?>
                <tr>
                    <td><strong><?php echo $ders; ?></strong></td>
                    <td><?php echo $v['toplam_soru']; ?> Soru</td>
                    <td align="center">
                        <span class="badge-d"><?php echo $v['d']; ?>D</span> 
                        <span class="badge-y"><?php echo $v['y']; ?>Y</span> 
                        <small style="color:#94a3b8;"><?php echo $v['b']; ?>B</small>
                    </td>
                    <td>
                        <div style="display:flex; justify-content:space-between; font-size:12px; font-weight:bold;">
                            <span>%<?php echo round($basari_yuzde); ?></span>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $basari_yuzde; ?>%; background: <?php echo $basari_yuzde < 50 ? '#ef4444' : '#22c55e'; ?>;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>📊 Kronolojik Sınav Geçmişi</h3>
        <table>
            <thead>
                <tr>
                    <th>Sınav Tarihi</th>
                    <th>Sınav Adı</th>
                    <th style="text-align:center;">Doğru</th>
                    <th style="text-align:center;">Yanlış</th>
                    <th style="text-align:center;">Net</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sinav_gecmisi as $sg): ?>
                <tr>
                    <td style="color:#64748b;"><?php echo date('d.m.Y', strtotime($sg['tarih'])); ?></td>
                    <td><strong><?php echo htmlspecialchars($sg['ad']); ?></strong></td>
                    <td align="center"><?php echo $sg['d']; ?></td>
                    <td align="center"><?php echo $sg['y']; ?></td>
                    <td align="center" style="font-weight:bold; color:#2563eb; background:#f8fafc;"><?php echo number_format($sg['net'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($sinav_gecmisi)) echo "<tr><td colspan='5' align='center'>Öğrenci henüz bir sınava girmemiş.</td></tr>"; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>