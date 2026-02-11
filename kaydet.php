<?php
// 1. Veritabanı Bağlantısı (Dışarıdan dosya çağırmak yerine buraya yazdık)
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");

if (!$baglanti) {
    die("Bağlantı hatası: " . mysqli_connect_error());
}

// Türkçe karakterler için ayar
mysqli_set_charset($baglanti, "utf8");

// 2. Formdan gelen bilgileri alıyoruz
$ad_soyad = $_POST['ad_soyad'];
$telefon = $_POST['telefon'];
$il_kodu = $_POST['il_kodu'];
$okul_id = $_POST['okul_id'];

// 3. Veritabanına kaydetme komutu
$sorgu = "INSERT INTO ogrenciler (ad_soyad, telefon, il_kodu, okul_id) 
          VALUES ('$ad_soyad', '$telefon', '$il_kodu', '$okul_id')";

// 4. İşlem sonucu kontrolü
if (mysqli_query($baglanti, $sorgu)) {
    echo "<h1>Kayıt Başarılı!</h1>";
    echo "<p>Öğrenci: <b>$ad_soyad</b> başarıyla kaydedildi.</p>";
    echo "<br><a href='index.html'>Yeni Kayıt Ekranına Dön</a>";
} else {
    echo "Veritabanı hatası: " . mysqli_error($baglanti);
}
?>