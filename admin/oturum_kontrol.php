<?php
// admin/oturum_kontrol.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basit timeout (isteğe bağlı): 2 saat
$timeoutSeconds = 2 * 60 * 60;

if (isset($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > $timeoutSeconds) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Yetki kontrolü
if (empty($_SESSION['yonetici_id'])) {
    header("Location: login.php");
    exit;
}
