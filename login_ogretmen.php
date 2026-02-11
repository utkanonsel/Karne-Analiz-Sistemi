<?php
session_start();
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
if (!$baglanti) { die("Bağlantı hatası: " . mysqli_connect_error()); }
mysqli_set_charset($baglanti, "utf8mb4");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $telefon = mysqli_real_escape_string($baglanti, $_POST['telefon']);
    $sifre   = mysqli_real_escape_string($baglanti, $_POST['sifre']);

    // ogretmenler tablosunda kontrol yapıyoruz
    $sorgu = mysqli_query($baglanti, "SELECT * FROM ogretmenler WHERE telefon = '$telefon' AND sifre = '$sifre'");
    
    if (mysqli_num_rows($sorgu) > 0) {
        $veri = mysqli_fetch_assoc($sorgu);
        
        // Öğretmen oturum bilgilerini tanımlıyoruz
        $_SESSION['ogretmen_id'] = $veri['id'];
        $_SESSION['ogretmen_ad'] = $veri['ad_soyad'];
        
        // BAĞLANTI: Giriş başarılıysa öğretmeni analiz sayfasına gönder
        header("Location: ogretmen_analiz.php");
        exit();
    } else {
        $mesaj = "<div style='color:white; background:#e74c3c; padding:15px; border-radius:8px; margin-bottom:15px; text-align:center;'>❌ Telefon veya şifre hatalı!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmen Giriş Paneli</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .kart { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 380px; }
        h2 { text-align: center; color: #28a745; margin-bottom: 25px; font-size: 24px; }
        input { width: 100%; padding: 14px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 14px; background: #28a745; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 16px; transition: background 0.3s; }
        button:hover { background: #218838; }
        .alt-link { text-align: center; margin-top: 20px; }
        .alt-link a { color: #666; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="kart">
    <h2>👩‍🏫 Öğretmen Girişi</h2>
    
    <?php echo $mesaj; ?>

    <form method="POST">
        <input type="text" name="telefon" placeholder="Telefon Numaranız" required>
        <input type="password" name="sifre" placeholder="Şifreniz" required>
        <button type="submit">Sisteme Giriş Yap</button>
    </form>

    <div class="alt-link">
        <a href="index.php">← Ana Sayfaya Dön</a><br><br>
        <a href="ogretmen_kayit.php" style="color: #28a745; font-weight: bold;">Yeni Öğretmen Kaydı</a>
    </div>
</div>

</body>
</html>