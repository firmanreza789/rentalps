<?php
// Judul tab peramban untuk halaman tambah unit
$page_title = "Tambah Unit PlayStation Baru";

// Sisipken header template yang berisi navigasi dan session flash message
require_once __DIR__ . '/header.php';

// Bukak konek PDO ke database
$pdo = get_db_connection();

// Inisialisasi varibel formulir dengan nilai default (kosong atau nilai standar)
// Tujuannya agar saat halaman pertama kali dibuka, input form tidak menghasilkan PHP Warning "undefined variable"
$kode_unit    = '';
$nama_station = '';
$id_kategori  = '';
$jumlah_stik  = '2'; // Standar rental biasanya menyediakan 2 stik controller per station
$kondisi      = 'Bagus';
$status       = 'Tersedia';
$catatan      = '';

// Siapkan variabel penampung pesan error validasi dan array daftar kategori
$error_message = '';
$kategori_list = [];

if ($pdo) {
    try {
        // Ambil seluruh data tipe konsol dari tabel master `kategori_konsol`
        // Data ini akan kita gunakan untuk mengisi pilihan dropdown (<select><option>) di formulir
        // ORDER BY nama_kategori ASC mengurutkan nama konsol dari huruf A ke Z
        $stmt_kategori = $pdo->query("SELECT id_kategori, nama_kategori, tarif_per_jam FROM kategori_konsol ORDER BY nama_kategori ASC");
        $kategori_list = $stmt_kategori->fetchAll();
    } catch (\PDOException $e) {
        log_db_error($e);
    }
}

