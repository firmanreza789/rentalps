# PlayZone Rental PlayStation

Aplikas manajemen inventaris dan pemantauan stasiun rental PlayStation berbasis PHP 8 Native (PDO) dan MySQL. Proyek ini dibuat untuk mencatat daftar unit konsol, memantau status ketersediaan meja bermain secara real-time, serta mengelola tarif sewa per jam berdasarkan kategori konsol.

Setiap unt stasiun yang terdaftar terhubung langsung dengan kategori konsol melalui relasi database. Pengelola dapat memantau ringkasan jumlah unit yang tersedia, sedang dimainkan, maupun dalam perbaikan (maintenance), serta memperbarui status operasional stasiun dengan cepat melalui antarmuka dasbor.

## Fitur Utama

### Halaman Dasbor & Daftar Unit (`index.php`)
- Menampilkan kartu statistik ringkasan jumlah total unit, unit berstatus `Tersedia`, `Sedang Main`, dan `Maintenance`.
- Menampilkan tabel inventaris unit rental dari hasil `INNER JOIN` antara tabel `unit_rental` dan `kategori_konsol`.
- Filter berdasarkan status ketersediaan dan pencarian kata kunci (kode unit atau nama stasiun) menggunakan metode `GET`.

### Halaman Tambah Unit (`tambah.php`)
- Formulir pendaftaran unit stasiun baru dengan pilihan kategori konsol dinamis dari database.
- Validasi kelengkapan input dan pemeriksaan keunikan `kode_unit` agar tidak ada kode stasiun yang ganda.

### Halaman Edit Unit (`edit.php`)
- Menapilkan *pre-filled form* berdasarkan `id_unit` untuk memperbarui kode unit, nama stasiun, kategori konsol, jumlah stik, kondisi fisik, status operasional (`Tersedia`, `Sedang Main`, `Maintenance`), serta catatan tambahan.
- Validasi pencegahan duplikasi kode unit terhadap stasiun lain.

### Halaman Kategori Konsol (`kategori.php`)
- Mengelola data master tipe konsol beserta tarif sewa per jam dan deskripsi fasilitas.
- Menampilkan jumlah unit yang terhubung pada setiap kategori menggunakan query `LEFT JOIN` dan `COUNT()`.
- Menerapkan proteksi integritas relasi (`ON DELETE RESTRICT`) yang menolak penghapusan kategori apabila masih digunakan oleh unit aktif.

### Penghapusan Unit (`hapus.php` & `footer.php`)
- Penghapusa data unit dilengkapi dialog modal konfirmasi interaktif dan dieksekusi menggunakan metode `POST`.

## Struktur Direktori

```text
.
├── assets/
│   └── style.css    
├── 244242008.sql          
├── config.php             
├── database.sql           
├── edit.php               
├── erd_rental_ps.png      
├── footer.php             
├── hapus.php              
├── header.php             
├── index.php              
├── kategori.php           
└── tambah.php             
```

## Skema Database (244242008)

Database terdiri dari dua tabel yang berelasi *One-to-Many*:
- **`kategori_konsol` (Tabel Induk)**: menyimpan `id_kategori`, `nama_kategori`, `tarif_per_jam`, dan `deskripsi`.
- **`unit_rental` (Tabel Anak)**: menyimpan `id_unit`, `kode_unit`, `nama_station`, `id_kategori` (*Foreign Key* `ON DELETE RESTRICT` & `ON UPDATE CASCADE`), `jumlah_stik`, `kondisi`, `status`, `catatan`, dan `created_at`.

## Cara Menjalankan di Lokal (XAMPP / Laragon)

1. Pastikan modul **Apache** dan **MySQL** sudah berjalan di XAMPP atau Laragon.
2. Simpan folder proyek ini di dalam direktori `htdocs` (XAMPP) atau `www` (Laragon), misalnya `C:\xampp\htdocs\rental_ps\`.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, lalu impor file `244242008.sql` (atau `database.sql`). File ini otomatis membuat database `244242008` beserta kedua tabel dan data awalnya.
4. File `config.php` sudah dikonfigurasi untuk mendeteksi `localhost` secara otomatis dengan pengaturan:
   - **Host**: `localhost`
   - **Database**: `244242008`
   - **User**: `root`
   - **Password**: *(kosong)*
5. Buka aplikasi melalui browser:
   - **Halaman Dasbor Utama**: `http://localhost/rental_ps/index.php`
   - **Halaman Tambah Unit**: `http://localhost/rental_ps/tambah.php`
   - **Halaman Kategori Konsol**: `http://localhost/rental_ps/kategori.php`

## Catatan Keamanan & Teknis

- Kabeh quey yang menerima input pengguna atau parameter URL menggunakan **PDO Prepared Statements** (`prepare()` dan `execute()`) dengan opsi `PDO::ATTR_EMULATE_PREPARES => false`.
- Seluruh output variabel pada HTML dibungkus fungsi `htmlspecialchars()` dengan `ENT_QUOTES` dan `UTF-8` untuk mencegah celah XSS (*Cross-Site Scripting*).
- Setiap aksi `POST` (Create, Update, Delete) menerapkan pola *Post/Redirect/Get* (PRG) disertai notifikasi *session flash message* agar tidak terjadi duplikasi pengiriman data saat halaman dimuat ulang.
- Kegagalan koneksi atau kesalahan query ditangkap menggunakan blok `try-catch` (`PDOException`) dan dicatat ke dalam file `logs/db_error.log` tanpa menampilkan pesan error teknis mentah ke layar pengguna.