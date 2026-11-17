# Faeyza Elektronik Service

Aplikasi manajemen penerimaan/perbaikan elektronik, PHP native 8.2+, MySQLi prepared statements, MySQL 8 atau MariaDB, HTML/CSS/JavaScript. Bahasa Indonesia, zona Asia/Jakarta, IDR. Tidak memerlukan Node.js, Composer, framework, WhatsApp API atau PDF library untuk produksi.

Deployment Docker/Coolify: lihat [DEPLOYMENT.md](DEPLOYMENT.md). Database produksi memakai container dan volume khusus Faeyza, terpisah dari database lokal maupun aplikasi lain.

Tanda tangan bengkel diatur per pesanan melalui **Edit servis → Pengerjaan & biaya**. Toggle muncul untuk status **Selesai / Sudah Diambil**, default aktif. Simpan perubahan untuk menerapkannya pada invoice dan cetak. Gambar asli disimpan privat di `config/assets/faeyza-ttd.png`; konfigurasi berada pada `invoice_signature` di branding.php. File tidak disajikan langsung melalui URL publik.

Pembaruan database lama: backup dahulu, gunakan akun migrasi yang memiliki ALTER pada database Faeyza, lalu jalankan `php bin/migrate.php --only-db=NAMA_DATABASE`. Target harus sama dengan DB_NAME. Migrasi v2 hanya menambahkan kolom `show_workshop_signature` (default 1) dan mencatat versi. `bin/setup.php` menjalankan migrasi untuk instalasi baru maupun skema lama. Akun runtime tidak perlu diberi hak ALTER.

**Produksi aktif:** https://faeyza.dirgasatya.id — [repository privat GitHub](https://github.com/masmutdevofficial/faeyza-elektronik-service). Hasil deployment dan pemeriksaan aktual tersedia di [DEPLOYMENT_REPORT.md](DEPLOYMENT_REPORT.md). Admin produksi dibuat secara terpisah dengan persetujuan pemilik; kredensial privat tidak tersimpan dalam repository. Coolify disetel untuk deployment manual.

## Hasil lokal pada komputer ini

Source: `C:\project\bengkel`. Runtime portable terpisah: `C:\project\bengkel-runtime` (PHP 8.5.12 dan MariaDB 11.4.5, port DB 3307, hanya loopback). Database `faeyza` untuk development dan `faeyza_test` untuk pengujian. `.env` lokal sudah dikonfigurasi dengan akun aplikasi terbatas; testing memakai akun tersendiri. Password DB acak tidak dicantumkan di dokumentasi.

Jalankan dari PowerShell:

```powershell
cd C:\project\bengkel
.\bin\start.ps1
```

Buka **http://127.0.0.1:8080**. Script mendeteksi PHP portable/PATH/XAMPP dan menyalakan MariaDB portable yang sudah disiapkan bila belum berjalan. Ctrl+C menghentikan server PHP. Alternatif setelah DB berjalan:

```powershell
C:\project\bengkel-runtime\php\php.exe -S 127.0.0.1:8080 -t public public/router.php
```

Akun contoh **khusus development** dibuat melalui seed dengan password acak. Lihat `tests/local-credentials.json` secara lokal untuk akun admin dan konsumen. File ini diabaikan Git, berada di luar web root, dan jangan diunggah/dibagikan. Tidak ada akun demo atau password default yang dibuat otomatis saat aplikasi mulai. Untuk data bersih gunakan database baru dan buat admin dengan CLI; jangan reset database pengguna.

## Instalasi baru / XAMPP

1. Siapkan PHP 8.2+ dengan `mysqli`, `mbstring`, dan session; MySQL 8+ atau MariaDB yang mendukung InnoDB/CHECK. Aktifkan ekstensi di `php.ini`, lalu restart Apache bila diperlukan. `curl` hanya diperlukan untuk HTTP tests.
2. Salin proyek ke `C:\xampp\htdocs\bengkel` atau direktori aplikasi terpisah.
3. Salin `.env.example` menjadi `.env`, isi host/port/nama DB/user/password. Jangan mengganti konfigurasi lokal yang sudah berisi data pengguna.
4. Buat database `faeyza` dengan charset utf8mb4 dan user DB melalui phpMyAdmin atau administrator database. Untuk migrasi gunakan akun dengan izin CREATE/ALTER/INDEX/REFERENCES; sesudah migrasi gunakan akun runtime dengan SELECT/INSERT/UPDATE. Aplikasi tidak menyediakan penghapusan data/histori.
5. Jalankan migrasi di terminal dengan konfigurasi akun migrasi:

```powershell
C:\xampp\php\php.exe bin/setup.php
# Jika akun migrasi boleh membuat database:
C:\xampp\php\php.exe bin/setup.php --create-db
C:\xampp\php\php.exe bin/doctor.php
```

