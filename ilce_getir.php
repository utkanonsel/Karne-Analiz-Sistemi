<?php
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
mysqli_set_charset($baglanti, "utf8");

if(isset($_POST['il_id'])) {
    $il_id = (int)$_POST['il_id'];
    $sorgu = mysqli_query($baglanti, "SELECT * FROM ilceler WHERE il_id = $il_id ORDER BY ilce_adi ASC");
    echo '<option value="">İlçe Seçin</option>';
    while($row = mysqli_fetch_assoc($sorgu)) {
        echo '<option value="'.$row['id'].'">'.$row['ilce_adi'].'</option>';
    }
}
?>