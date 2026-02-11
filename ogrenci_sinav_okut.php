<?php
session_start();
if (!isset($_SESSION['ogrenci_id'])) { die("Lütfen giriş yapınız."); }

$sinav_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sinav_adi = isset($_GET['ad']) ? htmlspecialchars($_GET['ad']) : "Sınav";

if ($sinav_id == 0) { die("Hata: Sınav seçilmedi."); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sınav Okut</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #000; margin: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        
        /* Kamera Alanı */
        #camera-container { flex: 1; position: relative; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        video { width: 100%; height: 100%; object-fit: cover; }
        
        /* Kılavuz Çizgileri (Nişangah) */
        .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; border: 20px solid rgba(0,0,0,0.5); box-sizing: border-box; }
        .guide-box { border: 2px dashed rgba(255, 255, 255, 0.7); width: 80%; height: 60%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border-radius: 10px; }
        
        /* Alt Kontrol Paneli */
        .controls { background: #111; padding: 20px; display: flex; justify-content: space-between; align-items: center; color: white; min-height: 80px; }
        
        .btn-capture { width: 70px; height: 70px; border-radius: 50%; background: white; border: 4px solid #ddd; cursor: pointer; transition: 0.2s; }
        .btn-capture:active { transform: scale(0.9); background: #eee; }
        
        .btn-gallery { background: none; border: none; color: white; display: flex; flex-direction: column; align-items: center; cursor: pointer; font-size: 12px; }
        .btn-gallery svg { width: 28px; height: 28px; margin-bottom: 4px; }
        
        .btn-cancel { color: white; text-decoration: none; font-size: 14px; font-weight: 500; }
        
        /* Yükleme Ekranı */
        #loading { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; color: white; text-align: center; flex-direction: column; justify-content: center; align-items: center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #fff; border-top: 4px solid #2563eb; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- Yükleniyor Ekranı -->
<div id="loading">
    <div class="spinner"></div>
    <div id="loading-text">Analiz ediliyor...</div>
</div>

<!-- Kamera Görüntüsü -->
<div id="camera-container">
    <video id="video" autoplay playsinline></video>
    <div class="overlay">
        <div class="guide-box"></div> <!-- Kağıdı buraya oturt -->
    </div>
</div>

<!-- Kontroller -->
<div class="controls">
    <!-- Galeriden Seç (Sol Alt) -->
    <button class="btn-gallery" onclick="document.getElementById('fileInput').click()">
        <svg fill="currentColor" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
        Galeri
    </button>
    <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="dosyaSecildi(this)">

    <!-- Fotoğraf Çek (Orta) -->
    <button class="btn-capture" onclick="fotoCek()"></button>

    <!-- İptal (Sağ) -->
    <a href="ogrenci_panel.php" class="btn-cancel">İptal</a>
</div>

<!-- Kanvas (Görünmez, fotoğrafı işlemek için) -->
<canvas id="canvas" style="display: none;"></canvas>

<script>
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const loading = document.getElementById('loading');
    const sinavId = <?php echo $sinav_id; ?>;

    // Kamerayı Başlat (Arka Kamera Öncelikli)
    async function startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: "environment" } // Arka kamera
            });
            video.srcObject = stream;
        } catch (err) {
            alert("Kamera başlatılamadı. Lütfen izin verin.");
        }
    }
    startCamera();

    // Fotoğraf Çek ve Gönder
    function fotoCek() {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        canvas.toBlob(blob => {
            gonder(blob);
        }, 'image/jpeg', 0.9); // %90 kalite
    }

    // Galeriden Seçilirse
    function dosyaSecildi(input) {
        if (input.files && input.files[0]) {
            gonder(input.files[0]);
        }
    }

    // Sunucuya Yükleme (AJAX)
    function gonder(blob) {
        loading.style.display = 'flex';
        const formData = new FormData();
        formData.append('optik_foto', blob, 'capture.jpg');
        formData.append('sinav_id', sinavId);

        fetch('sonuc_isle.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Sonuç sayfasını direk ekrana bas
            document.body.innerHTML = html;
        })
        .catch(err => {
            alert("Yükleme hatası: " + err);
            loading.style.display = 'none';
        });
    }
</script>

</body>
</html>