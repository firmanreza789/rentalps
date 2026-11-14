<?php
require_once __DIR__ . '/config.php';
// nyokot ngaran file nu lagi dibuka meh menu navbarna hurung (active)
$halAktif = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>PlayZone Rental PS</title>
    <!-- Bootstra 5 CSS ti CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- file css kustom dipisah ka folder assets -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- bagian navigasi luhur -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-controller fs-4 text-warning"></i>
                <span>PlayZone <small class="fw-normal opacity-75">Rental</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $halAktif == 'index.php' ? 'active fw-semibold' : '' ?>" href="index.php">
                            <i class="bi bi-tv me-1"></i> Data Unit PS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $halAktif == 'tambah.php' ? 'active fw-semibold' : '' ?>" href="tambah.php">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Unit
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $halAktif == 'kategori.php' ? 'active fw-semibold' : '' ?>" href="kategori.php">
                            <i class="bi bi-grid me-1"></i> Tipe Konsol
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- tempat nampilkeun flash message lamun beres nyimpen atawa ngahapus data -->
    <div class="container mt-3">
        <?php if (!empty($_SESSION['pesan_sukses'])): ?>
            <div class="alert alert-success alert-dismissible fade show card-custom border-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['pesan_sukses']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['pesan_sukses']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['pesan_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show card-custom border-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['pesan_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['pesan_error']); ?>
        <?php endif; ?>
    </div>
