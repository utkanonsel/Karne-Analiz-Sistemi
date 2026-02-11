<?php
// admin/admin_sinav_listesi.php
require __DIR__ . '/oturum_kontrol.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../db.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$mesaj = '';
$hata  = '';

function getKazanimCols(PDO $db): array
{
    $cols = [];
    $q = $db->query("SHOW COLUMNS FROM sinav_sorular");
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cols[] = $row['Field'];
    }

    $kodu = in_array('kazanim_kodu', $cols, true) ? 'kazanim_kodu'
          : (in_array('kazanım_kodu', $cols, true) ? 'kazanım_kodu' : null);

    $adi  = in_array('kazanim_adi', $cols, true) ? 'kazanim_adi'
          : (in_array('kazanım_adi', $cols, true) ? 'kazanım_adi' : null);

    return [$kodu, $adi];
}

list($KAZANIM_KODU_COL, $KAZANIM_ADI_COL) = getKazanimCols($db);
if (!$KAZANIM_KODU_COL || !$KAZANIM_ADI_COL) {
    $hata = "sinav_sorular tablosunda kazanim kolonlari bulunamadi.";
}

// QR indir
if (isset($_GET['qr'])) {
    $id = (int)$_GET['qr'];

    $q = $db->prepare("SELECT deneme_kodu FROM sinavlar WHERE id=? LIMIT 1");
    $q->execute([$id]);
    $row = $q->fetch(PDO::FETCH_ASSOC);

    if (!$row) { http_response_code(404); echo "Sinav bulunamadi."; exit; }

    $denemeKodu = $row['deneme_kodu'];
    $qrDir  = __DIR__ . '/../qr';
    $qrPath = $qrDir . '/' . $denemeKodu . '.png';
    if (!is_dir($qrDir)) mkdir($qrDir, 0777, true);

    if (!is_file($qrPath)) {
        $qr = new QrCode($denemeKodu);
        $writer = new PngWriter();
        $writer->write($qr)->saveToFile($qrPath);
    }

    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="'.$denemeKodu.'.png"');
    readfile($qrPath);
    exit;
}

// Sil
if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    try {
        $db->prepare("DELETE FROM sinavlar WHERE id=?")->execute([$id]);
        header("Location: admin_sinav_listesi.php?mesaj=silindi");
        exit;
    } catch (Exception $e) {
        $hata = "Silme hatasi: " . $e->getMessage();
    }
}

// Güncelle
if (isset($_POST['kaydet']) && !$hata) {
    $sinav_id   = (int)($_POST['sinav_id'] ?? 0);
    $deneme_adi = trim($_POST['deneme_adi'] ?? '');

    $soru_id      = $_POST['soru_id'] ?? [];
    $kazanim_kodu = $_POST['kazanim_kodu'] ?? [];
    $kazanim_adi  = $_POST['kazanim_adi'] ?? [];
    $cevap        = $_POST['cevap'] ?? [];

    if ($sinav_id <= 0) $hata = "Gecersiz sinav.";
    elseif ($deneme_adi === '') $hata = "Deneme adi bos olamaz.";
    else {
        try {
            $db->beginTransaction();

            $db->prepare("UPDATE sinavlar SET deneme_adi=? WHERE id=?")
               ->execute([$deneme_adi, $sinav_id]);

            $sql = "
                UPDATE sinav_sorular
                SET `$KAZANIM_KODU_COL` = ?,
                    `$KAZANIM_ADI_COL`  = ?,
                    cevap              = ?
                WHERE id = ? AND sinav_id = ?
            ";
            $u = $db->prepare($sql);

            for ($i=0; $i<count($soru_id); $i++) {
                $sid = (int)$soru_id[$i];
                $kk  = trim($kazanim_kodu[$i] ?? '');
                $ka  = trim($kazanim_adi[$i] ?? '');
                $cv  = strtoupper(trim($cevap[$i] ?? ''));

                if ($sid <= 0 || $kk === '' || $ka === '' || $cv === '') {
                    throw new Exception("Bos alan birakma (kazanım/cevap).");
                }
                $u->execute([$kk, $ka, $cv, $sid, $sinav_id]);
            }

            $db->commit();
            header("Location: admin_sinav_listesi.php?duzenle=".$sinav_id."&mesaj=guncellendi");
            exit;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $hata = "Guncelleme hatasi: " . $e->getMessage();
        }
    }
}

if (isset($_GET['mesaj']) && $_GET['mesaj']==='silindi') $mesaj="Sinav silindi.";
if (isset($_GET['mesaj']) && $_GET['mesaj']==='guncellendi') $mesaj="Sinav guncellendi.";

