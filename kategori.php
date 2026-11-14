<?php
// Judul tab peramban untuk halaman kelola kategori tipe konsol
$page_title = "Kelola Tipe Konsol";

// Sisipkan template header
require_once __DIR__ . '/header.php';

// Buka koneksi database
$pdo = get_db_connection();

// Inisialisasi variabel error dan penampung daftar kategori
$error_message = '';
$kategori_data = [];

// 1. Proses Menambah Tipe Konsol Baru ke Tabel Master
// Pengecekan: Formulir harus dikirim lewat metode POST dan nilai field hidden 'action' harus bernilai 'tambah'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    // Bersihkan spasi berlebih pada input nama, tarif per jam, dan deskripsi
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $tarif_per_jam = trim($_POST['tarif_per_jam'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');

    // Validasi data di sisi server PHP
    if (empty($nama_kategori) || empty($tarif_per_jam)) {
        $error_message = "Nama tipe konsol dan tarif per jam wajib diisi!";
    } elseif ((int)$tarif_per_jam < 1000) {
        $error_message = "Tarif per jam minimal Rp 1.000!";
    } else {
        if ($pdo) {
            try {
                // Query INSERT ke tabel master kategori_konsol dengan Prepared Statement
                $sql_insert = "INSERT INTO kategori_konsol (nama_kategori, tarif_per_jam, deskripsi) 
                               VALUES (:nama, :tarif, :deskripsi)";
                $stmt = $pdo->prepare($sql_insert);
                $stmt->execute([
                    ':nama'      => $nama_kategori,
                    ':tarif'     => (int)$tarif_per_jam, // Pastikan tarif disimpan sebagai angka bilangan bulat
                    ':deskripsi' => $deskripsi,
                ]);

                // Simpan pesan sukses ke session dan refresh halaman kategori
                $_SESSION['pesan_sukses'] = "Tipe konsol baru '{$nama_kategori}' berhasil ditambahkan!";
                header("Location: kategori.php");
                exit;

            } catch (\PDOException $e) {
                log_db_error($e);

                // Kode error SQLSTATE 23000 pada MySQL artinya: "Integrity constraint violation (Duplicate entry)"
                // Ini terjadi jika pengguna mencoba menambahkan nama konsol yang sudah pernah ada di database
                if ($e->getCode() == 23000) {
                    $error_message = "Nama tipe konsol ini sudah ada di daftar!";
                } else {
                    $error_message = "Gagal menyimpan kategori: " . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

// 2. Proses Menghas Tipe Konsol (Uji Coba Keamanan Relasi Foreign Key)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    // Ambil dan validasi ID kategori target
    $id_kategori = filter_input(INPUT_POST, 'id_kategori', FILTER_VALIDATE_INT);

    if ($id_kategori && $pdo) {
        try {
            // Jalankan query DELETE untuk menghapus tipe konsol
            $sql_del = "DELETE FROM kategori_konsol WHERE id_kategori = :id";
            $stmt_del = $pdo->prepare($sql_del);
            $stmt_del->execute([':id' => $id_kategori]);

            // Jika berhasil terhapus (hanya bisa terjadi jika tipe konsol ini tidak dipakai oleh satu pun unit)
            $_SESSION['pesan_sukses'] = "Tipe konsol berhasil dihapus!";
            header("Location: kategori.php");
            exit;

        } catch (\PDOException $e) {
            log_db_error($e);

            // Penjelasan Logika Integritas Referensial Database:
            // Pada tabel unit_rental, kolom id_kategori dipasangi aturan: ON DELETE RESTRICT.
            // Jika ada seseorang yang mencoba menghapus kategori yang masih memiliki unit station aktif di rental,
            // MySQL secara otomatis menolak penghapusan tersebut dan melemparkan Exception error code 23000.
            // Ini menjaga agar database tidak memiliki data 'yatim' (unit yang tidak jelas jenis konsolnya).
            $_SESSION['pesan_error'] = "Tipe konsol tidak bisa dihapus karena masih ada unit PS yang memakainya. Hapus atau pindahkan dulu unit PS terkait.";
            header("Location: kategori.php");
            exit;
        }
    }
}

// 3. Mengambil Seluruh Kategori Sekaligus Menghitung Berapa Unit yang Memakainya
// Penjelasan Query Kompleks (LEFT JOIN + GROUP BY):
// - Mengapa menggunakan LEFT JOIN, bukan INNER JOIN?
//   Karena jika menggunakan INNER JOIN, tipe konsol yang belum memiliki unit (0 unit) tidak akan ikut muncul di tabel.
//   Dengan LEFT JOIN, semua kategori di tabel kiri (kategori_konsol) pasti tampil, meskipun di tabel kanan (unit_rental) datanya belum ada.
// - COUNT(u.id_unit): Menghitung berapa banyak unit station yang terhubung dengan kategori tersebut.
// - GROUP BY k.id_kategori: Mengelompokkan hasil hitungan berdasarkan masing-masing ID kategori.
if ($pdo) {
    try {
        $sql = "SELECT 
                    k.id_kategori, 
                    k.nama_kategori, 
                    k.tarif_per_jam, 
                    k.deskripsi, 
                    COUNT(u.id_unit) AS total_unit_terpasang
                FROM kategori_konsol k
                LEFT JOIN unit_rental u ON k.id_kategori = u.id_kategori
                GROUP BY k.id_kategori
                ORDER BY k.nama_kategori ASC";
        $kategori_data = $pdo->query($sql)->fetchAll();
    } catch (\PDOException $e) {
        log_db_error($e);
        display_db_error_message();
    }
}
?>

<div class="container py-4">

    <!-- Header Halaman dan Tombol Kembali -->
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

    <!-- Kotak Pesan Error jika Ada Masalah -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Kolom Kiri: Formulir Penambahan Tipe Konsol Baru -->
        <div class="col-lg-4">
            <div class="card card-custom p-4">
                <h5 class="fw-bold mb-3 text-dark">
                    <i class="bi bi-plus-circle text-primary me-1"></i> Tambah Tipe Konsol
                </h5>
                <form method="POST" action="kategori.php">
                    <!-- Field tersembunyi (hidden input) untuk menandai aksi 'tambah' -->
                    <input type="hidden" name="action" value="tambah">
                    
                    <!-- Input Nama Tipe Konsol -->
                    <div class="mb-3">
                        <label for="nama_kategori" class="form-label fw-semibold">Nama Tipe Konsol</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" 
                               placeholder="Misal: PlayStation 5 Pro" required>
                    </div>

                    <!-- Input Tarif Sewa per Jam -->
                    <div class="mb-3">
                        <label for="tarif_per_jam" class="form-label fw-semibold">Tarif Sewa per Jam (Rp)</label>
                        <input type="number" class="form-control" id="tarif_per_jam" name="tarif_per_jam" 
                               placeholder="Misal: 25000" step="1000" min="1000" required>
                    </div>

                    <!-- Input Keterangan Fasilitas -->
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label fw-semibold">Deskripsi / Fasilitas</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" 
                                  placeholder="Keterangan spesifikasi TV, game, atau fitur konsol"></textarea>
                    </div>

                    <!-- Tombol Simpan Kategori -->
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Tipe Konsol
                    </button>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Tabel Daftar Tipe Konsol yang Sudah Ada -->
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
                            <?php if (!empty($kategori_data)): ?>
                                <?php foreach ($kategori_data as $kat): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong class="text-dark d-block"><?= htmlspecialchars($kat['nama_kategori']); ?></strong>
                                            <small class="text-muted"><?= htmlspecialchars($kat['deskripsi'] ?? '-'); ?></small>
                                        </td>
                                        <td class="fw-semibold text-success">
                                            <?= format_rupiah($kat['tarif_per_jam']); ?>
                                        </td>
                                        <td>
                                            <!-- Badge yang menampilkan berapa jumlah unit station yang menggunakan tipe ini -->
                                            <span class="badge bg-primary-subtle text-primary border">
                                                <i class="bi bi-tv me-1"></i><?= $kat['total_unit_terpasang']; ?> Unit
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <!-- Form kecil untuk eksekusi hapus kategori -->
                                            <form method="POST" action="kategori.php" class="d-inline" 
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus tipe konsol ini?');">
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
// Tutup koneksi PDO dan panggil footer
$pdo = null;
require_once __DIR__ . '/footer.php'; 
?>
