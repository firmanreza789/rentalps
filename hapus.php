<?php
require_once __DIR__ . '/config.php';

// pengmanan metode kirim data: ngahapus wajib liwat POST meh teu bisa ditembak langsung ti URL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['pesan_error'] = "Akses ditolak! Penghapusan harus melalui tombol konfirmasi hapus.";
    header("Location: index.php");
    exit;
}

// pariksa id unit tina form modal beneran angka bulat atawa lain
$id = filter_input(INPUT_POST, 'id_unit', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['pesan_error'] = "ID Unit tidak valid!";
    header("Location: index.php");
    exit;
}

$pdo = get_db();
if (!$pdo) {
    $_SESSION['pesan_error'] = "Database terputus. Tidak bisa menghapus data.";
    header("Location: index.php");
    exit;
}

try {
    // cokot heula ngaran unitna meh bisa ditampilkeun dina notifikasi sukses
    $cek = $pdo->prepare("SELECT kode_unit, nama_station FROM unit_rental WHERE id_unit = :id");
    $cek->execute([':id' => $id]);
    $unit = $cek->fetch();

    if (!$unit) {
        $_SESSION['pesan_error'] = "Data unit yang mau dihapus sudah tidak ada di database!";
    } else {
        // jalanken query DELETE pake prepared statement
        $del = $pdo->prepare("DELETE FROM unit_rental WHERE id_unit = :id");
        $del->execute([':id' => $id]);
        $_SESSION['pesan_sukses'] = "Unit '{$unit['kode_unit']} - {$unit['nama_station']}' berhasil dihapus!";
    }
} catch (PDOException $e) {
    error_log("Delete Unit Error: " . $e->getMessage());
    $_SESSION['pesan_error'] = "Gagal menghapus data unit.";
}

// tutup koneksina terus balikkeun ka halaman utama
$pdo = null;
header("Location: index.php");
exit;
