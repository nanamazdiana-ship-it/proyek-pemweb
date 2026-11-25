<?php
declare(strict_types=1);

// Memanggil file konfigurasi dan koneksi database
require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Db.php';

// Membaca konfigurasi dari file .env
\App\Env::load(__DIR__ . '/../.env');

// Mengatur response menjadi JSON
header('Content-Type: application/json');

try {
    // Membuat koneksi ke database MySQL menggunakan PDO
    $db = \App\Db::conn();

    // Mengambil nama database yang sedang digunakan
    $result = $db->query('SELECT DATABASE() AS nama_database')->fetch();

    // Menampilkan hasil jika koneksi berhasil
    echo json_encode([
        'status' => 'success',
        'message' => 'Koneksi PDO MySQL berhasil!',
        'database' => $result['nama_database']
    ], JSON_PRETTY_PRINT);

} catch (\PDOException $e) {
    // Mengatur status HTTP jika koneksi gagal
    http_response_code(500);

    // Menampilkan pesan error untuk keperluan pengujian
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal.',
        'detail' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}