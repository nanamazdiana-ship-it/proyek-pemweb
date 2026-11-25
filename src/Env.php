<?php
declare(strict_types=1);

namespace App;

/**
 * Pembaca konfigurasi file .env sederhana
 * tanpa menggunakan paket tambahan.
 */
final class Env
{
    // Menyimpan konfigurasi yang dibaca dari file .env.
    private static array $v = [];

    // ============================================================
    // 1. MEMBACA FILE .env
    // ============================================================
    public static function load(string $file): void
    {
        // Jika file tidak ditemukan, hentikan proses pembacaan.
        if (!is_file($file)) {
            return;
        }

        // Membaca file per baris.
        foreach (
            file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            as $line
        ) {
            $line = trim($line);

            // Lewati baris kosong, komentar, dan baris tanpa tanda "=".
            if (
                $line === ''
                || $line[0] === '#'
                || !str_contains($line, '=')
            ) {
                continue;
            }

            // Pisahkan nama konfigurasi dan nilainya.
            [$k, $val] = explode('=', $line, 2);

            // Simpan konfigurasi setelah membersihkan spasi dan kutip.
            self::$v[trim($k)] = trim(trim($val), "\"'");
        }
    }

    // ============================================================
    // 2. MENGAMBIL NILAI KONFIGURASI
    // ============================================================
    public static function get(
        string $key,
        string $default = ''
    ): string {
        // Gunakan nilai dari .env, lalu environment server,
        // dan terakhir nilai default jika keduanya tidak tersedia.
        return self::$v[$key] ?? (getenv($key) ?: $default);
    }
}
