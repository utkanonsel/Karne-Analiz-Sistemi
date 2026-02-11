<?php
// Bu dosya tek başına çalışmaz, diğer sayfaların tepesine eklenir.
// Eğer bir sayfada bu dosya varsa, session_start() komutunu tekrar yazma.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// AYAR: Kaç saniye hareketsiz kalırsa atsın?
// 300 saniye = 5 Dakika
// 10 saniye = Test için (Hemen denemek istersen bunu 10 yap)
$zaman_asimi = 300; 

// Son hareket zamanı var mı ve süre dolmuş mu?
if (isset($_SESSION['son_hareket']) && (time() - $_SESSION['son_hareket'] > $zaman_asimi)) {
    
    // Kimin giriş yaptığını bulalım ki doğru kapıdan atalım
    $giris_sayfasi = "index.php"; // Varsayılan
    
    if(isset($_SESSION['ogretmen_id'])) {
        $giris_sayfasi = "login_ogretmen.php?durum=zamanasimi";
    } elseif(isset($_SESSION['ogrenci_id'])) {
        $giris_sayfasi = "login_ogrenci.php?durum=zamanasimi";
    }

    // Oturumu boşalt ve bitir
    session_unset();
    session_destroy();

    // Kullanıcıyı postala
    header("Location: " . $giris_sayfasi);
    exit();
}

// Süre dolmamışsa saati güncelle (Kronometreyi sıfırla)
$_SESSION['son_hareket'] = time();
?>