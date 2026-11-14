-- nyieu datbse sesuai NPM, pake tanda backtick (`) sabab ngaranna angka wungkul
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

-- tabel anak (transaksional): nyimpen daptar station PS di rental
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
(1, 'PlayStation 5 Disc Edition', 20000, 'Konsol generasi terbaru, support 4K 120Hz & HDR, TV 55 Inch'),
(2, 'PlayStation 4 Pro', 12000, 'Resolusi 4K Enhanced, koleksi game petualangan lengkap'),
(3, 'PlayStation 4 Slim', 10000, 'Konsol hemat daya, cocok untuk game kasual, eFootball, & FIFA'),
(4, 'PlayStation 3 Super Slim', 6000, 'Koleksi game retro & nostalgia (GTA V, PES, Dynasty Warriors)'),
(5, 'Nintendo Switch OLED', 15000, 'Party game multiplayer (Mario Kart, Smash Bros, Overcooked)')
ON DUPLICATE KEY UPDATE nama_kategori=VALUES(nama_kategori);

-- lebetkeun data sampel unit rental
INSERT INTO unit_rental (kode_unit, nama_station, id_kategori, jumlah_stik, kondisi, status, catatan) VALUES
('PS5-01', 'Station VIP 1 (Sofa Kulit & TV 55")', 1, 2, 'Sangat Bagus', 'Sedang Main', 'Sewa paket 3 jam sampai 22:00'),
('PS5-02', 'Station VIP 2 (Audio Surround)', 1, 4, 'Sangat Bagus', 'Tersedia', 'Stik 4 buah siap main bareng'),
('PS4-01', 'Station Reguler A (TV 43")', 2, 2, 'Bagus', 'Tersedia', 'Update eFootball terbaru'),
('PS4-02', 'Station Reguler B (TV 43")', 3, 2, 'Bagus', 'Sedang Main', 'Main FIFA 24'),
('PS4-03', 'Station Reguler C (TV 43")', 3, 2, 'Stik R2 Agak Keras', 'Maintenance', 'Sedang diganti karet tombol stik'),
('PS3-01', 'Station Retro 1 (TV 32")', 4, 2, 'Bagus', 'Tersedia', 'Game nostalgia lengkap'),
('NSW-01', 'Station Party Switch', 5, 4, 'Sangat Bagus', 'Tersedia', '4 Joycon untuk main ramai-ramai')
ON DUPLICATE KEY UPDATE kode_unit=VALUES(kode_unit);
