<?php
// admin/admin_sinav_yukle.php
require __DIR__ . '/oturum_kontrol.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/sinav_kaydet.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$mesaj = '';
$qrDosya = '';

if (isset($_POST['uploadBtn'])) {

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== 0) {
        $mesaj = 'Dosya secilmedi.';
    } else {

        $tmpPath = $_FILES['excel_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $mesaj = 'Sadece Excel (.xlsx, .xls) yukleyin.';
        } else {

            try {
                $spreadsheet = IOFactory::load($tmpPath);
                $sheet = $spreadsheet->getActiveSheet();

                $denemeKodu = trim((string)$sheet->getCell('B1')->getValue());
                $denemeAdi  = trim((string)$sheet->getCell('B2')->getValue());
                $ders       = trim((string)$sheet->getCell('B3')->getValue());
                $tarihRaw   = $sheet->getCell('B4')->getValue();
                $sinif      = trim((string)$sheet->getCell('B5')->getValue());

                $sinavTarihi = '';
                if ($tarihRaw instanceof DateTime) {
                    $sinavTarihi = $tarihRaw->format('Y-m-d');
                } else {
                    $t = trim((string)$tarihRaw);
                    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $t)) {
                        [$gg,$aa,$yy] = explode('.', $t);
                        $sinavTarihi = $yy . '-' . $aa . '-' . $gg;
                    } else {
                        $sinavTarihi = $t;
                    }
                }

                if ($denemeKodu === '' || $denemeAdi === '' || $ders === '' || $sinavTarihi === '' || $sinif === '') {
                    $mesaj = 'Excel ust bilgileri eksik. (B1-B5 kontrol et)';
                } else {

                    $sorular = [];
                    $row = 7;

                    while (true) {
                        $soruNo = trim((string)$sheet->getCell("A{$row}")->getValue());
                        $kk     = trim((string)$sheet->getCell("B{$row}")->getValue());
                        $ka     = trim((string)$sheet->getCell("C{$row}")->getValue());
                        $cv     = trim((string)$sheet->getCell("D{$row}")->getValue());

                        if ($soruNo === '' && $kk === '' && $ka === '' && $cv === '') break;

                        $sorular[] = [
                            'no' => $soruNo,
                            'kazanim_kodu' => $kk,
                            'kazanim_adi' => $ka,
                            'cevap' => $cv
                        ];

                        $row++;
                        if ($row > 1000) { $mesaj = 'Excel cok uzun gorunuyor (1000+ satir).'; break; }
                    }

                    if ($mesaj === '') {
                        $data = [
                            'deneme_kodu'  => $denemeKodu,
                            'deneme_adi'   => $denemeAdi,
                            'ders'         => $ders,
                            'sinav_tarihi' => $sinavTarihi,
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

            } catch (Exception $e) {
                $mesaj = 'Excel okunamadi: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><title>Excel ile Sinav Yukle</title></head>
<body>
<h2>Excel ile Sinav Yukle</h2>

<form method="post" enctype="multipart/form-data">
  <input type="file" name="excel_file" required>
  <button type="submit" name="uploadBtn">Yukle</button>
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
