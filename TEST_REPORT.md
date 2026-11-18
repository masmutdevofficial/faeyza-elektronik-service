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

Pembaruan UI ringkas berikutnya: **111 native/HTTP PASS dan 56 browser PASS**. Browser benar-benar menjalankan registrasi, login, pengajuan TV, invoice tanpa biaya, stok 10 → 8, penetapan Rp150.000, selesai/garansi, invoice final, IDOR, serta viewport 375/768/1440. Pemeriksaan tambahan memastikan data kontak terisi otomatis, bagian opsional tertutup pada awalnya, dan kontak invalid otomatis terbuka. Bukti terbaru: `tests/artifacts/minimal-browser-results.json`, `minimal-admin-desktop.png`, `minimal-customer-dashboard-375.png`, dan `minimal-service-form-mobile.png`. Pengujian viewport diulang setelah tab Coolify terpisah mengambil fokus ukuran layar; ukuran CSS aktual diperiksa sebelum assertion. Schema dan template cetak tidak berubah pada revisi UI ini.

UI ringkas sudah dirilis ke domain produksi, commit runtime f6d9ea1. HTTP produksi dijalankan ulang: **30 PASS, 0 FAIL**. Pemeriksaan visual/DOM pada domain aktif memastikan antrean baru, tidak ada banner promosi, MDI tetap tampil, dan tidak ada overflow desktop. Koreksi empty state admin diverifikasi dengan render dashboard kosong pada DB testing dan halaman produksi. Bukti: `minimal-production-admin-1440.png`, `minimal-live-checks.json`, `production-smoke-results.json`; detail deployment di DEPLOYMENT_REPORT.md.

Hasil aktual 17 November 2026: **111 native/HTTP PASS, 30 HTTP produksi PASS, 20 browser produksi PASS, 0 FAIL, 0 BLOCKED**. Sebanyak 33 file PHP lolos lint, Apache syntax OK, PHP 8.4.27 dan MariaDB 11.4.13 terhubung ke database Faeyza khusus. Status 24 resource lama sama sebelum/sesudah deployment; semua database lama tetap healthy.

Produksi awal: 27 HTTP PASS dan 3 FAIL. Dua kegagalan berasal dari respons CSRF nonstandar 419 yang diubah Apache menjadi 500; reason phrase eksplisit memperbaikinya. Satu assertion cookie membaca respons yang belum tepat; cookie sebenarnya terbit saat redirect sebelumnya. Pemeriksa diperbaiki dan seluruh 30 skenario lulus pada domain produksi. Setelah perubahan helper, suite regresi native/HTTP benar-benar dijalankan ulang dan menghasilkan 111/111 PASS.

Perintah: `.\tests\test.ps1 -Php C:\project\bengkel-runtime\php\php.exe`, `node --check public/assets/app.js`, dan `php tests/production-smoke.php https://faeyza.dirgasatya.id`. Browser dipakai pada profil Chrome Imam untuk login, menu, form, pencarian, mobile 375px/tablet 768px/desktop 1440px, logout dan login ulang. Penantian navigasi ditambahkan pada pemeriksaan login ulang; screenshot full-page mobile diganti capture viewport setelah timeout alat. Ini keterbatasan otomasi yang telah ditangani, bukan kegagalan halaman.

Bukti JSON: `tests/artifacts/production-smoke-results.json`, `production-browser-results.json`, dan `coolify-resource-comparison.json`. Screenshot dashboard/form/isolasi/runtime disimpan lokal pada direktori artefak yang diabaikan Git. Detail resource, volume, hak database, perbaikan, status akun, serta batas operasional tersedia di **DEPLOYMENT_REPORT.md**. Suite bisnis browser 53, PDF 24, dan MDI 7 tetap merupakan hasil lokal terdahulu; tidak dihitung sebagai pengujian ulang di produksi.

## Tanda tangan bengkel per pesanan

Hasil aktual: 125 native/HTTP PASS, 63 browser PASS, 26 cetak/PDF PASS, 0 FAIL. Pengujian mencakup default aktif ketika Selesai/Sudah Diambil, tidak tampil untuk status lain, aktif/nonaktif tersimpan setelah reload, invoice konsumen mengikuti pilihan admin, role/CSRF, input toggle invalid/array, dan preferensi yang dipertahankan jika request admin tidak memuat opsi. Browser mengoperasikan toggle nyata; tambahan pemeriksaan mobile memastikan sakelar dapat dipakai pada 375px tanpa overflow.

Perintah: tests/test.ps1, runBrowserE2E dari tests/browser-e2e.mjs, tests/print.ps1 dengan Python venv development dan Poppler. PDF A4 dihasilkan oleh engine print browser dan diperiksa secara visual; tanda tangan berada dalam kotak Pihak bengkel tanpa memotong nama atau isi. Dua assertion PDF baru memastikan invoice awal tidak memiliki gambar tanda tangan dan invoice selesai memiliki logo serta tanda tangan. Template cetak panjang juga tetap lolos.