`database/schema.sql` juga dapat diimpor ke database terpilih melalui phpMyAdmin. Setup v1 menggunakan IF NOT EXISTS dan schema_migrations; dapat diulang tanpa menghapus data. Upgrade berikutnya harus memakai migrasi bernomor/additive, bukan mengedit tabel aktif lewat reset test.

6. Pastikan `storage/logs` dan `storage/sessions` writable oleh PHP. Jangan memberi write permission pada source/config untuk web server.
7. Buat akun admin pertama (nomor contoh harus diganti dengan nomor admin sebenarnya):

```powershell
C:\xampp\php\php.exe bin/create-admin.php 08xxxxxxxxxx
```

Password diminta dari stdin, minimal 12 byte, maksimal 72 byte. Gunakan terminal privat karena terminal tertentu menampilkan input. Password tidak masuk argumen command, source, atau log. CLI menolak nomor duplikat dan tidak mengubah role akun yang sudah ada. Simpan kredensial admin secara privat.

8. Disarankan atur Apache VirtualHost `DocumentRoot "C:/xampp/htdocs/bengkel/public"`, dengan `AllowOverride All` dan `Require all granted` pada direktori public. Alternatif XAMPP: buka `http://localhost/bengkel/public/`. `.htaccess` di root menolak akses source, dan public mengizinkan hanya area publik. Apache 2.4 diperlukan untuk aturan Require.

## Konfigurasi

Environment dibaca dari `.env` (atau `.env.testing` hanya dengan APP_ENV=testing). Environment dari proses/server mendapat prioritas. Restart server PHP setelah mengganti environment proses. Gunakan DB_PORT 3306 untuk XAMPP standar, 3307 untuk runtime portable yang disiapkan di komputer ini. Kredensial administrator DB portable tersimpan privat di `C:\project\bengkel-runtime\local-database-admin.json`, di luar proyek; hanya untuk pemeliharaan DB lokal.

`APP_ENV=production` menolak seeding. `SESSION_SECURE=true` pada HTTPS. `APP_URL` mendokumentasikan alamat deployment; route dan aset memakai URL relatif agar berfungsi dalam subfolder. Jam bisnis berasal dari server Asia/Jakarta; input tanggal masuk tidak mengubah tanggal penerbitan invoice. TEST_DATE hanya dipakai oleh proses dengan APP_ENV=testing, tidak diterima dari HTTP request.

Ubah identitas di `config/branding.php`: nama, nama pendek, deskripsi, tagline, warna primary/accent, alamat, kontak, kota, website, catatan invoice, nama penanda tangan, syarat/ketentuan, dan path logo. Logo asli yang diberikan pemilik tersimpan tanpa modifikasi di `public/assets/faeyza-logo.jpg`, digunakan pada login, sidebar, dan nota. Website invoice adalah `www.faeyzateknik.com` dan penanda tangan `Asmat`, sesuai permintaan pemilik. Logo kosong kembali memakai identitas teks. Gunakan warna hex valid.

## Penggunaan

**Konsumen:** daftar dengan nama/alamat/nomor HP/password, login, pilih Pengajuan servis, lengkapi perangkat dan keluhan, kirim. Halaman sukses menampilkan nomor invoice; buka Lihat/cetak invoice. Form konsumen tidak memiliki biaya/status admin. Data setelah dikirim hanya diubah admin. Data servis dan pencarian dibatasi ke akun pemilik, termasuk akses URL langsung. Satu akun dapat mengajukan banyak perangkat.

**Admin:** ringkasan menampilkan jumlah konsumen, total servis, baru, dalam proses, selesai, stok menipis, dan servis terbaru. Menu Konsumen menyediakan tambah/detail/edit dan riwayat. Data servis menyediakan cari/filter, detail, edit perangkat, status, tanggal selesai/garansi, catatan teknisi, dan total biaya. Pengajuan oleh admin harus memilih akun pemilik yang sah. Menu Invoice dapat mencari nomor invoice, nama, atau nomor HP semua konsumen. Menu Sparepart menyediakan tambah/edit metadata, stok masuk, histori, dan indikator stok minimum. Penggunaan komponen diatur dari detail servis.

Nomor HP 08…, 628…, +628… dinormalisasi menjadi 628…. HP akun boleh diubah admin tanpa memutus relasi pemilik; invoice menyimpan snapshot nama/kontak/alamat ketika diterima. Admin dapat mengubah snapshot servis melalui Edit servis. Nomor invoice dan pemilik akun tidak dapat diubah melalui form maupun POST edit.