$duzenle_id = isset($_GET['duzenle']) ? (int)$_GET['duzenle'] : 0;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Admin Sinav Listesi</title>
<style>
body{font-family:Arial;background:#f4f4f4}
.wrap{width:1100px;margin:20px auto;background:#fff;padding:15px;border:1px solid #ddd}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ddd;padding:8px}
th{background:#eee}
input,textarea{width:100%;box-sizing:border-box;padding:6px}
textarea{height:50px}
.btn{padding:8px 12px;background:#1976d2;color:#fff;border:none;cursor:pointer}
.msg{padding:10px;background:#e8ffe8;border:1px solid #b6f0b6;margin-bottom:12px}
.err{padding:10px;background:#ffe8e8;border:1px solid #f0b6b6;margin-bottom:12px}
</style>
</head>
<body>
<div class="wrap">
<h2>Sinav Listesi</h2>
<?php if($mesaj): ?><div class="msg"><?=h($mesaj)?></div><?php endif; ?>
<?php if($hata): ?><div class="err"><?=h($hata)?></div><?php endif; ?>

<?php if($duzenle_id): ?>
<?php
$q = $db->prepare("SELECT * FROM sinavlar WHERE id=? LIMIT 1");
$q->execute([$duzenle_id]);
$sinav = $q->fetch(PDO::FETCH_ASSOC);

if(!$sinav){ echo '<div class="err">Sinav bulunamadi.</div>'; }
else {
  $sqlSorular = "
    SELECT id, sinav_id, soru_no,
           `$KAZANIM_KODU_COL` AS kazanim_kodu,
           `$KAZANIM_ADI_COL`  AS kazanim_adi,
           cevap
    FROM sinav_sorular
    WHERE sinav_id=?
    ORDER BY soru_no ASC
  ";
  $q2 = $db->prepare($sqlSorular);
  $q2->execute([$duzenle_id]);
  $sorular = $q2->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Sinav Duzenle</h3>

<form method="post">
<input type="hidden" name="sinav_id" value="<?= (int)$sinav['id'] ?>">

<table>
<tr><td style="width:200px;">Deneme Adi</td>
<td><input type="text" name="deneme_adi" value="<?= h($sinav['deneme_adi']) ?>"></td></tr>
</table>

<br>

<table>
<tr><th style="width:80px;">Soru No</th><th style="width:220px;">Kazanim Kodu</th><th>Kazanim Adi</th><th style="width:90px;">Cevap</th></tr>
<?php foreach($sorular as $s): ?>
<tr>
  <td><b><?= (int)$s['soru_no'] ?></b></td>
  <td>
    <input type="hidden" name="soru_id[]" value="<?= (int)$s['id'] ?>">
    <input type="text" name="kazanim_kodu[]" value="<?= h($s['kazanim_kodu']) ?>">
  </td>
  <td><textarea name="kazanim_adi[]"><?= h($s['kazanim_adi']) ?></textarea></td>
  <td><input type="text" name="cevap[]" value="<?= h($s['cevap']) ?>"></td>
</tr>
<?php endforeach; ?>
</table>

<br>
<button class="btn" type="submit" name="kaydet">Kaydet</button>
<a style="margin-left:12px;" href="admin_sinav_listesi.php">Listeye Don</a>
<a style="margin-left:12px;" href="admin_sinav_listesi.php?qr=<?= (int)$sinav['id'] ?>">QR indir</a>
</form>

<?php } ?>
<?php else: ?>
<?php
$sinavlar = $db->query("SELECT * FROM sinavlar ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<table>
<tr><th>ID</th><th>Deneme Kodu</th><th>Deneme Adi</th><th>Ders</th><th>Sinif</th><th>Tarih</th><th>Islem</th></tr>
<?php foreach($sinavlar as $s): ?>
<tr>
  <td><?= (int)$s['id'] ?></td>
  <td><b><?= h($s['deneme_kodu']) ?></b></td>
  <td><?= h($s['deneme_adi']) ?></td>
  <td><?= h($s['ders']) ?></td>
  <td><?= h($s['sinif']) ?></td>
  <td><?= h($s['sinav_tarihi']) ?></td>
  <td>
    <a href="?duzenle=<?= (int)$s['id'] ?>">Duzenle</a> |
    <a href="?qr=<?= (int)$s['id'] ?>">QR</a> |
    <a href="?sil=<?= (int)$s['id'] ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<p><a href="index.php">Admin ana sayfa</a></p>
<?php endif; ?>

</div>
</body>
</html>
