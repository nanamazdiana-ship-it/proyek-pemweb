
// ==========================================
// login.js
// Formulir login dan registrasi.
// Terhubung ke API autentikasi PHP (sesi server).
// ==========================================

import { api } from './app.js';


// ==========================================
// 1. PENGALIHAN HALAMAN BERDASARKAN PERAN
// ==========================================

// Admin diarahkan ke dashboard admin.
// Penghuni diarahkan ke dashboard penghuni.
const arah = (role) => {
  location.href = role === 'admin'
    ? 'admin.html'
    : 'penghuni.html';
};


// ==========================================
// 2. PEMERIKSAAN SESI LOGIN
// ==========================================

// Jika pengguna sudah login, arahkan sesuai perannya.
// Jika belum login, pengguna tetap berada di halaman ini.
api('auth/me')
  .then((me) => arah(me.role))
  .catch(() => {
    // Belum login: tidak perlu melakukan pengalihan.
  });


// ==========================================
// 3. FUNGSI PEMASANGAN FORMULIR
// ==========================================

function pasang(form, route, validasi) {
  // Hentikan proses jika formulir tidak ditemukan.
  if (!form) return;

  // Elemen untuk menampilkan pesan hasil proses formulir.
  const status = form.querySelector('.form-status');

  // Menampilkan pesan validasi pada masing-masing kolom.
  const tampil = (id, teks) => {
    const el = form.querySelector(`#${id}-error`);

    if (el) {
      el.textContent = teks;
    }

    // Menandai kolom yang memiliki kesalahan.
    form.querySelector(`#${id}`)
      ?.setAttribute('aria-invalid', String(Boolean(teks)));
  };


  // ==========================================
  // 4. PROSES PENGIRIMAN FORMULIR
  // ==========================================

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Bersihkan pesan sebelumnya.
    // PERBAIKAN: cek keberadaan elemen status terlebih dahulu.
    if (status) {
      status.replaceChildren();
    }

    // Mengambil data input dan menghapus spasi di awal/akhir.
    const data = Object.fromEntries(
      [...form.querySelectorAll('input')].map((i) => [
        i.name,
        i.value.trim()
      ])
    );

    // Menjalankan validasi sesuai jenis formulir.
    const galat = validasi(data);

    // Menampilkan pesan kesalahan pada setiap input.
    form.querySelectorAll('input').forEach((i) => {
      tampil(i.id, galat[i.name] || '');
    });

    // Fokuskan kursor ke kolom pertama yang tidak valid.
    const pertama = form.querySelector('[aria-invalid="true"]');

    if (pertama) {
      pertama.focus();
      return;
    }

    // Menonaktifkan tombol selama proses pengiriman.
    const tombol = form.querySelector('button[type=submit]');

    if (tombol) {
      tombol.disabled = true;
    }

    try {
      // Mengirim data ke API autentikasi.
      const me = await api(route, {
        method: 'POST',
        body: data
      });

      // Mengarahkan pengguna sesuai perannya.
      arah(me.role);

    } catch (err) {
      // Menampilkan pesan kesalahan dari API.
      Object.entries(err.fields || {}).forEach(([k, v]) => {
        tampil(k, v);
      });

      // PERBAIKAN: tampilkan pesan jika elemen status tersedia.
      if (status) {
        status.textContent = err.message;
      }

      // Mengaktifkan kembali tombol setelah proses gagal.
      if (tombol) {
        tombol.disabled = false;
      }
    }
  });
}


// ==========================================
// 5. FORMULIR LOGIN
// ==========================================

pasang(
  document.querySelector('#form-login'),
  'auth/login',
  (d) => ({
    email: /^\S+@\S+\.\S+$/.test(d.email)
      ? ''
      : 'Masukkan email yang valid.',

    password: d.password
      ? ''
      : 'Kata sandi wajib diisi.'
  })
);


// ==========================================
// 6. FORMULIR REGISTRASI
// ==========================================

pasang(
  document.querySelector('#form-register'),
  'auth/register',
  (d) => ({
    nama: /^\p{L}+(?:[ '-]\p{L}+)*$/u.test(d.nama)
      && d.nama.length >= 2
      && d.nama.length <= 80
        ? ''
        : 'Nama 2–80 karakter, hanya huruf, spasi, apostrof, atau tanda hubung.',

    email: /^\S+@\S+\.\S+$/.test(d.email)
      ? ''
      : 'Masukkan email yang valid.',

    telepon: /^\d{9,15}$/.test(d.telepon)
      ? ''
      : 'Nomor WhatsApp terdiri dari 9–15 angka.',

    password: d.password.length >= 8
      ? ''
      : 'Kata sandi minimal 8 karakter.',

    konfirmasi: d.konfirmasi === d.password
      ? ''
      : 'Konfirmasi tidak sama dengan kata sandi.'
  })
);