Biaya NULL tampil **-**; biaya 0 tampil **Rp0**; 150000 tampil **Rp150.000**. Harga beli/jual sparepart tidak menetapkan total invoice. Catatan teknisi terlihat oleh konsumen dan tercetak; jangan menulis rahasia internal di sana.

## Aturan stok dan transaksi

Stok awal sparepart baru 0. Catat penerimaan melalui Stok masuk (jumlah positif dan keterangan). Pengiriman ulang token penerimaan sama tidak menambah dua kali. Edit metadata tidak mengubah jumlah stok.

Alokasi di detail servis adalah **jumlah akhir**: 2→3 mengurangi tambahan 1; 3→1 mengembalikan 2; jumlah 0 mengembalikan seluruh alokasi. Baris penggunaan tetap tersimpan dengan kuantitas 0. Pembatalan servis mengembalikan seluruh alokasi aktif dalam satu transaksi. Pembatalan ulang tidak menggandakan pengembalian. Setelah dibuka kembali, admin harus mengalokasikan ulang secara eksplisit. Tidak ada penghapusan histori. Stok berkurang ketika alokasi disimpan, bukan saat membuka halaman atau mengganti status biasa.

Transaksi mengunci row servis kemudian row sparepart, memvalidasi saldo, mencatat penggunaan dan stock_movements secara atomik. Penyimpanan gagal di-rollback. Edit servis dan metadata sparepart memiliki revision untuk menolak penyimpanan formulir kedaluwarsa. Penggunaan memiliki expected quantity; retry kuantitas sama idempotent. Deadlock/lock timeout dicoba ulang secara terbatas. Invoice memakai invoice_sequences per tanggal dengan SELECT FOR UPDATE dan unique constraints.

Warna dominan mengikuti biru logo: `primary_color` #0756b5, `accent_color` #dcecff, dan `primary_soft_color` #eaf2ff di `config/branding.php`. Pengaturan ini mengendalikan banner, tombol utama, navigasi aktif, serta permukaan biru muda. Latar dan teks pendukung memakai nuansa slate/biru; warna status tetap membedakan kondisi servis.

## Invoice / penyesuaian referensi

Ikon aplikasi menggunakan SVG resmi **Material Design Icons (MDI) 7.4.47**, tersimpan di `public/assets/icons/mdi/`. Lisensi dan asal paket tersedia dalam folder tersebut. Helper `mdi()` di `app/helpers.php` menampilkan ikon memakai `currentColor` dan `aria-hidden`; tidak ada ketergantungan CDN, font eksternal, npm, atau Node.js saat produksi. Banner admin menggunakan ilustrasi perangkat dari MDI, gradasi biru, ringkasan antrean aktual, dan tautan langsung ke servis serta invoice. Banner konsumen tetap memuat informasi pemantauan perangkat.

Template terpisah di `views/invoice.php`; tampilan nota di `public/assets/invoice.css`, aturan cetak di `public/assets/print.css`. Tombol Cetak invoice memanggil `window.print()`. Pilih A4, skala default, Save as PDF atau printer lokal. Nonaktifkan header/footer tambahan browser untuk tampilan paling bersih. Menu tidak ikut tercetak; teks panjang mengalir ke halaman lanjutan. Tidak ada dependency PDF produksi.

Nota telah disesuaikan dengan gambar referensi pemilik: logo kiri, identitas bengkel di tengah, kota/tanggal dan judul NOTA SERVICE di kanan, tabel biru Data Konsumen/Data Perangkat/Kelengkapan, tabel Status/Biaya, catatan, syarat, serta tanda tangan. Nomor invoice, tanggal masuk, status, garansi, dan biaya berasal dari transaksi. Nama konsumen tidak disalin dari data contoh. Rincian biaya yang tidak dicatat tetap ditandai "-"; hanya total final admin yang menjadi nominal keseluruhan. Pada mobile tabel disusun vertikal agar terbaca; hasil cetak tetap mengikuti kolom referensi. Untuk perubahan selanjutnya edit ketiga file template/CSS dan branding, pertahankan escaping serta ownership, kemudian uji cetak ulang.

## Testing

Jangan arahkan pengujian ke database produksi. Salin `.env.testing.example` menjadi `.env.testing` lalu isi akun khusus DB **faeyza_test**. Akun testing boleh mengelola hanya database tersebut. Pada komputer ini sudah disiapkan.

Terminal pertama:

```powershell
.\bin\start.ps1 -TestDatabase
```

Terminal kedua:

```powershell
.\tests\test.ps1 -Php C:\project\bengkel-runtime\php\php.exe
# Atau lint/migrasi/tes terpisah:
$env:APP_ENV='testing'
php bin/setup.php --create-db
php tests/run.php --reset-test
```

