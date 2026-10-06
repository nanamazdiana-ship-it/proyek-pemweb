// ============================================================
// MODUL 05 — DOM, EVENT, WEB STORAGE, DAN DYNAMIC UI
// Mengimpor data & fungsi olahan dari Modul 04 (data.js), lalu
// merender ke DOM secara AMAN memakai createElement/textContent
// (bukan innerHTML) sesuai anjuran modul untuk data yang dirender
// berulang. Juga menangani 4 interaksi: filter, tema (Web Storage),
// auto-pilih unit di form, dan submit form.
// ============================================================

import { dataKontrakan, ringkasKontrakan } from "./data.js";

const containerKatalog = document.querySelector(".katalog-grid");
const filterUnit = document.querySelector("#filter-unit");
const hasilFilter = document.querySelector("#hasil-filter");
const ringkasanUnit = document.querySelector("#ringkasan-unit");
const formulir = document.querySelector("#kontak form");
const pilihanPintu = document.querySelector("#pilihan-pintu");
const tombolTema = document.querySelector("#theme-toggle");

// ------------------------------------------------------------------
// Web Storage  — simpan pilihan tema terang/gelap.
// ------------------------------------------------------------------
const kunciTema = "kontrakan-tema-v1";
let tema = "terang";
try {
  tema = localStorage.getItem(kunciTema) === "gelap" ? "gelap" : "terang";
} catch {
  tema = "terang";
}
function terapkanTema() {
  document.documentElement.dataset.theme = tema;
  tombolTema.setAttribute("aria-pressed", String(tema === "gelap"));
  tombolTema.textContent = tema === "gelap" ? "☀️ Tema terang" : "🌙 Tema gelap";
}
terapkanTema();

tombolTema.addEventListener("click", () => {
  tema = tema === "gelap" ? "terang" : "gelap";
  terapkanTema();
  try {
    localStorage.setItem(kunciTema, tema);
  } catch {
    // Tema tetap berubah selama halaman terbuka jika storage tidak tersedia.
  }
});

const formatRupiah = (angka) =>
  new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(angka);

// ------------------------------------------------------------------
//  — render kartu unit 
// ------------------------------------------------------------------
function buatKartuUnit(unit) {
  const idUnit = `pintu-0${unit.id}`;
  const statusKey = unit.status.toLowerCase(); // "tersedia" | "terisi"
  const tersedia = statusKey === "tersedia";

  const li = document.createElement("li");
  li.dataset.status = statusKey;

  const article = document.createElement("article");
  article.className = "unit-card";

  // Figure + badge status + gambar
  const figure = document.createElement("figure");
  const badge = document.createElement("span");
  badge.className = `status-badge status-${statusKey}`;
  badge.textContent = unit.status;
  const gambar = document.createElement("img");
  gambar.src = "assets/images/pintu.jpeg";
  gambar.alt = `Unit ${unit.nama} Kontrakan Pak Sapriadi`;
  figure.append(badge, gambar);

  // Judul
  const judul = document.createElement("h3");
  judul.textContent = unit.nama;

  // Harga
  const harga = document.createElement("p");
  harga.className = "price-tag";
  const hargaStrong = document.createElement("strong");
  hargaStrong.textContent = formatRupiah(unit.harga);
  harga.append(hargaStrong, " / bulan");

  // Daftar fasilitas
  const daftarFasilitas = document.createElement("ul");
  daftarFasilitas.className = "facility-list";
  unit.fasilitas.forEach((f) => {
    const item = document.createElement("li");
    item.textContent = f;
    daftarFasilitas.append(item);
  });

  // Tombol aksi: pesan (kalau tersedia) atau disabled (kalau terisi)
  let tombolAksi;
  if (tersedia) {
    tombolAksi = document.createElement("a");
    tombolAksi.href = "#kontak";
    tombolAksi.className = "btn-secondary";
    tombolAksi.dataset.unit = idUnit;
    tombolAksi.textContent = `Pesan ${unit.nama}`;
  } else {
    tombolAksi = document.createElement("button");
    tombolAksi.type = "button";
    tombolAksi.className = "btn-disabled";
    tombolAksi.disabled = true;
    tombolAksi.textContent = "Terisi Saat Ini";
  }

  article.append(figure, judul, harga, daftarFasilitas, tombolAksi);
  li.append(article);
  return li;
}

