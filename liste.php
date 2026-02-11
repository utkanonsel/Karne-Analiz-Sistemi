<?php
// 1. Veritabanı Bağlantısı
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

// 2. Öğrencileri çekme sorgusu
$sorgu = "SELECT * FROM ogrenciler WHERE okul_id = 1";
$sonuclar = mysqli_query($baglanti, $sorgu);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Öğretmen Paneli - Öğrenci Listesi</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f4; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #1a73e8; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        h2 { color: #333; }
    </style>
</head>
<body>

    <h2>Kayıtlı Öğrenci Listesi</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Ad Soyad</th>
            <th>Telefon</th>
            <th>Okul ID</th>
        </tr>

        <?php
        // 3. Verileri satır satır tabloya yazdırıyoruz
        while($satir = mysqli_fetch_assoc($sonuclar)) {
            echo "<tr>";
            echo "<td>" . $satir['id'] . "</td>";
            echo "<td>" . $satir['ad_soyad'] . "</td>";
            echo "<td>" . $satir['telefon'] . "</td>";
            echo "<td>" . $satir['okul_id'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <br>
    <a href="index.html">Yeni Öğrenci Ekle</a>

</body>
</html>