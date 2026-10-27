<?php
declare(strict_types=1);

// ==========================================
// ENDPOINT CREATE UNIT
// Menerima dan memvalidasi data unit baru.
// ==========================================

// Memanggil fungsi middleware.
require_once __DIR__ . '/middleware.php';

// Mengatur format respons menjadi JSON.
header('Content-Type: application/json; charset=utf-8');

// Endpoint ini hanya menerima POST.
requireMethod('POST');

// Memastikan request menggunakan format JSON.
requireJsonContentType();

// Membaca isi request dari Postman.
$rawInput = file_get_contents('php://input');

// Mengubah JSON menjadi array PHP.
$input = json_decode($rawInput, true);

// Memeriksa apakah JSON valid.
if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'message' => 'Format JSON tidak valid.'
    ], JSON_PRETTY_PRINT);

    exit;
}

// Mengambil data dari request.
$kodeInput = $input['kode'] ?? null;
$namaInput = $input['nama'] ?? null;
$statusInput = $input['status'] ?? null;
$harga = $input['harga'] ?? null;

// Memastikan kode, nama, dan status berupa teks.
if (
    !is_string($kodeInput) ||
    !is_string($namaInput) ||
    !is_string($statusInput)
) {
    http_response_code(422);

    echo json_encode([
        'status' => 'error',
        'message' => 'Kode, nama, dan status harus berupa teks.'
    ], JSON_PRETTY_PRINT);

    exit;
}

// Membersihkan spasi di awal dan akhir teks.
$kode = trim($kodeInput);
$nama = trim($namaInput);
$status = trim($statusInput);

// Memastikan semua field wajib diisi.
if ($kode === '' || $nama === '' || $status === '' || $harga === null) {
    http_response_code(422);

    echo json_encode([
        'status' => 'error',
        'message' => 'Kode, nama, status, dan harga wajib diisi.'
    ], JSON_PRETTY_PRINT);

    exit;
}

// Memastikan status unit sesuai pilihan.
if (!in_array($status, ['Tersedia', 'Terisi'], true)) {
    http_response_code(422);

    echo json_encode([
        'status' => 'error',
        'message' => 'Status harus Tersedia atau Terisi.'
    ], JSON_PRETTY_PRINT);

    exit;
}

// Memastikan harga berupa angka positif.
if (!is_numeric($harga) || (float) $harga <= 0) {
    http_response_code(422);

    echo json_encode([
        'status' => 'error',
        'message' => 'Harga harus berupa angka lebih dari nol.'
    ], JSON_PRETTY_PRINT);

    exit;
}

// Menyusun data yang sudah lolos validasi.
$unit = [
    'kode' => $kode,
    'nama' => $nama,
    'status' => $status,
    'harga' => (float) $harga
];

// Mengirim status 201 Created.
http_response_code(201);

// Mengirim hasil dalam format JSON.
echo json_encode([
    'status' => 'success',
    'message' => 'Data unit berhasil divalidasi.',
    'data' => $unit
], JSON_PRETTY_PRINT);
