<?php
// Judul tab peramban untuk halaman edit unit
$page_title = "Edit Data Unit PlayStation";

// Sisipkan header template yang berisi navigasi dan session flash message
require_once __DIR__ . '/header.php';

// Buka koneksi PDO ke database
$pdo = get_db_connection();

// 1. Mengambil ID Unit dari Parameter URL (Metode GET)
// Contoh URL: edit.php?id=3
// Fungsi filter_input() dengan FILTER_VALIDATE_INT digunakan agar data yang masuk benar-benar divalidasi sebagai angka bulat.
// Jika pengunjung sengaja mengubah URL menjadi edit.php?id=abc atau karakter jahat, fungsi ini otomatis menghasilkan nilai false.
$id_unit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Jika nilai ID tidak ada atau tidak valid, alihkan kembali pengguna ke tabel utama dengan pesan error
if (!$id_unit) {
    $_SESSION['pesan_error'] = "ID Unit tidak valid atau tidak ditemukan!";
    header("Location: index.php");
    exit;
}

// Inisialisasi variabel formulir dan data penampung
$error_message = '';
$kategori_list = [];
$unit = null;

if ($pdo) {
    try {
        // Ambil daftar seluruh tipe konsol untuk pilihan pada dropdown (<select><option>)
        $stmt_kategori = $pdo->query("SELECT id_kategori, nama_kategori, tarif_per_jam FROM kategori_konsol ORDER BY nama_kategori ASC");
        $kategori_list = $stmt_kategori->fetchAll();

        // 2. Mengambil Data Lama Unit dari Database (Teknik Form Pre-filling)
        // Kita menggunakan query SELECT WHERE dengan Prepared Statement berdasarkan id_unit yang ingin diedit
        $sql_select = "SELECT * FROM unit_rental WHERE id_unit = :id_unit";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute([':id_unit' => $id_unit]);

        // fetch() mengambil satu baris data saja (karena ID bersifat unik/Primary Key)
        $unit = $stmt_select->fetch();

        // Jika setelah dicari di database ternyata ID unit tersebut tidak ditemukan (misal sudah terhapus)
        if (!$unit) {
            $_SESSION['pesan_error'] = "Data unit tidak ditemukan di database!";
            header("Location: index.php");
            exit;
        }

    } catch (\PDOException $e) {
        log_db_error($e);
        display_db_error_message();
    }
}

// Mengisi variabel formulir dengan data lama yang diambil dari database (Form Pre-filling)
// Operator '??' memastikan ada nilai default jika nilai kolom di database bernilai null
$kode_unit    = $unit['kode_unit'] ?? '';
$nama_station = $unit['nama_station'] ?? '';
$id_kategori  = $unit['id_kategori'] ?? '';
$jumlah_stik  = $unit['jumlah_stik'] ?? 2;
$kondisi      = $unit['kondisi'] ?? 'Bagus';
$status       = $unit['status'] ?? 'Tersedia';
$catatan      = $unit['catatan'] ?? '';

