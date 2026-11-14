<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>PlayZone Rental PS</title>
    <!-- Bootstra 5 CSS dari CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --ps-blue: #003791;
            --ps-dark: #0f172a;
            --ps-accent: #0070d1;
        }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-custom {
            background: linear-gradient(135deg, var(--ps-dark) 0%, var(--ps-blue) 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
            background: #ffffff;
        }
        .badge-tersedia {
            background-color: #10b981;
            color: #ffffff;
        }
        .badge-main {
            background-color: #f59e0b;
            color: #ffffff;
        }
        .badge-maintenance {
            background-color: #ef4444;
            color: #ffffff;
        }
        .table th {
            font-weight: 600;
            background-color: #f8fafc;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        .footer {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem 0;
            font-size: 0.9rem;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Navigasi Utama -->
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
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active fw-semibold' : '' ?>" href="index.php">
                            <i class="bi bi-tv me-1"></i> Data Unit PS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tambah.php' ? 'active fw-semibold' : '' ?>" href="tambah.php">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Unit
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'kategori.php' ? 'active fw-semibold' : '' ?>" href="kategori.php">
                            <i class="bi bi-grid me-1"></i> Tipe Konsol
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Notifikasi flash message -->
    <div class="container mt-3">
        <?php if (isset($_SESSION['pesan_sukses'])): ?>
            <div class="alert alert-success alert-dismissible fade show card-custom border-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['pesan_sukses']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['pesan_sukses']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['pesan_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show card-custom border-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($_SESSION['pesan_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['pesan_error']); ?>
        <?php endif; ?>
    </div>
