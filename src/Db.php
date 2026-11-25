<?php
declare(strict_types=1);

namespace App;

use PDO;

/**
 * Koneksi database MySQL menggunakan PDO.
 *
 * Database:
 * kontrakan_pak_sapriadi
 *
 * Struktur tabel dibuat melalui HeidiSQL
 * berdasarkan ERD proyek kontrakan Pak Sapriadi.
 */
final class Db
{
    private static ?PDO $pdo = null;

    // ============================================================
    // 1. KONEKSI DATABASE MYSQL
    // ============================================================
    public static function conn(): PDO
    {
        // Memeriksa apakah koneksi sudah tersedia.
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Mengambil konfigurasi database dari file .env.
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get(
            'DB_NAME',
            'kontrakan_pak_sapriadi'
        );
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASSWORD', '');

        // Membuat alamat koneksi PDO MySQL.
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        // Membuka koneksi ke database MySQL.
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Menyimpan koneksi agar dapat digunakan kembali.
        self::$pdo = $pdo;

        return self::$pdo;
    }
}
