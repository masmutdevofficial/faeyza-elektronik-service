# Panduan instalasi Faeyza Elektronik Service

Paket ini berisi aplikasi dan skema database kosong. Tidak ada password bawaan, akun produksi, atau data pelanggan. Gunakan database baru untuk uji coba. Akun dan data pada website yang sudah aktif tidak ikut dipindahkan melalui ZIP ini.

## 1 Uji coba dengan XAMPP

Siapkan XAMPP dengan PHP 8.2 atau lebih baru, Apache 2.4, serta MySQL 8 atau MariaDB yang kompatibel. PHP memerlukan mysqli dengan mysqlnd, mbstring, dan session. Tidak perlu Node.js atau Composer.

1. Ekstrak ZIP. Salin folder aplikasi, lalu ubah namanya menjadi `bengkel` di `C:\xampp\htdocs\bengkel`. Pastikan file `public\index.php` ada di dalamnya, bukan terbungkus folder tambahan.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel. Buka `http://localhost/phpmyadmin`.
3. Buat database baru bernama `faeyza_lokal`, dengan collation `utf8mb4_unicode_ci`. Jangan memilih database milik aplikasi lain.
4. Salin `.env.example` menjadi `.env` di folder `bengkel`. Aktifkan tampilan ekstensi file Windows agar nama file tidak menjadi `.env.txt`.
5. Isi `.env` sesuai konfigurasi berikut. Contoh root tanpa password hanya untuk XAMPP lokal standar; sesuaikan bila MySQL Anda memakai password atau port berbeda.

```ini
APP_ENV=development
APP_URL=http://localhost/bengkel/public
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=faeyza_lokal
DB_USER=root
DB_PASSWORD=
SESSION_SECURE=false
```

Buka PowerShell dan jalankan:

```powershell
cd C:\xampp\htdocs\bengkel
Copy-Item .env.example .env
```

Perintah Copy-Item dilakukan sekali sebelum mengedit `.env`; jangan diulang setelah konfigurasi diisi. Setelah menyimpan konfigurasi, jalankan:

```powershell
C:\xampp\php\php.exe bin/setup.php
C:\xampp\php\php.exe bin/doctor.php
```

Setup membuat tabel dan menjalankan migrasi, tanpa membuat akun contoh. Doctor harus menampilkan koneksi database, ekstensi, aset tanda tangan, dan storage dalam kondisi OK. Jika CLI tidak dapat dipakai, impor `database/schema.sql` ke database `faeyza_lokal` melalui phpMyAdmin. Untuk database lama gunakan prosedur pembaruan, bukan impor sembarang skema.

Buka aplikasi pelanggan di `http://localhost/bengkel/public/` dan login admin di `http://localhost/bengkel/public/admin/`. Gunakan alamat yang sama secara konsisten selama pengujian; jangan bergantian localhost dan 127.0.0.1 pada sesi yang sama.

## 2 Membuat admin dan mencoba aplikasi

Dari folder aplikasi, buat admin pertama dengan nomor HP pemilik. Ganti nomor contoh berikut dengan nomor yang sebenarnya:

```powershell
C:\xampp\php\php.exe bin/create-admin.php 08xxxxxxxxxx
```

Ketik password saat diminta lalu tekan Enter. Minimal 6 karakter dan maksimal 72 byte; gunakan password unik yang kuat. Input dapat terlihat pada terminal tertentu, jadi gunakan terminal privat. Nomor HP harus unik. Perintah ini tidak mengganti password atau menaikkan role akun yang sudah ada.

Admin masuk melalui `/admin/`. Pelanggan masuk melalui halaman utama dan dapat membuat akun sendiri. Admin juga dapat menambahkan pelanggan dari menu Konsumen. Setelah akun dibuat, admin dapat mengganti password pelanggan dari detail konsumen. Admin dan pelanggan dapat mengganti password sendiri melalui Pengaturan akun; admin juga dapat mengganti nomor HP sendiri jika belum digunakan akun lain.

Lakukan simulasi berikut pada instalasi lokal baru:

