-- Skema MySQL untuk aplikasi Kontrakan Pak Sapriadi.
-- Sesuai dengan nama database dan query di src/Db.php serta api/index.php.
-- Jalankan pada database baru. Skrip ini tidak menghapus database/tabel lama.

CREATE DATABASE IF NOT EXISTS kontrakan_pak_sapriadi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE kontrakan_pak_sapriadi;

CREATE TABLE IF NOT EXISTS unit_kontrakan (
    id_unit INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_unit VARCHAR(100) NOT NULL,
    harga_sewa DECIMAL(12,2) NOT NULL,
    status_unit ENUM('Tersedia', 'Terisi') NOT NULL DEFAULT 'Tersedia',
    deskripsi TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_unit_harga_positif CHECK (harga_sewa > 0),
    INDEX idx_unit_status (status_unit)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(80) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'calon_penghuni', 'penghuni') NOT NULL DEFAULT 'calon_penghuni',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengajuan_sewa (
    id_pengajuan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    id_unit INT UNSIGNED NOT NULL,
    tanggal_pengajuan DATE NOT NULL,
    catatan VARCHAR(1000) NULL,
    status_pengajuan ENUM('Menunggu', 'Disetujui', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pengajuan_user
        FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pengajuan_unit
        FOREIGN KEY (id_unit) REFERENCES unit_kontrakan(id_unit)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_pengajuan_status (status_pengajuan),
    INDEX idx_pengajuan_user_unit (id_user, id_unit)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penghuni (
    id_penghuni INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    id_unit INT UNSIGNED NOT NULL,
    id_pengajuan INT UNSIGNED NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NULL,
    status_penghuni ENUM('Aktif', 'Selesai') NOT NULL DEFAULT 'Aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_penghuni_user
        FOREIGN KEY (id_user) REFERENCES users(id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_penghuni_unit
        FOREIGN KEY (id_unit) REFERENCES unit_kontrakan(id_unit)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_penghuni_pengajuan
        FOREIGN KEY (id_pengajuan) REFERENCES pengajuan_sewa(id_pengajuan)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_penghuni_tanggal
        CHECK (tanggal_selesai IS NULL OR tanggal_selesai >= tanggal_mulai),
    UNIQUE KEY uq_penghuni_pengajuan (id_pengajuan),
    INDEX idx_penghuni_user_status (id_user, status_penghuni)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran (
    id_pembayaran INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penghuni INT UNSIGNED NOT NULL,
    tanggal_bayar DATE NULL,
    jumlah_bayar DECIMAL(12,2) NOT NULL,
    metode_pembayaran VARCHAR(50) NULL,
    status_pembayaran ENUM('Belum Dibayar', 'Lunas') NOT NULL DEFAULT 'Belum Dibayar',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pembayaran_penghuni
        FOREIGN KEY (id_penghuni) REFERENCES penghuni(id_penghuni)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_pembayaran_jumlah CHECK (jumlah_bayar > 0),
    INDEX idx_pembayaran_status (status_pembayaran),
    INDEX idx_pembayaran_tanggal (tanggal_bayar)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengaduan_fasilitas (
    id_pengaduan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penghuni INT UNSIGNED NOT NULL,
    id_unit INT UNSIGNED NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    deskripsi VARCHAR(1000) NOT NULL,
    tanggal_pengaduan DATE NOT NULL,
    status_pengaduan ENUM('Baru', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Baru',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pengaduan_penghuni
        FOREIGN KEY (id_penghuni) REFERENCES penghuni(id_penghuni)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pengaduan_unit
        FOREIGN KEY (id_unit) REFERENCES unit_kontrakan(id_unit)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_pengaduan_status (status_pengaduan),
    INDEX idx_pengaduan_tanggal (tanggal_pengaduan)
) ENGINE=InnoDB;

-- Data awal tiga unit. INSERT IGNORE mencegah data dengan ID yang sama
-- ditimpa jika skrip dijalankan kembali.
INSERT IGNORE INTO unit_kontrakan (id_unit, nama_unit, harga_sewa, status_unit)
VALUES
    (1, 'Pintu 1', 1000000, 'Tersedia'),
    (2, 'Pintu 2', 1000000, 'Tersedia'),
    (3, 'Pintu 3', 1000000, 'Tersedia')
;