function renderKatalog() {
  // 1. Bersihkan wadah catalog agar tidak terjadi penumpukan kartu (duplikasi)
  containerKatalog.textContent = ''; 

  // 2. Render ulang kartu murni dari 3 data utama
  dataKontrakan.forEach((unit) => {
    containerKatalog.append(buatKartuUnit(unit));
  });

}

// ------------------------------------------------------------------
// Dropdown "Pilih Unit Pintu" di form, dibuat dari dataKontrakan juga.
// ------------------------------------------------------------------
function renderPilihanPintu() {
  pilihanPintu.replaceChildren();

  const opsiKosong = document.createElement("option");
  opsiKosong.value = "";
  opsiKosong.textContent = "-- Pilih Unit Pintu --";
  pilihanPintu.append(opsiKosong);

  dataKontrakan.forEach((unit) => {
    const idUnit = `pintu-0${unit.id}`;
    const opsi = document.createElement("option");
    opsi.value = idUnit;
    opsi.textContent = `${unit.nama} (${unit.status})`;
    opsi.dataset.status = unit.status.toLowerCase();
    pilihanPintu.append(opsi);
  });
}

// ------------------------------------------------------------------
// Modul 04 dipakai di sini (Modul 05): panggil ringkasKontrakan()
// lalu tampilkan ke DOM, dengan error handling kalau data bermasalah.
// ------------------------------------------------------------------
function tampilkanRingkasan() {
  try {
    const ringkasan = ringkasKontrakan(dataKontrakan);
    ringkasanUnit.textContent =
      `${ringkasan.jumlahTersedia} dari ${ringkasan.totalUnit} unit tersedia saat ini.`;
  } catch (error) {
    console.error("Gagal memproses data kontrakan:", error.message);
    ringkasanUnit.textContent = "Ringkasan unit belum bisa ditampilkan.";
  }
}

// Render pertama kali saat halaman dibuka.
renderKatalog();
renderPilihanPintu();
tampilkanRingkasan();

// ------------------------------------------------------------------
// Interaksi 1 (event: change) — filter kartu unit & perbarui ringkasan hasil.
// ------------------------------------------------------------------
// — Filter unit
filterUnit.addEventListener("change", () => {
  const statusPilihan = filterUnit.value;
  const kartuUnit = [...containerKatalog.children]; // Ambil semua li di katalog
  let jumlahTampil = 0;

  kartuUnit.forEach((kartu) => {
    const cocok = statusPilihan === "semua" || kartu.dataset.status === statusPilihan;
    kartu.hidden = !cocok;
    if (cocok) jumlahTampil += 1;
  });

  // Tampilkan jumlah unit yang benar
  hasilFilter.textContent = `${jumlahTampil} unit ditampilkan.`;
});

// ------------------------------------------------------------------
// Interaksi 2 & 3 (event: click, pakai EVENT DELEGATION) — kartu dibuat
// dinamis oleh renderKatalog(), jadi listener dipasang di container (parent),
// bukan di tiap tombol satu-satu. Ini contoh event delegation dari Modul 05.
// ------------------------------------------------------------------
containerKatalog.addEventListener("click", (event) => {
  const tombolPesan = event.target.closest(".btn-secondary");
  if (tombolPesan) {
    pilihanPintu.value = tombolPesan.dataset.unit;
  }
});

