<?php
// Hata Raporlamayı Aç (Sorunu görmek için)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// GÜVENLİK KİLİDİ
// Eğer oturum_kontrol.php dosyan varsa başındaki // işaretini kaldır.
// include 'oturum_kontrol.php';

$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");

if (!$baglanti) {
    die("Veritabanı Bağlantı Hatası: " . mysqli_connect_error());
}

mysqli_set_charset($baglanti, "utf8");

// Sınav Silme İşlemi
if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    if(mysqli_query($baglanti, "DELETE FROM denemeler WHERE id = $sil_id")) {
        header("Location: admin_sinav_listesi.php?mesaj=silindi");
        exit();
    } else {
        echo "Silme Hatası: " . mysqli_error($baglanti);
    }
}

// Sınavları Listele
$sorgu = mysqli_query($baglanti, "SELECT * FROM denemeler ORDER BY id DESC");

if (!$sorgu) {
    die("Sorgu Hatası: " . mysqli_error($baglanti));
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Listesi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 950px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
        
        .btn { padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; margin-right: 5px; cursor: pointer; display: inline-block; }
        .btn-sil { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .btn-qr { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        
        /* QR Modal */
        .qr-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 999; }
        .qr-kutusu { background: white; padding: 30px; border-radius: 15px; text-align: center; width: 300px; }
        .kapat-btn { margin-top: 15px; padding: 10px; width: 100%; background: #333; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>📋 Sınav Listesi</h2>
        <a href="index.php" style="text-decoration:none; color:#666;">← Geri Dön</a>
    </div>

    <?php if(isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi'): ?>
        <p style="color: green; font-weight: bold;">Sınav başarıyla silindi.</p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Sınav Adı</th>
                <th>Soru</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php while($satir = mysqli_fetch_assoc($sorgu)): ?>
            <tr>
                <td><?php echo $satir['id']; ?></td>
                <td><?php echo htmlspecialchars($satir['deneme_adi']); ?></td>
                <td><?php echo $satir['soru_sayisi'] ?? $satir['toplam_soru'] ?? '-'; ?></td>
                <td>
                    <button onclick="qrGoster('<?php echo $satir['id']; ?>', '<?php echo htmlspecialchars($satir['deneme_adi']); ?>')" class="btn btn-qr">QR AL</button>
                    <a href="admin_sinav_listesi.php?sil=<?php echo $satir['id']; ?>" onclick="return confirm('Silinsin mi?')" class="btn btn-sil">SİL</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- QR MODAL -->
<div id="qrModal" class="qr-modal" onclick="if(event.target == this) this.style.display='none'">
    <div class="qr-kutusu">
        <h3 id="qrBaslik"></h3>
        <img id="qrResim" src="" width="200" height="200" style="margin:10px 0;">
        <div style="font-family:monospace; background:#eee; padding:5px;" id="qrText"></div>
        <p style="font-size:12px; color:#666;">Resme sağ tıklayıp kaydedin.</p>
        <button class="kapat-btn" onclick="document.getElementById('qrModal').style.display='none'">KAPAT</button>
    </div>
</div>

<script>
function qrGoster(id, ad) {
    var qrData = "ZIVER_" + id;
    var url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + qrData;
    document.getElementById('qrResim').src = url;
    document.getElementById('qrBaslik').innerText = ad;
    document.getElementById('qrText').innerText = qrData;
    document.getElementById('qrModal').style.display = 'flex';
}
</script>

</body>
</html>
