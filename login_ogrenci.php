<?php
session_start();
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $telefon = mysqli_real_escape_string($baglanti, $_POST['telefon']);
    $sifre   = mysqli_real_escape_string($baglanti, $_POST['sifre']);

    $sorgu = mysqli_query($baglanti, "SELECT * FROM ogrenciler WHERE telefon = '$telefon' AND sifre = '$sifre'");
    
    if (mysqli_num_rows($sorgu) > 0) {
        $veri = mysqli_fetch_assoc($sorgu);
        $_SESSION['ogrenci_id'] = $veri['id'];
        $_SESSION['ad_soyad'] = $veri['ad_soyad'];
        
        header("Location: ogrenci_panel.php");
        exit();
    } else {
        $mesaj = "<div style='color:white; background:#e74c3c; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center;'>Hatalı bilgiler!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğrenci Girişi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .kart { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 350px; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: #1a73e8; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; }
        .alt-link { text-align: center; margin-top: 15px; font-size: 14px; }
        .alt-link a { color: #1a73e8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="kart">
        <h2 style="text-align:center; color:#1a73e8;">Öğrenci Girişi</h2>
        <?php echo $mesaj; ?>
        <form method="POST">
            <input type="text" name="telefon" placeholder="Telefon (05...)" required>
            <input type="password" name="sifre" placeholder="Şifreniz" required>
            <button type="submit">GİRİŞ YAP</button>
        </form>
        
        <div class="alt-link">
            <p>Hesabınız yok mu? <a href="ogrenci_kayit.php">Hemen Kayıt Olun</a></p>
            <a href="index.php" style="color:#999; font-size:12px;">← Ana Sayfaya Dön</a>
        </div>
    </div>
</body>
</html>