<?php
declare(strict_types=1);

/**
 * ============================================================
 * REST API SISTEM MANAJEMEN KONTRAKAN PAK SAPRIADI
 * ============================================================
 * Format pemanggilan:
 * api/index.php?r=<resource>[/<id>]
 *
 * Database: kontrakan_pak_sapriadi
 * Koneksi: PDO MySQL
 * ============================================================
 */

use App\Db;
use App\Env;
use App\Http;

// ============================================================
// 1. MEMUAT AUTOLOADER
// ============================================================

$root = dirname(__DIR__);

// Menggunakan autoloader Composer jika tersedia.
if (is_file("$root/vendor/autoload.php")) {
    require "$root/vendor/autoload.php";
} else {
    // Autoloader sederhana jika Composer belum dijalankan.
    spl_autoload_register(function (string $c) use ($root): void {
        if (
            str_starts_with($c, 'App\\')
            && is_file($f = "$root/src/" . substr($c, 4) . '.php')
        ) {
            require $f;
        }
    });
}

// ============================================================
// 2. MEMUAT KONFIGURASI DAN MENYIAPKAN HTTP
// ============================================================

Env::load("$root/.env");
Http::boot();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$parts = array_values(array_filter(
    explode('/', trim((string) ($_GET['r'] ?? ''), '/'))
));

$res = $parts[0] ?? '';
$id = $parts[1] ?? null;

// ============================================================
// 3. KONEKSI DATABASE DAN FUNGSI BANTU QUERY
// ============================================================

