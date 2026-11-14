<?php
$page_title = "Kelola Tipe Konsol";
require_once __DIR__ . '/header.php';

$pdo = get_db();
$err = '';
$dataKat = [];

$aksi = $_POST['action'] ?? '';

// proses nambah tipe konsol anyar ka tabel master
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'tambah') {
    $nama  = trim($_POST['nama_kategori'] ?? '');
    $tarif = trim($_POST['tarif_per_jam'] ?? '');
    $desk  = trim($_POST['deskripsi'] ?? '');

    if ($nama === '' || $tarif === '') {
        $err = "Nama tipe konsol dan tarif per jam wajib diisi!";
    } elseif ((int)$tarif < 1000) {
        $err = "Tarif per jam minimal Rp 1.000!";
    } elseif ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori_konsol (nama_kategori, tarif_per_jam, deskripsi) VALUES (:nama, :tarif, :desk)");
            $stmt->execute([
                ':nama'  => $nama,
                ':tarif' => (int)$tarif,
                ':desk'  => $desk,
            ]);

            $_SESSION['pesan_sukses'] = "Tipe konsol baru '{$nama}' berhasil ditambahkan!";
            header("Location: kategori.php");
            exit;
        } catch (PDOException $e) {
            error_log("Insert Kategori Error: " . $e->getMessage());
            // kode 23000 artina duplicate entry (ngaran konsolna tos aya)
            $err = ($e->getCode() == 23000)
                ? "Nama tipe konsol ini sudah ada di daftar!"
                : "Gagal menyimpan kategori.";
        }
    }
}

// proses ngahapus tipe konsol (uji coba kaamanaan relasi foreign key)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'hapus') {
    $idKat = filter_input(INPUT_POST, 'id_kategori', FILTER_VALIDATE_INT);

    if ($idKat && $pdo) {
        try {
            $del = $pdo->prepare("DELETE FROM kategori_konsol WHERE id_kategori = :id");
            $del->execute([':id' => $idKat]);

            $_SESSION['pesan_sukses'] = "Tipe konsol berhasil dihapus!";
            header("Location: kategori.php");
            exit;
        } catch (PDOException $e) {
            error_log("Delete Kategori Error: " . $e->getMessage());
            // ditolak ku aturan ON DELETE RESTRICT lamun masih aya unit PS nu make kategori ieu
            $_SESSION['pesan_error'] = "Tipe konsol tidak bisa dihapus karena masih ada unit PS yang memakainya. Hapus atau pindahkan dulu unit PS terkait.";
            header("Location: kategori.php");
            exit;
        }
    }
}

if ($pdo) {
    try {
        // pake LEFT JOIN supados kategori nu acan gaduh unit tetep kaluar di tabel (jumlahna 0)
        $sql = "SELECT k.id_kategori, k.nama_kategori, k.tarif_per_jam, k.deskripsi,
                       COUNT(u.id_unit) AS total_unit
                FROM kategori_konsol k
                LEFT JOIN unit_rental u ON k.id_kategori = u.id_kategori
                GROUP BY k.id_kategori
                ORDER BY k.nama_kategori ASC";
        $dataKat = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Read Kategori Error: " . $e->getMessage());
        tampil_error_db();
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-dark">
                <i class="bi bi-grid text-primary me-2"></i>Kelola Tipe Konsol
            </h2>
            <p class="text-muted mb-0">Atur jenis PlayStation, tarif sewa per jam, dan fasilitas station.</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Unit PS
        </a>
    </div>

    <?php if ($err !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($err); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- kolom kenca: form tambah tipe konsol -->
        <div class="col-lg-4">
            <div class="card card-custom p-4">
                <h5 class="fw-bold mb-3 text-dark">
                    <i class="bi bi-plus-circle text-primary me-1"></i> Tambah Tipe Konsol
                </h5>
                <form method="POST" action="kategori.php">
                    <input type="hidden" name="action" value="tambah">

                    <div class="mb-3">
                        <label for="nama_kategori" class="form-label fw-semibold">Nama Tipe Konsol</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori"
                               placeholder="Misal: PlayStation 5 Pro" required>
                    </div>

                    <div class="mb-3">
                        <label for="tarif_per_jam" class="form-label fw-semibold">Tarif Sewa per Jam (Rp)</label>
                        <input type="number" class="form-control" id="tarif_per_jam" name="tarif_per_jam"
                               placeholder="Misal: 25000" step="1000" min="1000" required>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label fw-semibold">Deskripsi / Fasilitas</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"
                                  placeholder="Keterangan spesifikasi TV, game, atau fitur konsol"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Tipe Konsol
                    </button>
                </form>
            </div>
        </div>

        <!-- kolom katuhu: tabel daptar tipe konsol -->
        <div class="col-lg-8">
            <div class="card card-custom overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Tipe Konsol</th>
                                <th>Tarif / Jam</th>
                                <th>Unit Terpasang</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dataKat)): ?>
                                <?php foreach ($dataKat as $kat): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong class="text-dark d-block"><?= htmlspecialchars($kat['nama_kategori']); ?></strong>
                                            <small class="text-muted"><?= htmlspecialchars($kat['deskripsi'] ?? '-'); ?></small>
                                        </td>
                                        <td class="fw-semibold text-success">
                                            <?= format_rupiah($kat['tarif_per_jam']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border">
                                                <i class="bi bi-tv me-1"></i><?= $kat['total_unit']; ?> Unit
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <form method="POST" action="kategori.php" class="d-inline"
                                                  onsubmit="return confirm('Yakin bade ngahapus tipe konsol ieu?');">
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="id_kategori" value="<?= $kat['id_kategori']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus Kategori">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada tipe konsol.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
