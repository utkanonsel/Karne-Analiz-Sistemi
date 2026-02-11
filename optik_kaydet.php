<?php
session_start();
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

if ($_POST) {
    $ogrenci_id = (int)$_POST['ogrenci_id'];
    $deneme_id = (int)$_POST['deneme_id'];
    $cevaplar = mysqli_real_escape_string($baglanti, $_POST['cevaplar']);
    $durum = isset($_POST['bitir']) ? 1 : 0; 

    // Taslak olan (durum=0) kaydı ara
    $kontrol = mysqli_query($baglanti, "SELECT id FROM optik_sonuclar WHERE ogrenci_id = '$ogrenci_id' AND deneme_id = '$deneme_id' AND durum = 0");

    if (mysqli_num_rows($kontrol) > 0) {
        // Taslak kaydı güncelle
        $sorgu = "UPDATE optik_sonuclar SET cevaplar = '$cevaplar', durum = '$durum' 
                  WHERE ogrenci_id = '$ogrenci_id' AND deneme_id = '$deneme_id' AND durum = 0";
    } else {
        // Yeni bir taslak oluştur
        $sorgu = "INSERT INTO optik_sonuclar (ogrenci_id, deneme_id, optik_no, cevaplar, durum) 
                  VALUES ('$ogrenci_id', '$deneme_id', 'OPT-".time()."', '$cevaplar', '$durum')";
    }
    mysqli_query($baglanti, $sorgu);
}
?>