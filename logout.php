<?php
session_start();

// Tüm oturum değişkenlerini boşalt
$_SESSION = array();

// Eğer oturum çerezi kullanılıyorsa onu da imha et
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu tamamen sonlandır
session_destroy();

// Ana sayfaya yönlendir
header("Location: index.php");
exit();
?>