1. Daftarkan pelanggan dengan nomor HP uji yang berbeda dari nomor admin.
2. Login pelanggan, pilih Servis baru, isi barang, merek, model, kerusakan, dan kontak, lalu kirim.
3. Pastikan nomor invoice muncul. Buka invoice; total biaya awal harus berupa strip `-`.
4. Logout, masuk admin, lalu cari nomor invoice tersebut.
5. Ubah status melalui tombol Ubah status di daftar atau detail servis.
6. Buat sparepart, tambahkan stok 10, lalu alokasikan 2 ke servis. Stok harus menjadi 8.
7. Edit servis, isi total 150000, pilih Selesai, isi tanggal selesai dan garansi, lalu simpan.
8. Periksa pilihan tanda tangan bengkel. Default tampil untuk Selesai dan Sudah Diambil, dan dapat dinonaktifkan admin.
9. Login kembali sebagai pelanggan. Pastikan status terbaru dan biaya `Rp150.000` terlihat.
10. Klik Cetak invoice. Pilih A4, skala default, dan Simpan sebagai PDF atau printer. Matikan header/footer tambahan browser jika diperlukan.
11. Buat pelanggan kedua dan pastikan akun pertama tidak dapat membuka invoice akun kedua.

Dalam proses menggabungkan Menunggu Konfirmasi, Diterima, Dalam Pengerjaan, dan Menunggu Sparepart. Ringkasan Selesai menggabungkan Selesai dan Sudah Diambil. Dibatalkan tidak masuk kedua kelompok, tetapi tetap termasuk Total servis. Klik kartu untuk membuka daftar kelompok yang sama.

Tanggal selesai dan garansi hanya dapat diisi untuk Selesai atau Sudah Diambil. Biaya kosong ditampilkan `-`, biaya nol `Rp0`. Penggunaan sparepart diubah dengan jumlah akhir: 2 menjadi 3 hanya mengurangi 1 lagi. Pembatalan servis mengembalikan alokasi stok. Hapus konsumen bersifat permanen dan menghapus akun beserta seluruh invoice/servisnya. Hapus invoice tersedia khusus admin. Hapus sparepart tetap dapat dipulihkan dari filter Dihapus. Aturan stok dan izin database ada di PENGHAPUSAN-DATA.md.

## 3 Deployment hosting dengan document root khusus

Gunakan hosting PHP 8.2+, MySQL 8/MariaDB kompatibel, mysqli/mysqlnd, mbstring, session, dan HTTPS. Panduan ini menggunakan Apache 2.4 atau server yang mendukung aturan akses setara. Pada Nginx, minta pengelola mengatur document root dan larangan akses file tersembunyi; `.htaccess` tidak diproses Nginx.

1. Buat database baru dan user melalui panel hosting. Gunakan nama lengkap beserta prefix akun hosting, misalnya `akun_faeyza`. Jangan menggunakan database aplikasi lain.
2. Upload seluruh folder aplikasi ke direktori privat, misalnya `/home/akun/faeyza`.
3. Atur document root domain menjadi `/home/akun/faeyza/public`. Hanya folder public yang menjadi area website.
4. Salin `.env.example` menjadi `.env` di `/home/akun/faeyza` dan isi konfigurasi hosting:

```ini
APP_ENV=production
APP_URL=https://domain-anda.id
DB_HOST=localhost
DB_PORT=3306
DB_NAME=akun_faeyza
DB_USER=akun_faeyza_app
DB_PASSWORD=GANTI_DENGAN_PASSWORD_DATABASE
SESSION_SECURE=true
```

Gunakan hostname dan port dari penyedia hosting, bukan otomatis menyalin contoh. Password yang mengandung `#` tetap dibaca; jangan menambahkan komentar di akhir baris nilai. Jika memakai environment dari panel, nilainya mengalahkan `.env` dan harus konsisten.

Impor `database/schema.sql` ke database baru melalui phpMyAdmin. Alternatif dengan terminal hosting:

```sh
cd /home/akun/faeyza
php bin/setup.php
php bin/doctor.php
php bin/create-admin.php 08xxxxxxxxxx
```

Sesuaikan `php` dengan executable PHP 8.2+ milik hosting. User migrasi memerlukan izin CREATE, ALTER, INDEX, REFERENCES, SELECT dan INSERT. Setelah setup, gunakan akun runtime dengan SELECT, INSERT dan UPDATE hanya pada database Faeyza, serta DELETE terbatas pada enam tabel sesuai PENGHAPUSAN-DATA.md. Panel tertentu hanya menyediakan pilihan ALL PRIVILEGES; mintalah pengelola membatasi akun runtime bila tersedia. Jangan memberikan izin ke seluruh database server.