// Pemeriksaan Metode Pengiriman Data:
// $_SERVER['REQUEST_METHOD'] bernilai 'POST' HANYA ketika tombol "Simpan Unit" pada formulir ditekan.
// Jika pengunjung baru sekadar membuka halaman web (metode GET), blok kode di bawah ini akan dilewati.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitasi Data Input (Pembersihan Teks)
    // Fungsi trim() berfungsi membuang karakter spasi yang tidak terlihat di awal dan di akhir teks
    // Operator '??' memastikan jika input form kosong atau tidak terkirim, nilainya digantikan dengan string kosong ''
    $kode_unit    = trim($_POST['kode_unit'] ?? '');
    $nama_station = trim($_POST['nama_station'] ?? '');
    $id_kategori  = trim($_POST['id_kategori'] ?? '');
    $jumlah_stik  = trim($_POST['jumlah_stik'] ?? '2');
    $kondisi      = trim($_POST['kondisi'] ?? 'Bagus');
    $status       = trim($_POST['status'] ?? 'Tersedia');
    $catatan      = trim($_POST['catatan'] ?? '');

    // 2. Validasi Server-Side (Pemeriksaan Kebenaran Data di Sisi Server PHP)
    // Validasi ini wajib ada di PHP karena validasi HTML di browser bisa saja di-bypass oleh pengguna jahat.
    if (empty($kode_unit) || empty($nama_station) || empty($id_kategori)) {
        // Kolom kode unit, nama station, dan pilihan tipe konsol adalah data wajib (not null)
        $error_message = "Kode Unit, Nama Station, dan Tipe Konsol wajib diisi ya!";
    } elseif ((int)$jumlah_stik < 1) {
        // Jumlah controller stik minimal harus 1 buah
        $error_message = "Jumlah stik minimal harus 1!";
    } else {
        if ($pdo) {
            try {
                // 3. Pengecekan Duplikasi Kode Unit (Unique Constraint Check)
                // Di database, kolom `kode_unit` memiliki indeks UNIQUE.
                // Sebelum menyimpan, kita jalankan query SELECT COUNT untuk memeriksa apakah kode ini sudah pernah dipakai atau belum.
                $check_sql = "SELECT COUNT(*) FROM unit_rental WHERE kode_unit = :kode_unit";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([':kode_unit' => $kode_unit]);

                // fetchColumn() mengembalikan angka hasil COUNT (0 jika belum ada, 1 jika sudah ada)
                if ($check_stmt->fetchColumn() > 0) {
                    $error_message = "Kode Unit '{$kode_unit}' sudah ada di daftar. Coba gunakan kode lain!";
                } else {
                    // 4. Proses Simpan Data Baru (Operasi CREATE) Menggunakan PREPARED STATEMENT
                    // Mengapa kita menggunakan placeholder bertanda titik dua (:kode_unit, :nama_station, dll)?
                    // Karena teknik Prepared Statement memisahkan struktur perintah SQL dari isi data pengguna.
                    // Server database MySQL akan mengompilasi query SQL terlebih dahulu, baru kemudian menyuntikkan datanya.
                    // Dengan cara ini, sekalipun pengguna mengetik tanda kutip (') atau kode SQL berbahaya,
                    // nilainya tetap diperlakukan murni sebagai teks biasa, BUKAN sebagai perintah SQL (Anti SQL-Injection).
                    $insert_sql = "INSERT INTO unit_rental 
                                   (kode_unit, nama_station, id_kategori, jumlah_stik, kondisi, status, catatan) 
                                   VALUES 
                                   (:kode_unit, :nama_station, :id_kategori, :jumlah_stik, :kondisi, :status, :catatan)";
                    
                    // Siapkan cetak biru query ke server MySQL
                    $stmt_insert = $pdo->prepare($insert_sql);

                    // Eksekusi dan kirimkan data input yang sudah divalidasi ke masing-masing placeholder
                    $stmt_insert->execute([
                        ':kode_unit'    => strtoupper($kode_unit), // Ubah kode unit menjadi huruf kapital semua (misal: ps5-01 jadi PS5-01)
                        ':nama_station' => $nama_station,
                        ':id_kategori'  => (int)$id_kategori,       // Pastikan nilai ID kategori berupa angka bulat (integer)
                        ':jumlah_stik'  => (int)$jumlah_stik,       // Pastikan jumlah stik berupa angka bulat
                        ':kondisi'      => $kondisi,
                        ':status'       => $status,
                        ':catatan'      => $catatan,
                    ]);

                    // Simpan pesan keberhasilan ke dalam session flash message
                    $_SESSION['pesan_sukses'] = "Unit PS baru ({$kode_unit}) berhasil disimpan!";

                    // Alihkan (redirect) halaman kembali ke tabel data utama (index.php)
                    header("Location: index.php");

                    // PENTING: Selalu tuliskan exit setelah pemanggilan header("Location: ...")
                    // agar script PHP di bawahnya langsung berhenti dan tidak lanjut dieksekusi oleh server
                    exit;
                }
            } catch (\PDOException $e) {
                // Jika terjadi error pada proses penyimpanan, catat ke log dan simpan pesannya ke variabel error
                log_db_error($e);
                $error_message = "Gagal menyimpan data ke database: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Tombol Kembali ke Halaman Tabel Utama -->
            <a href="index.php" class="text-decoration-none text-secondary mb-3 d-inline-block">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Unit
            </a>

            <!-- Kartu Formulir Tambah Unit -->
            <div class="card card-custom p-4">
                <div class="border-bottom pb-3 mb-4">
                    <h3 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Tambah Unit PlayStation
                    </h3>
                    <p class="text-muted small mb-0">Isi data di bawah untuk mendaftarkan station rental konsol baru.</p>
                </div>

                <!-- Kotak Pesan Peringatan jika Validasi Gagal -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulir Tambah Data dengan Metode POST -->
                <form method="POST" action="tambah.php" novalidate>
                    <div class="row g-3">

                        <!-- Input Kode Unit: Menampilkan kembali nilai yang sudah diketik jika submit gagal (Sticky Form) -->
                        <div class="col-md-4">
                            <label for="kode_unit" class="form-label fw-semibold">Kode Unit <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="kode_unit" name="kode_unit" 
                                   placeholder="Contoh: PS5-03" required 
                                   value="<?= htmlspecialchars($kode_unit); ?>">
                            <small class="text-muted">Kode unik station.</small>
                        </div>

                        <!-- Input Nama Station & Televisi -->
                        <div class="col-md-8">
                            <label for="nama_station" class="form-label fw-semibold">Nama Station & TV <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_station" name="nama_station" 
                                   placeholder="Contoh: Station VIP 3 (TV 55 Inch)" required 
                                   value="<?= htmlspecialchars($nama_station); ?>">
                        </div>

                        <!-- Dropdown Pilihan Tipe Konsol (Diambil secara dinamis dari tabel kategori_konsol) -->
                        <div class="col-md-6">
                            <label for="id_kategori" class="form-label fw-semibold">Tipe Konsol <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_kategori" name="id_kategori" required>
                                <option value="">-- Pilih Tipe Konsol --</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <!-- Logika ternary: jika id_kategori cocok dengan pilihan sebelumnya, tandai sebagai 'selected' -->
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

                        <!-- Dropdown Pilihan Kondisi Fisik Unit -->
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

                        <!-- Input Teks Catatan Tambahan (Opsional / Boleh Kosong) -->
                        <div class="col-md-6">
                            <label for="catatan" class="form-label fw-semibold">Catatan Tambahan</label>
                            <input type="text" class="form-control" id="catatan" name="catatan" 
                                   placeholder="Contoh: Game FIFA 24 & Tekken 8 terpasang" 
                                   value="<?= htmlspecialchars($catatan); ?>">
                        </div>

                    </div>

                    <!-- Tombol Aksi Batal dan Simpan -->
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
// Tutup koneksi PDO dan sertakan footer
$pdo = null;
require_once __DIR__ . '/footer.php'; 
?>
