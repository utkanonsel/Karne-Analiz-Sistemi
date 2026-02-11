<?php
include 'oturum_kontrol.php'; // Güvenlik Kilidi
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

// --- İŞLEM 1: SİLME ---
if (isset($_GET['sil_tur']) && isset($_GET['id'])) {
    $tur = $_GET['sil_tur']; // 'ogretmen' veya 'ogrenci'
    $id = (int)$_GET['id'];

    if ($tur == 'ogretmen') {
        mysqli_query($baglanti, "DELETE FROM ogretmenler WHERE id=$id");
    } elseif ($tur == 'ogrenci') {
        mysqli_query($baglanti, "DELETE FROM ogrenciler WHERE id=$id");
        // Öğrenci silinince optik sonuçlarını da temizleyelim mi? (İsteğe bağlı)
        mysqli_query($baglanti, "DELETE FROM optik_sonuclar WHERE ogrenci_id=$id");
    }
    header("Location: admin_kullanici_yonetimi.php?mesaj=silindi");
    exit();
}

// --- İŞLEM 2: ŞİFRE GÜNCELLEME ---
if (isset($_POST['sifre_guncelle'])) {
    $yeni_sifre = $_POST['yeni_sifre'];
    $kullanici_id = (int)$_POST['kullanici_id'];
    $tablo = $_POST['tablo_adi']; // 'ogretmenler' veya 'ogrenciler'
    
    mysqli_query($baglanti, "UPDATE $tablo SET sifre='$yeni_sifre' WHERE id=$kullanici_id");
    $bilgi_mesaji = "✅ Şifre başarıyla güncellendi: $yeni_sifre";
}

// LİSTELERİ ÇEK
$ogretmenler = mysqli_query($baglanti, "SELECT o.*, k.okul_adi as gercek_okul_adi FROM ogretmenler o LEFT JOIN okullar k ON o.okul_id = k.id ORDER BY o.id DESC");
$ogrenciler = mysqli_query($baglanti, "SELECT o.*, k.okul_adi as gercek_okul_adi FROM ogrenciler o LEFT JOIN okullar k ON o.okul_id = k.id ORDER BY o.id DESC LIMIT 50"); // Çok kasmasın diye son 50 öğrenci
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kullanıcı Yönetimi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f2f6; padding: 20px; }
        .container { max-width: 1200px; margin: auto; }
        .panel { background: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { color: #2d3436; border-bottom: 2px solid #dfe6e9; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #636e72; }
        .btn-sil { background: #ff7675; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-size: 12px; }
        .btn-duzenle { background: #74b9ff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 5px; font-size: 12px; cursor: pointer; border:none; }
        
        /* Modal (Şifre Penceresi) */
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
        .modal-icerik { background:white; padding:30px; border-radius:10px; width:300px; text-align:center; }
        .kapat { float:right; cursor:pointer; font-weight:bold; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" style="text-decoration:none; color:#636e72;">← Ana Menüye Dön</a>
    
    <?php if(isset($bilgi_mesaji)) echo "<div style='background:#badc58; padding:10px; margin:10px 0; border-radius:5px;'>$bilgi_mesaji</div>"; ?>

    <div class="panel">
        <h2>👨‍🏫 Öğretmenler</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Okul</th>
                    <th>Telefon</th>
                    <th>Şifre</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while($ogr = mysqli_fetch_assoc($ogretmenler)): ?>
                <tr>
                    <td><?php echo $ogr['id']; ?></td>
                    <td><?php echo $ogr['ad'] . " " . $ogr['soyad']; ?></td>
                    <td><?php echo $ogr['gercek_okul_adi']; ?></td>
                    <td><?php echo $ogr['telefon']; ?></td>
                    <td><?php echo $ogr['sifre']; ?></td>
                    <td>
                        <button onclick="sifreAc(<?php echo $ogr['id']; ?>, 'ogretmenler')" class="btn-duzenle">🔑 Şifre Değiş</button>
                        <a href="admin_kullanici_yonetimi.php?sil_tur=ogretmen&id=<?php echo $ogr['id']; ?>" onclick="return confirm('Bu öğretmeni silmek istediğine emin misin?')" class="btn-sil">🗑️ Sil</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2>🎓 Son Kayıtlı Öğrenciler (İlk 50)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>Okul</th>
                    <th>Numara/Tel</th>
                    <th>Şifre</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while($ogrenci = mysqli_fetch_assoc($ogrenciler)): ?>
                <tr>
                    <td><?php echo $ogrenci['id']; ?></td>
                    <td><?php echo $ogrenci['ad'] . " " . $ogrenci['soyad']; ?></td>
                    <td><?php echo $ogrenci['gercek_okul_adi']; ?></td>
                    <td><?php echo $ogrenci['telefon']; ?></td>
                    <td><?php echo $ogrenci['sifre']; ?></td>
                    <td>
                        <button onclick="sifreAc(<?php echo $ogrenci['id']; ?>, 'ogrenciler')" class="btn-duzenle">🔑 Şifre Değiş</button>
                        <a href="admin_kullanici_yonetimi.php?sil_tur=ogrenci&id=<?php echo $ogrenci['id']; ?>" onclick="return confirm('Bu öğrenciyi silmek istediğine emin misin?')" class="btn-sil">🗑️ Sil</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="sifreModal" class="modal">
    <div class="modal-icerik">
        <span class="kapat" onclick="document.getElementById('sifreModal').style.display='none'">&times;</span>
        <h3>Yeni Şifre Belirle</h3>
        <form method="POST">
            <input type="hidden" name="kullanici_id" id="modal_id">
            <input type="hidden" name="tablo_adi" id="modal_tablo">
            <input type="text" name="yeni_sifre" placeholder="Yeni Şifre" style="padding:10px; width:80%; margin-bottom:10px;" required>
            <br>
            <button type="submit" name="sifre_guncelle" class="btn-duzenle" style="padding:10px 20px; font-size:14px;">Güncelle</button>
        </form>
    </div>
</div>

<script>
function sifreAc(id, tablo) {
    document.getElementById('modal_id').value = id;
    document.getElementById('modal_tablo').value = tablo;
    document.getElementById('sifreModal').style.display = 'flex';
}
</script>

</body>
</html>