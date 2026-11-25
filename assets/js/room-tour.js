
/*
 * ============================================================
 * ROOM TOUR 360° — kontrakan PAK SAPRIADI
 * ============================================================
 * Fungsi:
 * 1. Menampilkan foto panorama 360°.
 * 2. Memungkinkan pengunjung menggeser tampilan.
 * 3. Menyediakan kontrol untuk memperbesar dan memperkecil.
 * 4. Menyediakan tombol pindah ruangan.
 * 5. Menampilkan pesan jika panorama gagal dimuat.
 *
 * Library yang digunakan: Pannellum.
 * ============================================================
 */

// Menjalankan kode setelah elemen HTML selesai dimuat.
document.addEventListener('DOMContentLoaded', () => {

  // ----------------------------------------------------------
  // 1. MENCARI ELEMEN PEMUTAR PANORAMA
  // ----------------------------------------------------------

  const viewerElement = document.getElementById('room-tour-viewer');
  const statusElement = document.getElementById('room-tour-status');

  // Berhenti jika elemen Room Tour tidak tersedia di halaman.
  if (!viewerElement) return;

  // Memastikan library Pannellum sudah dimuat.
  if (typeof window.pannellum === 'undefined') {
    if (statusElement) {
      statusElement.textContent =
        'Pemutar Room Tour belum berhasil dimuat. Periksa koneksi internet.';
    }
    return;
  }

  // ----------------------------------------------------------
  // 2. KONFIGURASI DUA PANORAMA 360°
  // ----------------------------------------------------------

  const konfigurasi = {
    // Panorama pertama yang ditampilkan saat halaman dibuka.
    default: {
      firstScene: 'ruangan-depan',
      sceneFadeDuration: 1000
    },

    // Daftar panorama yang tersedia.
    scenes: {
      // Panorama ruangan depan.
      'ruangan-depan': {
        type: 'equirectangular',
        panorama: 'assets/images/tur-depan-360.jpeg',
        title: 'Ruangan Depan',
        hotSpots: []
      },

      // Panorama ruangan tengah.
      'ruangan-tengah': {
        type: 'equirectangular',
        panorama: 'assets/images/tur-tengah-360.jpeg',
        title: 'Ruangan Tengah',
        hotSpots: []
      }
    },

    // Pengaturan pemutar panorama.
    autoLoad: true,
    showControls: true,
    compass: false,

    // Pengaturan zoom.
    hfov: 100,
    minHfov: 50,
    maxHfov: 120,

    // Batas sudut vertikal.
    minPitch: -90,
    maxPitch: 90
  };

  // ----------------------------------------------------------
  // 3. MENAMBAHKAN TOMBOL PEMILIH RUANGAN
  // ----------------------------------------------------------

  // Membuat wadah tombol tanpa perlu mengubah HTML utama.
  const tombolRuangan = document.createElement('div');
  tombolRuangan.className = 'room-tour-scenes';
  tombolRuangan.style.display = 'flex';
  tombolRuangan.style.flexWrap = 'wrap';
  tombolRuangan.style.gap = '10px';
  tombolRuangan.style.marginBottom = '12px';

  // Membuat tombol untuk setiap panorama yang tersedia.
  const daftarRuangan = [
    { id: 'ruangan-depan', nama: 'Ruangan Depan' },
    { id: 'ruangan-tengah', nama: 'Ruangan Tengah' }
  ];

  daftarRuangan.forEach((ruangan) => {
    const tombol = document.createElement('button');

    tombol.type = 'button';
    tombol.textContent = ruangan.nama;
    tombol.className = 'room-tour-scene-button';
    tombol.dataset.scene = ruangan.id;

    // Gaya dasar tombol agar langsung terlihat.
    tombol.style.padding = '10px 16px';
    tombol.style.border = '1px solid #b52e53';
    tombol.style.borderRadius = '8px';
    tombol.style.cursor = 'pointer';
    tombol.style.fontSize = '14px';

    tombolRuangan.appendChild(tombol);
  });

  // Menempatkan tombol tepat sebelum pemutar panorama.
  viewerElement.parentNode.insertBefore(tombolRuangan, viewerElement);

  // ----------------------------------------------------------
  // 4. MEMBUAT PEMUTAR PANORAMA
  // ----------------------------------------------------------

  try {
    const viewer = window.pannellum.viewer(
      'room-tour-viewer',
      konfigurasi
    );

    // Memperbarui tampilan tombol sesuai ruangan aktif.
    const perbaruiTombolAktif = (sceneId) => {
      const semuaTombol = tombolRuangan.querySelectorAll('button');

      semuaTombol.forEach((tombol) => {
        const aktif = tombol.dataset.scene === sceneId;

        tombol.style.backgroundColor = aktif ? '#b52e53' : 'transparent';
        tombol.style.color = aktif ? '#ffffff' : 'inherit';
        tombol.setAttribute('aria-pressed', String(aktif));
      });
    };

    // Memilih panorama ketika tombol ditekan.
    tombolRuangan.addEventListener('click', (event) => {
      const tombol = event.target.closest('button[data-scene]');

      if (!tombol) return;

      const sceneId = tombol.dataset.scene;

      // Memastikan scene tersedia sebelum berpindah.
      if (konfigurasi.scenes[sceneId]) {
        viewer.loadScene(sceneId);
      }
    });

    // Mengatur tombol aktif saat panorama selesai dimuat.
    viewer.on('scenechange', (sceneId) => {
      perbaruiTombolAktif(sceneId);
    });

    // Menandai ruangan depan sebagai pilihan awal.
    perbaruiTombolAktif('ruangan-depan');

    // Menginformasikan cara menggunakan Room Tour.
    if (statusElement) {
      statusElement.textContent =
        'Pilih ruangan melalui tombol di atas. Geser panorama untuk melihat sekeliling.';
    }

    // --------------------------------------------------------
    // 5. MENANGANI PANORAMA YANG GAGAL DIMUAT
    // --------------------------------------------------------

    viewer.on('error', () => {
      if (statusElement) {
        statusElement.textContent =
          'Panorama gagal dimuat. Periksa file gambar dan jalurnya.';
      }
    });

  } catch (error) {
    // Menampilkan pesan yang mudah dipahami tanpa merusak halaman.
    console.error('Room Tour gagal diinisialisasi:', error);

    if (statusElement) {
      statusElement.textContent =
        'Room Tour belum dapat dibuka. Silakan coba muat ulang halaman.';
    }
  }

});
