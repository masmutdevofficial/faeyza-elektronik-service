# Subdomain Faeyza di dalam public_html bersama Laravel

Document root subdomain boleh berada di public_html tanpa menimpa aplikasi Laravel pada domain utama. Jangan mengganti public_html/.htaccess milik Laravel. Panduan ini untuk subdomain app.faeyzateknik.com; path /home/AKUN disesuaikan dengan akun hosting sebenarnya.

## Mengapa dapat muncul Forbidden

Folder akar paket Faeyza memiliki .htaccess berisi `Require all denied` untuk melindungi source, .env, database dan storage. Jika domain diarahkan ke akar proyek lengkap, akses memang ditolak. Entry point website yang benar adalah public/index.php. Konfirmasi juga nama document root di panel, permission/kepemilikan folder dan error log provider; tanpa akses ke hosting, penyebab aktual 403 belum dapat dipastikan.

## Pilihan pertama jika document root dapat diubah

Upload aplikasi lengkap ke public_html/app.faeyzateknik.com. Arahkan document root subdomain ke:

```text
/home/AKUN/public_html/app.faeyzateknik.com/public
```

Path ini tetap berada dalam public_html. File public/.htaccess asli mengizinkan area publik, sedangkan .htaccess akar tetap menolak berkas privat. Buka https://app.faeyzateknik.com dan https://app.faeyzateknik.com/admin/. URL browser tidak perlu diberi /public jika document root sudah menunjuk ke folder tersebut.

## Pilihan kedua jika document root terkunci

Jika provider menetapkan document root persis public_html/app.faeyzateknik.com, tempatkan source lengkap di luar public_html dan hanya file publik di document root tersebut:

```text
/home/AKUN/
  faeyza/
    .env
    app/ bin/ config/ database/ views/ storage/
    public/             (tetap disimpan lengkap)
  public_html/
    .htaccess           (Laravel asli, tidak diubah)
    index.php           (Laravel asli, tidak diubah)
    app.faeyzateknik.com/
      .htaccess         (contoh deploy/subdomain/.htaccess.example)
      index.php         (wrapper deploy/subdomain/index.php.example)
      admin/index.php
      assets/
```

1. Simpan aplikasi lengkap di /home/AKUN/faeyza. Jangan menghapus public/assets di sana karena helper ikon dan invoice membacanya secara lokal.
2. Salin seluruh isi faeyza/public ke public_html/app.faeyzateknik.com, termasuk admin, assets dan .htaccess.
3. Ganti hanya index.php dalam folder subdomain dengan contoh deploy/subdomain/index.php.example. Isinya:

```php
<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/faeyza/public/index.php';
```

dirname dua tingkat sesuai susunan /home/AKUN/public_html/app.faeyzateknik.com. Sesuaikan path jika susunan berbeda. Jangan menggunakan wrapper satu tingkat dari panduan public_html utama untuk susunan ini.

4. Gunakan deploy/subdomain/.htaccess.example sebagai .htaccess dalam folder subdomain saja. Contoh tersebut mengizinkan file publik, melarang file tersembunyi dan mematikan rewrite Laravel pada direktori ini. Aplikasi memakai query index.php?page=... sehingga tidak memerlukan front controller rewrite Laravel.
5. Isi .env privat dengan APP_URL=https://app.faeyzateknik.com dan SESSION_SECURE=true, beserta database khusus Faeyza. Setup/admin tetap mengikuti panduan instalasi utama.

Jika provider juga melarang penyimpanan atau pembacaan source di luar public_html (misalnya open_basedir), minta provider mengizinkan direktori privat atau document root pilihan pertama. Pembatasan document root di public_html tidak otomatis berarti source PHP dilarang berada di direktori privat.

## Pemeriksaan setelah perubahan

- Domain Laravel utama masih bekerja.
- Subdomain menampilkan login pelanggan; /admin/ menampilkan login admin.
- Logo, ikon dan invoice tersedia.
- URL /.env, /app/bootstrap.php, /database/schema.sql dan /config/assets/faeyza-ttd.png pada subdomain harus ditolak atau 404, bukan menampilkan isinya.
- Folder umumnya 755 dan file 644, dengan kepemilikan benar; hanya storage/logs dan storage/sessions yang perlu writable PHP. Jangan mengubah seluruh proyek menjadi 777.
- Jika masih 403, cek error log provider untuk keterangan seperti client denied by server configuration, directory index forbidden atau permission denied. Jika provider menolak directive .htaccess, minta aturan setara diterapkan oleh pengelola. Contoh ini memerlukan Apache 2.4/kompatibel dan izin override yang sesuai.

Konfigurasi contoh belum diterapkan atau diuji pada hosting klien. Tidak ada perubahan pada file Laravel, database atau website produksi selama penyiapannya.
