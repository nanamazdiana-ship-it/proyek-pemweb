<?php
declare(strict_types=1);

namespace App;

/**
 * Bantuan respons JSON, sesi, otorisasi,
 * dan validasi input untuk API kontrakan Pak Sapriadi.
 */
final class Http
{
    // ============================================================
    // 1. PENGATURAN AWAL HTTP DAN SESI
    // ============================================================
    public static function boot(): void
    {
        // Mengatur zona waktu WITA.
        date_default_timezone_set('Asia/Makassar');

        // Mengatur header respons API.
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');

        // Mengatur cookie sesi agar lebih aman.
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'path' => '/',
        ]);

        // Memulai sesi pengguna.
        session_start();
    }

    // ============================================================
    // 2. RESPONS JSON BERHASIL
    // ============================================================
    public static function ok(
        mixed $data,
        int $code = 200
    ): never {
        http_response_code($code);

        echo json_encode(
            ['data' => $data],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    // ============================================================
    // 3. RESPONS JSON KETIKA TERJADI KESALAHAN
    // ============================================================
    public static function fail(
        int $code,
        string $msg,
        array $fields = []
    ): never {
        http_response_code($code);

        echo json_encode([
            'error' => $msg,
            'fields' => (object) $fields,
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    // ============================================================
    // 4. MEMBACA DAN MEMVALIDASI BODY JSON
    // ============================================================
    public static function body(): array
    {
        // Body hanya dibaca untuk metode POST dan PUT.
        if (!in_array(
            $_SERVER['REQUEST_METHOD'],
            ['POST', 'PUT'],
            true
        )) {
            return [];
        }

        // Memastikan permintaan menggunakan JSON.
        if (!str_contains(
            $_SERVER['CONTENT_TYPE'] ?? '',
            'application/json'
        )) {
            self::fail(
                415,
                'Gunakan Content-Type application/json.'
            );
        }

        // Membaca isi permintaan.
        $d = json_decode(
            file_get_contents('php://input') ?: '{}',
            true
        );

        // Mengembalikan data jika JSON valid.
        return is_array($d)
            ? $d
            : self::fail(400, 'Body JSON tidak valid.');
    }

    // ============================================================
    // 5. MENGAMBIL NILAI STRING DARI INPUT
    // ============================================================
    public static function str(
        array $b,
        string $k
    ): string {
        return isset($b[$k]) && is_scalar($b[$k])
            ? trim((string) $b[$k])
            : '';
    }

    // ============================================================
    // 6. MENGAMBIL DATA PENGGUNA YANG SEDANG LOGIN
    // ============================================================
    public static function user(): ?array
    {
        // Tidak ada ID sesi berarti belum login.
        if (empty($_SESSION['uid'])) {
            return null;
        }

        // Mengambil data pengguna sesuai struktur tabel users.
        $s = Db::conn()->prepare(
            'SELECT
                id_user AS id,
                nama,
                email,
                role
             FROM users
             WHERE id_user = ?'
        );

        // Menjalankan query menggunakan parameter.
        $s->execute([$_SESSION['uid']]);

        return $s->fetch() ?: null;
    }

    // ============================================================
    // 7. MEMERIKSA LOGIN DAN HAK AKSES
    // ============================================================
    public static function need(
        ?string $role = null
    ): array {
        // Pengguna harus login terlebih dahulu.
        $u = self::user()
            ?? self::fail(
                401,
                'Silakan masuk terlebih dahulu.'
            );

        // Memastikan peran pengguna sesuai kebutuhan.
        if ($role !== null && $u['role'] !== $role) {
            self::fail(
                403,
                'Anda tidak memiliki akses.'
            );
        }

        return $u;
    }

    // ============================================================
    // 8. VALIDASI NAMA
    // ============================================================
    public static function nama(string $v): string
    {
        $n = mb_strlen($v);

        return (
            $n < 2
            || $n > 80
            || !preg_match(
                "/^\p{L}+(?:[ '\-]\p{L}+)*$/u",
                $v
            )
        )
            ? 'Nama 2–80 karakter, hanya huruf, spasi, apostrof, atau tanda hubung.'
            : '';
    }

    // ============================================================
    // 9. VALIDASI NOMOR WHATSAPP
    // ============================================================
    public static function telepon(string $v): string
    {
        return preg_match('/^\d{9,15}$/', $v)
            ? ''
            : 'Nomor WhatsApp terdiri dari 9–15 angka.';
    }
}