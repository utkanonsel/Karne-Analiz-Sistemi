<?php
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");

if (!$baglanti) { die("Bağlantı Hatası"); }

echo "<h3>OPTİK SONUÇLAR TABLOSU SÜTUNLARI:</h3>";
$sorgu = mysqli_query($baglanti, "DESCRIBE optik_sonuclar");

while($satir = mysqli_fetch_assoc($sorgu)) {
    echo $satir['Field'] . " (" . $satir['Type'] . ")<br>";
}
?>