// ------------------------------------------------------------------
// Interaksi 4 (event: submit) — umpan balik setelah validasi HTML berhasil.
// ------------------------------------------------------------------
const kolomForm = [...formulir.querySelectorAll("input, select, textarea")];
const kolomNama = formulir.querySelector("#nama");
const kolomTelepon = formulir.querySelector("#telepon");
const polaNama = /^[\p{L}]+(?:[ '-][\p{L}]+)*$/u;
const pesanKesalahan = (kolom) => {
  if (kolom.validity.customError) {
    if (kolom.id === "pilihan-pintu") return kolom.validationMessage;
    if (kolom.id === "nama") return "Nama hanya boleh berisi huruf dan pemisah nama, tanpa angka atau simbol lain.";
    if (kolom.id === "telepon") return "Nomor WhatsApp hanya boleh berisi 9 sampai 15 angka.";
  }
  if (kolom.validity.valueMissing) {
    return "Kolom ini wajib diisi.";
  }
  if (kolom.id === "nama" && kolom.validity.patternMismatch) {
    return "Nama hanya boleh berisi huruf, spasi, tanda hubung, atau apostrof.";
  }
  if (kolom.id === "nama" && kolom.validity.tooShort) {
    return "Nama harus terdiri dari minimal 2 karakter.";
  }
  if (kolom.id === "telepon" && (kolom.validity.patternMismatch || kolom.validity.tooShort || kolom.validity.tooLong)) {
    return "Nomor WhatsApp harus terdiri dari 9 sampai 15 angka tanpa spasi atau tanda baca.";
  }
  if (kolom.validity.tooLong) {
    return "Isi kolom ini terlalu panjang.";
  }
  return "Periksa kembali isi kolom ini.";
};

function perbaruiValidasi(kolom) {
  const errorEl = document.getElementById(`${kolom.id}-error`);
  const tidakValid = !kolom.validity.valid;
  kolom.setAttribute("aria-invalid", String(tidakValid));
  errorEl.textContent = tidakValid ? pesanKesalahan(kolom) : "";
  return tidakValid;
}

kolomForm.forEach((kolom) => {
  const hapusStatusLama = () => {
    const statusKirim = formulir.querySelector("#status-kirim");
    if (statusKirim) statusKirim.textContent = "";
  };
  kolom.addEventListener("input", () => {
    hapusStatusLama();
    perbaruiValidasi(kolom);
  });
  kolom.addEventListener("change", () => {
    hapusStatusLama();
    perbaruiValidasi(kolom);
  });
});

function perbaruiAturanKhusus() {
  kolomNama.setCustomValidity(kolomNama.value && !polaNama.test(kolomNama.value)
    ? "Nama hanya boleh berisi huruf dan pemisah nama, tanpa angka atau simbol lain."
    : "");
  kolomTelepon.setCustomValidity(kolomTelepon.value && !/^\d{9,15}$/.test(kolomTelepon.value)
    ? "Nomor WhatsApp hanya boleh berisi 9 sampai 15 angka."
    : "");
  const opsiUnit = pilihanPintu.selectedOptions[0];
  pilihanPintu.setCustomValidity(opsiUnit?.dataset.status === "terisi"
    ? `${opsiUnit.textContent.trim()} sudah terisi. Silakan pilih unit yang tersedia.`
    : "");
}

kolomNama.addEventListener("input", () => {
  perbaruiAturanKhusus();
  perbaruiValidasi(kolomNama);
});
kolomTelepon.addEventListener("input", () => {
  perbaruiAturanKhusus();
  perbaruiValidasi(kolomTelepon);
});
pilihanPintu.addEventListener("change", () => {
  perbaruiAturanKhusus();
  perbaruiValidasi(pilihanPintu);
});

formulir.addEventListener("submit", (event) => {
  event.preventDefault();
  let statusKirim = formulir.querySelector("#status-kirim");
  if (statusKirim) statusKirim.textContent = "";
  // Normalisasi spasi tepi sebelum validasi dan sebelum nilai diringkas.
  formulir.querySelector("#nama").value = formulir.querySelector("#nama").value.trim().replace(/\s+/g, " ");
  formulir.querySelector("#telepon").value = formulir.querySelector("#telepon").value.trim();
  formulir.querySelector("#pesan").value = formulir.querySelector("#pesan").value.trim();
  perbaruiAturanKhusus();
  const kolomTidakValid = kolomForm.filter(perbaruiValidasi);
  if (kolomTidakValid.length > 0) {
    kolomTidakValid[0].focus();
    kolomTidakValid[0].scrollIntoView({ behavior: "smooth", block: "center" });
    return;
  }

  if (!statusKirim) {
    statusKirim = document.createElement("p");
    statusKirim.id = "status-kirim";
    statusKirim.setAttribute("role", "status");
    statusKirim.tabIndex = -1;
    formulir.append(statusKirim);
  }
  const nama = formulir.querySelector("#nama").value;
  const unit = pilihanPintu.selectedOptions[0].textContent.trim();
  statusKirim.textContent = `Ringkasan pengajuan: ${nama}, nomor WhatsApp ${formulir.querySelector("#telepon").value}, pilihan ${unit}. Terima kasih! Pengajuan Anda tercatat di halaman ini. Silakan hubungi pemilik melalui WhatsApp untuk konfirmasi.`;
  formulir.reset();
  kolomNama.setCustomValidity("");
  kolomTelepon.setCustomValidity("");
  kolomForm.forEach((kolom) => {
    kolom.setAttribute("aria-invalid", "false");
    const errorEl = document.getElementById(`${kolom.id}-error`);
    if (errorEl) errorEl.textContent = "";
  });
  statusKirim.focus();
});
