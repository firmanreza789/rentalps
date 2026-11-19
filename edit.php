<?php
$page_title = "Edit Data Rental PS";
require_once __DIR__ . '/header.php';

// cokot id unit ti URL terus pastikeun beneran angka bulat
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['pesan_error'] = "ID Unit tidak valid atau tidak ditemukan!";
    header("Location: index.php");
    exit;
}

$pdo = get_db();
$err = '';
$listKat = [];
$data = null;

if ($pdo) {
    try {
        $listKat = $pdo->query("SELECT id_kategori, nama_kategori, tarif_per_jam FROM kategori_konsol ORDER BY nama_kategori ASC")->fetchAll();

        // cokot data heubeul ti databse kanggo ngeusi form otomatis (form pre-filling)
        $stmt = $pdo->prepare("SELECT * FROM unit_rental WHERE id_unit = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            $_SESSION['pesan_error'] = "Data unit tidak ditemukan di database!";
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Select Edit Error: " . $e->getMessage());
        tampil_error_db();
    }
}

// pasang data heubeul kana varibel form
$kode    = $data['kode_unit'] ?? '';
$nama    = $data['nama_station'] ?? '';
$idKat   = $data['id_kategori'] ?? '';
$stik    = $data['jumlah_stik'] ?? 2;
$kondisi = $data['kondisi'] ?? 'Bagus';
$status  = $data['status'] ?? 'Tersedia';
$catatan = $data['catatan'] ?? '';

// proses update pas tombol simpan perubahan diteken
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode    = trim($_POST['kode_unit'] ?? '');
    $nama    = trim($_POST['nama_station'] ?? '');
    $idKat   = trim($_POST['id_kategori'] ?? '');
    $stik    = trim($_POST['jumlah_stik'] ?? '2');
    $kondisi = trim($_POST['kondisi'] ?? 'Bagus');
    $status  = trim($_POST['status'] ?? 'Tersedia');
    $catatan = trim($_POST['catatan'] ?? '');

    if ($kode === '' || $nama === '' || $idKat === '') {
        $err = "Kode Unit, Nama Penyewa/Station, dan Tipe Konsol tidak boleh kosong!";
    } elseif ((int)$stik < 1) {
        $err = "Jumlah stik minimal harus 1!";
    } elseif ($pdo) {
        try {
            // pariksa bisi kode unitna bentrok jeung unit sejen (kecualikeun id_unit nu lagi diedit ieu)
            $cek = $pdo->prepare("SELECT COUNT(*) FROM unit_rental WHERE kode_unit = :kode AND id_unit != :id");
            $cek->execute([':kode' => $kode, ':id' => $id]);

            if ($cek->fetchColumn() > 0) {
                $err = "Kode Unit '{$kode}' sudah dipakai oleh unit lain!";
            } else {
                // jalanken perintah UPDATE pake prepared statement
                $sql = "UPDATE unit_rental SET
                            kode_unit    = :kode,
                            nama_station = :nama,
                            id_kategori  = :id_kat,
                            jumlah_stik  = :stik,
                            kondisi      = :kondisi,
                            status       = :status,
                            catatan      = :catatan
                        WHERE id_unit = :id";

                $upd = $pdo->prepare($sql);
                $upd->execute([
                    ':kode'    => strtoupper($kode),
                    ':nama'    => $nama,
                    ':id_kat'  => (int)$idKat,
                    ':stik'    => (int)$stik,
                    ':kondisi' => $kondisi,
                    ':status'  => $status,
                    ':catatan' => $catatan,
                    ':id'      => $id,
                ]);

                $_SESSION['pesan_sukses'] = "Data unit '" . strtoupper($kode) . "' berhasil diperbarui!";
                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Update Unit Error: " . $e->getMessage());
            $err = "Gagal memperbarui data unit.";
        }
    }
}
?>

<h2>Edit Data Rental PS</h2>

<?php if ($err !== ''): ?>
    <div class="pesan-error"><?= htmlspecialchars($err); ?></div>
<?php endif; ?>

<form method="POST" action="edit.php?id=<?= $id; ?>">
    <div class="form-grup">
        <label for="kode_unit">Kode Unit *</label>
        <input type="text" id="kode_unit" name="kode_unit" required value="<?= htmlspecialchars($kode); ?>">
    </div>

    <div class="form-grup">
        <label for="nama_station">Nama Penyewa / Station *</label>
        <input type="text" id="nama_station" name="nama_station" required value="<?= htmlspecialchars($nama); ?>">
    </div>

    <div class="form-grup">
        <label for="id_kategori">Tipe Konsol *</label>
        <select id="id_kategori" name="id_kategori" required>
            <option value="">-- Pilih Tipe Konsol --</option>
            <?php foreach ($listKat as $kat): ?>
                <option value="<?= $kat['id_kategori']; ?>" <?= $idKat == $kat['id_kategori'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($kat['nama_kategori']); ?> (<?= format_rupiah($kat['tarif_per_jam']); ?>/jam)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-grup">
        <label for="jumlah_stik">Jumlah Stik</label>
        <input type="number" id="jumlah_stik" name="jumlah_stik" min="1" max="8" value="<?= htmlspecialchars($stik); ?>">
    </div>

    <div class="form-grup">
        <label for="kondisi">Kondisi Unit</label>
        <select id="kondisi" name="kondisi">
            <option value="Sangat Bagus" <?= $kondisi === 'Sangat Bagus' ? 'selected' : ''; ?>>Sangat Bagus</option>
            <option value="Bagus" <?= $kondisi === 'Bagus' ? 'selected' : ''; ?>>Bagus</option>
            <option value="Cukup" <?= $kondisi === 'Cukup' ? 'selected' : ''; ?>>Cukup</option>
            <option value="Perlu Servis" <?= $kondisi === 'Perlu Servis' ? 'selected' : ''; ?>>Perlu Servis</option>
        </select>
    </div>

    <div class="form-grup">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="Tersedia" <?= $status === 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
            <option value="Sedang Main" <?= $status === 'Sedang Main' ? 'selected' : ''; ?>>Sedang Main</option>
            <option value="Maintenance" <?= $status === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
        </select>
    </div>

    <div class="form-grup">
        <label for="catatan">Catatan</label>
        <input type="text" id="catatan" name="catatan" value="<?= htmlspecialchars($catatan); ?>">
    </div>

    <button type="submit" class="btn btn-biru">Simpan Perubahan</button>
    <a href="index.php" class="btn">Batal</a>
</form>

<?php
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