Direktori umum dapat memakai izin 755 dan file 644, disesuaikan kepemilikan hosting. `.env` sebaiknya 600 jika PHP tetap dapat membacanya. Hanya `storage/logs` dan `storage/sessions` yang perlu dapat ditulis PHP; gunakan 775 bila grup hosting memang memerlukannya. Hindari 777. Aktifkan HTTPS sebelum login dengan SESSION_SECURE=true. Atur display_errors=Off pada konfigurasi PHP hosting.

## 4 Hosting dengan public_html tetap dan tanpa terminal

Jika document root tidak dapat diubah, gunakan susunan berikut. Tetap simpan folder public di dalam aplikasi privat karena helper ikon dan invoice membacanya dari sana.

```text
/home/akun/
  faeyza/
    .env
    app/  bin/  config/  database/  deploy/
    public/  storage/  views/
  public_html/
    .htaccess
    index.php
    admin/index.php
    assets/
```

1. Upload aplikasi lengkap ke `/home/akun/faeyza`, di luar public_html.
2. Salin seluruh isi `faeyza/public` ke `public_html`, termasuk `.htaccess`, `admin`, dan `assets`. Jangan menghapus folder public yang asli.
3. Ganti hanya `public_html/index.php` dengan isi `deploy/shared-hosting/index.php.example`. Nama file akhirnya harus `index.php`, bukan `.example`.

```php
<?php
declare(strict_types=1);
require dirname(__DIR__).'/faeyza/public/index.php';
```

4. Sesuaikan path apabila folder privat bernama lain. Pastikan aturan open_basedir hosting mengizinkan PHP membaca folder tersebut; minta bantuan penyedia bila dibatasi.
5. Konfigurasikan `.env`, impor skema, dan atur izin seperti bagian sebelumnya. Login admin berada di `https://domain-anda.id/admin/`.

Tanpa terminal hosting, buat SQL akun admin di komputer lokal memakai PHP XAMPP. Helper ini tidak memerlukan koneksi database lokal:

```powershell
cd C:\xampp\htdocs\bengkel
C:\xampp\php\php.exe bin/admin-sql.php 08xxxxxxxxxx |
  Set-Content -Encoding utf8 "$env:USERPROFILE\Desktop\faeyza-admin.sql"
```

Masukkan password saat diminta. File hasil berisi nomor HP dan hash password, bukan password asli. Buka phpMyAdmin hosting, pilih database Faeyza yang benar, lalu impor file itu sekali setelah skema dibuat. Nomor yang sudah dipakai akan ditolak, bukan ditimpa. Setelah login berhasil, hapus file SQL admin dari komputer dan jangan menaruhnya di public_html atau mengirimkannya bersama ZIP.

Jangan membuat halaman setup atau pembuatan admin publik. Jika open_basedir atau izin hosting menghalangi susunan ini, gunakan document root khusus atau minta pengelola menyiapkannya.

## 5 Pemeriksaan hosting dan pembaruan

Sebelum dipakai pelanggan, periksa HTTPS, login admin, registrasi pelanggan, pengajuan servis, pencarian, pemilik invoice, stok, perubahan status, dan cetak. Gunakan data uji baru yang dapat dikenali. Instalasi baru memang kosong; tidak ada akun admin/password bawaan atau data dari server lama.

Uji URL `/.env`, `/app/bootstrap.php`, `/database/schema.sql`, `/bin/create-admin.php`, `/config/assets/faeyza-ttd.png`, dan `/storage/logs/php.log`. Semuanya harus ditolak atau 404, tanpa mengunduh isinya. Halaman login dan aset publik harus tetap terbuka. Bila folder aplikasi berada di bawah htdocs untuk XAMPP, akses berkas privat melalui `/bengkel/...` juga harus ditolak Apache. Apache harus mengizinkan `.htaccess` melalui AllowOverride.

Untuk pembaruan aplikasi yang sudah berisi data:

1. Backup database melalui panel dan salin source, `.env`, branding, logo, serta tanda tangan ke tempat privat. Catat versi PHP dan konfigurasi.
2. Jadwalkan pembaruan ketika tidak ada transaksi baru. Jika memulihkan backup, jangan menimpa transaksi setelah waktu backup tanpa rekonsiliasi.
3. Upload source baru tanpa menimpa `.env` atau menghapus storage/data. Jangan memasukkan file demo ke produksi.
4. Jalankan migrasi menggunakan akun migrasi, dengan target database yang persis sama dengan DB_NAME:

```sh
php bin/migrate.php --only-db=akun_faeyza
php bin/doctor.php
```

5. Kembalikan akun runtime terbatas, lalu cek invoice lama, stok dan login. Untuk hosting tanpa terminal, minta pengelola menjalankan migrasi; jangan mengimpor ulang seluruh skema sebagai pengganti upgrade database lama.
6. Pada susunan public_html tetap, perbarui kedua salinan aset publik dan pertahankan wrapper index.php. Jangan mengganti wrapper dengan index.php asli tanpa menyesuaikan path.

ZIP ini merupakan source aplikasi, bukan backup database. Pemindahan data dari server aktif memerlukan ekspor database terpisah dan transfer privat oleh pengelola. Jangan membuat database tujuan dari data uji bila akan memigrasikan database produksi yang lama.

## 6 Pengaturan dan pemecahan masalah

Ubah nama toko, alamat, telepon, website, warna, nama penanda tangan dan syarat invoice melalui `config/branding.php`. Logo berada di `public/assets/faeyza-logo.jpg`, gambar tanda tangan privat di `config/assets/faeyza-ttd.png`. Template invoice berada di `views/invoice.php`; stylesheet di `public/assets/invoice.css` dan `public/assets/print.css`. Pada public_html tetap, sinkronkan aset publik yang diubah ke kedua lokasi.

- **Database tidak terhubung:** cek DB_HOST, DB_PORT, nama dengan prefix, user, password, serta akses user ke database itu. Untuk XAMPP, pastikan MySQL berjalan dan port sesuai Control Panel.
- **Halaman 500:** periksa `storage/logs/php.log` dan log PHP hosting. Cocokkan nomor insiden; jangan menampilkan detail error ke pengunjung. Pastikan PHP memenuhi syarat dan storage writable.
- **mysqli atau mbstring tidak tersedia:** aktifkan ekstensi di konfigurasi PHP yang dipakai Apache/hosting. PHP CLI dan PHP website bisa memiliki konfigurasi berbeda. Restart Apache setelah mengubah php.ini.
- **403 pada semua halaman:** pastikan membuka folder public, bukan akar aplikasi. Pada public_html tetap, gunakan .htaccess dari folder public dan wrapper yang benar.
- **Logo atau ikon hilang:** periksa direktori assets di area publik dan `faeyza/public/assets` pada direktori privat. Path dan huruf besar/kecil harus cocok di Linux.
- **Login kembali ke halaman login:** gunakan HTTPS dengan SESSION_SECURE=true. Untuk HTTP localhost gunakan false. Periksa izin storage/sessions dan hindari dua instalasi Faeyza aktif pada hostname yang sama karena cookie sesi memakai nama yang sama.
- **Terlalu banyak percobaan login:** tunggu 15 menit sebelum mencoba lagi. Jangan menonaktifkan pembatasan login di produksi.
- **Nomor HP ditolak:** gunakan nomor seluler Indonesia yang valid. Format 08, 628, dan +628 dianggap akun yang sama.
- **Dialog cetak tidak muncul:** buka invoice, lalu gunakan bantuan Cetak lewat menu browser. Di Chrome Android biasanya menu tiga titik, Bagikan, Cetak. Di komputer gunakan Ctrl+P. Pilihan menu dapat berbeda antar perangkat. Keluaran PDF telah diuji, tetapi dialog cetak pada HP klien belum diuji langsung.

XAMPP/Apache dan panel hosting klien perlu diperiksa pada perangkat tujuan. Paket rilis diuji pada PHP dan MariaDB lokal terpisah; hasil dan batas pemeriksaannya ada di `docs/HASIL-VERIFIKASI.md`. Tidak diperlukan instalasi library PDF atau Node.js di hosting.
