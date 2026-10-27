# Dokumentasi REST API
## Sistem Manajemen Kontrakkan Pak Sapriadi

### 1. Deskripsi
API ini dibuat menggunakan PHP untuk menyediakan endpoint pemeriksaan backend, menampilkan data unit kontrakkan, dan menerima serta memvalidasi data unit baru.

### 2. Daftar Endpoint

| Method | Endpoint | Fungsi | Status |
|---|---|---|---|
| GET | `/api/health.php` | Memeriksa kondisi API | 200 OK |
| GET | `/api/units.php` | Menampilkan daftar unit kontrakkan | 200 OK |
| POST | `/api/create-unit.php` | Memvalidasi data unit baru | 201 Created |
| POST | `/api/create-unit.php` | Menolak data yang tidak lengkap | 422 Unprocessable Content |
| GET | `/api/create-unit.php` | Menolak metode HTTP yang tidak diizinkan | 405 Method Not Allowed |
| POST | `/api/create-unit.php` | Menolak format Content-Type yang salah | 415 Unsupported Media Type |

### 3. Contoh Request POST

URL: `/api/create-unit.php`

Header:
`Content-Type: application/json`

Body:
```json
{
  "kode": "Pintu-04",
  "nama": "Pintu 4",
  "status": "Tersedia",
  "harga": 1000000
}
```

### 4. Validasi
API memeriksa keberadaan field `kode`, `nama`, `status`, dan `harga`. Status unit hanya boleh bernilai `Tersedia` atau `Terisi`, sedangkan harga harus berupa angka positif. Request harus menggunakan format JSON.

### 5. Catatan Implementasi
Data unit pada endpoint `units.php` masih menggunakan data statis di dalam kode. Endpoint `create-unit.php` memvalidasi data dan mengembalikan respons JSON, tetapi belum menyimpan data secara permanen ke database atau berkas.

### 6. Pengujian
Pengujian dilakukan menggunakan Postman dengan memeriksa respons JSON dan status HTTP untuk setiap skenario. Screenshot pengujian dilampirkan pada laporan praktikum.