Bukti: tests/artifacts/signature-integration-output.txt, signature-browser-results.json, print-results.json, signature-toggle-mobile.png, invoice-final-print-1.png, dan signature-production-migration.png. File PNG asli disalin tanpa perubahan; SHA256 FA6B4117D1A61DB5EDB96A2DDE1F34B82466EB7DA64F113ECDC1748913B2BD70. Aset berada di config/assets dan hanya disisipkan sebagai data URI dalam invoice yang sudah lolos autentikasi/kepemilikan.

Migrasi v2 diuji pada database lokal yang sudah ada serta database testing; eksekusi ulang berhasil dan target yang tidak cocok ditolak. Produksi dicadangkan terlebih dahulu pada volume Faeyza (file root-only), lalu hanya ditambahkan kolom pada service_orders dan versi migrasi. Jumlah pesanan sebelum/sesudah tetap sama. Tidak ada akun, password, kepemilikan, nomor invoice, biaya, atau stok yang diubah oleh migrasi.
Verifikasi produksi tanda tangan: 31 HTTP PASS, termasuk penolakan URL langsung ke config/assets/faeyza-ttd.png. Doctor produksi menyatakan aset dan kolom tanda tangan OK; PHP log tidak memiliki error. Pratinjau UI pada pesanan asli Diterima memastikan toggle tersembunyi lalu checked saat pilihan Selesai, tanpa submit, dan status asli dikonfirmasi tetap Diterima. Jumlah data tetap 1 pesanan/3 user. Rilis runtime cdb42b5 sukses. Semua pengujian perubahan aktif/nonaktif yang menyimpan data dilakukan pada DB testing; bukti PDF dan UI memakai data uji.

## Kontak otomatis dan identitas invoice

Hasil lokal aktual: **134 native/HTTP PASS, 70 browser PASS, 32 PDF PASS, 0 FAIL, 0 BLOCKED**. Perintah: tests/test.ps1, runBrowserE2E dari tests/browser-e2e.mjs, tests/print.ps1. Lint PHP dan sintaks kedua file JavaScript lulus. Semua mutasi simulasi memakai faeyza_test, bukan produksi.

Regresi meliputi pengajuan admin tanpa mengirim kontak, fallback field kosong, penolakan konsumen invalid/input array, koreksi kontak tanpa mengubah akun, pergantian konsumen, pembersihan pilihan, serta submit browser tanpa mengetik ulang nama/HP/alamat. Simulasi TV, stok 10 menjadi 8, biaya Rp150.000, status selesai, tanda tangan dan IDOR tetap lulus. Viewport 375/768/1440 diperiksa melalui ukuran CSS aktual dan scrollWidth.

Tiga PDF nyata (awal, final, teks panjang) memastikan www.faeyzateknik.com tercetak, nama pihak bengkel Asmat tanpa Bpk., ukuran A4, tanpa navigasi, dan konten panjang tidak hilang. PNG invoice final diperiksa visual: website di header dan Asmat di bawah tanda tangan, satu halaman rapi.

Pada percobaan browser pertama, pembacaan langsung nilai input tel disamarkan oleh alat sehingga assertion gagal walaupun kontak terisi. Pemeriksa diperbaiki untuk membandingkan nama/alamat serta teks HP pada ringkasan yang terlihat; submit sungguhan tetap diuji. Regresi browser kemudian 70/70 PASS. Bukti: tests/artifacts/contact-integration-output.txt, contact-browser-results.json, contact-print-output.txt, print-results.json, invoice-final-print-1.png.

Rilis produksi 9836381 sukses di Coolify (deployment enpjhqf2vcfolqvaqmcruope). Pengujian HTTP produksi **31 PASS** dan browser produksi **8 PASS**, tanpa submit pesanan: pilihan Indra menampilkan kontak yang sesuai, koreksi tertutup, aset v4 aktif, website invoice dan nama Asmat benar. Semua hasil revisi ini berjumlah **275 PASS, 0 FAIL, 0 BLOCKED**. Tidak ada migrasi database pada revisi ini. Bukti: contact-production-smoke.txt, contact-live-checks.json, contact-deployment-state.txt; screenshot invoice dengan data uji: contact-invoice-final-verified.png.

## Servis baru dari detail konsumen

Penyebab: tombol pada riwayat kosong membuka service-new tanpa ID konsumen. Tautan sekarang membawa customer_id; controller memvalidasi konteks GET khusus admin dan memilih konsumen beserta kontak sejak render pertama. Tombol juga tersedia pada riwayat berisi. Pilihan tetap dapat diganti, data POST tidak ditimpa konteks GET, dan form umum tidak mewarisi konsumen dari kunjungan sebelumnya.

Regresi aktual: **144 native/HTTP PASS, 73 browser PASS, 0 FAIL, 0 BLOCKED**. Perintah: tests/test.ps1 dan runBrowserE2E dari tests/browser-e2e.mjs. Sepuluh assertion HTTP baru menguji riwayat kosong/berisi, kontak sebelum JavaScript, ID invalid/array/injection, akses role konsumen, serta pilihan/koreksi setelah validasi gagal dan setelah submit. Tiga pemeriksaan browser baru benar-benar mengklik detail konsumen → Servis baru, memeriksa pilihan otomatis, lalu reload. Simulasi bisnis dan viewport 375/768/1440 tetap lulus. Bukti: tests/artifacts/context-integration-output.txt dan context-browser-results.json. Seluruh perubahan data simulasi hanya di faeyza_test; template cetak tidak berubah.

