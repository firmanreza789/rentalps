<?php
require_once __DIR__ . '/config.php';
// nyokot ngaran file nu lagi dibuka meh menu navbarna aktip
$halAktif = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Rental PS</title>
    <!-- file css kustom dipisah ka folder assets -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- bagian navigasi luhur sederhana -->
    <div class="header">
        <div class="header-isi">
            <a class="judul-web" href="index.php">Rental PS Firman</a>
            <div class="menu">
                <a class="<?= $halAktif == 'index.php' ? 'aktif' : '' ?>" href="index.php">Data Unit</a>
                <a class="<?= $halAktif == 'tambah.php' ? 'aktif' : '' ?>" href="tambah.php">Tambah Data</a>
                <a class="<?= $halAktif == 'kategori.php' ? 'aktif' : '' ?>" href="kategori.php">Tipe Konsol</a>
            </div>
        </div>
    </div>

    <div class="wadah">
        <!-- tempat nampilkeun flash message lamun beres nyimpen atawa ngahapus data -->
        <?php if (!empty($_SESSION['pesan_sukses'])): ?>
            <div class="pesan-sukses">
                <?= htmlspecialchars($_SESSION['pesan_sukses']); ?>
            </div>
            <?php unset($_SESSION['pesan_sukses']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['pesan_error'])): ?>
            <div class="pesan-error">
                <?= htmlspecialchars($_SESSION['pesan_error']); ?>
            </div>
            <?php unset($_SESSION['pesan_error']); ?>
        <?php endif; ?>
