<?php
// admin/login.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zaten giriş yaptıysa admin ana sayfaya
if (!empty($_SESSION['yonetici_id'])) {
    header("Location: index.php");
    exit;
}

$hata = '';

if (isset($_POST['giris'])) {
    $kullanici = trim($_POST['kullanici'] ?? '');
    $sifre     = trim($_POST['sifre'] ?? '');

    if ($kullanici === '' || $sifre === '') {
        $hata = "Kullanici adi ve sifre zorunlu.";
    } else {
        // yoneticiler tablosu: (id, kullanici_adi, sifre, ad_soyad vs) varsayımı
        // Şifreler plaintext ise: WHERE sifre = ?
        // Hash ise: password_verify ile kontrol et.
        $q = $db->prepare("SELECT id, kullanici_adi, sifre FROM yoneticiler WHERE kullanici_adi = ? LIMIT 1");
        $q->execute([$kullanici]);
        $row = $q->fetch();

        if (!$row) {
            $hata = "Hatali giris.";
        } else {
            $dbSifre = (string)$row['sifre'];

            $ok = false;

            // Hash ise
            if (preg_match('/^\$2y\$/', $dbSifre) || preg_match('/^\$argon2/i', $dbSifre)) {
                $ok = password_verify($sifre, $dbSifre);
            } else {
                // Plaintext ise
                $ok = hash_equals($dbSifre, $sifre);
            }

            if (!$ok) {
                $hata = "Hatali giris.";
            } else {
                $_SESSION['yonetici_id'] = (int)$row['id'];
                $_SESSION['yonetici_kullanici'] = (string)$row['kullanici_adi'];
                $_SESSION['last_activity'] = time();

                header("Location: index.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Admin Giris</title>
<style>
body{font-family:Arial;background:#f4f4f4}
.box{max-width:420px;margin:60px auto;background:#fff;border:1px solid #ddd;padding:18px}
input{width:100%;padding:10px;box-sizing:border-box;margin:6px 0}
button{width:100%;padding:10px;background:#1976d2;color:#fff;border:none;cursor:pointer}
.err{background:#ffe8e8;border:1px solid #f0b6b6;padding:10px;margin-bottom:10px}
.small{color:#666;font-size:12px}
</style>
</head>
<body>
<div class="box">
  <h2>Yonetici Giris</h2>
  <?php if ($hata): ?><div class="err"><?= htmlspecialchars($hata) ?></div><?php endif; ?>

  <?php if (isset($_GET['timeout'])): ?>
    <div class="err">Oturum suresi doldu. Tekrar giris yap.</div>
  <?php endif; ?>

  <form method="post">
    <input type="text" name="kullanici" placeholder="Kullanici adi">
    <input type="password" name="sifre" placeholder="Sifre">
    <button type="submit" name="giris">Giris</button>
  </form>
  <p class="small">Bu ekran yoneticiler tablosuna bakar.</p>
</div>
</body>
</html>