Produksi runtime 64dc7c9, deployment 09cpbx2mfvswbufowmy42e1u Success dalam 34 detik. **31 HTTP dan 8 browser produksi PASS**: detail Indra → Servis baru langsung memilih Indra, nama/HP/alamat benar, koreksi tertutup, reload tetap memilih Indra, dan tombol pada riwayat berisi juga membawa konsumen yang sesuai. Tidak dilakukan submit atau perubahan data produksi. Bukti: context-production-smoke.txt, context-live-results.json, context-deployment-state.txt. Screenshot context-customer-default.png memakai data uji.

## Pengaturan akun dan password enam karakter

Hasil aktual lokal: **186 native/HTTP PASS, 90 browser PASS, 0 FAIL, 0 BLOCKED**. Perintah: tests/test.ps1 dan runBrowserE2E dari tests/browser-e2e.mjs. Suite accounts.php dijalankan melalui run.php pada faeyza_test. Regresi meliputi registrasi enam karakter, penolakan lima karakter/Unicode pendek/input array/null/lebih dari 72 byte, konfirmasi berbeda, password saat ini salah, CSRF, otorisasi, manipulasi user_id/role, nomor HP duplikat setelah normalisasi, login dengan kredensial baru, serta penolakan kredensial lama. Rehash login memakai compare-and-update agar tidak menimpa perubahan kredensial bersamaan; stamp sesi menggunakan hasil kredensial yang telah diverifikasi/diperbarui dalam transaksi.

Perubahan password pelanggan sendiri, password admin sendiri, nomor admin sendiri, dan reset pelanggan oleh admin benar-benar dilakukan melalui HTTP dengan akun uji khusus. Sesi kedua dicabut, sesi perubahan sendiri bertahan, kepemilikan invoice dan hash akun lain tetap sama. Verifikasi password saat ini memakai rate limiter login. Pemeriksaan kode menemukan bahwa pencabutan sesi harus membuat token CSRF baru agar halaman login langsung dapat dipakai; perbaikan diuji dengan membuka login dari sesi kedaluwarsa.

Browser memeriksa tautan pengaturan pelanggan, menu Akun admin, Ganti password dari detail konsumen, field password saat ini, konfirmasi/minlength enam, serta semua halaman baru pada 375/768/1440px tanpa overflow. Form baru diperiksa visual melalui screenshot account-customer-account-mobile.png dan account-admin-account-desktop.png. Perubahan kredensial otomatis dilakukan oleh runner HTTP pada akun testing; browser hanya memeriksa formulir dan menjalankan simulasi bisnis yang sudah ada. Tidak ada password atau nomor akun produksi yang diubah untuk QA. Template cetak/skema tidak berubah.

Bukti: tests/artifacts/account-integration-output.txt, account-browser-results.json. Data uji/password acak tetap berada di file lokal yang diabaikan Git. Password asli tidak ditulis pada laporan atau artefak.

Rilis produksi **ad00792136c6e20452d56eac45cd5e2dd9696f84**, deployment **ivakvdcmcd0wlrpuchclzw6q Success**, durasi 37 detik. **34 HTTP produksi PASS dan 14 browser produksi PASS**. Admin login ulang setelah sesi versi lama dicabut, membuka Akun, mengakses Ganti password dari detail Indra, dan memeriksa form/CSRF/minimum enam karakter. Account dan reset pelanggan diuji pada 375/768/1440px tanpa overflow, console error 0. Pemeriksaan tidak mengirim perubahan nomor/password di produksi. Total revisi ini **324 PASS, 0 FAIL, 0 BLOCKED**; angka ini tidak mencakup hasil cetak historis.

Tidak ada migrasi, perubahan volume atau resource lain. Log deployment hanya mengganti container Faeyza, database khusus Healthy. Bukti produksi: account-production-smoke.txt, account-live-results.json, account-deployment-state.txt. Server QA 8081 dihentikan setelah selesai; server development 8080 dipertahankan.

## Login admin terpisah dan visibilitas password

Hasil aktual lokal: **203 native/HTTP PASS, 120 browser PASS, 9 tata letak browser PASS, 0 FAIL, 0 BLOCKED**. Perintah: tests/test.ps1, runBrowserE2E dari tests/browser-e2e.mjs, dan runAuthLayout dari tests/auth-visual.mjs. Semua file PHP lolos lint; app.js dan kedua runner browser lolos pemeriksaan sintaks. Semua mutasi simulasi berada di faeyza_test.

Entry point fisik public/admin/index.php menyediakan /admin tanpa mod_rewrite. Login memeriksa role di server; pelanggan ditolak di formulir admin, admin ditolak di formulir pelanggan. Query page/role tidak dapat mengubah akses entry admin. Logout admin dan akses admin oleh tamu mengarah ke /admin/. CSRF, password salah, session regeneration, pengaturan akun, serta simulasi bisnis lengkap tetap lulus.

