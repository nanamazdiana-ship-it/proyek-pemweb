<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Db;
use PDO;

/**
 * Repository untuk mengelola data unit kontrakan.
 *
 * Tabel: unit_kontrakan
 */
final class UnitRepository
{
    private PDO $db;

    public function __construct()
    {
        // Menggunakan koneksi PDO dari Db.php.
        $this->db = Db::conn();
    }

    // ============================================================
    // 1. MENGAMBIL SEMUA DATA UNIT
    // ============================================================
    public function getAll(): array
    {
        $sql = "SELECT * FROM unit_kontrakan ORDER BY id_unit ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ============================================================
    // 2. MENGAMBIL DATA UNIT BERDASARKAN ID
    // ============================================================
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM unit_kontrakan WHERE id_unit = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        $data = $stmt->fetch();

        return $data ?: null;
    }

    // ============================================================
    // 3. MENAMBAHKAN DATA UNIT
    // ============================================================
    public function create(
        string $nama,
        float $harga,
        string $status = 'Tersedia',
        ?string $deskripsi = null
    ): bool {
        $sql = "INSERT INTO unit_kontrakan
                (nama_unit, harga_sewa, status_unit, deskripsi)
                VALUES (:nama, :harga, :status, :deskripsi)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nama' => $nama,
            ':harga' => $harga,
            ':status' => $status,
            ':deskripsi' => $deskripsi
        ]);
    }

    // ============================================================
    // 4. MEMPERBARUI DATA UNIT
    // ============================================================
    public function update(
        int $id,
        string $nama,
        float $harga,
        string $status,
        ?string $deskripsi = null
    ): bool {
        $sql = "UPDATE unit_kontrakan
                SET nama_unit = :nama,
                    harga_sewa = :harga,
                    status_unit = :status,
                    deskripsi = :deskripsi
                WHERE id_unit = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':nama' => $nama,
            ':harga' => $harga,
            ':status' => $status,
            ':deskripsi' => $deskripsi
        ]);
    }

    // ============================================================
    // 5. MENGHAPUS DATA UNIT
    // ============================================================
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM unit_kontrakan WHERE id_unit = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}
