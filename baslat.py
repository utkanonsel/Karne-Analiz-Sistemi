from flask import Flask, jsonify, request
from flask_cors import CORS
import subprocess
import sys

app = Flask(__name__)
CORS(app)

@app.route('/okut', methods=['GET'])
def okut():
    try:
        # 1. Panelden gelen Öğrenci ID'sini al
        gelen_id = request.args.get('id')
        
        # Eğer ID gelmezse hata olmasın diye varsayılan 1 yapıyoruz
        if gelen_id is None:
            gelen_id = "1"
            print("⚠️ UYARI: ID gelmedi, varsayılan 1 kullanılıyor.")
        
        print(f"\n📡 İSTEK GELDİ -> Okunacak Öğrenci ID: {gelen_id}")

        # 2. main.py dosyasını BU ID İLE başlat
        # Komut aslında şuna dönüşüyor: python main.py 7
        process = subprocess.Popen(
            [sys.executable, 'main.py', str(gelen_id)], 
            stdout=subprocess.PIPE, 
            stderr=subprocess.PIPE,
            text=True
        )
        
        # İşlemin bitmesini bekle
        stdout, stderr = process.communicate()

        # Sonuçları ekrana bas (Hata takibi için)
        if stdout: print(f"📄 OKUYUCU ÇIKTISI:\n{stdout}")
        if stderr: print(f"❌ OKUYUCU HATASI:\n{stderr}")

        if process.returncode == 0:
            return jsonify({"durum": "basarili", "mesaj": "Sınav başarıyla okundu."})
        else:
            return jsonify({"durum": "hata", "mesaj": "Okuma sırasında hata oluştu."})

    except Exception as e:
        print(f"🔥 KRİTİK HATA: {e}")
        return jsonify({"durum": "hata", "mesaj": str(e)})

if __name__ == '__main__':
    print("--- ZIVER KÖPRÜSÜ (V3) HAZIR ---")
    app.run(debug=True, port=5000)