Browser mengoperasikan tombol tampilkan/sembunyikan pada login admin, login pelanggan, dan dua kolom registrasi. Keadaan awal/reload tersembunyi, aria-pressed mengikuti keadaan, konfirmasi bekerja mandiri, dan klik tidak submit. Ketiga halaman diuji pada 375/768/1440px, logo/CSS/MDI termuat. Pemeriksaan visual menemukan input perlu width 100% setelah dibungkus kontrol mata; perbaikan diuji dengan bounding box pada sembilan kombinasi halaman/viewport. Screenshot akhir: login-admin-login-mobile.png, login-login-mobile.png, login-register-mobile.png.

Percobaan native pertama: 199 PASS, 4 FAIL karena dirname pada Windows mengembalikan pemisah backslash untuk root URL, sehingga base/action/redirect tidak benar. Normalisasi pemisah setelah dirname memperbaiki penyebab; regresi penuh berikutnya 203/203 PASS. Bukti: tests/artifacts/login-integration-output.txt, login-browser-results.json, login-layout-results.json. Skema dan data produksi belum diubah oleh tahap testing lokal ini.

Rilis runtime **7870e1cf12efd551410562bda61fe20e7114f30c**, deployment **slm1k1prlfr1g6qj5egzvcot Success**, durasi 29 detik. Produksi **40 HTTP PASS dan 22 browser PASS**, total revisi **394 PASS, 0 FAIL, 0 BLOCKED** setelah perbaikan. URL /admin menuju /admin/ dan memuat login admin beserta aset yang benar. Admin berhasil login melalui halaman baru dan logout kembali ke /admin/. Toggle show/hide pada login admin/pelanggan dan konfirmasi register dioperasikan langsung. Sembilan pemeriksaan tata letak produksi pada 375/768/1440px lulus, console error 0.

Tidak dibuat akun/pesanan atau perubahan password/nomor di produksi. Tidak ada migrasi skema maupun perubahan resource lain. Database khusus Faeyza Healthy; server QA 8081 dihentikan setelah pengujian. Bukti: login-production-smoke.txt, login-live-results.json, login-deployment-state.txt, login-production-admin-login-mobile.png dan login-production-register-mobile.png. Artefak final menampilkan field kosong, tanpa password.

## Form pesanan khusus konsumen tanpa dropdown

Form dari detail konsumen tidak lagi menampilkan dropdown atau Tambah konsumen. Identitas pemilik diambil server dari konteks URL pada GET dan POST, ID berbeda dalam POST ditolak, dan ID yang tidak dikirim tetap ditentukan server. Ringkasan serta koreksi kontak tetap bekerja; Kembali menuju detail konsumen. Form servis umum masih memiliki dropdown. Query pada form khusus hanya memuat konsumen terkait.

Regresi backend aktual: **207 native/HTTP PASS, 0 FAIL**, melalui tests/test.ps1 pada faeyza_test. Pemeriksaan baru mencakup HTML tanpa dropdown, tujuan Kembali, validasi gagal yang mempertahankan kontak/konteks, penolakan POST pemilik berbeda tanpa pencatatan parsial, submit tanpa hidden field/kontak, dan konteks POST invalid. Skenario lama yang membolehkan pergantian konsumen dalam form dari detail diperbarui mengikuti permintaan terbaru. Bukti: tests/artifacts/locked-integration-output.txt.

Browser aktual: **124 PASS, 0 FAIL**, melalui runBrowserE2E dari tests/browser-e2e.mjs. Alur detail konsumen → servis baru tidak memiliki dropdown, reload mempertahankan konsumen, koreksi kontak memperbarui ringkasan, dan pengajuan tanpa memilih ulang berhasil. Form khusus 375px tanpa overflow; form umum tetap dapat memilih/mengganti konsumen. Regresi login, pengaturan akun, stok 10 → 8, biaya Rp150.000, selesai/garansi, tanda tangan, IDOR dan viewport 375/768/1440 tetap lulus. Bukti: locked-browser-results.json. Pengambilan gambar tambahan setelah suite selesai mengalami timeout koneksi browser; hasil suite sudah disimpan sebelum pemulihan koneksi dan tidak diklaim gagal aplikasi.

Produksi runtime **46e285a86b20bbfa23d067eae28805e7cdeed6e0**, deployment **yf0uerxbz8a5nc5qml7jifxl Success**, durasi 2 menit 4 detik. Produksi **40 HTTP PASS + 9 browser PASS**, sehingga total revisi ini **380 PASS, 0 FAIL, 0 BLOCKED**. Alur Konsumen → Simon → Servis baru benar-benar diklik: tidak ada dropdown/Tambah konsumen, kontak tampil, hidden pemilik sesuai, Kembali ke detail Simon, reload tetap terkunci, JS v6 termuat, dan form umum tetap memiliki dropdown. Pembacaan value pada hidden input disamarkan alat; kecocokan diperiksa melalui selector atribut value yang tidak sensitif, sementara HTML/penyimpanan sebenarnya juga telah diuji native. Console error 0.

