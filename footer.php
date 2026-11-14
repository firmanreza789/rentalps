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

    <!-- pop-up modal konfirmasi ngahapus, aslina disumputkeun heula samemeh tombol sampah diklik -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-custom border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-danger fw-bold" id="modalHapusLabel">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> Konfirmasi Penghapusan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-1 text-secondary">Apakah Anda yakin ingin menghapus data unit berikut?</p>
                    <div class="p-3 bg-light rounded-3 my-2 border">
                        <strong class="d-block text-dark" id="hapusNamaUnit">-</strong>
                        <small class="text-muted" id="hapusKodeUnit">-</small>
                    </div>
                    <small class="text-danger">
                        <i class="bi bi-info-circle me-1"></i> Data yang telah dihapus tidak dapat dipulihkan kembali.
                    </small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <!-- form kirim pake POST ka hapus.php meh lewih aman -->
                    <form method="POST" action="hapus.php" id="formHapusAction" class="d-inline">
                        <input type="hidden" name="id_unit" id="hapusIdUnit" value="">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3-fill me-1"></i> Ya, Hapus Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('modalHapus');
            if (!modalEl) return;

            // pas modal rek tampil, cokot data-id jeung ngaran unit tina tombol nu diklik
            modalEl.addEventListener('show.bs.modal', function (e) {
                const btn = e.relatedTarget;
                document.getElementById('hapusIdUnit').value = btn.getAttribute('data-id');
                document.getElementById('hapusKodeUnit').textContent = 'Kode: ' + btn.getAttribute('data-kode');
                document.getElementById('hapusNamaUnit').textContent = btn.getAttribute('data-nama');
            });
        });
    </script>
</body>
</html>
