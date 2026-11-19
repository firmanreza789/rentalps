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
        // ngitung jumlah data buat ringkesan di luhur
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

<h2>Daftar Rental PlayStation</h2>

<!-- ringkesan jumlah data -->
<div class="info-ringkas">
    Total Data: <b><?= $jmlUnit; ?></b> |
    Tersedia: <b><?= $jmlReady; ?></b> |
    Sedang Main: <b><?= $jmlMain; ?></b> |
    Tipe Konsol: <b><?= $jmlKat; ?></b>
</div>

<!-- form neangan data jeung filter -->
<form method="GET" action="index.php" class="form-cari">
    <input type="text" name="search" placeholder="Cari kode, nama, atau tipe PS..." value="<?= htmlspecialchars($cari); ?>">
    <select name="status">
        <option value="">-- Semua Status --</option>
        <option value="Tersedia" <?= $filterStatus === 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
        <option value="Sedang Main" <?= $filterStatus === 'Sedang Main' ? 'selected' : ''; ?>>Sedang Main</option>
        <option value="Maintenance" <?= $filterStatus === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
    </select>
    <button type="submit" class="btn btn-biru">Cari</button>
    <?php if ($cari !== '' || $filterStatus !== ''): ?>
        <a href="index.php" class="btn">Reset</a>
    <?php endif; ?>
    <a href="tambah.php" class="btn btn-biru">+ Tambah</a>
</form>

<!-- tabel daptar unit rental -->
<div class="tabel-scroll">
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Penyewa / Station</th>
                <th>Tipe Konsol</th>
                <th>Tarif/Jam</th>
                <th>Stik</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($units)): ?>
                <?php $no = 1; foreach ($units as $u): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <!-- pake htmlspecialchars meh aman tina serangan script XSS -->
                        <td><b><?= htmlspecialchars($u['kode_unit']); ?></b></td>
                        <td>
                            <?= htmlspecialchars($u['nama_station']); ?><br>
                            <span class="teks-kecil">
                                Kondisi: <?= htmlspecialchars($u['kondisi']); ?>
                                <?= !empty($u['catatan']) ? ' - ' . htmlspecialchars($u['catatan']) : ''; ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($u['nama_kategori']); ?></td>
                        <td><?= format_rupiah($u['tarif_per_jam']); ?></td>
                        <td><?= (int)$u['jumlah_stik']; ?></td>
                        <td>
                            <?php if ($u['status'] === 'Tersedia'): ?>
                                <span class="status-tersedia">Tersedia</span>
                            <?php elseif ($u['status'] === 'Sedang Main'): ?>
                                <span class="status-main">Sedang Main</span>
                            <?php else: ?>
                                <span class="status-rusak">Maintenance</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="edit.php?id=<?= $u['id_unit']; ?>" class="btn btn-biru btn-kecil">Edit</a>
                            <button type="button" class="btn btn-merah btn-kecil"
                                    data-id="<?= $u['id_unit']; ?>"
                                    data-kode="<?= htmlspecialchars($u['kode_unit']); ?>"
                                    data-nama="<?= htmlspecialchars($u['nama_station']); ?>"
                                    onclick="bukaModalHapus(this)">
                                Hapus
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">
                        Belum ada data yang cocok.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// tutup koneksina di akhir file
$pdo = null;
require_once __DIR__ . '/footer.php';
?>
