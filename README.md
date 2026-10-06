# Proyek Web Kontrakan Pak Sapriadi (3 Pintu) - Milestone Pertemuan 5

Repositori ini memuat implementasi **Interaktivitas DOM, Event Handling, Safe DOM Update, dan Web Storage (`localStorage`)** untuk Sistem Informasi Kontrakan Pak Sapriadi (3 Pintu) sebagai pemenuhan Tugas OBE Pertemuan 5 Mata Kuliah Pemrograman Web, Jurusan Teknik Komputer, Universitas Borneo Tarakan.

## 1. Deskripsi Proyek
* **Nama Sistem:** Landing Page & Informasi Kontrakan Pak Sapriadi (3 Pintu)
* **Spesifikasi Properti:** Properti sewa berupa 3 unit pintu kontrakan mandiri dengan tarif **Rp 1.000.000 / bulan**. Setiap unit memiliki fasilitas ruang tengah, 2 kamar tidur, dapur, dan toilet mandiri.
* **Tujuan Web:** Menyajikan antarmuka dinamis interaktif yang memudahkan calon penyewa melakukan penyaringan kondisi unit, melihat detail unit, memilih preferensi tema visual, serta mengajukan minat sewa dengan umpan balik instan.

## 2. Cara Menjalankan Aplikasi
1. Aktifkan server lokal **Laragon 5** (pastikan modul Apache aktif).
2. Simpan folder repositori ini pada direktori: `C:\laragon\www\proyek Semester` (atau `C:\laragon\www\pemweb-obe`).
3. Buka peramban web dan akses melalui alamat URL:
   `http://localhost/proyek Semester/`

## 3. Dokumentasi Alur Event & State Sederhana (3 Interaksi Bermakna)

### Interaksi 1: Filter Kondisi & Pencarian Real-Time (`input` & `click` Event)
* **Alur Event:** Pengguna mengetik kata kunci pada kolom pencarian atau mengklik tombol filter kondisi (`Semua`, `Baik`, `Perlu Cek`). Event listener `input` / `click` menyaring array data `unitKontrakan` di memori, lalu memanggil fungsi `renderKatalog()`.
* **Safe DOM Update:** Pengisian kartu dinamis dilakukan murni menggunakan `document.createElement()`, `textContent`, dan `replaceChildren()` tanpa `innerHTML` berisiko XSS.

### Interaksi 2: Ringkasan Detail Unit via Event Delegation (`click` Event)
* **Alur Event:** Listener dipasang pada wadah induk (`#daftar-alat`). Ketika tombol detail pada kartu yang dirender diklik, event merambat (*event bubbling*) dan dibaca oleh `e.target.closest('[data-detail]')` untuk memunculkan pesan ringkasan unit.

### Interaksi 3: Preferensi Tema Visual (`localStorage` Web Storage)
* **Alur Event:** Tombol "Ganti Tema" mengubah atribut `data-theme` pada root dokumen HTML dan menyimpan nilai (`light`/`dark`) ke `localStorage`.
* **Restorasi State:** Saat halaman dimuat/di-refresh, nilai `localStorage.getItem('theme')` dibaca kembali untuk memulihkan mode tema tanpa memerlukan database.
* **Data Non-Sensitif:** Web Storage murni digunakan untuk preferensi antarmuka pengguna (UI theme), bukan kredensial/token rahasia.

## 4. Milestone Pertemuan 6: Form & Validasi

### Aksesibilitas dan aturan validasi

- Setiap kontrol formulir memiliki `label` yang terhubung melalui `for` dan `id`.
- Panduan dan pesan kesalahan dihubungkan ke input memakai `aria-describedby`; status kesalahan disampaikan melalui `aria-invalid` dan area pesan `aria-live`.
- Fokus keyboard terlihat, dan saat submit gagal fokus serta tampilan berpindah ke kontrol invalid pertama.
- Nama wajib diisi, 2–80 karakter, hanya huruf Unicode dan pemisah nama (spasi, apostrof, tanda hubung).
- Nomor WhatsApp wajib 9–15 angka. Unit wajib dipilih dan unit berstatus `Terisi` ditolak. Pesan opsional, maksimal 1.000 karakter.
- Spasi tepi nama, nomor, dan pesan dinormalisasi sebelum validasi. Validasi tetap diperiksa saat submit, termasuk bila JavaScript mengubah nilai programatis.

