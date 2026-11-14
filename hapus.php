<?php
// Sisipkan file konfigurasi database
require_once __DIR__ . '/config.php';

// 1. Pengmanan Metode Pengiriman Data (Security Best Practice)
// Mengapa penghapusan data HARUS menggunakan metode POST, bukan link biasa (metode GET)?
// Alasan Keamanan:
// Jika kita menggunakan link GET (contoh: hapus.php?id=3), link tersebut bisa tidak sengaja diakses oleh web crawler mesin pencari,
// fitur pre-fetching browser, atau saat link tidak sengaja di-klik/di-copy orang lain.
// Dengan mewajikenn metode POST, penghapusan hanya bisa dieksekusi jika pengguna secara sadar menekan tombol di form pop-up konfirmasi.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['pesan_error'] = "Akses ditolak! Penghapusan harus melalui tombol konfirmasi hapus.";
    header("Location: index.php");
    exit;
}

// 2. Mengambil dan Memvalidasi ID Unit dari Form Modal Pop-up
// filter_input() memastikan data yang masuk ke variabel $id_unit benar-benar bilangan bulat murni (integer).
// Jika ada yang mencoba menyuntikkan karakter teks atau script jahat, hasilnya otomatis bernilai false.
$id_unit = filter_input(INPUT_POST, 'id_unit', FILTER_VALIDATE_INT);

// Jika ID tidak valid atau kosong, batalkan proses dan kembalikan ke tabel utama
if (!$id_unit) {
    $_SESSION['pesan_error'] = "ID Unit tidak valid!";
    header("Location: index.php");
    exit;
}

// Buka koneksi PDO
$pdo = get_db_connection();

if ($pdo) {
    try {
        // 3. Mengambil Informasi Unit Terlebih Dahulu Sebelum Dihapus
        // Tujuannya agar kita tahu nama kode dan nama station-nya (misal: 'PS5-01 - Station VIP 1'),
        // sehingga kita bisa menampilkan nama tersebut di pesan notifikasi hijau setelah data berhasil dihapus.
        $stmt_check = $pdo->prepare("SELECT kode_unit, nama_station FROM unit_rental WHERE id_unit = :id_unit");
        $stmt_check->execute([':id_unit' => $id_unit]);
        $unit = $stmt_check->fetch();

        // Jika data unit yang dicari ternyata tidak ada di database
        if (!$unit) {
            $_SESSION['pesan_error'] = "Data unit yang mau dihapus sudah tidak ada di database!";
        } else {
            // 4. Menjalankan Perintah DELETE Menggunakan Prepared Statement
            // Query DELETE menghapus baris data pada tabel unit_rental yang memiliki id_unit sesuai parameter
            $sql_delete = "DELETE FROM unit_rental WHERE id_unit = :id_unit";
            $stmt_delete = $pdo->prepare($sql_delete);
            
            // Eksekusi query dengan menyuntikkan ID unit secara aman
            $stmt_delete->execute([':id_unit' => $id_unit]);

            // Buat pesan notifikasi sukses dengan menyebutkan kode dan nama unit yang berhasil dihapus
            $_SESSION['pesan_sukses'] = "Unit '{$unit['kode_unit']} - {$unit['nama_station']}' berhasil dihapus!";
        }

    } catch (\PDOException $e) {
        // Jika ada kendala pada database (misal tabel terkunci atau kendala koneksi),
        // catat error ke log tersembunyi dan tampilkan pesan kesalahan ke pengguna
        log_db_error($e);
        $_SESSION['pesan_error'] = "Gagal menghapus data: " . htmlspecialchars($e->getMessage());
    } finally {
        // Blok FINALLY: Bagian yang selalu dieksekusi baik ada error maupun tidak
        // Selalu tutup koneksi PDO di akhir proses
        $pdo = null;
    }
} else {
    $_SESSION['pesan_error'] = "Database terputus. Tidak bisa menghapus data.";
}

// 5. Kembalikan pengguna ke halaman daftar unit utama (index.php)
header("Location: index.php");
exit;
?>