Tidak dilakukan submit pesanan atau koreksi data produksi untuk QA. Screenshot produksi akhir locked-production-simon.png memperlihatkan ringkasan Simon tanpa dropdown, dan sudah diperiksa visual. Koneksi Chrome dipulihkan dengan tab baru pada profil Imam setelah tab lama terlepas; screenshot tambahan berhasil tanpa mengulang perubahan data. Server QA 8081 dihentikan. Tidak ada migrasi, perubahan volume/compose atau resource lain. Bukti: locked-production-smoke.txt, locked-live-results.json, locked-deployment-state.txt.

## Label perangkat pada rincian pelanggan

Nama barang, merek, dan model sebelumnya hanya muncul dalam judul/ringkasan kartu. Ketiganya sekarang juga memiliki baris berlabel pada rincian servis, halaman sukses, serta detail admin yang memakai template sama. Nilai tetap berasal dari pesanan, termasuk pilihan custom, dan di-escape sebelum ditampilkan. Tidak ada perubahan skema, penyimpanan, hak akses atau template invoice.

Hasil aktual lokal: **214 native/HTTP PASS + 14 browser PASS, 0 FAIL, 0 BLOCKED**. Perintah: tests/test.ps1 dan runDeviceDetails dari tests/device-details.mjs. Tujuh assertion HTTP baru memeriksa tiga nilai berlabel, konsistensi sukses/admin, custom/XSS, dan penolakan pelanggan lain. Browser benar-benar login pelanggan dan memeriksa rincian normal/custom pada 375/768/1440px: nilai sesuai fixture, tidak ada HTML input aktif maupun overflow, dan IDOR tetap ditolak.

Bukti: tests/artifacts/device-integration-output.txt, device-browser-results.json, device-details-mobile.png. Data simulasi berada di faeyza_test; screenshot memakai akun/data uji. Suite browser bisnis lengkap 124 pada revisi sebelumnya tidak dihitung sebagai pengujian ulang untuk perubahan label ini.

Runtime **65ee1c7e5a2bb754bd86ba57bf09be0c332e7191** dirilis, deployment **1s09t6hj7bs7kd2ycyid6re0 Success**, durasi 27 detik. Produksi **40 HTTP PASS + 3 browser PASS**, total revisi **271 PASS, 0 FAIL, 0 BLOCKED**. Detail pesanan produksi yang ditunjukkan pengguna dibuka read-only melalui admin: Nama barang Speaker Aktif Bluetooth, Merek Harman Kardon, Model Onyx 5 tampil pada tiga baris. Template rincian sama untuk admin/pelanggan; akses browser sebagai pelanggan benar-benar diuji di lingkungan testing, bukan memakai kredensial pelanggan produksi. Console error 0. Tidak ada perubahan data servis, schema, compose atau volume.

Bukti: device-production-smoke.txt, device-live-results.json, device-deployment-state.txt, device-production-details.png. Server QA 8081 dihentikan setelah pengujian. Screenshot pelanggan device-details-mobile.png sudah diperiksa visual dan menunjukkan tiga baris baru pada 375px.

## Tanggal selesai dan hapus/pulihkan konsumen serta sparepart

Aturan tanggal mengikuti status pada frontend dan backend. Selesai/Sudah Diambil memerlukan tanggal selesai; status lain mengosongkan tanggal selesai/garansi pada penyimpanan. Template detail/invoice langsung menyembunyikan tanggal lama yang tidak sesuai status, tanpa mengubah transaksi produksi secara massal.

Penghapusan dapat dipulihkan melalui filter Dihapus. Relasi akun/invoice/pemakaian/stok tetap tersimpan; nomor HP/SKU tidak dapat dipakai ulang. record_events menyimpan operator, waktu, jenis entitas dan tindakan. Penghapusan/pemulihan konsumen mengganti versi sesi, sehingga sesi lama tetap dicabut sesudah restore. Sparepart dihapus menolak stok masuk/penambahan pemakaian; pengurangan pemakaian/pembatalan tetap mengembalikan stok.

Hasil aktual lokal:

- **264 native/HTTP PASS, 0 FAIL**: `tests/test.ps1 -Php C:\project\bengkel-runtime\php\php.exe`. Database faeyza_test terpisah, lint seluruh PHP, migration v3, regresi autentikasi/otorisasi/CSRF/IDOR, tujuh status, pembersihan tanggal, konfirmasi hapus, sesi dicabut, filter/restore, stok terpelihara, pengembalian saat dibatalkan, dua proses hapus bersamaan menghasilkan satu audit, dan race hapus/stok masuk konsisten. Dua assertion tambahan memastikan label konfirmasi tidak tertimpa navigasi. Bukti: archive-integration-output.txt dan integration-results.json.
- **124 browser bisnis PASS, 0 FAIL**: runBrowserE2E dari tests/browser-e2e.mjs melalui profil Imam. Registrasi/login, pengajuan TV, invoice biaya kosong, admin mengubah status, stok 10 menjadi 8, biaya Rp150.000, selesai/garansi, tanda tangan, pelanggan melihat hasil dan IDOR ditolak. Regresi akun/login/konteks konsumen serta viewport 375/768/1440px. Bukti: archive-business-results.json dan screenshot archive-*.
- **42 cetak PDF PASS, 0 FAIL**: `tests/print.ps1` dengan PHP, Chrome headless untuk renderer lokal, Poppler dan pypdf. Empat dokumen: invoice awal, final, teks panjang, dan data lama bertanggal selesai/garansi tetapi status Menunggu Sparepart. PDF terakhir menampilkan kedua tanggal sebagai '-', tanpa tanda tangan; gambar invoice-pending-print-1.png diperiksa visual. Layout A4, tabel, logo, website, signatory Asmat, navigasi tersembunyi, teks panjang tercetak seluruhnya. Bukti: archive-print-output.txt dan print-results.json.

