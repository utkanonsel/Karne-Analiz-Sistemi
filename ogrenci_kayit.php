<?php
// PROFESYONEL SİSTEM: ID BAZLI ÖĞRENCİ KAYDI
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
if (!$baglanti) { die("Veritabanı bağlantı hatası: " . mysqli_connect_error()); }
mysqli_set_charset($baglanti, "utf8mb4");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad      = mysqli_real_escape_string($baglanti, $_POST['ad']);
    $soyad   = mysqli_real_escape_string($baglanti, $_POST['soyad']);
    $telefon = mysqli_real_escape_string($baglanti, $_POST['telefon']);
    $sifre   = mysqli_real_escape_string($baglanti, $_POST['sifre']);
    
    // Formdan gelen okul ADINI alıyoruz
    $okul_adi = mysqli_real_escape_string($baglanti, $_POST['asil_okul_adi']);

    // 1. Önce bu okulun ID'sini veritabanından buluyoruz (En sağlam yöntem)
    $okul_sorgu = mysqli_query($baglanti, "SELECT id FROM okullar WHERE okul_adi = '$okul_adi'");
    $okul_bul   = mysqli_fetch_assoc($okul_sorgu);

    if ($okul_bul) {
        $okul_id = $okul_bul['id']; // Okulun kimlik numarasını bulduk!

        // 2. Telefon tekrarı var mı?
        $kontrol = mysqli_query($baglanti, "SELECT id FROM ogrenciler WHERE telefon = '$telefon'");
        if (mysqli_num_rows($kontrol) > 0) {
            $mesaj = "<div style='color:red; background:#f8d7da; padding:10px; border-radius:8px; margin-bottom:15px; text-align:center;'>Bu telefon numarası zaten kayıtlı!</div>";
        } else {
            // 3. Kaydı yap (okul_id sütununa dikkat!)
            // Not: İleride lazım olur diye okul_adi sütununa da yazıyoruz ama sistem ID üzerinden çalışacak.
            $ekle = mysqli_query($baglanti, "INSERT INTO ogrenciler (ad, soyad, telefon, sifre, okul_id, okul_adi) VALUES ('$ad', '$soyad', '$telefon', '$sifre', '$okul_id', '$okul_adi')");
            
           if ($ekle) {
    // Alert yerine şık bir bekleme ekranı
    echo "
    <div style='display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif; flex-direction:column; background:#f0f2f5;'>
        <div style='background:white; padding:40px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); text-align:center;'>
            <h2 style='color:#28a745; margin:0 0 10px 0;'>✅ Kayıt Başarılı!</h2>
            <p style='color:#666;'>Giriş sayfasına yönlendiriliyorsunuz, lütfen bekleyin...</p>
        </div>
    </div>
    <meta http-equiv='refresh' content='2;url=login_ogrenci.php'>
    ";
    exit();
            } else {
                $mesaj = "Hata: " . mysqli_error($baglanti);
            }
        }
    } else {
        $mesaj = "<div style='color:red; text-align:center;'>Seçilen okul sistemde bulunamadı. Lütfen listeden tıklayarak seçin.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğrenci Kayıt</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
        .kart { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        input, select { width: 100%; padding: 14px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: #1a73e8; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; }
        
        /* Arama Listesi Stili */
        #okul_liste_alani {
            border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; display: none; position: absolute; background: white; width: calc(100% - 80px); max-width: 400px; z-index: 1000; margin-top: -15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .okul-satir { padding: 12px; cursor: pointer; border-bottom: 1px solid #eee; }
        .okul-satir:hover { background-color: #e3f2fd; }
    </style>
</head>
<body>

<div class="kart">
    <h2 style="text-align:center; color:#1a73e8;">Öğrenci Kayıt</h2>
    <?php echo $mesaj; ?>
    
    <form method="POST" autocomplete="off">
        <input type="text" name="ad" placeholder="Adınız" required>
        <input type="text" name="soyad" placeholder="Soyadınız" required>
        <input type="text" name="telefon" placeholder="Telefon (05...)" required>
        
        <select id="il" onchange="ilceGetir()" required>
            <option value="">İl Seçiniz...</option>
            <?php
            $iller = mysqli_query($baglanti, "SELECT DISTINCT il FROM okullar ORDER BY il ASC");
            while($row = mysqli_fetch_assoc($iller)){ echo '<option value="'.$row['il'].'">'.$row['il'].'</option>'; }
            ?>
        </select>

        <select id="ilce" required>
            <option value="">Önce İl Seçin</option>
        </select>

        <div style="position:relative;">
            <input type="text" id="okul_ara_input" placeholder="Okul Ara..." onkeyup="okulSüz()" autocomplete="off">
            <input type="hidden" name="asil_okul_adi" id="asil_okul_adi" required>
            <div id="okul_liste_alani"></div>
        </div>

        <input type="password" name="sifre" placeholder="Şifre Belirleyin" required>
        <button type="submit">Kayıt Ol</button>
    </form>
    
    <p style="text-align:center; margin-top:20px;"><a href="login_ogrenci.php" style="text-decoration:none; color:#1a73e8;">Giriş Yap</a> | <a href="index.php" style="text-decoration:none; color:#999;">Ana Sayfa</a></p>
</div>

<script>
function ilceGetir() {
    var il = document.getElementById("il").value;
    fetch('okul_getir.php?islem=ilce_getir&il=' + encodeURIComponent(il))
        .then(res => res.text()).then(data => { document.getElementById("ilce").innerHTML = data; });
}

function okulSüz() {
    var il = document.getElementById("il").value;
    var ilce = document.getElementById("ilce").value;
    var ara = document.getElementById("okul_ara_input").value;
    var liste = document.getElementById("okul_liste_alani");

    if (ilce === "") return;

    fetch('okul_getir.php?islem=okul_getir&il=' + encodeURIComponent(il) + '&ilce=' + encodeURIComponent(ilce) + '&ara=' + encodeURIComponent(ara))
        .then(res => res.text()).then(data => {
            liste.innerHTML = data;
            liste.style.display = "block";
        });
}

function okulSec(ad) {
    document.getElementById("okul_ara_input").value = ad;
    document.getElementById("asil_okul_adi").value = ad;
    document.getElementById("okul_liste_alani").style.display = "none";
}

document.addEventListener('click', function(e) {
    if (e.target.id !== 'okul_ara_input') { document.getElementById('okul_liste_alani').style.display = 'none'; }
});
</script>

</body>
</html>