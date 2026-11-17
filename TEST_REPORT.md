# Laporan Pengujian — Faeyza Elektronik Service

Tanggal: 17 November 2026, Asia/Jakarta. **PROJECT STATUS: COMPLETE untuk pemeriksaan lokal.**

Semua 13 kelompok penerimaan memiliki bukti pengujian aktual. Regresi terbaru setelah penerapan logo dan referensi nota: **188 assertion PASS, 0 FAIL, 0 BLOCKED** (111 native/HTTP + 53 browser + 24 print/PDF). Lint 32 file PHP lulus pada pengulangan ini; tiga file JavaScript juga telah lulus pada pengujian sebelumnya. Tidak ada error PHP aplikasi tercatat pada akhir pemeriksaan.

## Lingkungan dan isolasi

- Source: `C:\project\bengkel`.
- PHP 8.5.12, ekstensi mysqli/mysqlnd, mbstring, session, curl untuk HTTP test.
- MariaDB 11.4.5, InnoDB, utf8mb4, timezone koneksi +07:00.
- Development: `faeyza`, server `127.0.0.1:8080`, akun DB aplikasi SELECT/INSERT/UPDATE.
- Testing: `faeyza_test`, server `127.0.0.1:8081`, akun DB testing terpisah yang hanya memiliki izin pada database testing.
- Tidak ada database produksi atau data nyata pengguna yang direset. Reset hanya dijalankan dengan `--reset-test` dan guard APP_ENV/testing + suffix `_test`.
- Chrome melalui Browser skill untuk interaksi UI. Renderer Chrome headless terpisah untuk file HTML QA, tanpa memakai profil pengguna. Poppler dan pypdf untuk verifikasi PDF.

## Ringkasan per skenario penerimaan

| Test | Tujuan dan bukti aktual | Status |
|---|---|---|
| 01 Registrasi/login | Normalisasi 08/62/+62, login konsumen/admin, password salah, duplikat format lain, logout, sesi berakhir, endpoint admin ditolak konsumen. Native + HTTP + browser. | PASS |
| 02 Input servis | Form nyata, field wajib, pilihan barang/merk, dua kelengkapan, custom, INSERT MySQL, invoice, reload/logout/login tetap ada. Pengajuan admin terkait pemilik sah. | PASS |
| 03 Validasi | Kosong/HP/tanggal invalid ditolak, form mempertahankan data, admin field buatan ditolak, double submit HTTP dan dua proses token sama hanya satu order. | PASS |
| 04 Sequence invoice | Clock testing 2031-01-01 menghasilkan 001/002; hari berbeda reset 001; sequence 1000 didukung; enam proses/koneksi bersamaan unik 001–006. Invoice tetap walaupun POST admin mencoba mengganti. | PASS |
| 05 Ownership | A/B terisolasi pada domain, HTTP detail/invoice, pencarian HP B oleh A kosong, admin melihat keduanya. Browser IDOR mendapat 404. | PASS |
| 06 Biaya | NULL pada DB/tampilan `-`, admin mengisi 0 → Rp0, 150000 → Rp150.000. Customer POST edit dan field biaya buatan ditolak. Harga komponen tidak otomatis menjadi final. | PASS |
| 07 Sparepart | Tambah/edit, penerimaan 10, alokasi 2 → saldo 8, retry/refresh tetap 8, 2→3 → 7, 3→1 → 9, 1→0 → 10; pembatalan servis memulihkan; baris alokasi dan histori tersimpan. | PASS |
| 08 Stok terbatas/concurrent | Saldo 2, penggunaan 5 ditolak tanpa partial row; stok 0 ditolak; dua proses pada servis berbeda memperebutkan stok 2, tepat satu berhasil; saldo 0 dan total alokasi 2. Rollback kegagalan FK teruji. | PASS |
| 09 Update servis | Status, tanggal selesai, garansi, catatan, total tersimpan; customer melihat hasil. Revision stale ditolak. Perubahan HP akun mempertahankan ownership/snapshot; duplikat HP rollback nama/alamat. | PASS |
| 10 Invoice/cetak | Pemilik membuka invoice, tombol cetak ada dan handler browser dicoba. Nota sesuai referensi dirender menjadi PDF A4: awal/final satu halaman, logo tertanam, tabel biru, syarat/tanda tangan, navigasi/tombol hilang, footer/biaya/garansi benar. Enam halaman teks panjang memuat semua sentinel akhir. | PASS |
| 11 Security | Prepared SQL login/search injection, escaping stored script/img, CSRF salah/tanpa token 419, admin 403, IDOR 404, role/biaya injection, cookie HttpOnly/SameSite, regenerasi login, logout, rate limit, hash, input array/null byte/Unicode password. | PASS |
| 12 Responsive | Form konsumen, navigasi mobile, custom field; dashboard dan invoice pada 375/768/1440. Dashboard, daftar servis/invoice/konsumen/sparepart, form servis/konsumen/sparepart admin pada ketiga ukuran. Tidak ada document overflow. | PASS |
| 13 Simulasi bisnis | Registrasi → login → servis TV → invoice `-` → admin cari HP → Dalam Pengerjaan → sparepart stok 10 → pakai 2/sisa 8 → biaya 150000 → Selesai/tanggal/garansi → konsumen login/cari/lihat final → invoice akun lain ditolak. | PASS |

