// ============================================================
// app.js — logika bersama + halaman beranda, calon penghuni, admin, penghuni.
// Halaman dipilih lewat atribut <body data-page="...">. Semua konten dari API
// dimasukkan memakai DOM API/textContent (aman dari XSS), bukan innerHTML.
// ============================================================

import { fallback, IMG } from './images.js';

// Alamat API PHP dan kunci penyimpanan tema.
const BASE = 'api/index.php';
const KEY_TEMA = 'kontrakan-tema-v1';

// Menyesuaikan nama properti unit dari API dengan app.js.
function normalisasiUnit(u) {
  return {
    ...u,
    id: u.id_unit,
    nama: u.nama_unit,
    harga: u.harga_sewa,
    status: u.status_unit,
    fasilitas: u.deskripsi,

    // [PERBAIKAN] Menggunakan gambar pintu kontrakan.
    gambar: IMG.pintu
  };
}
/** Panggil API PHP. Melempar Error (dengan .status dan .fields) bila gagal. */
export async function api(route, { method = 'GET', body } = {}) {
  let res;

  try {
    res = await fetch(`${BASE}?r=${route}`, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(method !== 'GET' && { 'Content-Type': 'application/json' })
      },
      body: method !== 'GET' ? JSON.stringify(body ?? {}) : undefined,
    });
  } catch {
    throw new Error('Server tidak dapat dihubungi. Pastikan server PHP berjalan.');
  }

  let json = null;
  try {
    json = await res.json();
  } catch { /* respons bukan JSON */ }

  if (!res.ok) {
    const err = new Error(json?.error || `Server merespons HTTP ${res.status}.`);
    err.status = res.status;
    err.fields = json?.fields || {};
    throw err;
  }

  // [PERBAIKAN] Normalisasi data unit dari API.
  const data = json?.data;

  if (route === 'units' && Array.isArray(data)) {
    return data.map(normalisasiUnit);
  }

  return data;
}

/** Pembuat elemen: h('p', {class:'x'}, 'teks', anak...) */
export const h = (tag, attrs = {}, ...kids) => {
  const n = document.createElement(tag);

  for (const [k, v] of Object.entries(attrs)) {
    if (k.startsWith('on')) n.addEventListener(k.slice(2), v);
    else if (k === 'class') n.className = v;
    else if (v === true) n.setAttribute(k, '');
    else if (v !== false && v != null) n.setAttribute(k, v);
  }

  n.append(...kids.flat().filter((x) => x != null && x !== false));
  return n;
};

// Format harga (Rp) dan tanggal sesuai lokal Indonesia.
export const rupiah = (n) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(n);

export const tgl = (s) => {
  if (!s) return '-';

  const d = s.length <= 10
    ? new Date(`${s}T00:00:00`)
    : new Date(`${s.replace(' ', 'T')}Z`);

  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};

const bulan = (p) =>
  new Date(`${p}-01T00:00:00`).toLocaleDateString('id-ID', {
    month: 'long',
    year: 'numeric'
  });

const badge = (t) =>
  h(
    'span',
    {
      class: `badge ${
        ['Tersedia', 'Lunas', 'Selesai'].includes(t)
          ? 'ok'
          : ['Terisi', 'Terlambat'].includes(t)
            ? 'warn'
            : ''
      }`
    },
    t
  );

// Pesan singkat (toast) untuk hasil aksi.
let timerToast;

export function pesan(teks, galat = false) {
  const el = document.querySelector('#toast');
  if (!el) return;

  el.textContent = teks;
  el.dataset.type = galat ? 'error' : 'ok';
  el.hidden = false;

  clearTimeout(timerToast);
  timerToast = setTimeout(() => {
    el.hidden = true;
  }, 4500);
}

