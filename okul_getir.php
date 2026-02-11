<?php
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8mb4");

$islem = isset($_GET['islem']) ? $_GET['islem'] : '';

// İLÇE GETİRME (Açılır kutu olarak kalmaya devam ediyor)
if ($islem == "ilce_getir") {
    $il = mysqli_real_escape_string($baglanti, $_GET['il']);
    $sorgu = mysqli_query($baglanti, "SELECT DISTINCT ilce FROM okullar WHERE il = '$il' ORDER BY ilce ASC");
    echo '<option value="">-- İlçe Seçin --</option>';
    while ($row = mysqli_fetch_assoc($sorgu)) {
        echo '<option value="'.htmlspecialchars($row['ilce']).'">'.htmlspecialchars($row['ilce']).'</option>';
    }
}

// OKUL GETİRME (Artık satır satır liste döndürüyor)
if ($islem == "okul_getir") {
    $il = mysqli_real_escape_string($baglanti, $_GET['il']);
    $ilce = mysqli_real_escape_string($baglanti, $_GET['ilce']);
    $ara = isset($_GET['ara']) ? mysqli_real_escape_string($baglanti, $_GET['ara']) : '';

    $sql = "SELECT okul_adi FROM okullar WHERE il = '$il' AND ilce = '$ilce'";
    if ($ara != '') {
        $sql .= " AND okul_adi LIKE '%$ara%'";
    }
    $sql .= " ORDER BY okul_adi ASC LIMIT 50"; // Çok fazla sonuçla tarayıcıyı yormayalım

    $sorgu = mysqli_query($baglanti, $sql);
    
    if (mysqli_num_rows($sorgu) > 0) {
        while ($row = mysqli_fetch_assoc($sorgu)) {
            $okul = htmlspecialchars($row['okul_adi']);
            // Her bir okul ismine tıklandığında JavaScript fonksiyonumuzu tetikliyoruz
            echo "<div class='okul-satir' onclick='okulSec(\"$okul\")'>$okul</div>";
        }
    } else {
        echo "<div style='padding:10px; color:#e74c3c;'>Eşleşen okul bulunamadı.</div>";
    }
}
?>