Label assertion dapat menyebut lebih dari satu kelompok; total 188 menghitung setiap assertion satu kali.

## Perintah dan artefak bukti

Migrasi development/testing benar-benar dijalankan, termasuk pengulangan setup testing tanpa penghapusan skema:

```powershell
C:\project\bengkel-runtime\php\php.exe bin/setup.php --create-db
$env:APP_ENV='testing'
C:\project\bengkel-runtime\php\php.exe bin/setup.php --create-db
C:\project\bengkel-runtime\php\php.exe bin/setup.php
```

Regresi native/HTTP final dijalankan melalui script delivery:

```powershell
.\tests\test.ps1 -Php C:\project\bengkel-runtime\php\php.exe
```

Ringkasan output aktual: `No syntax errors detected` untuk 32 file; `Migrasi v1 siap: faeyza_test`; `{"pass":111,"fail":0,"blocked":0}`. Hasil rinci: `tests/artifacts/integration-results.json`.

Browser: `runBrowserE2E` dari `tests/browser-e2e.mjs` dijalankan melalui browser terhubung dengan tab, capability viewport, dan fixture testing. Ringkasan aktual: `checks: 53, failed: []`. Buktinya `tests/artifacts/browser-results.json`, termasuk 30 pengukuran viewport (requested/innerWidth/scrollWidth). Adapter CLI untuk developer tersedia di `tests/e2e-cli.mjs`; adapter CLI itu tidak dijalankan pada sesi ini karena browser terhubung sudah dipakai.

Cetak aktual:

```powershell
.\tests\print.ps1 `
  -Php C:\project\bengkel-runtime\php\php.exe `
  -Python C:\Users\Administrator\.cache\codex-runtimes\codex-primary-runtime\dependencies\python\python.exe `
  -Poppler C:\Users\Administrator\.cache\codex-runtimes\codex-primary-runtime\dependencies\native\poppler\Library\bin