/** Tampilkan status memuat / hasil / kosong / gagal (dengan tombol coba lagi) pada sebuah wadah. */
async function muat(box, render) {
  box.replaceChildren(
    h('p', { class: 'state', role: 'status' }, 'Memuat data…')
  );

  try {
    box.replaceChildren(await render());
  } catch (e) {
    if (e.status === 401) {
      location.replace('login.html');
      return;
    }

    box.replaceChildren(
      h(
        'p',
        { class: 'state error', role: 'alert' },
        `${e.message} `,
        h(
          'button',
          {
            class: 'btn btn-ghost btn-sm',
            type: 'button',
            onclick: () => muat(box, render)
          },
          'Coba lagi'
        )
      )
    );
  }
}

/** Tabel; pada layar kecil CSS mengubahnya menjadi kartu (atribut data-label). */
function tabel(kolom, baris, kosong) {
  if (!baris.length) return h('p', { class: 'state' }, kosong);

  return h(
    'figure',
    { class: 'table-wrap' },
    h(
      'table',
      {},
      h(
        'thead',
        {},
        h(
          'tr',
          {},
          kolom.map((k) => h('th', { scope: 'col' }, k.j))
        )
      ),
      h(
        'tbody',
        {},
        baris.map((r) =>
          h(
            'tr',
            {},
            kolom.map((k) => h('td', { 'data-label': k.j }, k.f(r)))
          )
        )
      )
    )
  );
}

// ---------- Tema (preferensi disimpan di localStorage) ----------
function tema() {
  const btn = document.querySelector('#theme-toggle');
  let t = 'terang';

  try {
    if (localStorage.getItem(KEY_TEMA) === 'gelap') t = 'gelap';
  } catch { /* storage diblokir */ }

  const set = () => {
    document.documentElement.dataset.theme = t;

    if (btn) {
      btn.setAttribute('aria-pressed', String(t === 'gelap'));
      btn.textContent = t === 'gelap' ? '☀ Tema terang' : '☾ Tema gelap';
    }
  };

  set();

  btn?.addEventListener('click', () => {
    t = t === 'gelap' ? 'terang' : 'gelap';
    set();

    try {
      localStorage.setItem(KEY_TEMA, t);
    } catch { /* abaikan */ }
  });
}

// ---------- Akses halaman terlindungi ----------
async function butuhLogin(role) {
  try {
    const me = await api('auth/me');

    if (me.role !== role) {
      location.replace(me.role === 'admin' ? 'admin.html' : 'penghuni.html');
      return null;
    }

    document.querySelector('#nama-user').textContent = me.nama;

    document.querySelector('#btn-keluar').addEventListener('click', async () => {
      try {
        await api('auth/logout', { method: 'POST' });
      } finally {
        location.href = 'login.html';
      }
    });

    return me;
  } catch (e) {
    if (e.status === 401) {
      location.replace('login.html');
      return null;
    }

    document.querySelector('main').prepend(
      h('p', { class: 'state error', role: 'alert' }, e.message)
    );

    return null;
  }
}

// ---------- Kartu unit (dipakai beranda & calon penghuni) ----------
const kartuUnit = (u) =>
  h(
    'li',
    {},
    h(
      'article',
      { class: 'card unit-card' },
      h(
        'figure',
        {},
        fallback(
          h('img', {
            src: u.gambar,
            alt: `Pintu kontrakan ${u.nama}`,
            loading: 'lazy'
          })
        ),
        badge(u.status)
      ),
      h(
        'section',
        { class: 'card-body' },
        h('h3', {}, u.nama),
        h('p', { class: 'price' }, `${rupiah(u.harga)} / bulan`),
        h('p', { class: 'muted' }, u.fasilitas)
      )
    )
  );

