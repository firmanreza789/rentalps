<?php
// ngajalanken session meh bisa nyimpen notifikasi sementara (flash message)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// deteksi otomatis aplikasi teh lagi dibuka di lokal (XAMPP) atawa di server cloud
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) || php_sapi_name() === 'cli';

if ($isLocal) {
    // setingan databse lokal XAMPP pake NPM
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', '244242008');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // setingan kanggo server online (Clever Cloud)
    define('DB_HOST', 'bo6x166yey4tw68v8x82-mysql.services.clever-cloud.com');
    define('DB_PORT', '3306');
    define('DB_NAME', 'bo6x166yey4tw68v8x82');
    define('DB_USER', 'ubdzexo7enwqsq0j');
    define('DB_PASS', 'XGgr6hQvD9OzQKpQXNyA');
}

// opsi kaamanaan PDO, emulate prepares dipareuman meh aman tina SQL Injection
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// fungsi utama buat nyambungkeun ka database
function get_db() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    try {
        // nyoba muka koneksina ka MySQL
        return new PDO($dsn, DB_USER, DB_PASS, PDO_OPTIONS);
    } catch (PDOException $e) {
        // kode 1049 teh artina databse acan aya, jadi langsung dijieun otomatis
        if ($e->getCode() == 1049) {
            siapkan_db_awal();
            try {
                // coba sambugkan deui saentos tabelna beres dijieun
                return new PDO($dsn, DB_USER, DB_PASS, PDO_OPTIONS);
            } catch (PDOException $ex) {
                error_log("DB Retry Error: " . $ex->getMessage());
                tampil_error_db();
                return null;
            }
        }

        error_log("DB Error [" . $e->getCode() . "]: " . $e->getMessage());
        tampil_error_db();
        return null;
    }
}

// fungsi otomatis nyieun databse jeung data awal lamun di lokal masih kosong
function siapkan_db_awal() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, PDO_OPTIONS);

        // pake tanda backtick (`) sabab ngaran databasena angka NPM wungkul
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        // tabel induk kanggo nyimpen tipe konsol jeung harga sewana
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS kategori_konsol (
                id_kategori INT AUTO_INCREMENT PRIMARY KEY,
                nama_kategori VARCHAR(50) NOT NULL UNIQUE,
                tarif_per_jam INT NOT NULL,
                deskripsi VARCHAR(255) NULL
            ) ENGINE=InnoDB;
        ");

        // tabel anak kanggo unit rental, disambungkeun pake foreign key ka kategori_konsol
        $pdo->exec("
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
                CONSTRAINT fk_unit_kategori 
                    FOREIGN KEY (id_kategori) 
                    REFERENCES kategori_konsol(id_kategori) 
                    ON UPDATE CASCADE 
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB;
        ");

        // lebetkeun data sampel awal meh webna langsung aya isian
        $pdo->exec("
            INSERT INTO kategori_konsol (id_kategori, nama_kategori, tarif_per_jam, deskripsi) VALUES
            (1, 'PlayStation 5 Disc Edition', 20000, 'Konsol generasi terbaru, support 4K 120Hz & HDR, TV 55 Inch'),
            (2, 'PlayStation 4 Pro', 12000, 'Resolusi 4K Enhanced, koleksi game petualangan lengkap'),
            (3, 'PlayStation 4 Slim', 10000, 'Konsol hemat daya, cocok untuk game kasual, eFootball, & FIFA'),
            (4, 'PlayStation 3 Super Slim', 6000, 'Koleksi game retro & nostalgia (GTA V, PES, Dynasty Warriors)'),
            (5, 'Nintendo Switch OLED', 15000, 'Party game multiplayer (Mario Kart, Smash Bros, Overcooked)')
            ON DUPLICATE KEY UPDATE nama_kategori=VALUES(nama_kategori);
        ");

        $pdo->exec("
            INSERT INTO unit_rental (kode_unit, nama_station, id_kategori, jumlah_stik, kondisi, status, catatan) VALUES
            ('PS5-01', 'Station VIP 1 (Sofa Kulit & TV 55\")', 1, 2, 'Sangat Bagus', 'Sedang Main', 'Sewa paket 3 jam sampai 22:00'),
            ('PS5-02', 'Station VIP 2 (Audio Surround)', 1, 4, 'Sangat Bagus', 'Tersedia', 'Stik 4 buah siap main bareng'),
            ('PS4-01', 'Station Reguler A (TV 43\")', 2, 2, 'Bagus', 'Tersedia', 'Update eFootball terbaru'),
            ('PS4-02', 'Station Reguler B (TV 43\")', 3, 2, 'Bagus', 'Sedang Main', 'Main FIFA 24'),
            ('PS4-03', 'Station Reguler C (TV 43\")', 3, 2, 'Stik R2 Agak Keras', 'Maintenance', 'Sedang diganti karet tombol stik'),
            ('PS3-01', 'Station Retro 1 (TV 32\")', 4, 2, 'Bagus', 'Tersedia', 'Game nostalgia lengkap'),
            ('NSW-01', 'Station Party Switch', 5, 4, 'Sangat Bagus', 'Tersedia', '4 Joycon untuk main ramai-ramai')
            ON DUPLICATE KEY UPDATE kode_unit=VALUES(kode_unit);
        ");
    } catch (PDOException $e) {
        error_log("Init DB Error: " . $e->getMessage());
    }
}

// ngarobah angka biasa jadi format duit rupiah
function format_rupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// tampilkeun pesen peringtan lamun MySQL di XAMPP poho acan dihurungkeun
function tampil_error_db() {
    echo "<div style='color:#842029;background:#f8d7da;border:1px solid #f5c2c7;padding:15px;margin:20px auto;max-width:800px;border-radius:8px;text-align:center;font-family:sans-serif;'>";
    echo "<strong>MySQL Belum Nyala:</strong> Buka XAMPP Control Panel lalu klik <em>Start</em> pada baris MySQL.";
    echo "</div>";
}
