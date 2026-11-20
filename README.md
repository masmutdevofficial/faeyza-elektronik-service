# Faeyza Elektronik Service

Aplikasi manajemen penerimaan/perbaikan elektronik, PHP native 8.2+, MySQLi prepared statements, MySQL 8 atau MariaDB, HTML/CSS/JavaScript. Bahasa Indonesia, zona Asia/Jakarta, IDR. Tidak memerlukan Node.js, Composer, framework, WhatsApp API atau PDF library untuk produksi.

Panduan klien untuk XAMPP dan shared hosting tersedia di [docs/PANDUAN-INSTALASI.md](docs/PANDUAN-INSTALASI.md). PDF dan ZIP bersih dibuat dengan `tools/render-installation-guide.py` (ReportLab untuk development) dan `tools/build-client-package.py`. Paket tidak menyertakan konfigurasi privat, database pelanggan, seed, sesi, log, atau riwayat Git. Helper CLI `bin/admin-sql.php` menghasilkan SQL admin dengan hash password untuk hosting tanpa terminal; prosedurnya ada dalam panduan.

Deployment Docker/Coolify: lihat [DEPLOYMENT.md](DEPLOYMENT.md). Database produksi memakai container dan volume khusus Faeyza, terpisah dari database lokal maupun aplikasi lain.

Tanda tangan bengkel diatur per pesanan melalui **Edit servis → Pengerjaan & biaya**. Toggle muncul untuk status **Selesai / Sudah Diambil**, default aktif. Simpan perubahan untuk menerapkannya pada invoice dan cetak. Gambar asli disimpan privat di `config/assets/faeyza-ttd.png`; konfigurasi berada pada `invoice_signature` di branding.php. File tidak disajikan langsung melalui URL publik.

Pembaruan database lama: backup dahulu, gunakan akun migrasi yang memiliki CREATE/ALTER/INSERT pada database Faeyza, lalu jalankan `php bin/migrate.php --only-db=NAMA_DATABASE`. Target harus sama dengan DB_NAME. Migrasi v2 menambahkan `show_workshop_signature` (default 1); v3 menambahkan penanda penghapusan konsumen/sparepart, versi sesi pengguna, dan audit record_events; v4 menambahkan `spare_parts.part_type`. Jalankan migrasi sebelum merilis kode baru. Tidak ada penghapusan data lama. `bin/setup.php` menjalankan migrasi untuk instalasi baru maupun skema lama. Akun runtime memakai SELECT/INSERT/UPDATE serta DELETE terbatas pada enam tabel untuk penghapusan permanen; lihat docs/PENGHAPUSAN-DATA.md.

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
4. Buat database `faeyza` dengan charset utf8mb4 dan user DB melalui phpMyAdmin atau administrator database. Untuk migrasi gunakan akun dengan izin CREATE/ALTER/INDEX/REFERENCES; sesudah migrasi gunakan akun runtime dengan SELECT/INSERT/UPDATE dan DELETE terbatas sesuai database/runtime-delete-permissions.sql. Histori stok tetap dipertahankan meskipun invoice atau konsumen dihapus.
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

Password diminta dari stdin, minimal 6 karakter, maksimal 72 byte. Gunakan terminal privat karena terminal tertentu menampilkan input. Password tidak masuk argumen command, source, atau log. CLI menolak nomor duplikat dan tidak mengubah role akun yang sudah ada. Simpan kredensial admin secara privat.

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

Stok awal sparepart baru 0. Catat penerimaan melalui **Catat stok masuk** (jumlah positif dan **Tipe sparepart** yang diketik admin). Tipe tersimpan pada sparepart, otomatis terisi pada penerimaan berikutnya, dan dapat diubah melalui **Edit sparepart**. Pengiriman ulang token penerimaan sama tidak menambah dua kali. Edit tipe tidak mengubah jumlah stok; histori menyimpan tipe saat penerimaan. Pencarian mendukung SKU, nama, dan tipe. Lihat [panduan tipe sparepart dan migrasi v4](docs/TIPE-SPAREPART.md).

Untuk koreksi jumlah tersedia, buka detail sparepart → **Sesuaikan stok**. Isi jumlah akhir yang benar dan alasan wajib; 0 berarti stok habis. Perubahan mencatat selisih/saldo/admin dalam stock_movements secara atomik, menolak form stale, dan memakai token idempoten agar retry tidak menggandakan koreksi. Pemakaian servis tetap tersimpan; pembatalan tetap mengembalikan barang yang dipakai. Tautan koreksi tersedia juga di Edit sparepart.

