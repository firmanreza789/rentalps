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

<h2>Kelola Tipe Konsol</h2>

<?php if ($err !== ''): ?>
    <div class="pesan-error"><?= htmlspecialchars($err); ?></div>
<?php endif; ?>

<!-- form tambah kategori sederhana -->
<div class="kotak-form">
    <h3>Tambah Tipe Konsol Baru</h3>
    <form method="POST" action="kategori.php">
        <input type="hidden" name="action" value="tambah">

        <div class="form-grup">
            <label for="nama_kategori">Nama Tipe Konsol *</label>
            <input type="text" id="nama_kategori" name="nama_kategori" placeholder="Contoh: PlayStation 5 Pro" required>
        </div>

        <div class="form-grup">
            <label for="tarif_per_jam">Tarif per Jam (Rp) *</label>
            <input type="number" id="tarif_per_jam" name="tarif_per_jam" placeholder="Contoh: 20000" step="1000" min="1000" required>
        </div>

        <div class="form-grup">
            <label for="deskripsi">Deskripsi / Fasilitas</label>
            <input type="text" id="deskripsi" name="deskripsi" placeholder="Contoh: TV 43 inch, 2 stik">
        </div>

        <button type="submit" class="btn btn-biru">Simpan Kategori</button>
    </form>
</div>

<!-- tabel daptar tipe konsol -->
<div class="tabel-scroll">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tipe Konsol</th>
                <th>Tarif / Jam</th>
                <th>Jumlah Unit</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($dataKat)): ?>
                <?php $no = 1; foreach ($dataKat as $kat): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <b><?= htmlspecialchars($kat['nama_kategori']); ?></b><br>
                            <span class="teks-kecil"><?= htmlspecialchars($kat['deskripsi'] ?? '-'); ?></span>
                        </td>
                        <td><?= format_rupiah($kat['tarif_per_jam']); ?></td>
                        <td><?= $kat['total_unit']; ?> unit</td>
                        <td>
                            <form method="POST" action="kategori.php" style="display: inline;"
                                  onsubmit="return confirm('Yakin bade ngahapus tipe konsol ieu?');">
                                <input type="hidden" name="action" value="hapus">
                                <input type="hidden" name="id_kategori" value="<?= $kat['id_kategori']; ?>">
                                <button type="submit" class="btn btn-merah btn-kecil">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 15px;">Belum ada tipe konsol.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
