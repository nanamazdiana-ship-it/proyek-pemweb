<?php
declare(strict_types=1);

// ==========================================
// PENGATURAN DASAR API
// ==========================================
header('Content-Type: application/json; charset=utf-8');

// Memanggil konfigurasi Env, Db, dan Middleware
require_once dirname(__DIR__) . '/src/Env.php';
require_once dirname(__DIR__) . '/src/Db.php';
require_once dirname(__DIR__) . '/src/Http.php';
require_once __DIR__ . '/middleware.php';

// Memuat konfigurasi dari file .env
\App\Env::load(dirname(__DIR__) . '/.env');
\App\Http::boot();

// ==========================================
// FUNGSI RESPONS JSON
// ==========================================
function responseJson(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Mengambil metode HTTP
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ==========================================
// 1. METHOD GET: MENAMPILKAN DATA UNIT
//    DAN FILTER BERDASARKAN STATUS
// ==========================================
if ($method === 'GET') {
    try {
        // Membuat koneksi PDO MySQL
        $pdo = \App\Db::conn();

        // Membaca parameter status dari URL
        $status = $_GET['status'] ?? '';

        // Query dasar untuk mengambil seluruh unit
        $sql = 'SELECT
                    id_unit,
                    nama_unit,
                    harga_sewa,
                    status_unit,
                    deskripsi
                FROM unit_kontrakan';

        if ($status !== '') {
            // Memvalidasi status yang diperbolehkan
            if (!in_array($status, ['Tersedia', 'Terisi'], true)) {
                responseJson(400, [
                    'error' => 'Status harus Tersedia atau Terisi'
                ]);
            }

            // Menambahkan filter status
            $sql .= ' WHERE status_unit = :status';
        }

        // Mengurutkan data berdasarkan ID
        $sql .= ' ORDER BY id_unit ASC';

        // Menyiapkan query menggunakan prepared statement
        $stmt = $pdo->prepare($sql);

        // Menjalankan query sesuai kondisi filter
        if ($status !== '') {
            $stmt->execute([
                'status' => $status
            ]);
        } else {
            $stmt->execute();
        }

        // Mengirim hasil query dalam format JSON
        responseJson(200, [
            'status' => 'success',
            'message' => 'Data unit berhasil diambil',
            'data' => $stmt->fetchAll()
        ]);

    } catch (Throwable $e) {
        // Mencatat kesalahan pada log server
        error_log($e->getMessage());

        responseJson(500, [
            'status' => 'error',
            'message' => 'Gagal mengambil data unit'
        ]);
    }
}

// ==========================================
// 2. METHOD POST: MENAMBAHKAN DATA UNIT
// ==========================================
if ($method === 'POST') {

    // Hanya admin yang sudah login boleh menambahkan unit.
    // Endpoint memakai sesi autentikasi yang sama dengan api/index.php.
    try {
        \App\Http::need('admin');
    } catch (Throwable $e) {
        error_log($e->getMessage());

        responseJson(500, [
            'status' => 'error',
            'message' => 'Gagal memeriksa hak akses'
        ]);
    }

    // Memeriksa Content-Type harus application/json
    requireJsonContentType();

    // Membaca dan memproses body JSON
    try {
        $payload = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        responseJson(400, [
            'status' => 'error',
            'message' => 'Format JSON tidak valid'
        ]);
    }

    // Memastikan body JSON berbentuk object
    if (!is_array($payload)) {
        responseJson(400, [
            'status' => 'error',
            'message' => 'Body JSON harus berupa object'
        ]);
    }

    // Mengambil data dari JSON
    $namaUnit = $payload['nama_unit'] ?? '';
    $hargaInput = $payload['harga_sewa'] ?? null;
    $statusUnit = $payload['status_unit'] ?? 'Tersedia';
    $deskripsi = $payload['deskripsi'] ?? '';

    // Memvalidasi nama unit
    if (!is_string($namaUnit)) {
        responseJson(422, [
            'status' => 'error',
            'message' => 'Nama unit harus berupa teks'
        ]);
    }

    $namaUnit = trim($namaUnit);

    if ($namaUnit === '' || mb_strlen($namaUnit) > 100) {
        responseJson(422, [
            'status' => 'error',
            'message' => 'Nama unit wajib diisi dan maksimal 100 karakter'
        ]);
    }

    // Memvalidasi harga sewa
    if (
        !is_int($hargaInput) &&
        !is_float($hargaInput) &&
        !(is_string($hargaInput) && is_numeric($hargaInput))
    ) {
        responseJson(422, [
            'status' => 'error',
            'message' => 'Harga sewa harus berupa angka'
        ]);
    }

    $hargaSewa = (float) $hargaInput;

    if (!is_finite($hargaSewa) || $hargaSewa <= 0) {
        responseJson(422, [
            'status' => 'error',
            'message' => 'Harga sewa harus lebih besar dari nol'
        ]);
    }

    // Memvalidasi status unit
    if (
        !is_string($statusUnit) ||
        !in_array($statusUnit, ['Tersedia', 'Terisi'], true)
    ) {
        responseJson(422, [
            'status' => 'error',
            'message' => 'Status unit harus Tersedia atau Terisi'
        ]);
    }

    // Memvalidasi deskripsi
    if (!is_string($deskripsi)) {
        responseJson(422, [
            'status' => 'error',
            'message' => 'Deskripsi harus berupa teks'
        ]);
    }

    try {
        // Membuat koneksi database
        $pdo = \App\Db::conn();

        // Menyiapkan query INSERT dengan prepared statement
        $stmt = $pdo->prepare(
            'INSERT INTO unit_kontrakan
                (nama_unit, harga_sewa, status_unit, deskripsi)
             VALUES
                (:nama_unit, :harga_sewa, :status_unit, :deskripsi)'
        );

        // Menyimpan data unit ke database
        $stmt->execute([
            'nama_unit' => $namaUnit,
            'harga_sewa' => $hargaSewa,
            'status_unit' => $statusUnit,
            'deskripsi' => $deskripsi
        ]);

        // Mengambil ID unit yang baru ditambahkan
        $idUnit = (int) $pdo->lastInsertId();

        // Mengirim respons berhasil
        responseJson(201, [
            'status' => 'success',
            'message' => 'Data unit berhasil ditambahkan',
            'data' => [
                'id_unit' => $idUnit,
                'nama_unit' => $namaUnit,
                'harga_sewa' => $hargaSewa,
                'status_unit' => $statusUnit,
                'deskripsi' => $deskripsi
            ]
        ]);

    } catch (Throwable $e) {
        // Mencatat kesalahan pada log server
        error_log($e->getMessage());

        responseJson(500, [
            'status' => 'error',
            'message' => 'Gagal menambahkan data unit'
        ]);
    }
}

// ==========================================
// 3. METHOD LAIN: MENOLAK PERMINTAAN
// ==========================================
header('Allow: GET, POST');

responseJson(405, [
    'status' => 'error',
    'message' => 'Method tidak diizinkan'
]);
