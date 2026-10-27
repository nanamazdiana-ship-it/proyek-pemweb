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
        header('Allow: ' . $method);
        header('Content-Type: application/json; charset=utf-8');

        http_response_code(405);

        echo json_encode([
            'status' => 'error',
            'message' => 'Metode HTTP tidak diizinkan.'
        ]);

        exit;
    }
}

// Memeriksa format Content-Type JSON.
function requireJsonContentType(): void
{
    // Membaca Content-Type dari request.
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    // Jika bukan JSON, kirim status 415.
    if (stripos($contentType, 'application/json') !== 0) {
        header('Content-Type: application/json; charset=utf-8');

        http_response_code(415);

        echo json_encode([
            'status' => 'error',
            'message' => 'Content-Type harus application/json.'
        ]);

        exit;
    }
}
