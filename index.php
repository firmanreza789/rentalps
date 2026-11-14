<?php
$page_title = "Data Unit Rental PS";
require_once __DIR__ . '/header.php';

$pdo = get_db();

// cokot inputan pencarian jeung filter status ti URL (metode GET)
$cari = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$units = [];
$jmlUnit = 0;
$jmlReady = 0;
$jmlMain = 0;
$jmlKat = 0;

if ($pdo) {
    try {
        // ngitung jumlah data buat kartu statistik di luhur
        $jmlUnit  = $pdo->query("SELECT COUNT(*) FROM unit_rental")->fetchColumn();
        $jmlReady = $pdo->query("SELECT COUNT(*) FROM unit_rental WHERE status = 'Tersedia'")->fetchColumn();
        $jmlMain  = $pdo->query("SELECT COUNT(*) FROM unit_rental WHERE status = 'Sedang Main'")->fetchColumn();
        $jmlKat   = $pdo->query("SELECT COUNT(*) FROM kategori_konsol")->fetchColumn();

        // gabungkeun tabel unit_rental jeung kategori_konsol pake INNER JOIN meh ngaran konsolna kaluar
        // WHERE 1=1 dipasang supados gampang nyambungkeun kondisi AND di handapna
        $sql = "SELECT u.id_unit, u.kode_unit, u.nama_station, u.jumlah_stik, u.kondisi, u.status, u.catatan,
                       k.nama_kategori, k.tarif_per_jam
                FROM unit_rental u
                INNER JOIN kategori_konsol k ON u.id_kategori = k.id_kategori
                WHERE 1=1";

        $params = [];

        // lamun user ngetik kata kunci pencarian
        if ($cari !== '') {
            $sql .= " AND (u.kode_unit LIKE :cari OR u.nama_station LIKE :cari OR k.nama_kategori LIKE :cari)";
            $params[':cari'] = "%{$cari}%";
        }

        // lamun user milih filter status ketersediaan
        if ($filterStatus !== '') {
            $sql .= " AND u.status = :status";
            $params[':status'] = $filterStatus;
        }

        $sql .= " ORDER BY u.kode_unit ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $units = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Read Error: " . $e->getMessage());
        tampil_error_db();
    }
}
?>

<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold mb-1 text-dark">
            <i class="bi bi-controller text-primary me-2"></i>Daftar Unit PlayStation
        </h2>
        <p class="text-muted mb-0">Kelola ketersediaan station, tarif sewa, dan kondisi unit rental secara realtime.</p>
    </div>

    <!-- 4 kotak ringkesan statistik -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Total Unit PS</span>
                        <h3 class="fw-bold mb-0 text-dark"><?= $jmlUnit; ?></h3>
                    </div>
                    <div class="fs-2 text-primary opacity-50"><i class="bi bi-tv"></i></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Unit Tersedia</span>
                        <h3 class="fw-bold mb-0 text-success"><?= $jmlReady; ?></h3>
                    </div>
                    <div class="fs-2 text-success opacity-50"><i class="bi bi-check2-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Sedang Main</span>
                        <h3 class="fw-bold mb-0 text-warning"><?= $jmlMain; ?></h3>
                    </div>
                    <div class="fs-2 text-warning opacity-50"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-info border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Tipe Konsol</span>
                        <h3 class="fw-bold mb-0 text-info"><?= $jmlKat; ?></h3>
                    </div>
                    <div class="fs-2 text-info opacity-50"><i class="bi bi-grid-3x3-gap"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- form neangan data jeung filter -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light"
                           placeholder="Cari kode unit, nama station, atau tipe konsol..."
                           value="<?= htmlspecialchars($cari); ?>">
                </div>
            </div>

            <div class="col-md-4">
                <select name="status" class="form-select bg-light">
                    <option value="">-- Semua Status Ketersediaan --</option>
                    <option value="Tersedia" <?= $filterStatus === 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                    <option value="Sedang Main" <?= $filterStatus === 'Sedang Main' ? 'selected' : ''; ?>>Sedang Main</option>
                    <option value="Maintenance" <?= $filterStatus === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Cari / Filter
                </button>
                <?php if ($cari !== '' || $filterStatus !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Reset Pencarian">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- tabel daptar unit rental -->
    <div class="card card-custom overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width: 100px;">Kode</th>
                        <th>Station & Fasilitas</th>
                        <th>Tipe Konsol</th>
                        <th>Tarif Sewa</th>
                        <th>Stik</th>
                        <th>Status</th>
                        <th class="text-end pe-3" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($units)): ?>
                        <?php foreach ($units as $u): ?>
                            <tr>
                                <!-- pake htmlspecialchars meh aman tina serangan script XSS -->
                                <td class="ps-3 fw-bold text-primary font-monospace">
                                    <?= htmlspecialchars($u['kode_unit']); ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= htmlspecialchars($u['nama_station']); ?>
                                    </div>
                                    <small class="text-muted">
                                        Kondisi: <?= htmlspecialchars($u['kondisi']); ?>
                                        <?= !empty($u['catatan']) ? ' &bull; ' . htmlspecialchars($u['catatan']) : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($u['nama_kategori']); ?>
                                    </span>
                                </td>
                                <td class="fw-semibold text-success">
                                    <?= format_rupiah($u['tarif_per_jam']); ?> <small class="text-muted fw-normal">/jam</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-dpad-fill me-1"></i><?= (int)$u['jumlah_stik']; ?> Stik
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'Tersedia'): ?>
                                        <span class="badge badge-tersedia px-2 py-1"><i class="bi bi-check-circle me-1"></i>Tersedia</span>
                                    <?php elseif ($u['status'] === 'Sedang Main'): ?>
                                        <span class="badge badge-main px-2 py-1"><i class="bi bi-play-circle me-1"></i>Sedang Main</span>
                                    <?php else: ?>
                                        <span class="badge badge-maintenance px-2 py-1"><i class="bi bi-tools me-1"></i>Maintenance</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $u['id_unit']; ?>" class="btn btn-outline-primary" title="Edit Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalHapus"
                                                data-id="<?= $u['id_unit']; ?>"
                                                data-kode="<?= htmlspecialchars($u['kode_unit']); ?>"
                                                data-nama="<?= htmlspecialchars($u['nama_station']); ?>"
                                                title="Hapus Data">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                <span class="fw-semibold">Belum ada data unit yang cocok.</span>
                                <p class="small text-secondary mb-0">Coba ubah kata kunci pencarian atau tambahkan unit baru.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// tutup koneksina di akhir file
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