- **33 browser fitur baru PASS, 0 FAIL**: runCompletionDeletion dari tests/completion-deletion.mjs. Tujuh status menyalakan/menonaktifkan tanggal sesuai aturan; penyimpanan nonfinal membersihkan tanggal. Konfirmasi/penghapusan/pemulihan konsumen dan sparepart benar-benar diklik, invoice historis tetap terbaca, stok 8 tetap tersimpan saat hapus, pembatalan mengembalikan menjadi 10, restore mengaktifkan form stok kembali, pelanggan tetap ditolak di endpoint hapus. Lima halaman baru/berubah diuji pada 375/768/1440px tanpa overflow. Bukti: archive-ui-results.json, archive-ui-progress.txt dan screenshot archive-delete*/archive-service-dates-mobile.png.

Total lokal final: **463 PASS, 0 FAIL, 0 BLOCKED**. Masalah yang diperbaiki selama implementasi: kutip checkbox filter ditemukan lint; variabel label halaman konfirmasi tertimpa loop navigasi header sehingga tertulis Hapus Akun. Keduanya diperbaiki dan regresi akhir 264 native + 33 interaksi browser lulus. Otomasi navigasi browser diperbaiki agar menunggu halaman tujuan sebelum membaca DOM; koneksi yang terputus dipulihkan pada profil Imam yang sama. Tidak ada test gagal yang disembunyikan sebagai PASS. Hasil deployment dicatat setelah verifikasi produksi.

Runtime **5cd0e228fbec8f6f9f67017c5dbb9d7350a10663** dirilis melalui profil Imam, deployment **8r2qroyxrpnjtpdmqvwumlp7 Success**, durasi 39 detik, application healthcheck Healthy. Backup khusus faeyza_service dibuat sebelum migrasi v3, file privat root-only 600 berukuran 16.979 byte pada volume DB Faeyza. Jumlah akun/konsumen/servis/sparepart tetap **4/3/3/1** sebelum migrasi dan sesudah rilis; record_events tetap 0. Tidak dilakukan hapus/restore/submit/edit pesanan produksi untuk QA. Tidak ada perubahan compose, volume, hak akses runtime atau resource aplikasi lain.

Produksi **45 HTTP PASS + 14 browser PASS**, total akhir revisi **522 PASS, 0 FAIL, 0 BLOCKED**. Perintah HTTP: `php tests/production-smoke.php https://faeyza.dirgasatya.id`. Browser admin benar-benar login: tanggal Menunggu Sparepart kosong/disabled, pilihan Selesai/Sudah Diambil mengaktifkan kolom tanpa save, invoice lama menunjukkan strip, kedua filter Dihapus tersedia, kedua konfirmasi memakai label benar, Batal mempertahankan stok 5, status pesanan tetap Menunggu Sparepart, dan console error 0. Form/konfirmasi pada viewport aktual 375px tanpa overflow. Satu percobaan viewport produksi memakai ukuran host alih-alih CSS pixels; adapter dikalibrasi ulang dan assertion diulang PASS, bukti awal tetap disimpan di archive-live-initial-attempt.json.

`php bin/doctor.php` produksi: PHP 8.4.27, MariaDB 11.4.13/faeyza_service, kolom/tabel v3 OK, storage writable, timezone Asia/Jakarta. Hak runtime tetap SELECT/INSERT/UPDATE hanya pada faeyza_service; tidak ada file error log aplikasi. Bukti: archive-production-smoke.txt, archive-live-results.json, archive-deployment-state.txt, archive-migration-state.txt, archive-production-doctor.txt, archive-production-health.txt. Screenshot archive-production-delete-part-mobile.png dan archive-production-dates-mobile.png diperiksa visual. Server QA 8081 dihentikan; development 8080 tetap berjalan. Status revisi COMPLETE.

## Ubah status cepat — source dan push main

Admin mendapat aksi Ubah status pada daftar/detail servis. Dialog hanya memuat status, tanggal selesai/garansi untuk status final, dan konfirmasi pembatalan. Endpoint service-status mengubah tiga kolom tersebut saja dengan row lock/revision; kontak/perangkat/biaya/invoice/catatan/tanda tangan tetap utuh. Logika tanggal dan pengembalian stok dipakai bersama dengan Edit servis. Halaman status ringkas tetap tersedia sebagai fallback. Tidak ada migrasi database atau dependensi produksi baru.