Ringkasan dan daftar servis mengikuti data yang masih tersimpan. Hapus konsumen menghapus permanen akun, profil, seluruh invoice dan servisnya. Nomor invoice yang pernah diterbitkan tidak dipakai ulang. Data konsumen yang diarsipkan versi lama masih tersedia pada filter Dihapus sampai admin memilih Hapus permanen atau Pulihkan.

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
2. Simpan aplikasi lengkap (termasuk folder public) dan `.env` **di luar public_html**. Salin isi folder public ke public_html, lalu gunakan wrapper `deploy/shared-hosting/index.php.example` sebagai public_html/index.php. Folder public asli tetap diperlukan helper ikon/invoice; perubahan aset perlu disinkronkan ke kedua lokasi. Pada hosting yang mendukung document root khusus, arahkan langsung ke folder public sehingga tidak perlu wrapper atau salinan aset.
3. Jangan unggah tests, artefak, data seed, file local-credentials/local-fixtures, log, sesi, runtime portable atau file administrator DB lokal. Produksi memakai konfigurasi/kredensial baru, APP_ENV=production, SESSION_SECURE=true. Jangan gunakan akun demo di production.
4. Impor schema.sql ke DB terpilih menggunakan akun migrasi, lalu gunakan user runtime SELECT/INSERT/UPDATE dan DELETE terbatas sesuai database/runtime-delete-permissions.sql. Bila CLI tidak tersedia, impor melalui panel/phpMyAdmin; jalankan create-admin.php dengan PHP CLI privat dari terminal hosting atau hubungi pengelola hosting. Jangan membuat endpoint setup/admin publik.
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

## Tanggal penyelesaian servis

Tanggal selesai dan garansi hanya aktif pada status **Selesai** atau **Sudah Diambil**. Tanggal selesai wajib untuk kedua status itu. Mengubah status ke status lain mengosongkan kedua tanggal ketika disimpan. Invoice/detail tidak menampilkan tanggal penyelesaian lama yang tidak sesuai status.

## Ubah status cepat

Admin dapat menekan **Ubah status** di kolom status daftar servis atau pada detail servis. Pilih status lalu **Simpan status**, tanpa membuka Edit servis. Dialog hanya mengubah status dan tanggal penyelesaian/garansi; perangkat, konsumen, biaya, nomor invoice, catatan, dan pilihan tanda tangan tetap tersimpan.

Status Selesai/Sudah Diambil meminta tanggal selesai dan menyediakan tanggal garansi opsional. Status lain mengosongkan keduanya. Dibatalkan meminta konfirmasi pengembalian sparepart; stok dikembalikan secara atomik dan tidak berganda saat kirim ulang. Membuka kembali servis tidak otomatis memakai sparepart lagi. Jika data berubah dari tab lain, muat ulang sebelum menyimpan. Batal/Tutup/Escape menutup dialog tanpa perubahan.

Tanpa dukungan dialog/JavaScript, tautan membuka halaman status ringkas dengan validasi server yang sama. Pelanggan hanya dapat melihat status. Tidak ada migrasi database baru untuk fitur ini; pembaruan tetap memerlukan skema v3 dari rilis sebelumnya.

Pengujian browser tambahan: `runQuickStatus` pada tests/quick-status.mjs memakai fixture hasil tests/run.php dan runBrowserE2E. Di antara suite browser yang diulang, `php tests/reset-login-limits.php` dapat membersihkan bucket login **hanya database _test melalui CLI**, tanpa menghapus akun, pesanan, atau stok. Ini mengisolasi kuota percobaan antarsuite; pembatas login aplikasi tetap aktif.

## Filter status dan cetak invoice

Kartu ringkasan **Dalam proses** menghitung Menunggu Konfirmasi, Diterima, Dalam Pengerjaan, dan Menunggu Sparepart. Kartu **Selesai** menghitung Selesai dan Sudah Diambil. Dibatalkan tidak masuk kedua kelompok; tetap termasuk Total servis. Klik kartu membuka daftar kelompok yang sama. Dropdown menyediakan kelompok ringkasan serta masing-masing status servis. Kelompok hanya dipakai untuk pencarian, bukan status baru pada pesanan.

Pilihan status pada daftar Servis/Invoice langsung menyaring hasil tanpa menekan Cari. Kolom pencarian tetap dapat dikirim dengan Cari atau Enter. Tombol Kembali menyelaraskan pilihan dengan hasil yang dipulihkan browser. Mengganti filter memulai dari halaman pertama; perubahan status cepat mempertahankan pencarian, filter, dan halaman asal. Pesanan yang tidak lagi cocok keluar dari hasil filter tersebut.

Invoice memakai `public/assets/invoice-print.js` tersendiri, dengan URL aset berversi. Tombol Cetak invoice memanggil dialog browser secara langsung. Jika browser menolak/tidak menjalankan perintah, petunjuk alternatif terbuka: di HP gunakan menu browser → Bagikan → Cetak; di desktop Ctrl+P/⌘+P. Petunjuk juga dapat dibuka manual dan tidak ikut tercetak. Template A4, hak akses pemilik, biaya nullable, logo dan tanda tangan tetap sama.