// 3. Memproses Pembaruan Data Ketika Tombol "Simpan Perubahan" Ditekan (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Sanitasi input baru dari pengguna menggunakan trim()
    $kode_unit    = trim($_POST['kode_unit'] ?? '');
    $nama_station = trim($_POST['nama_station'] ?? '');
    $id_kategori  = trim($_POST['id_kategori'] ?? '');
    $jumlah_stik  = trim($_POST['jumlah_stik'] ?? '2');
    $kondisi      = trim($_POST['kondisi'] ?? 'Bagus');
    $status       = trim($_POST['status'] ?? 'Tersedia');
    $catatan      = trim($_POST['catatan'] ?? '');

    // B. Validasi data server-side
    if (empty($kode_unit) || empty($nama_station) || empty($id_kategori)) {
        $error_message = "Kode Unit, Nama Station, dan Tipe Konsol tidak boleh kosong!";
    } elseif ((int)$jumlah_stik < 1) {
        $error_message = "Jumlah stik minimal harus 1!";
    } else {
        if ($pdo) {
            try {
                // C. Memeriksa Apakah Kode Unit Baru Bertabrakan dengan Milik Unit LAIN
                // Perhatikan klausa: AND id_unit != :id_unit
                // Artinya: "Cari apakah ada unit lain yang memakai kode ini, tapi KECUALIKAN unit yang sedang diedit ini sendiri."
                // Tanpa pengecualian ini, jika kita hanya mengedit nama station tanpa mengubah kode unit, sistem akan salah mengira kode tersebut duplikat.
                $check_sql = "SELECT COUNT(*) FROM unit_rental WHERE kode_unit = :kode_unit AND id_unit != :id_unit";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([
                    ':kode_unit' => $kode_unit,
                    ':id_unit'   => $id_unit,
                ]);

                if ($check_stmt->fetchColumn() > 0) {
                    $error_message = "Kode Unit '{$kode_unit}' sudah dipakai oleh unit lain!";
                } else {
                    // D. Menjalanken Perintah UPDATE Menggunakan Prepared Statement
                    // Query ini memperbarui nilai kolom pada baris yang memiliki id_unit sesuai target
                    $update_sql = "UPDATE unit_rental SET 
                                      kode_unit    = :kode_unit,
                                      nama_station = :nama_station,
                                      id_kategori  = :id_kategori,
                                      jumlah_stik  = :jumlah_stik,
                                      kondisi      = :kondisi,
                                      status       = :status,
                                      catatan      = :catatan
                                   WHERE id_unit   = :id_unit";

                    $stmt_update = $pdo->prepare($update_sql);

                    // Kirimkan nila-nila baru ke database melalui parameter binding
                    $stmt_update->execute([
                        ':kode_unit'    => strtoupper($kode_unit), // Ubah kode unit menjadi huruf kapital
                        ':nama_station' => $nama_station,
                        ':id_kategori'  => (int)$id_kategori,
                        ':jumlah_stik'  => (int)$jumlah_stik,
                        ':kondisi'      => $kondisi,
                        ':status'       => $status,
                        ':catatan'      => $catatan,
                        ':id_unit'      => $id_unit,               // Target baris data yang diperbarui
                    ]);

                    // Set notifikasi sukses dan arahkan pengguna kembali ke tabel utama
                    $_SESSION['pesan_sukses'] = "Data unit '{$kode_unit}' berhasil diperbarui!";
                    header("Location: index.php");
                    exit;
                }
            } catch (\PDOException $e) {
                log_db_error($e);
                $error_message = "Gagal memperbarui data: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Tombol Kembali -->
            <a href="index.php" class="text-decoration-none text-secondary mb-3 d-inline-block">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Unit
            </a>

            <!-- Kartu Formulir Edit Unit -->
            <div class="card card-custom p-4">
                <div class="border-bottom pb-3 mb-4">
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Unit PlayStation
                    </h3>
                    <p class="text-muted small mb-0">Ubah data di bawah untuk memperbarui status atau spesifikasi unit.</p>
                </div>

                <!-- Kotak Pesan Error jika Ada Masalah -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulir Edit Data: action mengarah ke URL edit.php dengan menyertakan ID unit target -->
                <form method="POST" action="edit.php?id=<?= $id_unit; ?>" novalidate>
                    <div class="row g-3">

                        <!-- Input Kode Unit (Otomatis terisi nilai lama dari database) -->
                        <div class="col-md-4">
                            <label for="kode_unit" class="form-label fw-semibold">Kode Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_unit" name="kode_unit" 
                                   required value="<?= htmlspecialchars($kode_unit); ?>">
                        </div>

                        <!-- Input Nama Station -->
                        <div class="col-md-8">
                            <label for="nama_station" class="form-label fw-semibold">Nama Station & TV <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_station" name="nama_station" 
                                   required value="<?= htmlspecialchars($nama_station); ?>">
                        </div>

                        <!-- Dropdown Pilihan Tipe Konsol: Otomatis memilih tipe konsol yang sedang aktif (Pre-selected) -->
                        <div class="col-md-6">
                            <label for="id_kategori" class="form-label fw-semibold">Tipe Konsol <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_kategori" name="id_kategori" required>
                                <option value="">-- Pilih Tipe Konsol --</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <!-- Jika id_kategori di database sama dengan id_kategori baris ini, cetak atribut 'selected' -->
                                    <option value="<?= $kat['id_kategori']; ?>" <?= $id_kategori == $kat['id_kategori'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']); ?> (<?= format_rupiah($kat['tarif_per_jam']); ?>/jam)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Input Jumlah Stik Controller -->
                        <div class="col-md-3">
                            <label for="jumlah_stik" class="form-label fw-semibold">Jumlah Stik</label>
                            <input type="number" class="form-control" id="jumlah_stik" name="jumlah_stik" 
                                   min="1" max="8" value="<?= htmlspecialchars($jumlah_stik); ?>">
                        </div>

                        <!-- Dropdown Kondisi Fisik Unit -->
                        <div class="col-md-3">
                            <label for="kondisi" class="form-label fw-semibold">Kondisi</label>
                            <select class="form-select" id="kondisi" name="kondisi">
                                <option value="Sangat Bagus" <?= $kondisi === 'Sangat Bagus' ? 'selected' : ''; ?>>Sangat Bagus</option>
                                <option value="Bagus" <?= $kondisi === 'Bagus' ? 'selected' : ''; ?>>Bagus</option>
                                <option value="Cukup" <?= $kondisi === 'Cukup' ? 'selected' : ''; ?>>Cukup</option>
                                <option value="Perlu Servis" <?= $kondisi === 'Perlu Servis' ? 'selected' : ''; ?>>Perlu Servis</option>
                            </select>
                        </div>

                        <!-- Dropdown Status Ketersediaan Unit -->
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Status Ketersediaan</label>
                            <select class="form-select" id="status" name="status">
                                <option value="Tersedia" <?= $status === 'Tersedia' ? 'selected' : ''; ?>>Tersedia (Siap Disewa)</option>
                                <option value="Sedang Main" <?= $status === 'Sedang Main' ? 'selected' : ''; ?>>Sedang Main</option>
                                <option value="Maintenance" <?= $status === 'Maintenance' ? 'selected' : ''; ?>>Maintenance (Perbaikan)</option>
                            </select>
                        </div>

                        <!-- Input Catatan Tambahan -->
                        <div class="col-md-6">
                            <label for="catatan" class="form-label fw-semibold">Catatan Tambahan</label>
                            <input type="text" class="form-control" id="catatan" name="catatan" 
                                   value="<?= htmlspecialchars($catatan); ?>">
                        </div>

                    </div>

                    <!-- Tombol Batal dan Simpan Perubahan -->
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Batal</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php 
// Tutup koneksi PDO dan panggil footer
$pdo = null;
require_once __DIR__ . '/footer.php'; 
?>
