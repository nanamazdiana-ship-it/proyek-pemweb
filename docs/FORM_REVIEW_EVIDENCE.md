# Bukti Code Review Form Minat Sewa

Tanggal: 06-10-2026  
Ruang lingkup: `index.html`, `assets/js/script.js`, dan `assets/css/style.css`.

## Temuan dan perbaikan

| Temuan saat review | Perbaikan |
| --- | --- |
| Unit berstatus `Terisi` tetap bisa dipilih dan lolos submit. | Opsi dropdown memuat status unit; `setCustomValidity()` menolak unit terisi dan pesan error menyebut unit tersebut. |
| Nilai nama/nomor yang tidak sesuai perlu diarahkan ke kolomnya. | Validasi nama/nomor memberi pesan di bawah kontrol; submit menandai `aria-invalid`, fokus dan scroll ke kesalahan pertama. Nilai salah tetap terlihat agar pengguna dapat memperbaikinya. |
| Feedback sukses sebelumnya bisa tertinggal saat submit berikutnya gagal. | Feedback lama dikosongkan pada submit ulang dan saat pengguna mengubah kontrol. |
| Error perlu terhubung ke kontrol untuk teknologi bantu. | Pesan terhubung melalui `aria-describedby`, menggunakan `aria-live`, dan status kontrol memakai `aria-invalid`. |

## Pemeriksaan yang dilakukan

- `git diff --check` — **lulus** (tidak ditemukan whitespace error pada diff).
- `node --check assets/js/script.js` — **tidak dapat dijalankan** karena Node.js tidak tersedia di lingkungan ini.
- Pemeriksaan interaksi di browser dan pembaca layar — **belum dijalankan**. Hasil di bawah ini adalah skenario untuk verifikasi manual, bukan hasil uji yang diklaim sudah lulus.

## Lembar bukti skenario browser

Isi kolom hasil aktual setelah menjalankan aplikasi melalui Laragon. Simpan screenshot di folder ini dengan nama yang sesuai (misalnya `valid-submit.png` dan `occupied-unit-error.png`).

| Skenario | Data/aksi | Hasil yang diharapkan | Hasil aktual / bukti |
| --- | --- | --- | --- |
| Valid | `Siti Aminah`, `081234567890`, Unit Pintu 1 | Ringkasan konfirmasi muncul; tidak ada error. | Belum diuji |
| Nama angka/simbol | `Siti2` atau `Siti@`; submit | Submit tertahan, fokus ke nama, pesan error terlihat. | Belum diuji |
| Nomor huruf/pendek | `0812abc567` atau `08123`; submit | Submit tertahan, fokus ke nomor, pesan error terlihat. | Belum diuji |
| Unit terisi | Pilih Unit Pintu 2 (Terisi); submit | Submit tertahan, fokus ke unit, pesan “sudah terisi” terlihat. | Belum diuji |
| Kolom wajib kosong | Kosongkan nama/nomor/unit; submit | Submit tertahan dan fokus ke invalid pertama. | Belum diuji |
| Keyboard | Tab melalui form, submit invalid | Fokus tampak dan berpindah ke input invalid. | Belum diuji |

Peer review oleh rekan belum dilakukan. Reviewer: __________  Tanggal: __________  Tanggapan/tindak lanjut: __________.
