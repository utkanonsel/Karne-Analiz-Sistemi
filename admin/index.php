<?php
// admin/index.php
require __DIR__ . '/oturum_kontrol.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Admin Panel</title>
<style>
body{font-family:Arial;background:#f4f4f4}
.wrap{max-width:900px;margin:25px auto;background:#fff;border:1px solid #ddd;padding:16px}
a.btn{display:inline-block;padding:10px 14px;background:#1976d2;color:#fff;text-decoration:none;margin:6px 6px 0 0}
a.btn2{background:#2e7d32}
a.btn3{background:#6d4c41}
a.btn4{background:#c62828}
.small{color:#666;font-size:12px;margin-top:10px}
</style>
</head>
<body>
<div class="wrap">
  <h2>Admin Panel</h2>

  <a class="btn btn2" href="admin_sinav_ekle.php">Manuel Sinav Tanimla</a>
  <a class="btn" href="admin_sinav_yukle.php">Excel ile Sinav Yukle</a>
  <a class="btn btn3" href="admin_sinav_listesi.php">Sinav Listesi</a>
  <a class="btn" href="admin_kullanici_yonetimi.php">Kullanici Yonetimi</a>
  <a class="btn btn4" href="cikis.php">Cikis</a>

  <div class="small">
    Giris yapan yonetici: <b><?= htmlspecialchars($_SESSION['yonetici_kullanici'] ?? '') ?></b>
  </div>
</div>
</body>
</html>
