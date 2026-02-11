<?php
session_start();
// Güvenlik: Giriş yapılmamışsa giriş sayfasına at
if (!isset($_SESSION['ogrenci_id'])) { header("Location: login_ogrenci.php"); exit(); }

$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

$ogr_id = $_SESSION['ogrenci_id'];

// --- SQL ŞEMASINA GÖRE ÖĞRENCİ BİLGİLERİNİ ÇEK ---
$ogr_sorgu = mysqli_query($baglanti, "SELECT ad, soyad, okul_adi, telefon FROM ogrenciler WHERE id = $ogr_id");
$ogr = mysqli_fetch_assoc($ogr_sorgu);

// --- 1. GENEL İSTATİSTİKLERİ HESAPLA ---
$sonuclar_res = mysqli_query($baglanti, "SELECT os.cevaplar, d.cevap_anahtari FROM optik_sonuclar os JOIN denemeler d ON os.deneme_id = d.id WHERE os.ogrenci_id = $ogr_id");
$toplam_d = 0; $toplam_y = 0; $toplam_soru_sayisi = 0;
while($s = mysqli_fetch_assoc($sonuclar_res)) {
    $c = str_split(trim($s['cevaplar']));
    $a = str_split(trim($s['cevap_anahtari']));
    $limit = min(count($c), count($a));
    for($i=0; $i<$limit; $i++) {
        $toplam_soru_sayisi++;
        if($c[$i] == $a[$i]) $toplam_d++;
        else if($c[$i] != ' ' && $c[$i] != '') $toplam_y++;
    }
}

// --- 2. SINAV LİSTELERİ ---
// Bekleyenler: olusturma_tarihi sütununa göre
$bekleyenler = mysqli_query($baglanti, "SELECT * FROM denemeler WHERE id NOT IN (SELECT deneme_id FROM optik_sonuclar WHERE ogrenci_id = $ogr_id) ORDER BY olusturma_tarihi DESC");
// Tamamlananlar: tarih sütununa göre
$tamamlananlar = mysqli_query($baglanti, "SELECT d.deneme_adi, os.puan, os.tarih FROM optik_sonuclar os JOIN denemeler d ON os.deneme_id = d.id WHERE os.ogrenci_id = $ogr_id ORDER BY os.tarih DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğrenci Paneli | Ziver Analiz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #334155; }
        .container { max-width: 900px; margin: 0 auto; padding-bottom: 100px; }
        
        /* ÖĞRENCİ KİMLİK KARTI */
        .profile-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; border-left: 6px solid #16a34a; }
        .profile-info h2 { margin: 0; color: #1e293b; font-size: 24px; }
        .profile-info p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-val { font-size: 22px; font-weight: bold; color: #0f172a; display: block; }

        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h3 { margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 15px; text-align: left; border-bottom: 1px solid #f8fafc; }

        /* YÜZEN ŞIK BUTON */
        .scan-btn {
            position: fixed; bottom: 30px; right: 30px;
            width: 80px; height: 80px; background: linear-gradient(135deg, #16a34a, #15803d);
            border-radius: 50%; color: white; border: none; cursor: pointer;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.4);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: 0.3s; z-index: 1000;
        }
        .scan-btn:hover { transform: scale(1.1); box-shadow: 0 15px 35px rgba(22, 163, 74, 0.6); }
        .scan-btn i { font-size: 28px; }
        .scan-btn span { font-size: 10px; font-weight: bold; margin-top: 4px; }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-card">
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($ogr['ad'] . ' ' . $ogr['soyad']); ?></h2>
            <p>
                <i class="fa fa-school"></i> <?php echo htmlspecialchars($ogr['okul_adi']); ?> | 
                <i class="fa fa-phone"></i> <?php echo htmlspecialchars($ogr['telefon']); ?>
            </p>
        </div>
        <a href="logout.php" style="color: #ef4444; font-size: 22px;"><i class="fa fa-sign-out-alt"></i></a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"> <span class="stat-val"><?php echo $toplam_soru_sayisi; ?></span> Toplam Soru </div>
        <div class="stat-card"> <span class="stat-val" style="color:#16a34a;"><?php echo $toplam_d; ?></span> Doğru </div>
        <div class="stat-card"> 
            <span class="stat-val" style="color:#2563eb;">
                <?php echo ($toplam_soru_sayisi > 0) ? number_format($toplam_d - ($toplam_y/4), 2) : "0.00"; ?>
            </span> Net Ort. 
        </div>
    </div>

    <div class="card">
        <h3>⏳ Çözülmeyi Bekleyen Sınavlar</h3>
        <table>
            <?php while($b = mysqli_fetch_assoc($bekleyenler)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($b['deneme_adi']); ?></strong></td>
                <td align="right">
                    <a href="ogrenci_optik.php?id=<?php echo $b['id']; ?>" style="color:#2563eb; font-weight:600; text-decoration:none;">Elle Gir</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="card">
        <h3>📊 Geçmiş Sınav Karnelerim</h3>
        <table>
            <thead><tr><th>Sınav Adı</th><th>Tarih</th><th>Puan/Net</th></tr></thead>
            <tbody>
                <?php while($t = mysqli_fetch_assoc($tamamlananlar)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['deneme_adi']); ?></td>
                    <td><?php echo date("d.m.Y", strtotime($t['tarih'])); ?></td>
                    <td style="color:#16a34a; font-weight:bold;"><?php echo number_format($t['puan'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<button class="scan-btn" onclick="kameraAc()">
    <i class="fa fa-qrcode"></i>
    <span>OKUT</span>
</button>

<script>
function kameraAc() {
    if(confirm("Ziver Optik Okuma sistemi başlatılıyor. Hazır mısınız?")) {
        // ID'Yİ BURADA GÖNDERİYORUZ: ?id=...
        fetch('http://127.0.0.1:5000/okut?id=<?php echo $ogr_id; ?>')
        .then(res => res.json())
        .then(data => {
            if(data.durum === "basarili") {
                alert("✅ Sınav başarıyla okundu!");
                location.reload(); // Sayfayı yenile ki sonucu gör
            } else { 
                alert("❌ Hata: " + data.mesaj); 
            }
        })
        .catch((err) => {
            alert("⚠️ Hata: Python (baslat.py) kapalı olabilir!");
            console.error(err);
        });
    }
}
</script></body>
</html>