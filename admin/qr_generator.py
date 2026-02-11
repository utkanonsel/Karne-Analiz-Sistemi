<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$mesaj = '';
$qrDosya = '';

if (isset($_POST['uploadBtn'])) {

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== 0) {
        $mesaj = 'Dosya seçilmedi.';
    } else {

        $tmpPath = $_FILES['excel_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $mesaj = 'Sadece Excel dosyası yükleyin.';
        } else {

            $spreadsheet = IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            // A2 hücresinde deneme kodu var varsayımı
            $denemeKodu = trim($rows[2]['A'] ?? '');

            if ($denemeKodu === '') {
                $mesaj = 'Deneme kodu boş.';
            } else {

                $qrDir = __DIR__ . '/../qr';
                if (!is_dir($qrDir)) {
                    mkdir($qrDir, 0777, true);
                }

                $qr = QrCode::create($denemeKodu)->setSize(300);
                $writer = new PngWriter();
                $result = $writer->write($qr);

                $qrDosya = $qrDir . '/' . $denemeKodu . '.png';
                $result->saveToFile($qrDosya);

                $mesaj = 'Sınav yüklendi ve QR oluşturuldu.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Sınav Yükle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card p-4">

        <h4>Excel ile Sınav Yükle</h4>

        <form method="POST" enctype="multipart/form-data" class="mb-3">
            <input type="file" name="excel_file" class="form-control mb-3" required>
            <button type="submit" name="uploadBtn" class="btn btn-success">Yükle</button>
        </form>

        <?php if ($mesaj): ?>
            <div class="alert alert-info"><?= $mesaj ?></div>
        <?php endif; ?>

        <?php if ($qrDosya && file_exists($qrDosya)): ?>
            <div class="mt-3">
                <p><strong>QR Kod:</strong></p>
                <img src="../qr/<?= basename($qrDosya) ?>" alt="QR">
                <br><br>
                <a href="../qr/<?= basename($qrDosya) ?>" download class="btn btn-primary">
                    QR İndir
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
