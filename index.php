<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utkan Analiz Sistemi</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .ana-kart { background: white; padding: 50px 40px; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; width: 450px; }
        .baslik { display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 28px; font-weight: bold; color: #333; margin-bottom: 40px; }
        .buton-grubu { display: flex; flex-direction: column; gap: 20px; }
        .buton { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 20px; text-decoration: none; color: white; font-weight: bold; border-radius: 12px; font-size: 18px; transition: 0.3s; }
        .buton-ogrenci { background: #1a73e8; }
        .buton-ogretmen { background: #28a745; }
        .buton:hover { transform: translateY(-2px); opacity: 0.9; }
        
        /* Yönetim Paneli Link Stili */
        .yonetim-yazisi { margin-top: 40px; font-size: 13px; color: #ccc; text-decoration: none; display: block; transition: color 0.3s; }
        .yonetim-yazisi:hover { color: #d63031; font-weight: bold; }
    </style>
</head>
<body>

<div class="ana-kart">
    <div class="baslik">
        📊 Utkan Analiz Sistemi
    </div>

    <div class="buton-grubu">
        <a href="login_ogrenci.php" class="buton buton-ogrenci">
            👨‍🎓 Öğrenci Girişi
        </a>

        <a href="login_ogretmen.php" class="buton buton-ogretmen">
            👨‍🏫 Öğretmen Girişi
        </a>
    </div>

    <a href="admin/login.php" class="yonetim-yazisi">
        🔒 Yönetim Paneli Giriş
    </a>
</div>

</body>
</html>