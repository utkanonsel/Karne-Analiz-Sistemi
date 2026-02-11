<?php
session_start();
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

if (!isset($_SESSION['ogrenci_id'])) {
    header("Location: login.php");
    exit();
}

$sinav_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sorgu = mysqli_query($baglanti, "SELECT * FROM denemeler WHERE id = $sinav_id");
$sinav = mysqli_fetch_assoc($sorgu);

if (!$sinav) { die("Hata: Sınav bulunamadı."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cevaplar = "";
    for ($i = 1; $i <= $sinav['toplam_soru']; $i++) {
        $gelen = isset($_POST['soru_'.$i]) ? $_POST['soru_'.$i] : " ";
        $cevaplar .= $gelen;
    }
    $ogr_id = $_SESSION['ogrenci_id'];
    $ekle = mysqli_query($baglanti, "INSERT INTO optik_sonuclar (ogrenci_id, deneme_id, cevaplar) VALUES ('$ogr_id', '$sinav_id', '$cevaplar')");
    $son_id = mysqli_insert_id($baglanti);
    
    if ($ekle) {
        header("Location: analiz_karne.php?id=$son_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Optik Giriş | <?php echo htmlspecialchars($sinav['deneme_adi']); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; padding: 20px; display: flex; justify-content: center; }
        .optik-box { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .ust-baslik { text-align: center; border-bottom: 3px solid #1a73e8; margin-bottom: 25px; padding-bottom: 15px; }
        .satir { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .no { width: 45px; font-weight: bold; color: #1e293b; }
        .siklar { flex-grow: 1; display: flex; justify-content: space-around; align-items: center; }
        .siklar label { cursor: pointer; display: flex; flex-direction: column; align-items: center; font-size: 14px; font-weight: bold; color: #475569; }
        .siklar input { width: 24px; height: 24px; cursor: pointer; }
        .btn-gonder { width: 100%; padding: 16px; background: #1a73e8; color: white; border: none; border-radius: 12px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 30px; }
        .btn-gonder:hover { background: #1557b0; }
    </style>
</head>
<body>
<div class="optik-box">
    <div class="ust-baslik">
        <h2 style="margin:0;"><?php echo htmlspecialchars($sinav['deneme_adi']); ?></h2>
        <p style="margin:5px 0 0 0; color:#64748b;">İşaretlediğiniz şıkka tekrar basarak seçimi kaldırabilirsiniz.</p>
    </div>
    <form method="POST">
        <?php for($i=1; $i<=$sinav['toplam_soru']; $i++): ?>
        <div class="satir">
            <div class="no"><?php echo $i; ?>.</div>
            <div class="siklar">
                <?php foreach(['A','B','C','D','E'] as $harf): ?>
                    <label>
                        <input type="radio" name="soru_<?php echo $i; ?>" value="<?php echo $harf; ?>" class="toggle-radio">
                        <?php echo $harf; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endfor; ?>
        <button type="submit" class="btn-gonder">Optiği Gönder ve Analiz Et</button>
    </form>
</div>

<script>
// Radio butonların seçimini tekrar tıklandığında kaldıran script
document.querySelectorAll('.toggle-radio').forEach(radio => {
    radio.onclick = function() {
        if (this.previousValue === 'true') {
            this.checked = false;
            this.previousValue = 'false';
        } else {
            // Aynı soru grubundaki diğer radio'ların previousValue değerlerini sıfırla
            document.querySelectorAll('input[name="' + this.name + '"]').forEach(r => r.previousValue = 'false');
            this.previousValue = 'true';
        }
    };
});
</script>

</body>
</html>
