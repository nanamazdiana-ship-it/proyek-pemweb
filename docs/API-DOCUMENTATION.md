# Dokumentasi API dan CRUD Unit Kontrakan

Resource utama adalah `unit_kontrakan` pada MySQL database `kontrakan_pak_sapriadi`.
Endpoint diimplementasikan di `api/index.php?r=units`. Respons sukses memakai pembungkus
`{"data": ...}`; respons gagal memakai `{"error": "...", "fields": {...}}`.
`/api/units.php` tetap tersedia sebagai alias kompatibilitas ke router yang sama; untuk PUT/DELETE
gunakan parameter `id`, misalnya `/api/units.php?id=1`.

## Endpoint

| Operasi | Request | Hak akses | Respons sukses |
|---|---|---|---|
| Read | `GET /api/index.php?r=units` | Publik | `200`, data berupa array unit |
| Search/filter | `GET /api/index.php?r=units&q=pintu&status=Tersedia` | Publik | `200`, array hasil pencarian/filter |
| Create | `POST /api/index.php?r=units` | Admin | `201`, data unit baru |
| Update | `PUT /api/index.php?r=units/1` | Admin | `200`, data unit yang diperbarui |
| Delete | `DELETE /api/index.php?r=units/1` | Admin | `200`, `{"id_unit":1,"deleted":true}` |

Permintaan perubahan harus mengirim cookie sesi login dan header `Content-Type: application/json`.
Data create/update menggunakan bentuk berikut:

```json
{
  "nama_unit": "Pintu 4",
  "harga_sewa": 1200000,
  "status_unit": "Tersedia",
  "deskripsi": "Kamar mandi dalam"
}
```

`q` mencari potongan teks pada nama atau deskripsi unit. `status` menerima `Tersedia` atau
`Terisi`; keduanya dapat dipakai bersamaan. UI dashboard admin menyediakan formulir create/edit,
tombol delete, pencarian, dan filter status.

## Validasi dan keamanan database

- Nama unit wajib 2–100 karakter.
- Harga harus angka positif dan tidak lebih dari `9999999999.99`.
- Status hanya `Tersedia` atau `Terisi`.
- Deskripsi maksimal 2.000 karakter.
- Pencarian maksimal 100 karakter.
- Mutasi unit memerlukan sesi admin; query database menggunakan prepared statements.
- Unit yang sudah memiliki pengajuan, data penghuni, atau pengaduan tidak dapat dihapus (`409 Conflict`), agar relasi dan riwayat tetap utuh.

## Format error

| HTTP | Kondisi | Contoh body |
|---|---|---|
| `400` | ID unit tidak valid | `{"error":"ID unit tidak valid.","fields":{}}` |
| `401` | Belum login | `{"error":"Silakan masuk terlebih dahulu.","fields":{}}` |
| `403` | Bukan admin | `{"error":"Anda tidak memiliki akses.","fields":{}}` |
| `404` | Unit tidak ditemukan | `{"error":"Unit tidak ditemukan.","fields":{}}` |
| `405` | Metode tidak didukung | `{"error":"Metode tidak diizinkan untuk endpoint unit.","fields":{}}` |
| `409` | Unit masih memiliki data terkait | `{"error":"Unit tidak dapat dihapus karena sudah memiliki data terkait.","fields":{}}` |
| `415` | Body bukan JSON | `{"error":"Gunakan Content-Type application/json.","fields":{}}` |
| `422` | Data/filter tidak valid | `{"error":"Data unit tidak valid.","fields":{"nama_unit":"Nama unit harus 2–100 karakter."}}` |
| `500` | Kesalahan internal | `{"error":"Terjadi kesalahan pada server. Periksa log untuk detailnya.","fields":{}}` |

## Test matrix CRUD

Catat status aktual, hasil, dan ID data saat menjalankan tiap kasus pada server/database lokal.
Kasus valid mengubah database; gunakan data uji dan hapus kembali bila tidak terkait riwayat.

| ID | Operasi | Input / langkah | Hasil yang diharapkan | Hasil aktual |
|---|---|---|---|---|
| C1 | Create | Admin membuat unit dengan semua field valid | `201`; unit muncul di daftar | Belum dijalankan |
| C2 | Create | Harga `0` atau nama kosong | `422`; field error tampil, DB tidak berubah | Belum dijalankan |
| R1 | Read | Buka panel Unit | `200`; daftar unit tampil dari DB | Belum dijalankan |
| R2 | Search/filter | Cari `Pintu` dan pilih `Tersedia` | Hanya unit yang cocok tampil | Belum dijalankan |
| U1 | Update | Edit nama, harga, status, deskripsi | `200`; nilai baru tampil setelah muat ulang | Belum dijalankan |
| U2 | Update | ID tidak ada atau harga negatif | `404` atau `422`; data lama tetap | Belum dijalankan |
| D1 | Delete | Hapus unit uji yang belum direferensikan | `200`; unit hilang dari daftar | Belum dijalankan |
| D2 | Delete | Hapus unit yang sudah punya riwayat | `409`; riwayat tetap tersimpan | Belum dijalankan |

## Catatan pengujian awal

Pemeriksaan sintaks `php -l` untuk `api/index.php`, `api/units.php`, dan `buat-admin.php`,
`node --check assets/js/app.js`, serta `git diff --check` berhasil. Uji CRUD end-to-end belum
dijalankan karena MySQL lokal tidak merespons pada `127.0.0.1:3306`; kolom hasil aktual di atas
tetap menunggu pengujian dengan database dan akun admin lokal. Jangan menguji delete pada unit
produksi/berisi data penting.