| Suite | Tujuan | Hasil aktual | Perintah/bukti |
| --- | --- | --- | --- |
| Native/domain/HTTP | Regresi lama, status saja, otorisasi/CSRF, tanggal, field tampering, data tetap utuh, stok, double submit, stale edit, dua proses status bersamaan | 297 PASS, 0 FAIL | tests/test.ps1 -Php C:\project\bengkel-runtime\php\php.exe; quick-status-integration-output.txt, integration-results.json |
| Browser bisnis penuh | Registrasi/login, pengajuan, stok 10 menjadi 8, biaya Rp150.000, selesai/garansi, tanda tangan, pelanggan/IDOR, akun dan formulir, viewport | 124 PASS, 0 FAIL | runBrowserE2E dari tests/browser-e2e.mjs; quick-status-business-results.json |
| Browser status cepat | Dialog daftar/detail, tujuh status, tanggal wajib, konfirmasi, stok 8 kembali 10, Batal/Escape, fallback, pelanggan, responsive | 29 PASS, 0 FAIL | runQuickStatus dari tests/quick-status.mjs; quick-status-ui-results.json |

Total final revisi ini **450 PASS, 0 FAIL, 0 BLOCKED**. Dialog daftar/detail serta halaman status ringkas diperiksa pada 375/768/1440px; tanpa overflow horizontal. Screenshot quick-status-dates-mobile.png dan quick-status-cancel-mobile.png benar-benar memperlihatkan tanggal/konfirmasi dan telah diperiksa visual. Console browser tidak memiliki error. Lint PHP, node --check untuk JS/module test, dan git diff --check dijalankan. Log PHP/server QA tidak memiliki fatal error, warning atau parse error.

Masalah yang ditemukan dan diperbaiki:

- Assertion native menemukan tautan status ikut muncul pada kartu pelanggan karena penggantian template terlalu luas. Dikembalikan ke badge biasa; regresi final 297 PASS. Endpoint sejak awal tetap menolak pelanggan.
- Pemeriksaan visual menemukan dialog dapat tertutup oleh pageshow saat pemuatan awal belum selesai. Penutupan kini hanya untuk pemulihan bfcache (event.persisted); suite browser penuh dan dialog diulang PASS. Pemeriksaan responsive juga memastikan dialog tetap terbuka.
- Pembacaan form.elements pada adapter browser tidak mendukung akses properti bernama; assertion memakai querySelector tanpa mengubah perilaku aplikasi.
- Pengulangan beberapa suite dalam 15 menit mengenai rate limit login yang memang aktif. Bucket hanya faeyza_test dibersihkan melalui tests/reset-login-limits.php, kemudian suite diulang. Guard script benar-benar diuji menolak DB_NAME=faeyza_service sebelum koneksi/query. Tidak ada pelemahan rate limit produksi.

Seluruh simulasi memakai database faeyza_test; tidak ada perubahan pesanan, akun, stok atau skema produksi. Deployment manual Coolify tidak dijalankan pada revisi ini, sesuai permintaan implementasi dan push main. Hasil produksi pada bagian sebelumnya berlaku bagi runtime sebelumnya. UI/form invoice cetak tidak diubah sehingga suite PDF tidak dijalankan ulang pada revisi ini.

Source **4238066cbe23b540a874be1b70d5f4b084c1c6e1** berhasil dipush ke origin/main. Lint akhir **53 file PHP OK**, kedua syntax check JavaScript OK, git diff --check bersih. Server QA 8081 dihentikan setelah identitas proses diverifikasi; development 8080 tetap berjalan. Artefak uji dan kredensial tidak masuk Git. Status pekerjaan implementasi/push COMPLETE.

## Redeploy produksi Ubah status cepat

Permintaan redeploy berikutnya telah dijalankan. Runtime 62cc1d2e1a038f1884e335b26f28d56c3b883a91, deployment ugya9ldmwfbpta4nmp0blrp4 Success dalam 33 detik, healthcheck Healthy. Tidak ada migrasi atau perubahan data servis. Source fitur sama dengan fase 450 pemeriksaan lokal di atas; suite lokal tersebut tidak dihitung sebagai eksekusi ulang pada fase redeploy.

- **47 HTTP PASS, 0 FAIL**: `php tests/production-smoke.php https://faeyza.dirgasatya.id`. Seluruh pemeriksaan keamanan/aset/login lama dan dua assertion baru memastikan guest GET/POST service-status diarahkan ke login admin. Bukti: quick-status-production-smoke.txt dan production-smoke-results.json.
- **25 browser produksi PASS, 0 FAIL**: verifyLiveQuickStatus dari tests/quick-status-production.mjs, sesi admin profil Imam. Aset v8, aksi pada setiap baris, dialog daftar/detail, tujuh status, tanggal wajib/hanya aktif untuk final, konfirmasi pembatalan, Batal/Tutup/Escape, fallback, serta 375/768/1440px. Pratinjau tidak mengubah status, biaya, atau rincian pesanan. Console error 0. Bukti: quick-status-live-results.json; quick-status-production-mobile.png diperiksa visual.

