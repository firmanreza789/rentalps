    <!-- Footer Halaman Web -->
    <footer class="footer mt-auto">
        <div class="container text-center">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start mb-2 mb-md-0">
                    <span class="fw-semibold">PowerdBY: Firman reza alamzah</span> &copy; <?= date('Y'); ?>
                </div>
                <div class="col-md-6 text-md-end text-muted small">
                    Sistem Manajemen Rental PlayStation
                </div>
            </div>
        </div>
    </footer>

    <!-- Jendela Pop-up Konfirmasi ngahapus (Modal Bootstrap 5) -->
    <!-- Modal ini secara default disembuyikan dan hanya akan muncul di layar saat tombol hapus diklik -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-custom border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger fw-bold" id="modalHapusLabel">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> Konfirmasi Penghapusan
                    </h5>
                    <!-- Tombol silang untuk menutup modal -->
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-1 text-secondary">Apakah Anda yakin ingin menghapus data unit berikut?</p>
                    <!-- Kotak preview informasi nama dan kode unit yang akan dihapus -->
                    <div class="p-3 bg-light rounded-3 my-2 border">
                        <strong class="d-block text-dark" id="hapusNamaUnit">-</strong>
                        <small class="text-muted" id="hapusKodeUnit">-</small>
                    </div>
                    <small class="text-danger">
                        <i class="bi bi-info-circle me-1"></i> Data yang telah dihapus tidak dapat dipulihkan kembali.
                    </small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <!-- Tombol Batal untuk menutup modal tanpa menghapus data -->
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    
                    <!-- Formulir POST ke hapus.php untuk mengeksekusi penghapusan -->
                    <form method="POST" action="hapus.php" id="formHapusAction" class="d-inline">
                        <!-- Input hidden ini nilainya akan diisi oleh JavaScript dengan ID unit yang dipilih -->
                        <input type="hidden" name="id_unit" id="hapusIdUnit" value="">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pustaka JavaScript Bootstrap 5 untuk mendukung fitur interaktif (dropdown, modal pop-up, alert dismiss) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Skrip JavaScript Sederhana untuk Menghubungkan Tombol Tabel ke Jendela Modal -->
    <script>
        // Tunggu sampai seluruh struktur halaman HTML selesai dimuat oleh browser
        document.addEventListener('DOMContentLoaded', function () {
            const modalHapusElement = document.getElementById('modalHapus');
            if (modalHapusElement) {
                // Event listener 'show.bs.modal' aktif tepat sesaat sebelum jendela modal muncul di layar
                modalHapusElement.addEventListener('show.bs.modal', function (event) {
                    // event.relatedTarget adalah tombol tong sampah (Hapus) spesifik yang baru saja diklik
                    const button = event.relatedTarget;
                    
                    // Ambil nilai atribut data-* yang tertanam pada tombol tersebut
                    const id = button.getAttribute('data-id');
                    const kode = button.getAttribute('data-kode');
                    const nama = button.getAttribute('data-nama');

                    // Masukkan nilai ID ke dalam input hidden form agar siap dikirim ke hapus.php
                    document.getElementById('hapusIdUnit').value = id;
                    // Tampilkan kode dan nama unit ke dalam teks modal agar pengguna tahu unit apa yang akan dihapus
                    document.getElementById('hapusKodeUnit').textContent = 'Kode: ' + kode;
                    document.getElementById('hapusNamaUnit').textContent = nama;
                });
            }
        });
    </script>
</body>
</html>
