-- 1. Pembuatan Basis dat
CREATE DATABASE IF NOT EXISTS `244242008` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;


USE `244242008`;

-- 2. Tabel Master: katesori_konsol (Tabel Induk)
-- Mengapa dibuat terpisah (Normalisasi)?
-- Tujuannya agar nama tipe konsol, tarif sewa per jam, dan fasilitasnya tidak ditulis berulang-ulang di setiap baris unit station.
-- Cukup disimpan satu kali di tabel ini, lalu tabel unit tinggal memanggil ID-nya saja.
CREATE TABLE IF NOT EXISTS kategori_konsol (
    -- id_kategori sebagai Primary Key (pengenal unik), otomatis bertambah 1, 2, 3... (AUTO_INCREMENT)
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,

    -- nama_kategori wajib diisi (NOT NULL) dan tidak boleh ada nama ganda (UNIQUE)
    nama_kategori VARCHAR(50) NOT NULL UNIQUE,

    -- tarif_per_jam menyimpan harga sewa per jam dalam bilangan bulat (contoh: 20000)
    tarif_per_jam INT NOT NULL,

    -- deskripsi menyimpan keterangan tambahan fasilitas TV atau game (boleh kosong / NULL)
    deskripsi VARCHAR(255) NULL
) ENGINE=InnoDB; -- Engine InnoDB wajib digunakan agar tabel mendukung fitur relasi Foreign Key

-- 3. Tabel Transaksional: unit_rental (Tabel Anak)
-- Tabel ini menyimpan daftar seluruh station unit rental PS yang ada di toko
CREATE TABLE IF NOT EXISTS unit_rental (
    -- id_unit sebagai Primary Key untuk tabel unit
    id_unit INT AUTO_INCREMENT PRIMARY KEY,

    -- kode_unit adalah kode unik pengenal station (contoh: PS5-01, PS4-02), tidak boleh ada yang kembar (UNIQUE)
    kode_unit VARCHAR(20) NOT NULL UNIQUE,

    -- nama_station adalah nama meja atau ruang bermain (contoh: Station VIP 1 TV 55 Inch)
    nama_station VARCHAR(100) NOT NULL,

    -- id_kategori adalah kolom FOREIGN KEY yang menghubungkan tabel unit_rental ke tabel kategori_konsol
    id_kategori INT NOT NULL,

    -- jumlah_stik menyimpan berapa controller yang tersedia di station tersebut (default 2 stik)
    jumlah_stik INT NOT NULL DEFAULT 2,

    -- kondisi fisik unit PlayStatio (contoh: Sangat Bagus, Bagus, Cukup, Perlu Servis)
    kondisi VARCHAR(50) NOT NULL DEFAULT 'Bagus',

    -- status ketersediaan unit hanya boleh bernilai salah satu dari 3 opsi berikut (tipe ENUM)
    status ENUM('Tersedia', 'Sedang Main', 'Maintenance') NOT NULL DEFAULT 'Tersedia',

    -- catatan tambahan jika ada permintaan khusus atau game tertentu yang terpasang
    catatan VARCHAR(255) NULL,

    -- created_at secara otomatis mencatat tanggal dan jam berapa data unit ini pertama kali diinput
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Definisi Aturan Relasi Database (Integritas Referensial):
    -- CONSTRAINT fk_unit_kategori: Menamai aturan relasi ini
    -- FOREIGN KEY (id_kategori) REFERENCES kategori_konsol(id_kategori): id_kategori di tabel ini merujuk ke id_kategori di tabel master
    -- ON UPDATE CASCADE: Jika ID di tabel master diubah, ID di tabel anak ini otomatis ikut berubah menyesuaikan
    -- ON DELETE RESTRICT: Tipe konsol di tabel master DITOLAK untuk dihapus jika masih ada unit yang terdaftar memakainya
    CONSTRAINT fk_unit_kategori 
        FOREIGN KEY (id_kategori) 
        REFERENCES kategori_konsol(id_kategori) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. Memasukkan Contoh Data Awal untuk Tipe Konsol (Tabel Master)
INSERT INTO kategori_konsol (id_kategori, nama_kategori, tarif_per_jam, deskripsi) VALUES
(1, 'PlayStation 5 Disc Edition', 20000, 'Konsol generasi terbaru, support 4K 120Hz & HDR, TV 55 Inch'),
(2, 'PlayStation 4 Pro', 12000, 'Resolusi 4K Enhanced, koleksi game petualangan lengkap'),
(3, 'PlayStation 4 Slim', 10000, 'Konsol hemat daya, cocok untuk game kasual, eFootball, & FIFA'),
(4, 'PlayStation 3 Super Slim', 6000, 'Koleksi game retro & nostalgia (GTA V, PES, Dynasty Warriors)'),
(5, 'Nintendo Switch OLED', 15000, 'Party game multiplayer (Mario Kart, Smash Bros, Overcooked)')
ON DUPLICATE KEY UPDATE nama_kategori=VALUES(nama_kategori);

-- 5. Memasukkan Contoh Data Awal untuk Unit Station Rental
INSERT INTO unit_rental (kode_unit, nama_station, id_kategori, jumlah_stik, kondisi, status, catatan) VALUES
('PS5-01', 'Station VIP 1 (Sofa Kulit & TV 55")', 1, 2, 'Sangat Bagus', 'Sedang Main', 'Sewa paket 3 jam sampai 22:00'),
('PS5-02', 'Station VIP 2 (Audio Surround)', 1, 4, 'Sangat Bagus', 'Tersedia', 'Stik 4 buah siap main bareng'),
('PS4-01', 'Station Reguler A (TV 43")', 2, 2, 'Bagus', 'Tersedia', 'Update eFootball terbaru'),
('PS4-02', 'Station Reguler B (TV 43")', 3, 2, 'Bagus', 'Sedang Main', 'Main FIFA 24'),
('PS4-03', 'Station Reguler C (TV 43")', 3, 2, 'Stik R2 Agak Keras', 'Maintenance', 'Sedang diganti karet tombol stik'),
('PS3-01', 'Station Retro 1 (TV 32")', 4, 2, 'Bagus', 'Tersedia', 'Game nostalgia lengkap'),
('NSW-01', 'Station Party Switch', 5, 4, 'Sangat Bagus', 'Tersedia', '4 Joycon untuk main ramai-ramai')
ON DUPLICATE KEY UPDATE kode_unit=VALUES(kode_unit);
