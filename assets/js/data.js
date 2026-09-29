// ============================================================
// MODUL 04 — JAVASCRIPT MODERN (ES6+), MODULES, ERROR HANDLING
// File ini adalah SATU MODUL utuh: berisi data + fungsi pengolahan
// data yang di-export dan dipakai (import) oleh script.js (Modul 05).
// ============================================================

// Bukti Modul 04 — array of objects sebagai sumber data utama proyek.
export const dataKontrakan = [
  {
    id: 1,
    nama: "Unit Pintu 1",
    harga: 1000000,
    status: "Tersedia",
    fasilitas: ["2 Kamar Tidur", "Dapur", "Toilet Mandiri", "Ruang Tengah"],
    lokasi: "Depan",
  },
  {
    id: 2,
    nama: "Unit Pintu 2",
    harga: 1000000,
    status: "Terisi",
    fasilitas: ["2 Kamar Tidur", "Dapur", "Toilet Mandiri", "Ruang Tengah"],
    lokasi: "Tengah",
  },
  {
    id: 3,
    nama: "Unit Pintu 3",
    harga: 1000000,
    status: "Tersedia",
    fasilitas: ["2 Kamar Tidur", "Dapur", "Toilet Mandiri", "Ruang Tengah"],
    lokasi: "Belakang",
  },
];

// ------------------------------------------------------------------
// Bukti Modul 04 — pengolahan data pakai array methods (filter/map/reduce)
// Fungsi kecil, satu tanggung jawab, gampang dibaca & dites terpisah.
// ------------------------------------------------------------------

// filter(): ambil unit yang berstatus "Tersedia" saja
export function unitTersedia(data) {
  return data.filter((unit) => unit.status === "Tersedia");
}

// map(): ubah array object jadi array nama unit saja
export function namaSemuaUnit(data) {
  return data.map((unit) => unit.nama);
}

// reduce(): hitung jumlah unit yang sedang terisi
export function totalUnitTerisi(data) {
  return data.reduce((total, unit) => total + (unit.status === "Terisi" ? 1 : 0), 0);
}

// reduce(): hitung total potensi pendapatan bulanan kalau semua unit terisi
export function totalPotensiPendapatan(data) {
  return data.reduce((total, unit) => total + unit.harga, 0);
}

// ------------------------------------------------------------------
// Bukti Modul 04 — fungsi ringkasan + error handling dasar (throw/try-catch)
// Dipanggil dari script.js (Modul 05) untuk ditampilkan ke DOM.
// ------------------------------------------------------------------
export function ringkasKontrakan(data) {
  if (!Array.isArray(data)) {
    throw new TypeError("Data kontrakan harus berupa array");
  }
  if (data.length === 0) {
    throw new Error("Data kontrakan kosong, tidak ada unit untuk diringkas");
  }

  return {
    totalUnit: data.length,
    jumlahTersedia: unitTersedia(data).length,
    jumlahTerisi: totalUnitTerisi(data),
    potensiPendapatan: totalPotensiPendapatan(data),
  };
}