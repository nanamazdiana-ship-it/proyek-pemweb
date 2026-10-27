<?php
declare(strict_types=1);

// ==========================================
// MIDDLEWARE API
// Berisi fungsi untuk memeriksa metode HTTP
// dan format data yang dikirim pengguna.
// ==========================================

// Memeriksa apakah metode HTTP sesuai.
function requireMethod(string $method): void
{
    // Jika metode tidak sesuai, kirim status 405.
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        // Memberi tahu metode HTTP yang diizinkan.
        header('Allow: ' . $method);

        // Mengatur respons dalam format JSON.
        header('Content-Type: application/json; charset=utf-8');

        // Mengatur status HTTP 405 (Method Not Allowed).
        http_response_code(405);

        // Mengirim pesan kesalahan dalam format JSON.
        echo json_encode([
            'status' => 'error',
            'message' => 'Metode HTTP tidak diizinkan.'
        ], JSON_PRETTY_PRINT);

        // Menghentikan proses setelah mengirim respons.
        exit;
    }
}

// Memeriksa format Content-Type JSON.
function requireJsonContentType(): void
{
    // Membaca Content-Type dari request.
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // Mengambil tipe media sebelum parameter, misalnya charset.
    $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));

    // Jika bukan application/json, kirim status 415.
    if ($mediaType !== 'application/json') {
        // Mengatur respons dalam format JSON.
        header('Content-Type: application/json; charset=utf-8');

        // Mengatur status HTTP 415 (Unsupported Media Type).
        http_response_code(415);

        // Mengirim pesan kesalahan dalam format JSON.
        echo json_encode([
            'status' => 'error',
            'message' => 'Content-Type harus application/json.'
        ], JSON_PRETTY_PRINT);

        // Menghentikan proses setelah mengirim respons.
        exit;
    }
}
