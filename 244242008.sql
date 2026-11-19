-- nyieun databse sesuai NPM, pake tanda backtick (`) sabab ngaranna angka wungkul
CREATE DATABASE IF NOT EXISTS `244242008`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `244242008`;

-- tabel induk (master): dipisah meh ngaran konsol jeung tarifna teu ditulis bulak-balik di tiap baris unit
CREATE TABLE IF NOT EXISTS kategori_konsol (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL UNIQUE,
    tarif_per_jam INT NOT NULL,
    deskripsi VARCHAR(255) NULL
) ENGINE=InnoDB;

-- tabel anak (transaksional): nyimpen daptar station / penyewa PS di rental
CREATE TABLE IF NOT EXISTS unit_rental (
    id_unit INT AUTO_INCREMENT PRIMARY KEY,
    kode_unit VARCHAR(20) NOT NULL UNIQUE,
    nama_station VARCHAR(100) NOT NULL,
    id_kategori INT NOT NULL,
    jumlah_stik INT NOT NULL DEFAULT 2,
    kondisi VARCHAR(50) NOT NULL DEFAULT 'Bagus',
    status ENUM('Tersedia', 'Sedang Main', 'Maintenance') NOT NULL DEFAULT 'Tersedia',
    catatan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- aturan foreign key: ON DELETE RESTRICT nahan meh kategori teu bisa dihapus lamun masih dipake ku unit
    CONSTRAINT fk_unit_kategori
        FOREIGN KEY (id_kategori)
        REFERENCES kategori_konsol(id_kategori)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- lebetkeun data awal tipe konsol
INSERT INTO kategori_konsol (id_kategori, nama_kategori, tarif_per_jam, deskripsi) VALUES
(1, 'PlayStation 5', 15000, 'TV 43 inch, 2 stik'),
(2, 'PlayStation 4', 10000, 'TV 32 inch, game bola & GTA'),
(3, 'PlayStation 3', 6000, 'TV 32 inch, game PES')
ON DUPLICATE KEY UPDATE nama_kategori=VALUES(nama_kategori);

-- lebetkeun 2 contoh data rental
INSERT INTO unit_rental (kode_unit, nama_station, id_kategori, jumlah_stik, kondisi, status, catatan) VALUES
('PS5-01', 'Firman Reza Alamzah', 1, 2, 'Bagus', 'Sedang Main', 'Main 2 jam'),
('PS4-01', 'Reza', 2, 2, 'Bagus', 'Tersedia', 'Siap dipakai')
ON DUPLICATE KEY UPDATE kode_unit=VALUES(kode_unit);