try {
    // Menggunakan koneksi PDO MySQL dari Db.php.
    $db = Db::conn();

    // Menjalankan query dengan prepared statement.
    $q = static function (string $sql, array $params = []) use ($db) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    };

    $today = date('Y-m-d');

    // ========================================================
    // 4. ROUTING API
    // ========================================================

    switch ($res) {

        // ====================================================
        // AUTENTIKASI: LOGIN, REGISTER, LOGOUT, PROFIL
        // ====================================================
        case 'auth':

            // ------------------------------------------------
            // LOGIN
            // POST: ?r=auth/login
            // ------------------------------------------------
            if ($id === 'login' && $method === 'POST') {
                $b = Http::body();

                $email = strtolower(Http::str($b, 'email'));
                $pass = Http::str($b, 'password');

                $u = $q(
                    'SELECT id_user, nama, email, password, role
                     FROM users
                     WHERE email = ?',
                    [$email]
                )->fetch();

                if (!$u || !password_verify($pass, $u['password'])) {
                    Http::fail(401, 'Email atau kata sandi salah.');
                }

                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $u['id_user'];

                Http::ok([
                    'id' => (int) $u['id_user'],
                    'nama' => $u['nama'],
                    'email' => $u['email'],
                    'role' => $u['role'],
                ]);
            }

            // ------------------------------------------------
            // REGISTER CALON PENGHUNI
            // POST: ?r=auth/register
            // ------------------------------------------------
            if ($id === 'register' && $method === 'POST') {
                $b = Http::body();

                $nama = Http::str($b, 'nama');
                $email = strtolower(Http::str($b, 'email'));
                $pass = Http::str($b, 'password');

                $err = array_filter([
                    'nama' => Http::nama($nama),

                    'email' => filter_var(
                        $email,
                        FILTER_VALIDATE_EMAIL
                    ) ? '' : 'Format email tidak valid.',

                    'password' => strlen($pass) >= 8
                        ? ''
                        : 'Kata sandi minimal 8 karakter.',
                ]);

                if ($err) {
                    Http::fail(
                        422,
                        'Periksa kembali data yang diisi.',
                        $err
                    );
                }

                $existing = $q(
                    'SELECT id_user FROM users WHERE email = ?',
                    [$email]
                )->fetch();

                if ($existing) {
                    Http::fail(
                        409,
                        'Email sudah terdaftar.',
                        ['email' => 'Email sudah digunakan.']
                    );
                }

                // Pendaftaran publik hanya membuat akun calon penghuni.
                $q(
                    "INSERT INTO users (nama, email, password, role)
                     VALUES (?, ?, ?, 'calon_penghuni')",
                    [
                        $nama,
                        $email,
                        password_hash($pass, PASSWORD_DEFAULT),
                    ]
                );

                $newId = (int) $db->lastInsertId();

                session_regenerate_id(true);
                $_SESSION['uid'] = $newId;

                Http::ok([
                    'id' => $newId,
                    'nama' => $nama,
                    'email' => $email,
                    'role' => 'calon_penghuni',
                ], 201);
            }

            // ------------------------------------------------
            // LOGOUT
            // POST: ?r=auth/logout
            // ------------------------------------------------
            if ($id === 'logout' && $method === 'POST') {
                $_SESSION = [];

                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();

                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }

                session_destroy();

                Http::ok(['keluar' => true]);
            }

            // ------------------------------------------------
            // PROFIL PENGGUNA LOGIN
            // GET: ?r=auth/me
            // ------------------------------------------------
            if ($id === 'me' && $method === 'GET') {
                $u = Http::need();

                // Mengambil informasi penghuni jika sudah terdaftar.
                $tenant = $q(
                    "SELECT
                        p.id_penghuni,
                        p.tanggal_mulai,
                        p.tanggal_selesai,
                        p.status_penghuni,
                        n.id_unit,
                        n.nama_unit,
                        n.harga_sewa
                     FROM penghuni p
                     JOIN unit_kontrakan n
                        ON n.id_unit = p.id_unit
                     WHERE p.id_user = ?
                       AND p.status_penghuni = 'Aktif'
                     LIMIT 1",
                    [$u['id']]
                )->fetch();

                $u['penghuni'] = $tenant ?: null;

                Http::ok($u);
            }

            break;

        // ====================================================
        // UNIT KONTRAKAN
        // ====================================================
        case 'units':

            // ------------------------------------------------
            // MENAMPILKAN SEMUA UNIT
            // GET: ?r=units
            // ------------------------------------------------
            if ($method === 'GET') {
                Http::ok(
                    $q(
                        'SELECT
                            id_unit,
                            nama_unit,
                            harga_sewa,
                            status_unit,
                            deskripsi
                         FROM unit_kontrakan
                         ORDER BY id_unit'
                    )->fetchAll()
                );
            }

            // ------------------------------------------------
            // MENGUBAH STATUS UNIT
            // PUT: ?r=units/{id}
            // ------------------------------------------------
            if ($method === 'PUT' && $id !== null) {
                Http::need('admin');

                $b = Http::body();
                $status = Http::str($b, 'status_unit');

                if (!in_array(
                    $status,
                    ['Tersedia', 'Terisi'],
                    true
                )) {
                    Http::fail(
                        422,
                        'Status unit harus Tersedia atau Terisi.'
                    );
                }

                $unit = $q(
                    'SELECT id_unit FROM unit_kontrakan WHERE id_unit = ?',
                    [(int) $id]
                )->fetch();

                if (!$unit) {
                    Http::fail(404, 'Unit tidak ditemukan.');
                }

                $q(
                    'UPDATE unit_kontrakan
                     SET status_unit = ?
                     WHERE id_unit = ?',
                    [$status, (int) $id]
                );

                Http::ok([
                    'id_unit' => (int) $id,
                    'status_unit' => $status,
                ]);
            }

            break;

        // ====================================================
        // DATA PENGGUNA / PENGHUNI
        // ====================================================
        case 'users':

            Http::need('admin');

            // ------------------------------------------------
            // MENAMPILKAN DAFTAR PENGGUNA
            // GET: ?r=users
            // ------------------------------------------------
            if ($method === 'GET') {
            Http::ok(
                $q(
                    'SELECT
                        u.id_user,
                        u.nama,
                        u.email,
                        u.role,
                        p.id_penghuni,
                        p.id_unit,
                        p.tanggal_mulai,
                        p.tanggal_selesai,
                        p.status_penghuni
                    FROM users u
                    LEFT JOIN penghuni p
                        ON p.id_user = u.id_user
                        AND p.status_penghuni = ?
                    WHERE u.role = ?
                    ORDER BY u.id_user DESC',
                    ['Aktif', 'penghuni']
                )->fetchAll()
            );
        }

            break;

        // ====================================================
        // PENGAJUAN SEWA / MINAT SEWA
        // ====================================================
        case 'inquiries':

            // ------------------------------------------------
            // CALON PENGHUNI MENGAJUKAN SEWA
            // POST: ?r=inquiries
            // ------------------------------------------------
            if ($method === 'POST') {
                $u = Http::need();

                if ($u['role'] !== 'calon_penghuni') {
                    Http::fail(
                        403,
                        'Hanya calon penghuni yang dapat mengajukan sewa.'
                    );
                }

                $b = Http::body();

                $unitId = (int) Http::str($b, 'id_unit');
                $catatan = Http::str($b, 'catatan');

                $unit = $q(
                    'SELECT id_unit, nama_unit, status_unit
                     FROM unit_kontrakan
                     WHERE id_unit = ?',
                    [$unitId]
                )->fetch();

                $err = [];

                if (!$unit) {
                    $err['id_unit'] = 'Unit tidak ditemukan.';
                } elseif ($unit['status_unit'] !== 'Tersedia') {
                    $err['id_unit'] = 'Unit sudah terisi. Silakan pilih unit lain.';
                }

                if (mb_strlen($catatan) > 1000) {
                    $err['catatan'] = 'Catatan maksimal 1.000 karakter.';
                }

                if ($err) {
                    Http::fail(
                        422,
                        'Periksa kembali pengajuan sewa.',
                        $err
                    );
                }

                // Mencegah pengajuan aktif berulang untuk unit yang sama.
                $pending = $q(
                    "SELECT id_pengajuan
                     FROM pengajuan_sewa
                     WHERE id_user = ?
                       AND id_unit = ?
                       AND status_pengajuan = 'Menunggu'",
                    [$u['id'], $unitId]
                )->fetch();

                if ($pending) {
                    Http::fail(
                        409,
                        'Pengajuan untuk unit tersebut masih menunggu keputusan.'
                    );
                }

                $q(
                    "INSERT INTO pengajuan_sewa
                    (id_user, id_unit, tanggal_pengajuan, catatan, status_pengajuan)
                    VALUES (?, ?, ?, ?, 'Menunggu')",
                    [
                        $u['id'],
                        $unitId,
                        $today,
                        $catatan,
                    ]
                );

                Http::ok([
                    'id_pengajuan' => (int) $db->lastInsertId(),
                    'status_pengajuan' => 'Menunggu',
                ], 201);
            }

            // ------------------------------------------------
            // ADMIN MELIHAT SEMUA PENGAJUAN
            // GET: ?r=inquiries
            // ------------------------------------------------
            if ($method === 'GET') {
                Http::need('admin');

                Http::ok(
                    $q(
                        'SELECT
                            a.id_pengajuan,
                            a.id_user,
                            u.nama,
                            u.email,
                            a.id_unit,
                            n.nama_unit,
                            n.harga_sewa,
                            a.tanggal_pengajuan,
                            a.catatan,
                            a.status_pengajuan
                         FROM pengajuan_sewa a
                         JOIN users u ON u.id_user = a.id_user
                         JOIN unit_kontrakan n ON n.id_unit = a.id_unit
                         ORDER BY a.id_pengajuan DESC'
                    )->fetchAll()
                );
            }

            // ------------------------------------------------
            // ADMIN MENYETUJUI ATAU MENOLAK PENGAJUAN
            // PUT: ?r=inquiries/{id}
            // Body: {"status_pengajuan":"Disetujui"}
            // ------------------------------------------------
            if ($method === 'PUT' && $id !== null) {
                Http::need('admin');

                $b = Http::body();
                $status = Http::str($b, 'status_pengajuan');

                if (!in_array(
                    $status,
                    ['Disetujui', 'Ditolak'],
                    true
                )) {
                    Http::fail(
                        422,
                        'Status pengajuan harus Disetujui atau Ditolak.'
                    );
                }

                $db->beginTransaction();

                try {
                    $application = $q(
                        'SELECT *
                         FROM pengajuan_sewa
                         WHERE id_pengajuan = ?
                         FOR UPDATE',
                        [(int) $id]
                    )->fetch();

                    if (!$application) {
                        $db->rollBack();
                        Http::fail(404, 'Pengajuan tidak ditemukan.');
                    }

                    if ($application['status_pengajuan'] !== 'Menunggu') {
                        $db->rollBack();
                        Http::fail(
                            409,
                            'Pengajuan ini sudah diproses sebelumnya.'
                        );
                    }

                    if ($status === 'Disetujui') {
                        $unit = $q(
                            'SELECT *
                             FROM unit_kontrakan
                             WHERE id_unit = ?
                             FOR UPDATE',
                            [$application['id_unit']]
                        )->fetch();

                        if (!$unit || $unit['status_unit'] !== 'Tersedia') {
                            $db->rollBack();
                            Http::fail(
                                409,
                                'Unit sudah tidak tersedia.'
                            );
                        }

                        // Mengubah status akun menjadi penghuni.
                        $q(
                            "UPDATE users
                             SET role = 'penghuni'
                             WHERE id_user = ?",
                            [$application['id_user']]
                        );

                        // Membuat data penghuni baru.
                        $q(
                            "INSERT INTO penghuni
                            (id_user, id_unit, id_pengajuan, tanggal_mulai, status_penghuni)
                            VALUES (?, ?, ?, ?, 'Aktif')",
                            [
                                $application['id_user'],
                                $application['id_unit'],
                                $application['id_pengajuan'],
                                $today,
                            ]
                        );

                        // Mengubah status unit menjadi terisi.
                        $q(
                            "UPDATE unit_kontrakan
                             SET status_unit = 'Terisi'
                             WHERE id_unit = ?",
                            [$application['id_unit']]
                        );
                    }

                    // Memperbarui status pengajuan.
                    $q(
                        'UPDATE pengajuan_sewa
                         SET status_pengajuan = ?
                         WHERE id_pengajuan = ?',
                        [$status, (int) $id]
                    );

                    $db->commit();

                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }

                    throw $e;
                }

                Http::ok([
                    'id_pengajuan' => (int) $id,
                    'status_pengajuan' => $status,
                ]);
            }

            break;

        // ====================================================
        // PEMBAYARAN
        // ====================================================
        case 'payments':

            $u = Http::need();

            // ------------------------------------------------
            // MELIHAT DATA PEMBAYARAN
            // GET: ?r=payments
            // ------------------------------------------------
            if ($method === 'GET') {
                $sql = 'SELECT
                            b.id_pembayaran,
                            b.id_penghuni,
                            u.nama AS nama_penghuni,
                            n.nama_unit,
                            b.tanggal_bayar,
                            b.jumlah_bayar,
                            b.metode_pembayaran,
                            b.status_pembayaran
                        FROM pembayaran b
                        JOIN penghuni p
                            ON p.id_penghuni = b.id_penghuni
                        JOIN users u
                            ON u.id_user = p.id_user
                        JOIN unit_kontrakan n
                            ON n.id_unit = p.id_unit';

                if ($u['role'] === 'admin') {
                    Http::ok(
                        $q(
                            "$sql ORDER BY b.id_pembayaran DESC"
                        )->fetchAll()
                    );
                }

                if ($u['role'] !== 'penghuni') {
                    Http::fail(403, 'Anda tidak memiliki akses.');
                }

                Http::ok(
                    $q(
                        "$sql
                         WHERE p.id_user = ?
                         ORDER BY b.id_pembayaran DESC",
                        [$u['id']]
                    )->fetchAll()
                );
            }

            // ------------------------------------------------
            // ADMIN MEMBUAT DATA PEMBAYARAN
            // POST: ?r=payments
            // ------------------------------------------------
            if ($method === 'POST') {
                Http::need('admin');

                $b = Http::body();

                $tenantId = (int) Http::str($b, 'id_penghuni');
                $amount = Http::str($b, 'jumlah_bayar');

                $err = [];

                if ($tenantId < 1) {
                    $err['id_penghuni'] = 'Pilih penghuni yang valid.';
                }

                if (!is_numeric($amount) || (float) $amount <= 0) {
                    $err['jumlah_bayar'] = 'Jumlah pembayaran harus lebih dari nol.';
                }

                if ($err) {
                    Http::fail(422, 'Data pembayaran tidak valid.', $err);
                }

                $tenant = $q(
                    "SELECT id_penghuni
                     FROM penghuni
                     WHERE id_penghuni = ?
                       AND status_penghuni = 'Aktif'",
                    [$tenantId]
                )->fetch();

                if (!$tenant) {
                    Http::fail(404, 'Penghuni aktif tidak ditemukan.');
                }

                $methodPayment = Http::str($b, 'metode_pembayaran');

                $q(
                    "INSERT INTO pembayaran
                    (id_penghuni, jumlah_bayar, metode_pembayaran, status_pembayaran)
                    VALUES (?, ?, ?, 'Belum Dibayar')",
                    [
                        $tenantId,
                        (float) $amount,
                        $methodPayment !== '' ? $methodPayment : null,
                    ]
                );

                Http::ok([
                    'id_pembayaran' => (int) $db->lastInsertId(),
                    'status_pembayaran' => 'Belum Dibayar',
                ], 201);
            }

            // ------------------------------------------------
            // ADMIN MENANDAI PEMBAYARAN LUNAS
            // PUT: ?r=payments/{id}
            // ------------------------------------------------
            if ($method === 'PUT' && $id !== null) {
                Http::need('admin');

                $b = Http::body();
                $status = Http::str($b, 'status_pembayaran');

                if ($status !== 'Lunas') {
                    Http::fail(
                        422,
                        'Status pembayaran yang dapat ditetapkan adalah Lunas.'
                    );
                }

                $payment = $q(
                    'SELECT id_pembayaran
                     FROM pembayaran
                     WHERE id_pembayaran = ?',
                    [(int) $id]
                )->fetch();

                if (!$payment) {
                    Http::fail(404, 'Pembayaran tidak ditemukan.');
                }

                $paymentMethod = Http::str($b, 'metode_pembayaran');

                $q(
                    'UPDATE pembayaran
                     SET status_pembayaran = ?,
                         tanggal_bayar = ?,
                         metode_pembayaran = COALESCE(?, metode_pembayaran)
                     WHERE id_pembayaran = ?',
                    [
                        'Lunas',
                        $today,
                        $paymentMethod !== '' ? $paymentMethod : null,
                        (int) $id,
                    ]
                );

                Http::ok([
                    'id_pembayaran' => (int) $id,
                    'status_pembayaran' => 'Lunas',
                ]);
            }

            break;

        // ====================================================
        // PENGADUAN FASILITAS
        // ====================================================
        case 'reports':

            $u = Http::need();

            // ------------------------------------------------
            // MENAMPILKAN PENGADUAN
            // GET: ?r=reports
            // ------------------------------------------------
            if ($method === 'GET') {
                $sql = 'SELECT
                            a.id_pengaduan,
                            a.id_penghuni,
                            u.nama AS nama_penghuni,
                            a.id_unit,
                            n.nama_unit,
                            a.kategori,
                            a.deskripsi,
                            a.tanggal_pengaduan,
                            a.status_pengaduan
                        FROM pengaduan_fasilitas a
                        JOIN penghuni p
                            ON p.id_penghuni = a.id_penghuni
                        JOIN users u
                            ON u.id_user = p.id_user
                        JOIN unit_kontrakan n
                            ON n.id_unit = a.id_unit';

                if ($u['role'] === 'admin') {
                    Http::ok(
                        $q(
                            "$sql ORDER BY a.id_pengaduan DESC"
                        )->fetchAll()
                    );
                }

                if ($u['role'] !== 'penghuni') {
                    Http::fail(403, 'Anda tidak memiliki akses.');
                }

                Http::ok(
                    $q(
                        "$sql
                         WHERE p.id_user = ?
                         ORDER BY a.id_pengaduan DESC",
                        [$u['id']]
                    )->fetchAll()
                );
            }

            // ------------------------------------------------
            // PENGHUNI MENGIRIM PENGADUAN
            // POST: ?r=reports
            // ------------------------------------------------
            if ($method === 'POST') {
                if ($u['role'] !== 'penghuni') {
                    Http::fail(
                        403,
                        'Hanya penghuni aktif yang dapat mengirim pengaduan.'
                    );
                }

                $b = Http::body();

                $category = Http::str($b, 'kategori');
                $description = Http::str($b, 'deskripsi');

                $tenant = $q(
                    "SELECT id_penghuni, id_unit
                     FROM penghuni
                     WHERE id_user = ?
                       AND status_penghuni = 'Aktif'
                     LIMIT 1",
                    [$u['id']]
                )->fetch();

                if (!$tenant) {
                    Http::fail(
                        403,
                        'Data penghuni aktif tidak ditemukan.'
                    );
                }

                $err = [];

                if ($category === '') {
                    $err['kategori'] = 'Kategori pengaduan wajib diisi.';
                }

                if (
                    mb_strlen($description) < 5
                    || mb_strlen($description) > 1000
                ) {
                    $err['deskripsi'] = 'Deskripsi harus 5–1.000 karakter.';
                }

                if ($err) {
                    Http::fail(422, 'Periksa kembali pengaduan.', $err);
                }

                $q(
                    "INSERT INTO pengaduan_fasilitas
                    (id_penghuni, id_unit, kategori, deskripsi,
                     tanggal_pengaduan, status_pengaduan)
                    VALUES (?, ?, ?, ?, ?, 'Baru')",
                    [
                        $tenant['id_penghuni'],
                        $tenant['id_unit'],
                        $category,
                        $description,
                        $today,
                    ]
                );

                Http::ok([
                    'id_pengaduan' => (int) $db->lastInsertId(),
                    'status_pengaduan' => 'Baru',
                ], 201);
            }

            // ------------------------------------------------
            // ADMIN MEMPERBARUI STATUS PENGADUAN
            // PUT: ?r=reports/{id}
            // ------------------------------------------------
            if ($method === 'PUT' && $id !== null) {
                Http::need('admin');

                $b = Http::body();
                $status = Http::str($b, 'status_pengaduan');

                if (!in_array(
                    $status,
                    ['Baru', 'Diproses', 'Selesai'],
                    true
                )) {
                    Http::fail(422, 'Status pengaduan tidak valid.');
                }

                $record = $q(
                    'SELECT id_pengaduan
                     FROM pengaduan_fasilitas
                     WHERE id_pengaduan = ?',
                    [(int) $id]
                )->fetch();

                if (!$record) {
                    Http::fail(404, 'Pengaduan tidak ditemukan.');
                }

                $q(
                    'UPDATE pengaduan_fasilitas
                     SET status_pengaduan = ?
                     WHERE id_pengaduan = ?',
                    [$status, (int) $id]
                );

                Http::ok([
                    'id_pengaduan' => (int) $id,
                    'status_pengaduan' => $status,
                ]);
            }

            break;

        // ====================================================
        // STATISTIK DASHBOARD ADMIN
        // ====================================================
        case 'stats':

            Http::need('admin');

            // Fungsi untuk mengambil jumlah data.
            $count = static function (string $sql) use ($q): int {
                return (int) $q($sql)->fetchColumn();
            };

            Http::ok([
                'unit' => $count(
                    'SELECT COUNT(*) FROM unit_kontrakan'
                ),

                'tersedia' => $count(
                    "SELECT COUNT(*) FROM unit_kontrakan
                     WHERE status_unit = 'Tersedia'"
                ),

                'terisi' => $count(
                    "SELECT COUNT(*) FROM unit_kontrakan
                     WHERE status_unit = 'Terisi'"
                ),

                'calon_penghuni' => $count(
                    "SELECT COUNT(*) FROM users
                     WHERE role = 'calon_penghuni'"
                ),

                'penghuni' => $count(
                    "SELECT COUNT(*) FROM users
                     WHERE role = 'penghuni'"
                ),

                'pengajuan_menunggu' => $count(
                    "SELECT COUNT(*) FROM pengajuan_sewa
                     WHERE status_pengajuan = 'Menunggu'"
                ),

                'pembayaran_belum' => $count(
                    "SELECT COUNT(*) FROM pembayaran
                     WHERE status_pembayaran = 'Belum Dibayar'"
                ),

                'total_pembayaran_belum' => (float) $q(
                    "SELECT COALESCE(SUM(jumlah_bayar), 0)
                     FROM pembayaran
                     WHERE status_pembayaran = 'Belum Dibayar'"
                )->fetchColumn(),

                'pengaduan_baru' => $count(
                    "SELECT COUNT(*) FROM pengaduan_fasilitas
                     WHERE status_pengaduan = 'Baru'"
                ),
            ]);

            break;
    }

    // ========================================================
    // 5. ENDPOINT TIDAK DITEMUKAN
    // ========================================================

    Http::fail(404, 'Endpoint tidak ditemukan.');

} catch (Throwable $e) {

    // Mencatat detail kesalahan pada log server.
    error_log('[kontrakan-api] ' . $e->getMessage());

    // Tidak menampilkan detail database kepada pengguna.
    Http::fail(
        500,
        'Terjadi kesalahan pada server. Periksa log untuk detailnya.'
    );
}
