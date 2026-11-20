# Pemeriksaan hosting klien melalui UltraViewer

Tanggal pemeriksaan 19 November 2026. Pemeriksaan read-only melalui sesi UltraViewer yang sudah terhubung dan cPanel pada browser komputer klien. Tidak mengubah file, permission, database, PHP, domain atau aplikasi Laravel.

## Temuan terkonfirmasi

1. cPanel Domains menetapkan app.faeyzateknik.com ke public_html/app.faeyzateknik.com, bukan subfolder public. Direktori tersebut berisi source lengkap Faeyza.
2. View file .htaccess pada akar subdomain menunjukkan Options -Indexes dan Require all denied. File ini memang melindungi direktori source; membuka akar tersebut ditolak.
3. Browser subdomain pada /public/ menunjukkan HTTP ERROR 500. File public/error_log memuat fatal Call to undefined function str_starts_with() di app/bootstrap.php baris 8, berulang pada entri yang diperiksa.
4. MultiPHP Manager menampilkan versi sistem PHP 8.4 ea-php84 dan kedua domain berstatus Inherited. Ini bukan bukti PHP efektif aplikasi adalah 8.4.
5. CloudLinux PHP Selector Account Global Settings menunjukkan 7.4 current. Per Domain Settings menunjukkan app.faeyzateknik.com dan faeyzateknik.com sama-sama memakai 7.4 account default dan ditangani CloudLinux PHP Selector, bukan cPanel MultiPHP Manager (2 domain Selector, 0 MultiPHP).
6. Site Isolation pada PHP Selector dinonaktifkan oleh administrator server. Pengaturan global tidak boleh diganti begitu saja karena akan mencakup domain Laravel utama.
7. Manage Domain untuk subdomain menyediakan New Document Root dengan prefix public_html dan nilai app.faeyzateknik.com. Dengan demikian tersedia form untuk mengarahkannya ke app.faeyzateknik.com/public tanpa keluar dari public_html. Form tidak disimpan selama pemeriksaan.

## Solusi yang disiapkan

- Ubah document root subdomain saja menjadi public_html/app.faeyzateknik.com/public. Tetap pertahankan aturan penolakan akar proyek dan file Laravel di public_html.
- Atur PHP 8.2 atau lebih baru khusus app.faeyzateknik.com, melalui per-domain handler MultiPHP Manager yang eksplisit, bukan pengaturan PHP Selector global. Jika provider membatasi perpindahan handler/per-domain, minta provider mengaktifkan PHP tersendiri untuk subdomain. Pastikan mysqli/mysqlnd, mbstring dan session tersedia.
- Sesudah PHP dan document root efektif, uji login pelanggan, /admin/, aset, invoice dan koneksi database. Koneksi database belum dapat dinilai karena error terjadi sebelum bootstrap selesai. Periksa juga penolakan URL privat setelah penataan.

Tidak ada perubahan yang diterapkan. Hasil ini merupakan diagnosis dan rencana perbaikan berdasarkan UI serta log hosting yang benar-benar dibaca, bukan klaim aplikasi hosting sudah berfungsi.
