<?php
// C:\xampp\htdocs\analiz\db.php

$host = "127.0.0.1";
$dbname = "analiz_sistemi";
$user = "root";
$pass = ""; // XAMPP default genelde boş

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die("DB bağlantı hatası: " . $e->getMessage());
}
