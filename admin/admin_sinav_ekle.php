<?php
// admin/admin_sinav_ekle.php
require __DIR__ . '/oturum_kontrol.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/sinav_kaydet.php';

$mesaj = '';
$qrDosya = '';
$hatalıSatırlar = [];

$denemeKodu = $_POST['deneme_kodu'] ?? '';
$denemeAdi  = $_POST['deneme_adi'] ?? '';
$ders       = $_POST['ders'] ?? '';
$tarih      = $_POST['tarih'] ?? '';
$sinif      = $_POST['sinif'] ?? '';

$soru_no      = $_POST['soru_no'] ?? [];
$kazanim_kodu = $_POST['kazanim_kodu'] ?? [];
$kazanim_adi  = $_POST['kazanim_adi'] ?? [];
$cevap        = $_POST['cevap'] ?? [];

if (isset($_POST['kaydet'])) {

    if (trim($denemeKodu) === '' || trim($denemeAdi) === '' || trim($ders) === '' || trim($sinif) === '' || trim($tarih) === '') {
        $mesaj = 'Ust bilgiler eksik.';
    } else {

        $sorular = [];
        $gecerliSoruSayisi = 0;

        for ($i = 0; $i < 200; $i++) {
            $no = trim($soru_no[$i] ?? '');
            $kk = trim($kazanim_kodu[$i] ?? '');
            $ka = trim($kazanim_adi[$i] ?? '');
            $cv = trim($cevap[$i] ?? '');

            $satirDoluMu = ($no !== '' || $kk !== '' || $ka !== '' || $cv !== '');
            if (!$satirDoluMu) {
                $sorular[] = ['no'=>'','kazanim_kodu'=>'','kazanim_adi'=>'','cevap'=>''];
                continue;
            }

            if ($no === '' || $kk === '' || $ka === '' || $cv === '') {
                $hatalıSatırlar[] = $i;
            } else {
                $gecerliSoruSayisi++;
            }

            $sorular[] = ['no'=>$no,'kazanim_kodu'=>$kk,'kazanim_adi'=>$ka,'cevap'=>$cv];
        }

        if ($gecerliSoruSayisi === 0) {
            $mesaj = 'En az 1 soru girilmeli.';
        } elseif (!empty($hatalıSatırlar)) {
            $mesaj = 'Kirmizi satirlarda eksik bilgi var.';
        } else {
            $data = [
                'deneme_kodu'  => $denemeKodu,
                'deneme_adi'   => $denemeAdi,
                'ders'         => $ders,
                'sinav_tarihi' => $tarih,
                'sinif'        => $sinif,
                'sorular'      => $sorular
            ];

            $sonuc = sinavKaydet($data);

            if (!$sonuc['ok']) {
                $mesaj = $sonuc['mesaj'];
            } else {
                $mesaj = 'Sinav kaydedildi. QR uretildi.';
                $qrDosya = $sonuc['qr'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Manuel Sinav Ekle</title>
<style>
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #ccc;padding:5px}
.hata{background:#f8caca}
input[type="text"],input[type="number"],textarea{width:100%;box-sizing:border-box}
textarea{height:46px}
</style>
</head>
<body>

<h2>Manuel Sinav Ekle</h2>

<form method="post">
Deneme Kodu: <input type="text" name="deneme_kodu" value="<?= htmlspecialchars($denemeKodu) ?>"><br><br>
Deneme Adi: <input type="text" name="deneme_adi" value="<?= htmlspecialchars($denemeAdi) ?>"><br><br>
Ders: <input type="text" name="ders" value="<?= htmlspecialchars($ders) ?>"><br><br>
Deneme Tarihi: <input type="date" name="tarih" value="<?= htmlspecialchars($tarih) ?>"><br><br>
Sinif: <input type="text" name="sinif" value="<?= htmlspecialchars($sinif) ?>"><br><br>

<table>
<tr>
  <th>Soru No</th><th>Kazanim Kodu</th><th>Kazanim Adi</th><th>Cevap</th>
</tr>

<?php for ($i=0; $i<200; $i++): ?>
<tr class="<?= in_array($i, $hatalıSatırlar) ? 'hata' : '' ?>">
  <td><input type="number" name="soru_no[]" value="<?= htmlspecialchars($soru_no[$i] ?? '') ?>"></td>
  <td><input type="text" name="kazanim_kodu[]" value="<?= htmlspecialchars($kazanim_kodu[$i] ?? '') ?>"></td>
  <td><textarea name="kazanim_adi[]"><?= htmlspecialchars($kazanim_adi[$i] ?? '') ?></textarea></td>
  <td><input type="text" name="cevap[]" value="<?= htmlspecialchars($cevap[$i] ?? '') ?>"></td>
</tr>
<?php endfor; ?>

</table>

<br>
<button type="submit" name="kaydet">Sinavi Yukle</button>
</form>

<p><?= htmlspecialchars($mesaj) ?></p>

<?php if ($qrDosya): ?>
<h3>QR</h3>
<img src="../qr/<?= htmlspecialchars($qrDosya) ?>" width="200"><br>
<a href="../qr/<?= htmlspecialchars($qrDosya) ?>" download>QR indir</a>
<?php endif; ?>

<p><a href="index.php">Admin ana sayfa</a></p>

</body>
</html>
