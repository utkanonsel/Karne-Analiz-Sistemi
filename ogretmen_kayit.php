<?php
// FİNAL KOD: Senin veritabanına birebir uyumlu (Ad ve Soyad Ayrı)
$baglanti = mysqli_connect("localhost", "root", "", "analiz_sistemi");
if (!$baglanti) { die("Bağlantı hatası: " . mysqli_connect_error()); }
mysqli_set_charset($baglanti, "utf8mb4");

$mesaj = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verileri AYRI AYRI alıyoruz (Senin tablonda ad ve soyad ayrı olduğu için)
    $ad      = mysqli_real_escape_string($baglanti, $_POST['ad']);
    $soyad   = mysqli_real_escape_string($baglanti, $_POST['soyad']);
    $telefon = mysqli_real_escape_string($baglanti, $_POST['telefon']);
    $sifre   = mysqli_real_escape_string($baglanti, $_POST['sifre']);
    
    // Okul adını formdan alıyoruz
    $okul_adi = mysqli_real_escape_string($baglanti, $_POST['asil_okul_adi']); 

    // Boş alan kontrolü
    if (empty($ad) || empty($soyad) || empty($telefon) || empty($sifre) || empty($okul_adi)) {
        $mesaj = "<div style='color:red; background:#f8d7da; padding:10px; border-radius:8px; margin-bottom:15px; text-align:center;'>Lütfen tüm alanları doldurun.</div>";
    } else {
        // 2. Okulun ID'sini buluyoruz (Sistemin doğru çalışması için bu şart)
        $okul_sorgu = mysqli_query($baglanti, "SELECT id FROM okullar WHERE okul_adi = '$okul_adi'");
        $okul_bul = mysqli_fetch_assoc($okul_sorgu);

        if ($okul_bul) {
            $okul_id = $okul_bul['id']; 

            // 3. Telefon numarası daha önce kayıtlı mı?
            $kontrol = mysqli_query($baglanti, "SELECT id FROM ogretmenler WHERE telefon = '$telefon'");
            if (mysqli_num_rows($kontrol) > 0) {
                $mesaj = "<div style='color:red; background:#f8d7da; padding:10px; border-radius:8px; margin-bottom:15px; text-align:center;'>❌ Bu telefon numarası zaten kayıtlı!</div>";
            } else {
                // 4. KAYIT İŞLEMİ (Ad ve Soyad'ı ayrı ayrı kaydediyoruz)
                // Veritabanında: ad, soyad, telefon, sifre, okul_id, okul_adi sütunları dolacak.
                $ekle = mysqli_query($baglanti, "INSERT INTO ogretmenler (ad, soyad, telefon, sifre, okul_id, okul_adi) VALUES ('$ad', '$soyad', '$telefon', '$sifre', '$okul_id', '$okul_adi')");
                
                if ($ekle) {
                    // Şık bir bekleme ekranı ve otomatik yönlendirme
                    echo "
                    <div style='display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif; flex-direction:column; background:#f0f2f5;'>
                        <div style='background:white; padding:40px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.1); text-align:center;'>
                            <h2 style='color:#28a745; margin:0 0 10px 0;'>✅ Kayıt Başarılı!</h2>
                            <p style='color:#666;'>Öğretmen paneline yönlendiriliyorsunuz...</p>
                        </div>
                    </div>
                    <meta http-equiv='refresh' content='2;url=login_ogretmen.php'>
                    ";
                    exit();
                } else {
                    $mesaj = "<div style='color:red;'>Veritabanı Hatası: " . mysqli_error($baglanti) . "</div>";
                }
            }
        } else {
             $mesaj = "<div style='color:red; text-align:center;'>Seçilen okul sistemde bulunamadı. Lütfen listeden tıklayarak seçin.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Öğretmen Kayıt</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .kart { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; position: relative; }
        h2 { text-align: center; color: #28a745; margin-bottom: 25px; }
        input, select { width: 100%; padding: 14px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; font-size: 16px; }
        button { width: 100%; padding: 14px; background: #28a745; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 18px; transition: background 0.3s; }
        button:hover { background: #218838; }
        .alt-link { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
        .alt-link a { color: #28a745; text-decoration: none; font-weight: bold; }

        #okul_liste_alani {
            border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; max-height: 200px; overflow-y: auto; display: none; position: absolute; background: white; width: calc(100% - 80px); max-width: 400px; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: -15px; left: 40px;
        }
        .okul-satir { padding: 12px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 15px; color: #333; }
        .okul-satir:hover { background-color: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

<div class="kart">
    <h2>👩‍🏫 Öğretmen Kayıt</h2>
    <?php echo $mesaj; ?>
    
    <form method="POST" autocomplete="off">
        <input type="text" name="ad" placeholder="Adınız" required>
        <input type="text" name="soyad" placeholder="Soyadınız" required>
        
        <input type="text" name="telefon" placeholder="Telefon (05...)" required>
        
        <select id="il" onchange="ilceGetir()" required>
            <option value="">İl Seçiniz...</option>
            <?php
            $iller = mysqli_query($baglanti, "SELECT DISTINCT il FROM okullar ORDER BY il ASC");
            while($row = mysqli_fetch_assoc($iller)){
                echo '<option value="'.$row['il'].'">'.$row['il'].'</option>';
            }
            ?>
        </select>

        <select id="ilce" required>
            <option value="">Önce İl Seçin</option>
        </select>

        <div style="position:relative;">
            <input type="text" id="okul_ara_input" placeholder="Okul adını yazın..." onkeyup="okulSüz()" autocomplete="off">
            <input type="hidden" name="asil_okul_adi" id="asil_okul_adi" required>
        </div>
        
        <div id="okul_liste_alani"></div>

        <input type="password" name="sifre" placeholder="Şifre Belirleyin" required>
        
        <button type="submit">Kayıt Ol</button>
    </form>
    
    <div class="alt-link">
        <p>Zaten hesabınız var mı? <a href="login_ogretmen.php">Giriş Yap</a></p>
        <a href="index.php" style="color:#999; font-weight:normal;">← Ana Sayfaya Dön</a>
    </div>
</div>

<script>
// Bu fonksiyonlar senin sisteminde zaten sorunsuz çalışıyordu, aynen koruduk
function ilceGetir() {
    var il = document.getElementById("il").value;
    fetch('okul_getir.php?islem=ilce_getir&il=' + encodeURIComponent(il))
        .then(res => res.text()).then(data => {
            document.getElementById("ilce").innerHTML = data;
            document.getElementById("okul_liste_alani").style.display = "none";
        });
}

function okulSüz() {
    var il = document.getElementById("il").value;
    var ilce = document.getElementById("ilce").value;
    var ara = document.getElementById("okul_ara_input").value;
    var listeAlani = document.getElementById("okul_liste_alani");

    if (ilce === "") return;

    fetch('okul_getir.php?islem=okul_getir&il=' + encodeURIComponent(il) + '&ilce=' + encodeURIComponent(ilce) + '&ara=' + encodeURIComponent(ara))
        .then(res => res.text()).then(data => {
            listeAlani.innerHTML = data;
            listeAlani.style.display = "block";
        });
}

function okulSec(ad) {
    document.getElementById("okul_ara_input").value = ad; 
    document.getElementById("asil_okul_adi").value = ad; 
    document.getElementById("okul_liste_alani").style.display = "none"; 
    document.getElementById("okul_ara_input").style.borderColor = "#28a745";
}

document.addEventListener('click', function(e) {
    if (e.target.id !== 'okul_ara_input') { document.getElementById('okul_liste_alani').style.display = 'none'; }
});
</script>

</body>
</html>