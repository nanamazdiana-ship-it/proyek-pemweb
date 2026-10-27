<?php
declare(strict_types=1);

// ==========================================
// ENDPOINT HEALTH CHECK
// Memeriksa apakah backend API berjalan.
// ==========================================

// Mengatur response agar dikirim dalam format JSON.
header('Content-Type: application/json; charset=utf-8');

// Mengatur status HTTP menjadi 200 (OK).
http_response_code(200);

// Mengirim respons JSON.
echo json_encode([
    'status' => 'ok',
    'message' => 'API Kontrakkan Pak Sapriadi berjalan.'
]);