async function daftarUnit() {
  const box = document.querySelector('#unit-list');
  const ringkas = document.querySelector('#unit-summary');
  const pilih = document.querySelector('#pilihan-unit');

  await muat(box, async () => {
    const units = await api('units');

    if (ringkas) {
      ringkas.textContent =
        `${units.length} unit · ` +
        `${units.filter((u) => u.status === 'Tersedia').length} tersedia · ` +
        `${units.filter((u) => u.status === 'Terisi').length} terisi`;
    }

    if (pilih) {
      // Unit terisi tampil tetapi tidak dapat dipilih.
      pilih.replaceChildren(new Option('Pilih unit kontrakan', ''));

      units.forEach((u) => {
        const o = new Option(`${u.nama} — ${u.status}`, u.id);
        o.disabled = u.status === 'Terisi';
        pilih.add(o);
      });
    }

    return units.length
      ? h('ul', { class: 'grid-units' }, units.map(kartuUnit))
      : h('p', { class: 'state' }, 'Belum ada unit yang terdaftar.');
  });
}

// ---------- Formulir minat sewa (calon penghuni) ----------
function formMinat() {
  const f = document.querySelector('#form-minat');
  if (!f) return;

  const hasil = document.querySelector('#hasil-minat');

  // [id input, kunci error dari server, fungsi validasi]
  const aturan = [
    [
      'nama',
      'nama',
      (v) =>
        (/^\p{L}+(?:[ '-]\p{L}+)*$/u.test(v) &&
          v.length >= 2 &&
          v.length <= 80
          ? ''
          : 'Nama 2–80 karakter, hanya huruf, spasi, apostrof, atau tanda hubung.')
    ],
    [
      'telepon',
      'telepon',
      (v) =>
        (/^\d{9,15}$/.test(v)
          ? ''
          : 'Nomor WhatsApp terdiri dari 9–15 angka.')
    ],
    [
      'pilihan-unit',
      'unit_id',
      (v) => (v ? '' : 'Pilih unit yang tersedia.')
    ],
    [
      'pesan',
      'pesan',
      (v) => (v.length > 1000 ? 'Pesan maksimal 1.000 karakter.' : '')
    ],
  ];

  const tampil = (id, teks) => {
    f.querySelector(`#${id}-error`).textContent = teks;
    f.querySelector(`#${id}`).setAttribute('aria-invalid', String(Boolean(teks)));
  };

  f.addEventListener('submit', async (e) => {
    e.preventDefault();
    hasil.replaceChildren();

    const nilai = Object.fromEntries(
      aturan.map(([id]) => [id, f.querySelector(`#${id}`).value.trim()])
    );

    let pertama = null;

    aturan.forEach(([id, , cek]) => {
      const m = cek(nilai[id]);
      tampil(id, m);
      if (m && !pertama) pertama = id;
    });

    if (pertama) {
      f.querySelector(`#${pertama}`).focus();
      return;
    }

    const tombol = f.querySelector('button[type=submit]');
    tombol.disabled = true;

    try {
      const r = await api('inquiries', {
        method: 'POST',
        body: {
          nama: nilai.nama,
          telepon: nilai.telepon,
          unit_id: nilai['pilihan-unit'],
          pesan: nilai.pesan
        }
      });

      f.reset();

      hasil.replaceChildren(
        h(
          'section',
          { class: 'notice', role: 'status' },
          h(
            'p',
            {},
            r.wa_url
              ? 'Pengajuan tersimpan. Lanjutkan ke WhatsApp untuk menghubungi pemilik.'
              : 'Pengajuan tersimpan dan dapat dilihat pemilik di dashboard. Nomor WhatsApp pemilik belum diatur, jadi tombol WhatsApp belum tersedia.'
          ),
          r.wa_url &&
            h(
              'a',
              {
                class: 'btn btn-primary',
                href: r.wa_url,
                target: '_blank',
                rel: 'noopener'
              },
              'Lanjut ke WhatsApp'
            )
        )
      );

      daftarUnit();
    } catch (err) {
      aturan.forEach(([id, kunci]) => {
        if (err.fields?.[kunci]) tampil(id, err.fields[kunci]);
      });

      hasil.replaceChildren(
        h('p', { class: 'notice error', role: 'alert' }, err.message)
      );
    } finally {
      tombol.disabled = false;
    }
  });
}

// ---------- Dashboard admin ----------
async function halamanAdmin() {
  if (!await butuhLogin('admin')) return;

  const panel = {
    // Panel Ringkasan: kartu statistik dari endpoint stats.
    ringkasan: async () => {
      const s = await api('stats');

      const kartu = (judul, nilai, ket, aksen) =>
        h(
          'article',
          { class: `card card-pad stat${aksen ? ' accent' : ''}` },
          h('p', { class: 'muted' }, judul),
          h('strong', {}, String(nilai)),
          h('small', { class: 'muted' }, ket)
        );

      return h(
        'section',
        { class: 'stats' },
        kartu('Unit', s.unit, `${s.tersedia} tersedia · ${s.terisi} terisi`),
        kartu('Penghuni terdaftar', s.penghuni, 'Akun dengan peran penghuni'),
        kartu('Tagihan belum dibayar', s.tagihan_belum, rupiah(s.tagihan_belum_total), true),
        kartu('Laporan baru', s.laporan_baru, 'Menunggu ditindaklanjuti', true),
        kartu('Minat sewa', s.minat, 'Pengajuan calon penghuni')
      );
    },

    // Panel Unit: dropdown status langsung menyimpan ke API.
    unit: async () => {
      const units = await api('units');

      return tabel(
        [
          { j: 'Unit', f: (u) => u.nama },
          { j: 'Harga', f: (u) => `${rupiah(u.harga)} / bln` },
          { j: 'Fasilitas', f: (u) => u.fasilitas },
          {
            j: 'Status',
            f: (u) =>
              h(
                'select',
                {
                  class: 'inline-select',
                  'aria-label': `Status ${u.nama}`,
                  onchange: async (e) => {
                    try {
                      await api(`units/${u.id}`, {
                        method: 'PUT',
                        body: { status_unit: e.target.value }
                      });

                      pesan(`${u.nama} kini ${e.target.value}.`);
                    } catch (er) {
                      pesan(er.message, true);
                      e.target.value = u.status;
                    }
                  }
                },
                ['Tersedia', 'Terisi'].map((s) =>
                  h('option', { value: s, selected: s === u.status }, s)
                )
              )
          },
        ],
        units,
        'Belum ada unit.'
      );
    },

    // Panel Penghuni: dropdown unit; status unit disesuaikan otomatis oleh server.
    penghuni: async () => {
      const [users, units] = await Promise.all([
        api('users'),
        api('units')
      ]);

      return tabel(
        [
          { j: 'Nama', f: (u) => u.nama },
          { j: 'Email', f: (u) => u.email },
          { j: 'WhatsApp', f: (u) => u.telepon || '-' },
          {
            j: 'Unit yang dihuni',
            f: (u) =>
              h(
                'select',
                {
                  class: 'inline-select',
                  'aria-label': `Unit ${u.nama}`,
                  onchange: async (e) => {
                    try {
                      await api(`users/${u.id}`, {
                        method: 'PUT',
                        body: { unit_id: e.target.value || null }
                      });

                      pesan(`Unit ${u.nama} diperbarui.`);
                    } catch (er) {
                      pesan(er.message, true);
                    }
                  }
                },
                h('option', { value: '' }, '— Belum ada unit —'),
                units.map((n) =>
                  h(
                    'option',
                    {
                      value: n.id,
                      selected: n.id === u.unit_id
                    },
                    n.nama
                  )
                )
              )
          },
        ],
        users,
        'Belum ada penghuni terdaftar. Penghuni mendaftar lewat halaman Daftar.'
      );
    },

    // Panel Pembayaran: tombol terbit tagihan dan tandai lunas.
    pembayaran: async (muatUlang) => {
      const rows = await api('payments');

      const terbit = h(
        'button',
        {
          class: 'btn btn-primary',
          type: 'button',
          onclick: async () => {
            try {
              const r = await api('payments/generate', {
                method: 'POST',
                body: {}
              });

              pesan(`${r.dibuat} tagihan baru untuk ${bulan(r.periode)}.`);
              muatUlang();
            } catch (er) {
              pesan(er.message, true);
            }
          }
        },
        'Terbitkan tagihan bulan ini'
      );

      return h(
        'section',
        {},
        h(
          'p',
          { class: 'toolbar' },
          terbit,
          h(
            'span',
            { class: 'hint' },
            'Terbit tgl 1, jatuh tempo tgl 10, tanpa denda. Hanya untuk penghuni yang sudah punya unit.'
          )
        ),
        tabel(
          [
            { j: 'Periode', f: (p) => bulan(p.periode) },
            { j: 'Penghuni', f: (p) => p.penghuni },
            { j: 'Unit', f: (p) => p.unit || '-' },
            { j: 'Jumlah', f: (p) => rupiah(p.jumlah) },
            { j: 'Jatuh tempo', f: (p) => tgl(p.jatuh_tempo) },
            {
              j: 'Status',
              f: (p) =>
                badge(
                  p.status === 'Lunas'
                    ? 'Lunas'
                    : p.terlambat
                      ? 'Terlambat'
                      : 'Belum dibayar'
                )
            },
            {
              j: 'Aksi',
              f: (p) =>
                p.status === 'Lunas'
                  ? tgl(p.dibayar_pada)
                  : h(
                      'button',
                      {
                        class: 'btn btn-ghost btn-sm',
                        type: 'button',
                        onclick: async () => {
                          try {
                            await api(`payments/${p.id}`, {
                              method: 'PUT',
                              body: {}
                            });

                            pesan('Tagihan ditandai lunas.');
                            muatUlang();
                          } catch (er) {
                            pesan(er.message, true);
                          }
                        }
                      },
                      'Tandai lunas'
                    )
            },
          ],
          rows,
          'Belum ada tagihan. Tetapkan unit untuk penghuni, lalu terbitkan tagihan.'
        )
      );
    },

    // Panel Laporan: admin mengubah status Baru/Diproses/Selesai.
    laporan: async () =>
      tabel(
        [
          { j: 'Tanggal', f: (r) => tgl(r.created_at) },
          { j: 'Penghuni', f: (r) => r.penghuni },
          { j: 'Judul', f: (r) => r.judul },
          { j: 'Isi', f: (r) => r.isi },
          {
            j: 'Status',
            f: (r) =>
              h(
                'select',
                {
                  class: 'inline-select',
                  'aria-label': `Status laporan ${r.judul}`,
                  onchange: async (e) => {
                    try {
                      await api(`reports/${r.id}`, {
                        method: 'PUT',
                        body: { status: e.target.value }
                      });

                      pesan('Status laporan diperbarui.');
                    } catch (er) {
                      pesan(er.message, true);
                    }
                  }
                },
                ['Baru', 'Diproses', 'Selesai'].map((s) =>
                  h('option', { value: s, selected: s === r.status }, s)
                )
              )
          },
        ],
        await api('reports'),
        'Belum ada laporan dari penghuni.'
      ),

    // Panel Minat sewa: daftar pengajuan calon penghuni (hanya baca).
    minat: async () =>
      tabel(
        [
          { j: 'Tanggal', f: (i) => tgl(i.created_at) },
          { j: 'Nama', f: (i) => i.nama },
          { j: 'WhatsApp', f: (i) => i.telepon },
          { j: 'Unit', f: (i) => i.unit || '-' },
          { j: 'Pesan', f: (i) => i.pesan || '-' },
        ],
        await api('inquiries'),
        'Belum ada pengajuan minat sewa.'
      ),
  };

  // Pindah panel: tampilkan satu panel, muat datanya, sembunyikan yang lain.
  const tombol = [...document.querySelectorAll('[data-panel-btn]')];

  const tampil = (nama) => {
    tombol.forEach((b) =>
      b.setAttribute('aria-current', String(b.dataset.panelBtn === nama))
    );

    document.querySelectorAll('[data-panel]').forEach((p) => {
      p.hidden = p.dataset.panel !== nama;
    });

    const box = document.querySelector(`[data-panel="${nama}"] .panel-body`);
    const jalan = () => muat(box, () => panel[nama](jalan));

    jalan();
  };

  tombol.forEach((b) =>
    b.addEventListener('click', () => tampil(b.dataset.panelBtn))
  );

  tampil('ringkasan');
}

// ---------- Dashboard penghuni ----------
async function halamanPenghuni() {
  const me = await butuhLogin('penghuni');
  if (!me) return;

  await muat(document.querySelector('#info-unit'), async () => {
    const u = me.unit;

    if (!u) {
      return h(
        'p',
        { class: 'notice' },
        'Akun Anda belum dihubungkan ke unit. Hubungi pemilik agar unit Anda ditetapkan.'
      );
    }

    return h(
      'article',
      { class: 'card unit-card' },
      h(
        'figure',
        {},
        fallback(
          h('img', {
            src: u.gambar,
            alt: `Pintu kontrakan ${u.nama}`
          })
        )
      ),
      h(
        'section',
        { class: 'card-body' },
        h('h3', {}, u.nama),
        h('p', { class: 'price' }, `${rupiah(u.harga)} / bulan`),
        h('p', { class: 'muted' }, u.fasilitas),
        badge(u.status)
      )
    );
  });

  await muat(
    document.querySelector('#riwayat-bayar'),
    async () =>
      tabel(
        [
          { j: 'Periode', f: (p) => bulan(p.periode) },
          { j: 'Jumlah', f: (p) => rupiah(p.jumlah) },
          { j: 'Jatuh tempo', f: (p) => tgl(p.jatuh_tempo) },
          {
            j: 'Status',
            f: (p) =>
              badge(
                p.status === 'Lunas'
                  ? 'Lunas'
                  : p.terlambat
                    ? 'Terlambat'
                    : 'Belum dibayar'
              )
          },
        ],
        await api('payments'),
        'Belum ada tagihan. Tagihan diterbitkan pemilik setiap tanggal 1.'
      )
  );

  // Muat ulang daftar laporan setelah laporan baru dikirim.
  const daftarLaporan = () =>
    muat(document.querySelector('#daftar-laporan'), async () =>
      tabel(
        [
          { j: 'Tanggal', f: (r) => tgl(r.created_at) },
          { j: 'Judul', f: (r) => r.judul },
          { j: 'Isi', f: (r) => r.isi },
          { j: 'Status', f: (r) => badge(r.status) },
        ],
        await api('reports'),
        'Belum ada laporan yang Anda kirim.'
      )
    );

  daftarLaporan();

  // Kirim laporan baru; validasi di browser, error server ditampilkan per kolom.
  const f = document.querySelector('#form-laporan');

  f.addEventListener('submit', async (e) => {
    e.preventDefault();

    const data = {
      judul: f.judul.value.trim(),
      isi: f.isi.value.trim()
    };

    f.querySelector('#judul-error').textContent =
      data.judul.length < 3 ? 'Judul minimal 3 karakter.' : '';

    f.querySelector('#isi-error').textContent =
      data.isi.length < 5 ? 'Isi laporan minimal 5 karakter.' : '';

    if (f.querySelector('.field-error:not(:empty)')) return;

    try {
      await api('reports', {
        method: 'POST',
        body: data
      });

      f.reset();
      pesan('Laporan terkirim.');
      daftarLaporan();
    } catch (er) {
      for (const [k, v] of Object.entries(er.fields || {})) {
        f.querySelector(`#${k}-error`).textContent = v;
      }

      pesan(er.message, true);
    }
  });
}

// ---------- Pemilihan halaman ----------
tema();

const halaman = document.body.dataset.page;

if (halaman === 'beranda' || halaman === 'calon') {
  daftarUnit();
  formMinat();
}

if (halaman === 'admin') halamanAdmin();
if (halaman === 'penghuni') halamanPenghuni();