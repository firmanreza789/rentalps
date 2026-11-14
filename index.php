<?php
// Tentukan judul halaman web yang akan muncul di tab browser
$page_title = "Data Unit Rental PS";

// Sisipkan header.php yang berisi pembuka tag HTML, CSS Bootstrap, dan navigasi menu atas
require_once __DIR__ . '/header.php';

// 1. Panggil fungsi get_db_connection() dari config.php untuk membuka koneksi ke database MySQL
$pdo = get_db_connection();

// 2. Tangkap parameter pencarian dan filter jika ada yang dikirimkan oleh pengguna lewat URL (metode GET)
// Fungsi trim() digunakan untuk membersihkan spasi di awal dan di akhir yang mungkin tidak sengaja terketik
// Operator '??' (null coalescing) memastikan jika variabel $_GET belum ada, variabel diisi nilai string kosong
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

// Siapkan variabel penampung data awal sebelum query dijalankan
$unit_list = [];
$total_unit = 0;
$total_tersedia = 0;
$total_main = 0;
$total_kategori = 0;

if ($pdo) {
    try {
        // A. Mengambil Data Statistik untuk 4 Kotak Ringkasan di Bagian Atas
        // Kita menggunakan metode query() dan fetchColumn() untuk mengambil angka agregat secara cepat
        // COUNT(*) adalah fungsi bawaan SQL untuk menghitung berapa jumlah total baris yang ada di tabel
        $total_unit     = $pdo->query("SELECT COUNT(*) FROM unit_rental")->fetchColumn();
        $total_tersedia = $pdo->query("SELECT COUNT(*) FROM unit_rental WHERE status = 'Tersedia'")->fetchColumn();
        $total_main     = $pdo->query("SELECT COUNT(*) FROM unit_rental WHERE status = 'Sedang Main'")->fetchColumn();
        $total_kategori = $pdo->query("SELECT COUNT(*) FROM kategori_konsol")->fetchColumn();

        // B. Mengambil Data Unit Rental Menggunakan Query Relasi (INNER JOIN)
        // Penjelasan logika query:
        // Di tabel `unit_rental` (kita beri alias 'u'), kolom tipe konsol hanya tersimpan berupa angka ID (id_kategori).
        // Supaya kita bisa menampilkan nama tipe konsol (misal: 'PlayStation 5 Disc Edition') dan tarif per jamnya,
        // kita menggabungkan tabel `unit_rental` dengan tabel `kategori_konsol` (kita beri alias 'k').
        // Hubungannya ditentukan pada klausa: ON u.id_kategori = k.id_kategori.
        $sql = "SELECT 
                    u.id_unit, 
                    u.kode_unit, 
                    u.nama_station, 
                    u.jumlah_stik, 
                    u.kondisi, 
                    u.status, 
                    u.catatan, 
                    k.nama_kategori, 
                    k.tarif_per_jam 
                FROM unit_rental u
                INNER JOIN kategori_konsol k ON u.id_kategori = k.id_kategori
                WHERE 1=1"; // Teknik 'WHERE 1=1' digunakan agar kita bisa dengan mudah menyambung klausa 'AND' di bawahnya

        // Array penampung nilai parameter untuk Prepared Statement (Teknik Anti SQL-Injection)
        $params = [];

        // Jika pengunjung mengetikkan kata kunci di kolom pencarian
        if (!empty($search)) {
            // Operator LIKE digunakan untuk pencarian teks yang fleksibel (tidak harus persis 100%)
            // Simbol '%' di awal dan akhir ($search) artinya kata kunci tersebut boleh berada di posisi mana saja
            $sql .= " AND (u.kode_unit LIKE :search OR u.nama_station LIKE :search OR k.nama_kategori LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        // Jika pengunjung memilih opsi filter status pada dropdown (misal: hanya unit yang 'Tersedia')
        if (!empty($filter_status)) {
            $sql .= " AND u.status = :status";
            $params[':status'] = $filter_status;
        }

        // Urutkan tampilan data berdasarkan kolom kode_unit secara menaik (ASC: A-Z / PS3, PS4, PS5)
        $sql .= " ORDER BY u.kode_unit ASC";

        // 1. prepare(): Server MySQL menyiapkan struktur query SQL terlebih dahulu
        $stmt = $pdo->prepare($sql);

        // 2. execute(): Mengirimkan data filter secara terpisah ke dalam query
        $stmt->execute($params);

        // 3. fetchAll(): Mengambil semua baris data hasil query dan menyimpannya ke dalam variabel array $unit_list
        $unit_list = $stmt->fetchAll();

    } catch (\PDOException $e) {
        // Jika ada kesalahan query atau database, catat ke log dan tampilkan pesan error
        log_db_error($e);
        display_db_error_message();
    }
}
?>

<div class="container py-4">

    <!-- Bagian Judul Halaman -->
    <div class="mb-4">
        <h2 class="fw-bold mb-1 text-dark">
            <i class="bi bi-controller text-primary me-2"></i>Daftar Unit PlayStation
        </h2>
        <p class="text-muted mb-0">Kelola ketersediaan station, tarif sewa, dan kondisi unit rental secara realtime.</p>
    </div>

    <!-- 4 Kotak Ringkasan Informasi (Dashboard Cards) -->
    <div class="row g-3 mb-4">
        <!-- Kotak 1: Total Seluruh Unit Station -->
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Total Unit PS</span>
                        <h3 class="fw-bold mb-0 text-dark"><?= $total_unit; ?></h3>
                    </div>
                    <div class="fs-2 text-primary opacity-50"><i class="bi bi-tv"></i></div>
                </div>
            </div>
        </div>

        <!-- Kotak 2: Unit yang Sedang Kosong / Siap Disewa -->
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Unit Tersedia</span>
                        <h3 class="fw-bold mb-0 text-success"><?= $total_tersedia; ?></h3>
                    </div>
                    <div class="fs-2 text-success opacity-50"><i class="bi bi-check2-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Kotak 3: Unit yang Sedang Dimainkan Pelanggan -->
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Sedang Main</span>
                        <h3 class="fw-bold mb-0 text-warning"><?= $total_main; ?></h3>
                    </div>
                    <div class="fs-2 text-warning opacity-50"><i class="bi bi-clock-history"></i></div>
                </div>
            </div>
        </div>

        <!-- Kotak 4: Jumlah Jenis Konsol yang Terdaftar -->
        <div class="col-6 col-lg-3">
            <div class="card card-custom p-3 border-start border-info border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold">Tipe Konsol</span>
                        <h3 class="fw-bold mb-0 text-info"><?= $total_kategori; ?></h3>
                    </div>
                    <div class="fs-2 text-info opacity-50"><i class="bi bi-grid-3x3-gap"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulir Pencarian dan Filter Data (Mengirim nilai lewat metode GET) -->
    <div class="card card-custom p-3 mb-4">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <!-- Kolom Input Kata Kunci Pencarian -->
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" 
                           placeholder="Cari kode unit, nama station, atau tipe konsol..." 
                           value="<?= htmlspecialchars($search); ?>">
                </div>
            </div>

            <!-- Dropdown Filter Status Ketersediaan -->
            <div class="col-md-4">
                <select name="status" class="form-select bg-light">
                    <option value="">-- Semua Status Ketersediaan --</option>
                    <option value="Tersedia" <?= $filter_status === 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                    <option value="Sedang Main" <?= $filter_status === 'Sedang Main' ? 'selected' : ''; ?>>Sedang Main</option>
                    <option value="Maintenance" <?= $filter_status === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>

            <!-- Tombol Eksekusi Filter dan Tombol Reset Pencarian -->
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Cari / Filter
                </button>
                <?php if (!empty($search) || !empty($filter_status)): ?>
                    <!-- Tombol reset hanya muncul jika sedang ada filter yang aktif -->
                    <a href="index.php" class="btn btn-outline-secondary" title="Reset Pencarian">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Data Unit Rental -->
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
                    <?php if (!empty($unit_list)): ?>
                        <!-- Loop foreach: Membaca satu per satu data baris dari array $unit_list menjadi $row -->
                        <?php foreach ($unit_list as $row): ?>
                            <tr>
                                <!-- htmlspecialchars() wajib dipasang pada semua data string dari database.
                                     Fungsinya mengubah karakter khusus HTML (seperti <, >, ") menjadi entitas teks aman.
                                     Ini mencegah celah keamanan Cross-Site Scripting (XSS). -->
                                <td class="ps-3 fw-bold text-primary font-monospace">
                                    <?= htmlspecialchars($row['kode_unit']); ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= htmlspecialchars($row['nama_station']); ?>
                                    </div>
                                    <small class="text-muted">
                                        Kondisi: <?= htmlspecialchars($row['kondisi']); ?>
                                        <?= !empty($row['catatan']) ? ' &bull; ' . htmlspecialchars($row['catatan']) : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($row['nama_kategori']); ?>
                                    </span>
                                </td>
                                <td class="fw-semibold text-success">
                                    <!-- Format angka tarif sewa ke format mata uang Rupiah menggunakan fungsi buatan kita -->
                                    <?= format_rupiah($row['tarif_per_jam']); ?> <small class="text-muted fw-normal">/jam</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <i class="bi bi-dpad-fill me-1"></i><?= (int)$row['jumlah_stik']; ?> Stik
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Logika percabangan (if-elseif-else) untuk menentukan warna badge status:
                                    // - Hijau untuk unit yang Tersedia
                                    // - Oranye untuk unit yang Sedang Main
                                    // - Merah untuk unit yang Maintenance (Rusak/Servis)
                                    if ($row['status'] === 'Tersedia') {
                                        echo '<span class="badge badge-tersedia px-2 py-1"><i class="bi bi-check-circle me-1"></i>Tersedia</span>';
                                    } elseif ($row['status'] === 'Sedang Main') {
                                        echo '<span class="badge badge-main px-2 py-1"><i class="bi bi-play-circle me-1"></i>Sedang Main</span>';
                                    } else {
                                        echo '<span class="badge badge-maintenance px-2 py-1"><i class="bi bi-tools me-1"></i>Maintenance</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Tombol Edit: Melewatkan parameter ID unit lewat URL (contoh: edit.php?id=3) -->
                                        <a href="edit.php?id=<?= $row['id_unit']; ?>" class="btn btn-outline-primary" title="Edit Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <!-- Tombol Hapus: Menggunakan atribut data-* (data-id, data-kode, data-nama)
                                             Ketika tombol ini diklik, JavaScript pada footer.php akan membaca data-* tersebut
                                             dan otomatis menampilkannya ke dalam jendela pop-up modal konfirmasi hapus. -->
                                        <button type="button" class="btn btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalHapus"
                                                data-id="<?= $row['id_unit']; ?>"
                                                data-kode="<?= htmlspecialchars($row['kode_unit']); ?>"
                                                data-nama="<?= htmlspecialchars($row['nama_station']); ?>"
                                                title="Hapus Data">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Tampilan cadangan jika hasil query pencarian kosong / belum ada data -->
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
// Praktik terbaik: Selalu tutup koneksi PDO di akhir file dengan mengisinya null agar memori RAM server kembali beas
$pdo = null;

// Sisipkan footer.php yang berisi penutup tag HTML, skrip modal konfirmasi, dan library JS Bootstrap
require_once __DIR__ . '/footer.php'; 
?>
