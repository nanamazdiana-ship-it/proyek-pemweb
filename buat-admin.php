<?php
declare(strict_types=1);

// Skrip pembuatan admin hanya boleh dijalankan dari terminal, bukan web.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Memuat konfigurasi dan koneksi database.
require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Db.php';

use App\Env;
use App\Db;

// Memuat file .env.
Env::load(__DIR__ . '/.env');

// Menghubungkan ke database.
$db = Db::conn();

// Kredensial admin harus disediakan melalui file .env.
$nama = trim(Env::get('ADMIN_NAMA'));
$email = strtolower(trim(Env::get('ADMIN_EMAIL')));
$password = Env::get('ADMIN_PASSWORD');

if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "ADMIN_NAMA dan ADMIN_EMAIL harus diisi dengan benar di .env.\n");
    exit(1);
}

if (strlen($password) < 12) {
    fwrite(STDERR, "ADMIN_PASSWORD harus diisi dan minimal 12 karakter di .env.\n");
    exit(1);
}

// Periksa apakah email sudah terdaftar.
$cek = $db->prepare(
    "SELECT id_user FROM users WHERE email = :email"
);
$cek->execute(['email' => $email]);

if ($cek->fetch()) {
    exit("Email admin sudah terdaftar.\n");
}

// Hash password sebelum disimpan.
$hashPassword = password_hash($password, PASSWORD_DEFAULT);

// Simpan akun dengan role admin.
$sql = "INSERT INTO users (nama, email, password, role)
        VALUES (:nama, :email, :password, 'admin')";

$stmt = $db->prepare($sql);
$stmt->execute([
    'nama' => $nama,
    'email' => $email,
    'password' => $hashPassword
]);

echo "Akun admin berhasil dibuat!\n";
