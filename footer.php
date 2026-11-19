    </div>

    <div class="footer">
        PowerdBY: Firman reza alamzah &copy; <?= date('Y'); ?> - Aplikasi Rental PlayStation
    </div>

    <!-- pop-up modal konfirmasi ngahapus, aslina disumputkeun heula samemeh tombol hapus diklik -->
    <div class="modal-latar" id="modalHapus">
        <div class="modal-kotak">
            <h3>Konfirmasi Hapus</h3>
            <p>Yakin mau menghapus data ini?</p>
            <p>
                <b id="hapusNamaUnit">-</b><br>
                <span class="teks-kecil" id="hapusKodeUnit">-</span>
            </p>
            <div class="modal-tombol">
                <button type="button" class="btn" onclick="tutupModal()">Batal</button>
                <!-- form kirim pake POST ka hapus.php meh lewih aman -->
                <form method="POST" action="hapus.php" style="display: inline;">
                    <input type="hidden" name="id_unit" id="hapusIdUnit" value="">
                    <button type="submit" class="btn btn-merah">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // buka modal terus oper data-id jeung ngaran tina tombol hapus
        function bukaModalHapus(btn) {
            document.getElementById('hapusIdUnit').value = btn.getAttribute('data-id');
            document.getElementById('hapusKodeUnit').textContent = 'Kode: ' + btn.getAttribute('data-kode');
            document.getElementById('hapusNamaUnit').textContent = btn.getAttribute('data-nama');
            document.getElementById('modalHapus').style.display = 'block';
        }

        function tutupModal() {
            document.getElementById('modalHapus').style.display = 'none';
        }

        // lamun klik di luar kotak modal, tutup otomatis
        window.onclick = function (e) {
            const modal = document.getElementById('modalHapus');
            if (e.target === modal) {
                tutupModal();
            }
        };
    </script>
</body>
</html>
