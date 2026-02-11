<?php
// C:\xampp\htdocs\analiz\admin\sinav_kaydet.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../db.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function sinavKaydet(array $data): array
{
    global $db;

    try {
        $denemeKodu  = trim($data['deneme_kodu'] ?? '');
        $denemeAdi   = trim($data['deneme_adi'] ?? '');
        $ders        = trim($data['ders'] ?? '');
        $sinavTarihi = trim($data['sinav_tarihi'] ?? '');
        $sinif       = trim($data['sinif'] ?? '');
        $sorular     = $data['sorular'] ?? [];

        if ($denemeKodu === '' || $denemeAdi === '' || $ders === '' || $sinavTarihi === '' || $sinif === '') {
            return ['ok' => false, 'mesaj' => 'Üst bilgiler eksik.'];
        }

        if (!is_array($sorular) || count($sorular) === 0) {
            return ['ok' => false, 'mesaj' => 'En az 1 soru olmalı.'];
        }

        // Aynı deneme kodu var mı?
        $q = $db->prepare("SELECT id FROM sinavlar WHERE deneme_kodu = ? LIMIT 1");
        $q->execute([$denemeKodu]);
        if ($q->fetch()) {
            return ['ok' => false, 'mesaj' => 'Bu deneme kodu zaten kayıtlı.'];
        }

        $db->beginTransaction();

        // Sinav ekle
        $ins = $db->prepare("
            INSERT INTO sinavlar (deneme_kodu, deneme_adi, ders, sinav_tarihi, sinif)
            VALUES (?,?,?,?,?)
        ");
        $ins->execute([$denemeKodu, $denemeAdi, $ders, $sinavTarihi, $sinif]);
        $sinavId = (int)$db->lastInsertId();

        // Soru ekle
        $insSoru = $db->prepare("
            INSERT INTO sinav_sorular (sinav_id, soru_no, kazanım_kodu, kazanım_adi, cevap)
            VALUES (?,?,?,?,?)
        ");

        $gecerliSoruSayisi = 0;

        foreach ($sorular as $idx => $s) {
            $no  = trim((string)($s['no'] ?? ''));
            $kk  = trim((string)($s['kazanim_kodu'] ?? ''));
            $ka  = trim((string)($s['kazanim_adi'] ?? ''));
            $cv  = strtoupper(trim((string)($s['cevap'] ?? '')));

            $satirBombozMu = ($no === '' && $kk === '' && $ka === '' && $cv === '');
            if ($satirBombozMu) {
                continue;
            }

            if ($no === '' || $kk === '' || $ka === '' || $cv === '') {
                $db->rollBack();
                return ['ok' => false, 'mesaj' => 'Eksik soru satırı var (manuelde kırmızı olanlar gibi).'];
            }

            $insSoru->execute([$sinavId, (int)$no, $kk, $ka, $cv]);
            $gecerliSoruSayisi++;
        }

        if ($gecerliSoruSayisi === 0) {
            $db->rollBack();
            return ['ok' => false, 'mesaj' => 'Hiç geçerli soru bulunamadı.'];
        }

        // QR üret
        $qrDir = __DIR__ . '/../qr';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }

        $qrFile = $denemeKodu . '.png';
        $qrPath = $qrDir . '/' . $qrFile;

        $qr = new QrCode($denemeKodu);
        $writer = new PngWriter();
        $writer->write($qr)->saveToFile($qrPath);

        $db->commit();

        return ['ok' => true, 'sinav_id' => $sinavId, 'qr' => $qrFile];

    } catch (Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        return ['ok' => false, 'mesaj' => 'Hata: ' . $e->getMessage()];
    }
}
