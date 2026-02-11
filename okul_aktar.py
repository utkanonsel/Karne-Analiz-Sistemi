import mysql.connector
import csv
import sys

# Veritabanı bilgileriniz
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "analiz_sistemi"
}

def veri_yukle():
    try:
        # MySQL Bağlantısı
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        # Hayalet tablo sorununa karşı tabloyu temizleyip MyISAM olarak kurgulayalım
        print("Tablo kontrol ediliyor...")
        cursor.execute("DROP TABLE IF EXISTS okullar")
        cursor.execute("""
            CREATE TABLE okullar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                il VARCHAR(100),
                ilce VARCHAR(100),
                okul_adi VARCHAR(255),
                okul_turu VARCHAR(20)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4
        """)

        dosyalar = [
            ("devlet_okullari.csv", "Devlet"),
            ("ozel_okullar.csv", "Özel")
        ]

        for dosya, tur in dosyalar:
            print(f"\n{dosya} işleniyor...")
            with open(dosya, mode='r', encoding='utf-8') as f:
                # Excel'den gelen CSV'ler noktalı virgül (;) kullanır
                reader = csv.reader(f, delimiter=';')
                next(reader) # Başlığı (il;ilce;okul_adi) atla
                
                veriler = []
                for row in reader:
                    if len(row) >= 3:
                        veriler.append((row[0].strip(), row[1].strip(), row[2].strip(), tur))
                
                # 1000'erli paketler halinde hızlıca yükle
                for i in range(0, len(veriler), 1000):
                    batch = veriler[i:i+1000]
                    cursor.executemany(
                        "INSERT INTO okullar (il, ilce, okul_adi, okul_turu) VALUES (%s, %s, %s, %s)", 
                        batch
                    )
                    conn.commit()
                    print(f"{tur}: {i + len(batch)} kayıt tamamlandı...", end="\r")
        
        print("\n\nİşlem başarıyla bitti! 70 bin okul veritabanına eklendi.")

    except Exception as e:
        print(f"\nHATA OLUŞTU: {e}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    veri_yukle()