Doctor produksi OK: PHP 8.4.27, MariaDB 11.4.13/faeyza_service, skema v3 tersedia, method changeStatus tersedia, storage writable dan aset privat terbaca. Izin runtime SELECT/INSERT/UPDATE hanya pada DB Faeyza. Data terbaca 4 akun/3 konsumen/3 servis/1 sparepart; tidak ada file error log aplikasi. Tidak membuat data uji atau menekan Simpan status di produksi. Transaksi penyimpanan/pengembalian stok telah diuji pada faeyza_test dalam fase sebelumnya. Bukti doctor/health/deployment tersimpan sebagai quick-status-production-doctor.txt, quick-status-production-health.txt dan quick-status-deployment-state.txt.

Hasil fase redeploy: **72 PASS, 0 FAIL, 0 BLOCKED**. Total fase implementasi dan rilis fitur: **522 PASS**. Lint PHP tests/production-smoke.php, node --check tests/quick-status-production.mjs dan git diff --check juga lulus. Tambahan commit sesudah rilis hanya dokumentasi/script QA; kode runtime tidak berubah. Status redeploy COMPLETE.

## Keluhan filter status dan cetak invoice

Reproduksi produksi melalui profil Imam sebelum perubahan: memilih Selesai tanpa Cari tidak mengubah URL dan masih menampilkan Menunggu Sparepart, Diterima, Selesai, Sudah Diambil. Setelah Cari, daftar hanya memuat Selesai. Bukti: invoice-filter-production-before.json. Penyebab filter adalah pilihan belum dikirim, bukan query SQL mengabaikan status.

Perbaikan: select filter memakai requestSubmit pada change (fallback submit); server memvalidasi enum dan kepemilikan. Pencarian/filter/halaman disimpan pada tautan dialog status dan dipulihkan pada Batal, validasi gagal, dan redirect sukses. Invoice memuat invoice-print.js?v=1 secara mandiri, invoice.css?v=4, halaman aplikasi memakai app.js?v=9. Modul cetak memanggil window.print secara sinkron dari klik, mengenali event cetak, dan membuka bantuan jika tidak tersedia/ditolak/diabaikan. Petunjuk HTML tetap dapat dibuka tanpa skrip cetak dan disembunyikan ketika print.

Hasil lokal aktual:

| Suite | Tujuan | Hasil | Bukti/perintah |
| --- | --- | --- | --- |
| Native/domain/HTTP | Regresi sebelumnya; tujuh filter untuk admin/pelanggan, ownership, input invalid, konteks redirect, invoice memakai modul terpisah | 322 PASS, 0 FAIL | tests/test.ps1; invoice-filter-integration-output.txt |
| Unit JavaScript | Klik benar-benar memanggil window.print, before/afterprint, media print, API hilang/throw/silent, klik ulang; filter otomatis pada form yang tepat | 12 PASS, 0 FAIL | node tests/invoice-filter-js.mjs; invoice-filter-js-results.json |
| Browser filter/cetak | Pilih status tanpa Cari, Semua status, empty state, filter tetap setelah update, filter Invoice/pelanggan, bantuan cetak dan viewport | 20 PASS, 0 FAIL | runInvoiceFilter; invoice-filter-browser-results.json |
| Browser bisnis | Registrasi/login, servis, stok 10→8, Rp150.000, selesai/garansi, tanda tangan, IDOR dan formulir | 124 PASS, 0 FAIL | runBrowserE2E; invoice-filter-business-results.json |
| Browser dialog | Status/tanggal, konfirmasi pembatalan, stok 8→10, konteks URL baru, pelanggan, responsive | 29 PASS, 0 FAIL | runQuickStatus; invoice-filter-status-results.json |
| PDF aktual | Empat dokumen A4: awal, final, teks panjang, legacy nonfinal; navigasi dan bantuan tidak tercetak | 42 PASS, 0 FAIL | tests/print.ps1; invoice-filter-print-output.txt, print-results.json |

Total assertion lokal **549 PASS, 0 FAIL**. Pengujian UI lokal awal memakai in-app browser, sedangkan regresi bisnis/dialog memakai Chrome profil Imam setelah tersambung. PDF terbaru diperiksa visual sebagai PNG. Metadata PDF diambil dari pesanan yang benar-benar dirender, sehingga fixture browser lama tidak membuat pemeriksaan garansi keliru.

Batas verifikasi cetak: invoice produksi benar-benar terbuka tanpa error JavaScript sebelum perbaikan. Klik Cetak aktual di in-app browser/Chrome membuat perintah Input/Runtime menunggu saat native print berjalan; dialog native tidak dapat diinspeksi tuntas melalui target halaman. Tab uji milik agen ditutup untuk pulih. Ini **bukan** bukti PASS printer fisik atau reproduksi HP Android klien. Unit membuktikan pemanggilan handler; PDF membuktikan keluaran engine cetak. Perangkat/browser HP klien belum diuji langsung, sehingga penyebab spesifik keluhan cetak HP belum dapat dipastikan.

Masalah lingkungan yang diatasi: MariaDB portable lokal belum berjalan setelah pergantian sesi; runtime proyek pada loopback 3307 dinyalakan tanpa menyentuh produksi, lalu suite 322 PASS. Chrome profil Imam awalnya terputus; pemilik menyambungkannya kembali. Semua perubahan transaksi dilakukan hanya pada faeyza_test. Tidak ada migrasi database baru atau dependensi PDF produksi baru.