`run.php` menolak reset tanpa flag eksplisit, APP_ENV=testing, dan nama DB berakhiran _test. Hanya tabel pada DB testing yang dikosongkan. Tes membuat password acak per run, fixture di `tests/local-fixtures.json` (privat), dan hasil di `tests/artifacts/integration-results.json`. Request HTTP menuju 8081; TEST_BASE_URL dapat diganti untuk server testing lain. Tes concurrent memakai proses PHP terpisah dan koneksi DB independen, bukan server PHP single-worker.

Browser simulation yang digunakan saat pengerjaan berada di `tests/browser-e2e.mjs`; dijalankan melalui Browser skill yang terhubung. Untuk developer lokal, adapter Playwright opsional:

```powershell
npm install --no-save playwright
npx playwright install chromium
node tests/e2e-cli.mjs
```

Untuk mencetak artefak QA setelah simulasi browser, pasang `pypdf` di Python development lalu jalankan `tests/print.ps1 -Php path/php.exe -Python path/python.exe`. Script memakai Chrome headless terpisah sebagai renderer file HTML QA (tidak memakai profil/sesi pengguna); parameter `-Poppler path/bin` menambahkan render PNG untuk inspeksi. Dependency tersebut hanya untuk QA.

Node/Playwright hanya untuk development. Jalankan sesudah integration tests agar fixture cocok. Script mengoperasikan form nyata dan menguji tiga ukuran CSS viewport. Hasil dan screenshot berada di tests/artifacts. `tests/render-invoices.php` mengekspor HTML QA memakai template yang sama; PDF A4 dibuat dengan engine print Chromium lalu dicek oleh `tests/check-pdf.py` (pypdf hanya dependency QA). Lihat **TEST_REPORT.md** untuk perintah/bukti aktual dan keterbatasan otomasi dialog cetak.

## Shared hosting

1. Siapkan PHP 8.2+, mysqli/mysqlnd, mbstring, InnoDB dan HTTPS. Buat database dan user melalui panel hosting; nama DB mungkin berprefix akun hosting.
2. Simpan app/config/database/bin/storage dan `.env` **di luar public_html**. Isi public_html dengan isi folder public. Sesuaikan baris require di `public_html/index.php` agar menunjuk bootstrap aplikasi di luar public_html. Pada hosting yang mendukung document root khusus, arahkan langsung ke folder public sehingga tidak perlu mengubah bootstrap path.
3. Jangan unggah tests, artefak, data seed, file local-credentials/local-fixtures, log, sesi, runtime portable atau file administrator DB lokal. Produksi memakai konfigurasi/kredensial baru, APP_ENV=production, SESSION_SECURE=true. Jangan gunakan akun demo di production.
4. Impor schema.sql ke DB terpilih menggunakan akun migrasi, lalu gunakan user runtime SELECT/INSERT/UPDATE. Bila CLI tidak tersedia, impor melalui panel/phpMyAdmin; jalankan create-admin.php dengan PHP CLI privat dari terminal hosting atau hubungi pengelola hosting. Jangan membuat endpoint setup/admin publik.
5. Set izin hanya storage/logs dan storage/sessions writable. Pastikan .env/source tidak dapat diunduh. Matikan display_errors pada konfigurasi server juga. Log aplikasi menggunakan kode insiden tanpa kredensial/input SQL.
6. Buat admin asli, uji login/role/penerimaan/cetak menggunakan data uji baru, backup DB dan source sebelum perubahan. Jadwalkan backup dengan fasilitas hosting. Aplikasi tidak melakukan deployment publik otomatis.

## Struktur

```
app/              bootstrap, koneksi, domain transaksi, controller, helper
config/           identitas terpusat
database/         schema.sql v1
public/           satu entry point + aset + stylesheet cetak
views/            template halaman dan invoice terpisah
bin/              setup, create-admin, doctor, seed development, start.ps1
tests/            native integration, worker concurrent, browser E2E, PDF QA
storage/          log privat dan session files
```

Rate limit login: maksimal 8 percobaan per nomor dan per IP dalam jendela 15 menit. Bucket IP menghitung semua percobaan untuk menahan credential stuffing. Session idle 2 jam, regenerasi setelah login, cookie HttpOnly/SameSite, CSRF semua POST, role server-side, output escaping dan query prepared. Semua respons privat memakai no-store dan CSP. Tidak mempercayai header proxy untuk IP tanpa konfigurasi proxy yang sah.

Riwayat hasil aktual dan batas verifikasi tersedia di TEST_REPORT.md. Pengujian lokal memakai PHP development server dan MariaDB nyata; Apache produksi kemudian dijalankan dan diverifikasi pada container Coolify, lihat DEPLOYMENT_REPORT.md. XAMPP/shared hosting konvensional tidak dijalankan dalam sesi ini.
