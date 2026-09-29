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
