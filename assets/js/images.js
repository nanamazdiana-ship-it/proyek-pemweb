// Jalur gambar milik pengelola (satu tempat agar tidak salah ketik di banyak file).
export const IMG = {
  logo: 'assets/images/logo.png',
  kontrakan: 'assets/images/kontrakan.png',
  pintu: 'assets/images/pintuKontrakan.jpeg',
};

// Jika gambar gagal dimuat, tampilkan foto bangunan sebagai cadangan (sekali saja).
export function fallback(img) {
  img.addEventListener('error', () => {
    img.src = IMG.kontrakan;
  }, { once: true });

  return img;
}