<?php
$page_title = "Tambah Data Rental PS";
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
        $err = "Kode Unit, Nama Penyewa/Station, dan Tipe Konsol wajib diisi ya!";
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
                $_SESSION['pesan_sukses'] = "Data rental baru (" . strtoupper($kode) . ") berhasil disimpan!";
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

<h2>Tambah Data Rental PS</h2>

<?php if ($err !== ''): ?>
    <div class="pesan-error"><?= htmlspecialchars($err); ?></div>
<?php endif; ?>

<form method="POST" action="tambah.php">
    <div class="form-grup">
        <label for="kode_unit">Kode Unit *</label>
        <input type="text" id="kode_unit" name="kode_unit" placeholder="Contoh: PS5-02" required value="<?= htmlspecialchars($kode); ?>">
    </div>

    <div class="form-grup">
        <label for="nama_station">Nama Penyewa / Station *</label>
        <input type="text" id="nama_station" name="nama_station" placeholder="Contoh: Firman Reza Alamzah" required value="<?= htmlspecialchars($nama); ?>">
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
        <input type="text" id="catatan" name="catatan" placeholder="Contoh: Main 2 jam" value="<?= htmlspecialchars($catatan); ?>">
    </div>

    <button type="submit" class="btn btn-biru">Simpan</button>
    <a href="index.php" class="btn">Batal</a>
</form>

<?php
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
