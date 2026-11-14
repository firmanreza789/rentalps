<?php
$page_title = "Tambah Unit PlayStation Baru";
require_once __DIR__ . '/header.php';

// bukak konek ka databse
$pdo = get_db();

// siapkeun varibel default meh teu error undefined pas form awal dibuka
$kode    = '';
$nama    = '';
$idKat   = '';
$stik    = '2';
$kondisi = 'Bagus';
$status  = 'Tersedia';
$catatan = '';

$err = '';
$listKat = [];

if ($pdo) {
    try {
        // cokot daptar tipe konsol buat eusi pilihan dropdown
        $listKat = $pdo->query("SELECT id_kategori, nama_kategori, tarif_per_jam FROM kategori_konsol ORDER BY nama_kategori ASC")->fetchAll();
    } catch (PDOException $e) {
        error_log("Load Kategori Error: " . $e->getMessage());
    }
}

// dijalankeun pas tombol simpan diklik (metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // bersihan spasi kosong pake trim
    $kode    = trim($_POST['kode_unit'] ?? '');
    $nama    = trim($_POST['nama_station'] ?? '');
    $idKat   = trim($_POST['id_kategori'] ?? '');
    $stik    = trim($_POST['jumlah_stik'] ?? '2');
    $kondisi = trim($_POST['kondisi'] ?? 'Bagus');
    $status  = trim($_POST['status'] ?? 'Tersedia');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($kode === '' || $nama === '' || $idKat === '') {
        $err = "Kode Unit, Nama Station, dan Tipe Konsol wajib diisi ya!";
    } elseif ((int)$stik < 1) {
        $err = "Jumlah stik minimal harus 1!";
    } elseif ($pdo) {
        try {
            // dicek heula bisi kode unitna tos aya nu make (duplikat)
            $cek = $pdo->prepare("SELECT COUNT(*) FROM unit_rental WHERE kode_unit = :kode");
            $cek->execute([':kode' => $kode]);

            if ($cek->fetchColumn() > 0) {
                $err = "Kode Unit '{$kode}' sudah ada di daftar. Coba gunakan kode lain!";
            } else {
                // asupkeun data anyar pake prepared statement meh aman tina SQL injection
                $sql = "INSERT INTO unit_rental (kode_unit, nama_station, id_kategori, jumlah_stik, kondisi, status, catatan)
                        VALUES (:kode, :nama, :id_kat, :stik, :kondisi, :status, :catatan)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':kode'    => strtoupper($kode),
                    ':nama'    => $nama,
                    ':id_kat'  => (int)$idKat,
                    ':stik'    => (int)$stik,
                    ':kondisi' => $kondisi,
                    ':status'  => $status,
                    ':catatan' => $catatan,
                ]);

                // simpen pesen sukses terus balikkeun ka index.php
                $_SESSION['pesan_sukses'] = "Unit PS baru (" . strtoupper($kode) . ") berhasil disimpan!";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Insert Unit Error: " . $e->getMessage());
            $err = "Gagal menyimpan data ke database.";
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <a href="index.php" class="text-decoration-none text-secondary mb-3 d-inline-block">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Unit
            </a>

            <div class="card card-custom p-4">
                <div class="border-bottom pb-3 mb-4">
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Tambah Unit PlayStation
                    </h3>
                    <p class="text-muted small mb-0">Isi data di bawah untuk mendaftarkan station rental konsol baru.</p>
                </div>

                <?php if ($err !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($err); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="tambah.php" novalidate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="kode_unit" class="form-label fw-semibold">Kode Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_unit" name="kode_unit"
                                   placeholder="Contoh: PS5-03" required
                                   value="<?= htmlspecialchars($kode); ?>">
                            <small class="text-muted">Kode unik station.</small>
                        </div>

                        <div class="col-md-8">
                            <label for="nama_station" class="form-label fw-semibold">Nama Station & TV <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_station" name="nama_station"
                                   placeholder="Contoh: Station VIP 3 (TV 55 Inch)" required
                                   value="<?= htmlspecialchars($nama); ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="id_kategori" class="form-label fw-semibold">Tipe Konsol <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_kategori" name="id_kategori" required>
                                <option value="">-- Pilih Tipe Konsol --</option>
                                <?php foreach ($listKat as $kat): ?>
                                    <option value="<?= $kat['id_kategori']; ?>" <?= $idKat == $kat['id_kategori'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']); ?> (<?= format_rupiah($kat['tarif_per_jam']); ?>/jam)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="jumlah_stik" class="form-label fw-semibold">Jumlah Stik</label>
                            <input type="number" class="form-control" id="jumlah_stik" name="jumlah_stik"
                                   min="1" max="8" value="<?= htmlspecialchars($stik); ?>">
                        </div>

                        <div class="col-md-3">
                            <label for="kondisi" class="form-label fw-semibold">Kondisi</label>
                            <select class="form-select" id="kondisi" name="kondisi">
                                <option value="Sangat Bagus" <?= $kondisi === 'Sangat Bagus' ? 'selected' : ''; ?>>Sangat Bagus</option>
                                <option value="Bagus" <?= $kondisi === 'Bagus' ? 'selected' : ''; ?>>Bagus</option>
                                <option value="Cukup" <?= $kondisi === 'Cukup' ? 'selected' : ''; ?>>Cukup</option>
                                <option value="Perlu Servis" <?= $kondisi === 'Perlu Servis' ? 'selected' : ''; ?>>Perlu Servis</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Status Ketersediaan</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Tersedia" <?= $status === 'Tersedia' ? 'selected' : ''; ?>>Tersedia (Siap Disewa)</option>
                                <option value="Sedang Main" <?= $status === 'Sedang Main' ? 'selected' : ''; ?>>Sedang Main</option>
                                <option value="Maintenance" <?= $status === 'Maintenance' ? 'selected' : ''; ?>>Maintenance (Perbaikan)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="catatan" class="form-label fw-semibold">Catatan Tambahan</label>
                            <input type="text" class="form-control" id="catatan" name="catatan"
                                   placeholder="Contoh: Game FIFA 24 & Tekken 8 terpasang"
                                   value="<?= htmlspecialchars($catatan); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Unit
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
