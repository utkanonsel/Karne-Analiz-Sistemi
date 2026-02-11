<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['ogrenci_id'])) { die("Oturum kapalı."); }
// ... (Veritabanı bağlantıları aynı) ...
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

if (isset($_FILES['optik_foto'])) {
    // ... (Dosya yükleme ve Python çalıştırma kodları aynı) ...
    $dosya = $_FILES['optik_foto'];
    $hedef_yol = "uploads/" . time() . ".jpg";
    if (!is_dir("uploads")) mkdir("uploads");
    move_uploaded_file($dosya['tmp_name'], $hedef_yol);
    
    // ZIVER MOTORUNU ÇAĞIR
    $komut = "python ziver_engine.py " . escapeshellarg($hedef_yol);
    $cikti = shell_exec($komut);
    $sonuc = json_decode($cikti, true);
    
    // HTML ÇIKTISI BAŞLIYOR (Mobil Uyumlu)
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<style>body{font-family:sans-serif; background:#f0fdf4; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; text-align:center} .card{background:white; padding:40px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1); width:90%; max-width:400px;} .btn{background:#16a34a; color:white; padding:15px 30px; border-radius:50px; text-decoration:none; font-weight:bold; display:inline-block; margin-top:20px;}</style>';
    
    if ($sonuc && $sonuc['success']) {
        // ... (Puan hesaplama ve Kayıt işlemleri buraya) ...
        // (SQL Kayıt kodlarını buraya aynen koy)
        
        // BAŞARI EKRANI
        echo '<div class="card">';
        echo '<div style="font-size:50px;">🎉</div>';
        echo '<h1>Tebrikler!</h1>';
        echo '<p>Sınavınız başarıyla okundu.</p>';
        echo '<a href="ogrenci_panel.php" class="btn">Panele Dön</a>';
        echo '</div>';
    } else {
        // HATA EKRANI
        echo '<style>body{background:#fef2f2} .btn{background:#dc2626}</style>';
        echo '<div class="card">';
        echo '<div style="font-size:50px;">⚠️</div>';
        echo '<h1>Okunamadı</h1>';
        echo '<p>Lütfen kağıdı düz bir zeminde, iyi ışıkta tekrar çekin.</p>';
        echo '<a href="ogrenci_sinav_okut.php?id='.$_POST['sinav_id'].'" class="btn">Tekrar Dene</a>';
        echo '</div>';
    }
}
?>