Uji tambahan: `node tests/invoice-filter-js.mjs`, `runInvoiceFilter` dari tests/invoice-filter.mjs, serta tests/print.ps1. Renderer PDF QA memakai fixture database testing dan mencatat metadata cetak sendiri, sehingga tidak bergantung pada hasil browser lama. Pengujian PDF tidak membuktikan akses ke printer fisik atau perilaku semua versi browser HP.

## Hapus dan pulihkan data

Admin membuka **Konsumen → detail → Hapus konsumen**, lalu mengonfirmasi penghapusan permanen akun, profil, seluruh invoice, servis dan alokasinya. Akun tidak dapat login, sesi lama tidak berlaku, dan data tidak dapat dipulihkan. Data arsip versi lama juga mempunyai tombol Hapus permanen. Lihat [aturan dan izin database](docs/PENGHAPUSAN-DATA.md).

Tombol **Hapus invoice** tersedia untuk admin pada nota dan daftar Invoice, termasuk invoice lama. Invoice dan servis merupakan satu transaksi sehingga keduanya dihapus bersama; akun konsumen dan invoice lain tetap ada. Alokasi servis belum selesai dikembalikan ke stok. Pemakaian servis Selesai/Sudah Diambil tetap mengurangi stok; histori stok dipertahankan tanpa referensi invoice. Nomor urut invoice tidak direset.

Penghapusan **sparepart** tetap dapat dipulihkan melalui filter Dihapus dan dicatat di record_events. Stok sparepart yang dihapus tetap tersimpan. Stok masuk dan penambahan penggunaan ditolak sampai dipulihkan. Pengurangan penggunaan atau pembatalan servis tetap mengembalikan stok dengan audit normal. Penghapusan sendiri tidak mengubah stok maupun mencatat pergerakan palsu.

## Pesanan dari detail konsumen

Untuk pesanan dari **Konsumen → detail konsumen → Servis baru**, pemilik pesanan sudah ditetapkan. Form menampilkan ringkasan kontak tanpa dropdown atau tautan Tambah konsumen. Koreksi kontak penerimaan tetap tersedia; tombol Kembali menuju detail konsumen yang sama. Backend menolak customer_id berbeda pada POST formulir ini. Form **Servis baru dari menu umum** tetap menyediakan dropdown konsumen.

## Pengaturan akun dan password

Login admin menggunakan **/admin** (Apache meneruskan ke /admin/), sedangkan login pelanggan tetap **index.php?page=login**. Akun admin hanya dapat login melalui formulir admin dan akun pelanggan melalui formulir pelanggan. Endpoint administrasi untuk tamu serta logout admin mengarah ke /admin/. Tidak ada registrasi admin publik. Folder public/admin harus ikut disalin saat deployment, termasuk pada XAMPP/shared hosting; fitur ini tidak membutuhkan mod_rewrite. Aset dan tautan mengikuti direktori aplikasi.

Login admin, login pelanggan, dan kedua kolom password registrasi memiliki tombol mata MDI untuk tampilkan/sembunyikan password. Default tersembunyi; masing-masing tombol bekerja mandiri dan tidak mengirim formulir. Reload atau kembali ke halaman mengembalikan kondisi tersembunyi.

- Admin: buka **Akun** di navigasi. Nomor HP sendiri dapat diganti dengan verifikasi password saat ini. Nomor dinormalisasi ke 62 dan harus unik di seluruh akun, termasuk konsumen.
- Admin mengganti password pelanggan: **Konsumen → detail pelanggan → Ganti password**. Masukkan password admin saat ini, password baru pelanggan, dan konfirmasinya. Nomor akun, pemilik invoice, dan riwayat servis tetap sama.
- Pelanggan: buka ikon roda gigi **Pengaturan akun** di header untuk mengganti password sendiri dengan password saat ini dan konfirmasi password baru.
- Minimum password **6 karakter**, maksimum **72 byte**, berlaku pada registrasi, tambah konsumen, perubahan password, dan pembuatan admin melalui CLI. Password lama yang lebih panjang tetap berlaku.
- Perubahan nomor atau password mencabut sesi lain milik akun tersebut. Sesi yang melakukan perubahan sendiri diperbarui otomatis; pelanggan yang direset admin perlu login kembali. Setelah pembaruan fitur ini, sesi dari versi lama perlu login sekali lagi.
- Password tidak dapat ditampilkan kembali; semua kolom password selalu kosong setelah validasi gagal. Verifikasi password saat ini memakai pembatas percobaan login yang sama. Tidak ada endpoint reset publik atau pengiriman SMS/WhatsApp.
