<?php
// OTURUM VE VERİTABANI BAĞLANTI
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Güvenlik kontrolü dosyasını buraya ekleyebilirsin: include 'oturum_kontrol.php';

$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

$sonuc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. ÖĞRENCİ VE DENEME BİLGİLERİNİ ÇEK (YENİ SİSTEME UYGUN: o.ad ve o.soyad ayrıldı)
$sonuc_sorgu = mysqli_query($baglanti, "SELECT os.*, d.deneme_adi, d.cevap_anahtari, d.toplam_soru, o.ad, o.soyad 
    FROM optik_sonuclar os 
    JOIN denemeler d ON os.deneme_id = d.id 
    JOIN ogrenciler o ON os.ogrenci_id = o.id 
    WHERE os.id = $sonuc_id");
$sonuc = mysqli_fetch_assoc($sonuc_sorgu);

if (!$sonuc) { 
    die("<div style='padding:20px; color:red; font-family:sans-serif;'>Hata: Sınav sonucu bulunamadı veya silinmiş olabilir.</div>"); 
}

// 2. Sınava ait tüm kazanımları çek
$kazanim_sorgu = mysqli_query($baglanti, "SELECT * FROM soru_kazanimlari WHERE deneme_id = ".$sonuc['deneme_id']." ORDER BY soru_no ASC");
$kazanimlar = [];
while($k = mysqli_fetch_assoc($kazanim_sorgu)) {
    $kazanimlar[$k['soru_no']] = $k;
}

$ogrenci_cevaplari = str_split(trim($sonuc['cevaplar']));
$cevap_anahtari = str_split(trim($sonuc['cevap_anahtari']));
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Analiz Karnesi</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-box { padding: 15px; border-radius: 10px; text-align: center; color: white; font-weight: bold; font-size: 18px; }
        .stat-d { background: #2ecc71; } .stat-y { background: #e74c3c; } .stat-b { background: #f1c40f; } .stat-n { background: #3498db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f8fafc; color: #64748b; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .bg-success { background: #dcfce7; color: #166534; }
        .bg-danger { background: #fee2e2; color: #991b1b; }
        .bg-warning { background: #fef9c3; color: #854d0e; }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } .container { box-shadow: none; max-width: 100%; } }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2 style="margin:0; color:#1e293b;">Öğrenci: <?php echo htmlspecialchars($sonuc['ad'] . " " . $sonuc['soyad']); ?></h2>
            <p style="margin:5px 0 0 0; color:#64748b;">Sınav: <strong><?php echo htmlspecialchars($sonuc['deneme_adi']); ?></strong></p>
        </div>
        <button onclick="window.print()" class="no-print" style="padding:10px 20px; background:#6366f1; color:white; border:none; border-radius:8px; cursor:pointer;">🖨️ Yazdır / PDF</button>
    </div>

    <?php
    $dogru = 0; $yanlis = 0; $bos = 0;
    $rows = "";

    foreach($cevap_anahtari as $index => $d_cevap) {
        $no = $index + 1;
        $o_cevap = isset($ogrenci_cevaplari[$index]) ? strtoupper($ogrenci_cevaplari[$index]) : "-";
        $d_cevap = strtoupper($d_cevap);
        
        $k = isset($kazanimlar[$no]) ? $kazanimlar[$no] : ['ders_adi' => '-', 'kazanim_aciklamasi' => 'Kazanım girilmemiş'];
        
        if ($o_cevap == "-" || $o_cevap == " ") {
            $bos++; $durum = "BOŞ"; $class = "bg-warning";
        } elseif ($o_cevap == $d_cevap) {
            $dogru++; $durum = "DOĞRU"; $class = "bg-success";
        } else {
            $yanlis++; $durum = "YANLIŞ"; $class = "bg-danger";
        }

        $rows .= "<tr>
            <td>$no</td>
            <td><b>{$k['ders_adi']}</b></td>
            <td style='max-width:350px;'>{$k['kazanim_aciklamasi']}</td>
            <td align='center' style='font-family:monospace; font-weight:bold;'>$d_cevap</td>
            <td align='center' style='font-family:monospace; font-weight:bold;'>$o_cevap</td>
            <td align='center'><span class='badge $class'>$durum</span></td>
        </tr>";
    }
    $net = $dogru - ($yanlis * 0.25);
    ?>

    <div class="stats-grid">
        <div class="stat-box stat-d">DOĞRU: <?php echo $dogru; ?></div>
        <div class="stat-box stat-y">YANLIŞ: <?php echo $yanlis; ?></div>
        <div class="stat-box stat-b">BOŞ: <?php echo $bos; ?></div>
        <div class="stat-box stat-n">NET: <?php echo number_format($net, 2); ?></div>
    </div>

    <h3 style="color:#1e293b; border-left:4px solid #6366f1; padding-left:10px;">🔍 Soru ve Kazanım Analizi</h3>
    <table>
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="120">Ders</th>
                <th>Kazanım / Konu Açıklaması</th>
                <th width="50" style="text-align:center;">D.C.</th>
                <th width="50" style="text-align:center;">Ö.C.</th>
                <th width="70" style="text-align:center;">Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $rows; ?>
        </tbody>
    </table>

    <div class="no-print" style="margin-top:30px; text-align:center;">
        <hr>
        <p style="color:#94a3b8; font-size:12px;">Utkan Analiz Sistemi - Gelişim Raporu</p>
    </div>
</div>

</body>
</html>