```

Script menjalankan `tests/render-invoices.php`, Chrome `--headless --no-pdf-header-footer --print-to-pdf`, Poppler `pdftoppm`, dan `tests/check-pdf.py`. Output terbaru: 24 PASS. Bukti `tests/artifacts/print-results.json`, `invoice-initial.pdf`, `invoice-final.pdf`, `invoice-long.pdf`, dan PNG setiap halaman. PDF final yang diuji adalah invoice **INV-20261117-005**, status Selesai, Rp150.000, selesai 17/11/2026, garansi 17/12/2026. Simulasi browser order ID 17, part ID 7, stok akhir 8.

Verifikasi tambahan:

```powershell
php bin/doctor.php
node --check public/assets/app.js
node --check tests/browser-e2e.mjs
node --check tests/e2e-cli.mjs
```

Doctor: versi/ekstensi/direktori writable/sembilan tabel/timezone OK pada database development asli. HTTP smoke: login 200; `/.env`, `/database/schema.sql`, `/config/branding.php` masing-masing 404. Bukti `tests/artifacts/http-smoke.json`. `.env`, `.env.testing`, dan kredensial/fixture lokal terverifikasi diabaikan Git. Source diinisialisasi sebagai repository lokal tanpa remote atau publikasi.

## Temuan, perbaikan, dan regresi

1. Dua assertion awal salah: histori seharusnya 7 movement, bukan 8; Set-Cookie harus diperiksa pada respons awal yang menerbitkan cookie. Assertion diperbaiki; tidak ada perubahan stok yang diperlukan. Regresi native akhir 111/111.
2. Label aksesibilitas absolut di header tabel melebar keluar container dan menyebabkan overflow dashboard mobile. Container tabel dibuat position:relative. Regresi seluruh matriks viewport lulus.
3. Invoice A4 pada layar 768px lebih lebar dari viewport. Lebar layar tablet dibatasi ke viewport dengan margin; print CSS tetap A4. Regresi invoice 375/768/1440 dan PDF lulus.
4. Validasi password diperkuat memakai panjang byte, menolak null byte, dan mempertahankan spasi. Menghindari ValueError password hashing untuk Unicode terlalu panjang. Tiga assertion baru lulus.
5. Runner browser membaca URL yang masih lama ketika redirect belum selesai. Penantian halaman tujuan dan pembaruan observasi ditambahkan; pengulangan penuh 53/53 lulus.
6. Pembatasan source root Apache dan izin folder public dipisahkan secara eksplisit untuk mencegah aturan deny root diwariskan ke area public. Runtime teruji melalui PHP server; konfigurasi Apache disediakan di README.

## Verifikasi visual

Screenshot diperiksa langsung: login desktop, form mobile, dashboard admin desktop, dashboard konsumen mobile/tablet/desktop, invoice awal mobile, invoice final desktop, histori stok, IDOR. Nota terbaru diperiksa pada desktop dan mobile; logo JPEG berhasil dimuat. PDF awal/final dan enam halaman teks panjang dirender ke PNG; seluruh penanda akhir terdeteksi dan navigasi tidak ikut tercetak. Bukti baru: `nota-reference-desktop.png` dan `nota-reference-mobile.png` di tests/artifacts.

Screenshot final yang dapat diperiksa:

- `tests/artifacts/admin-desktop.jpg`
- `tests/artifacts/service-form-mobile.jpg`
- `tests/artifacts/customer-dashboard-375.jpg`
- `tests/artifacts/customer-dashboard-768.jpg`
- `tests/artifacts/customer-dashboard-1440.jpg`
- `tests/artifacts/invoice-final-desktop.jpg`
- `tests/artifacts/invoice-final-print-1.png`
- `tests/artifacts/stock-history-desktop.jpg`
- `tests/artifacts/idor-denied.jpg`

## Batas verifikasi dan known issues

Tidak ada kegagalan fitur wajib yang diketahui setelah regresi final. Form referensi Google tidak dapat diakses; field lengkap spesifikasi diterapkan. Pada tindak lanjut pemilik memberikan logo dan referensi invoice. Logo asli disalin tanpa perubahan, dan nota ditata mengikuti tabel biru, identitas bengkel, catatan, syarat, serta tanda tangan. Website pada gambar terlalu kecil untuk dipastikan sehingga konfigurasi website tetap kosong. Tidak ada schema, biaya, ownership, atau stok pengguna yang diubah untuk penerapan desain ini.

Pemeriksaan sentinel teks panjang awalnya menganggap penanda `AKHIR-TECHNICIAN_NOTES` hilang karena terbungkus menjadi dua baris. Ekstraksi membuktikan teks utuh; pemeriksa memakai whitespace opsional setelah tanda hubung. Regresi ulang 24/24 lulus. Native/HTTP 111/111 dan browser 53/53 juga dijalankan ulang dengan desain baru. Pemeriksaan visual memakai in-app browser pada tindak lanjut ini karena koneksi extension lama tidak merespons.

Klik window.print pada browser terhubung membuat dialog native memblokir respons otomasi tab. Computer Use tidak dapat mengambil monitor/dialog di lingkungan ini. Tab testing dipulihkan, dan keluaran cetak diverifikasi memakai engine print Chrome terpisah, PDF A4 aktual, ekstraksi teks, dan inspeksi PNG. Screenshot dialog native/printer fisik bukan bukti yang tersedia; tidak diklaim sebagai pengujian printer fisik. Pemeriksaan cetak wajib dipenuhi oleh hasil browser print-to-PDF aktual.

Pada fase lokal, Apache/XAMPP dan hosting publik belum dijalankan; pengujian memakai PHP server + MariaDB nyata. Pada fase deployment berikutnya, Apache produksi benar-benar dibangun dan dijalankan di Coolify, domain HTTPS aktif, dan database/admin produksi baru diverifikasi. Rincian terbaru berada di DEPLOYMENT_REPORT.md. Akun dan data simulasi development/testing tidak disalin ke produksi.

## Final verdict

Tindak lanjut MDI/banner: seluruh simbol ikon menu, ringkasan, aksi, pencarian, penerimaan, dan toolbar cetak diganti dengan SVG resmi `@mdi/svg` 7.4.47. Paket diunduh dari registry NPM dan diverifikasi SHA-512 terhadap metadata; subset SVG serta lisensi disimpan lokal. Tidak ada font/CDN yang diperlukan saat aplikasi berjalan. Banner admin baru memakai gradasi biru, ilustrasi perangkat dengan ikon MDI, jumlah antrean/servis selesai aktual, dan tombol servis/invoice yang telah dioperasikan melalui browser. Pemulihan tombol setelah navigasi kembali mempertahankan markup SVG. Tidak ada perubahan skema atau data development untuk pembaruan ini.

Regresi ulang setelah penggantian markup: **111 native/HTTP PASS**, **53 browser PASS**, **24 cetak/PDF PASS**, dan **7 pemeriksaan visual/interaksi PASS**, total **195 PASS, 0 FAIL**. Pemeriksaan visual meliputi keberadaan path SVG, aria-hidden/focusable, beranda 375px dan 768px tanpa overflow, menu mobile, serta kedua tautan banner. Bukti baru: `tests/artifacts/mdi-visual-results.json`, `mdi-admin-desktop.png`, `mdi-admin-375.png`, dan `mdi-admin-768.png`. Seluruh file PHP lulus lint; app.js dan runner browser lulus pemeriksaan sintaks. Teks tombol pada runner diperbarui agar menggunakan nama aksesibel tanpa simbol panah dekoratif. Cetak A4 diperiksa ulang melalui tests/print.ps1; ikon toolbar tidak ikut tercetak dan tata nota tetap sesuai referensi.

Tindak lanjut tema biru: warna dominan aplikasi diselaraskan dengan logo melalui config/branding.php, variabel header, dan app.css. Pemeriksaan browser tambahan **9 PASS, 0 FAIL** mencakup warna aktual banner/tombol/navigasi/fokus input/login, dashboard mobile 375px dan tablet 768px tanpa overflow, navigasi mobile, serta login kembali. Bukti: `tests/artifacts/blue-theme-results.json`, `blue-dashboard-desktop.png`, `blue-dashboard-375.png`, `blue-login-desktop.png`, dan `blue-service-mobile.png`. Kedua file PHP yang berubah lulus lint dan `git diff --check` lulus. Revisi ini hanya menyentuh konfigurasi warna serta stylesheet; suite backend/cetak 188 assertion di atas adalah hasil regresi sebelumnya, tidak dihitung sebagai pengujian ulang untuk revisi palet.

**Siap diperiksa lokal dan melalui https://faeyza.dirgasatya.id.** Seluruh fitur diimplementasikan dengan penyimpanan MySQL/MariaDB. Riwayat suite 195 assertion lokal di atas tetap dibedakan dari pemeriksaan deployment terbaru di bawah.

## Deployment Coolify dan regresi setelah perbaikan Apache

Hasil aktual 17 November 2026: **111 native/HTTP PASS, 30 HTTP produksi PASS, 20 browser produksi PASS, 0 FAIL, 0 BLOCKED**. Sebanyak 33 file PHP lolos lint, Apache syntax OK, PHP 8.4.27 dan MariaDB 11.4.13 terhubung ke database Faeyza khusus. Status 24 resource lama sama sebelum/sesudah deployment; semua database lama tetap healthy.

Produksi awal: 27 HTTP PASS dan 3 FAIL. Dua kegagalan berasal dari respons CSRF nonstandar 419 yang diubah Apache menjadi 500; reason phrase eksplisit memperbaikinya. Satu assertion cookie membaca respons yang belum tepat; cookie sebenarnya terbit saat redirect sebelumnya. Pemeriksa diperbaiki dan seluruh 30 skenario lulus pada domain produksi. Setelah perubahan helper, suite regresi native/HTTP benar-benar dijalankan ulang dan menghasilkan 111/111 PASS.

Perintah: `.\tests\test.ps1 -Php C:\project\bengkel-runtime\php\php.exe`, `node --check public/assets/app.js`, dan `php tests/production-smoke.php https://faeyza.dirgasatya.id`. Browser dipakai pada profil Chrome Imam untuk login, menu, form, pencarian, mobile 375px/tablet 768px/desktop 1440px, logout dan login ulang. Penantian navigasi ditambahkan pada pemeriksaan login ulang; screenshot full-page mobile diganti capture viewport setelah timeout alat. Ini keterbatasan otomasi yang telah ditangani, bukan kegagalan halaman.

Bukti JSON: `tests/artifacts/production-smoke-results.json`, `production-browser-results.json`, dan `coolify-resource-comparison.json`. Screenshot dashboard/form/isolasi/runtime disimpan lokal pada direktori artefak yang diabaikan Git. Detail resource, volume, hak database, perbaikan, status akun, serta batas operasional tersedia di **DEPLOYMENT_REPORT.md**. Suite bisnis browser 53, PDF 24, dan MDI 7 tetap merupakan hasil lokal terdahulu; tidak dihitung sebagai pengujian ulang di produksi.