### Skenario pemeriksaan manual

| Skenario | Data/aksi | Hasil yang diharapkan |
| --- | --- | --- |
| Data valid | Nama `Siti Aminah`, nomor `081234567890`, pilih Unit Pintu 1 atau 3, pesan opsional | Submit menampilkan ringkasan nama, nomor, dan unit; tidak ada pesan error. |
| Nama mengandung angka | Nama `Siti2 Aminah`, lalu submit | Submit ditolak; fokus ke Nama Lengkap dan pesan menjelaskan nama hanya menerima huruf/pemisah nama. |
| Nama mengandung simbol terlarang | Nama `Siti@`, lalu submit | Submit ditolak; fokus ke Nama Lengkap dan pesan kesalahan tampil di bawah input. |
| Nomor berisi huruf | Nomor `0812abc567`, lalu submit | Submit ditolak; fokus ke Nomor WhatsApp dan pesan meminta 9–15 angka. |
| Nomor terlalu pendek | Nomor `08123`, lalu submit | Submit ditolak; fokus ke Nomor WhatsApp dan panjang yang diminta dijelaskan. |
| Unit sudah terisi | Pilih Unit Pintu 2 (Terisi), lalu submit | Submit ditolak; fokus ke pilihan unit dan pesan menyebut unit sudah terisi. |
| Wajib belum diisi | Kosongkan nama, nomor, atau unit, lalu submit | Submit ditolak dan fokus menuju kolom wajib pertama yang invalid. |
| Keyboard/pembaca layar | Navigasi dengan Tab dan coba submit invalid | Fokus terlihat; label dan pesan error terasosiasi/terumumkan untuk kontrol terkait. |

Rincian review, pemeriksaan yang berhasil, batas verifikasi, dan lembar pencatatan skenario ada di [`docs/FORM_REVIEW_EVIDENCE.md`](docs/FORM_REVIEW_EVIDENCE.md). Skenario browser manual tetap perlu dijalankan dan hasil aktual/tangkapan layarnya dicatat.

### Catatan code review dan perbaikan

- Review menemukan opsi unit berstatus `Terisi` sebelumnya tetap dapat dipilih dan diterima. Opsi sekarang membawa status unit dan validasi submit menolaknya dengan pesan khusus.
- Review menemukan feedback sukses sebelumnya dapat tertinggal setelah submit invalid. Pesan lama kini dikosongkan ketika submit atau ketika pengguna mengubah input.
- Validasi nama/nomor kini menghasilkan error pada kolom terkait. Submit invalid memfokuskan dan menggulir ke kolom salah pertama.
- Review kode pada implementasi ini sudah dicatat di bukti review. Review oleh rekan (peer) belum dilakukan; catat nama reviewer, tanggal, dan tanggapan setelah rekan meninjau perubahan.

### Refleksi: validasi client-side dan server-side

Validasi client-side memberi feedback cepat, mencegah kesalahan sederhana, serta membantu aksesibilitas dan UX. Namun, pengguna dapat mematikan atau memodifikasi JavaScript dan mengirim request sendiri. Karena itu, validasi client-side bukan batas keamanan. Jika aplikasi kelak menyimpan atau mengirim pengajuan, server harus memeriksa ulang format, panjang, kolom wajib, dan ketersediaan unit sebelum menerima data. Saat ini proyek hanya menampilkan feedback di browser dan belum mengirim atau menyimpan pengajuan pada server.

### Riwayat commit

Riwayat yang sudah ada sebelum perubahan formulir ini mencakup `e0821df` (interaksi DOM, event delegation, dan tema) serta `ca74302` (katalog dan formulir kontak). Perubahan Milestone 6 yang sedang dikerjakan perlu dicatat sebagai commit Git tersendiri setelah ditinjau; jangan menganggap file yang belum di-commit sebagai riwayat commit.
