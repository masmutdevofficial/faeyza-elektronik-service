# Laporan deployment — 17 November 2026

**PROJECT STATUS: COMPLETE — aplikasi aktif dan siap diperiksa.**

- Aplikasi: https://faeyza.dirgasatya.id
- Repository privat: https://github.com/masmutdevofficial/faeyza-elektronik-service
- Coolify: https://server1.polontos.com
- Project: Faeyza Elektronik Service, production.
- Project UUID: `a0yuccdnmbmxcqiogeuoq2v7`.
- Environment UUID: `tpelhtw4jiayh6clerrnseli`.
- Application UUID: `usuqy7yd16bjf9olu6nn7cys`.
- Runtime source commit: `cdb42b563e292e052d981564a61b95db1f5065aa` (toggle tanda tangan bengkel).
- Build strategy: GitHub App + Docker Compose. Deployment berikutnya **manual**; push dokumentasi tidak merestart container.
- Pengoperasian Coolify dilakukan melalui Chrome profil **Imam**, sesuai permintaan pemilik.

## Database dan isolasi

Pembaruan tanda tangan: kolom v2 `show_workshop_signature` sudah ditambahkan hanya pada `faeyza_service`, default 1. Backup sebelum migrasi berada di `/var/lib/mysql/faeyza-pre-signature-v2.sql` pada volume database Faeyza, izin root-only 0600. Jumlah pesanan/user sebelum dan sesudah tetap 1/3. Tidak ada data bisnis yang diubah untuk pengujian produksi. Status pesanan yang diperiksa tetap Diterima setelah pratinjau UI tanpa submit.

Toggle tersedia di Edit servis untuk Selesai/Sudah Diambil, default aktif. Preferensi tersimpan per pesanan, hanya dapat diubah admin, dan berlaku pada invoice pemilik serta cetak A4. Status lain selalu tanpa tanda tangan. PNG asli tersimpan privat dalam image web pada config/assets, lalu disisipkan ke invoice terotorisasi sebagai data URI; URL langsung diblokir.

Hasil revisi: **125 native/HTTP, 63 browser, 26 PDF, 31 HTTP produksi PASS; 0 FAIL**. Runtime doctor memastikan aset terbaca, kolom tersedia, schema/DB/penyimpanan OK, dan log PHP tanpa error. Pratinjau produksi membuktikan toggle tersembunyi pada status Diterima, muncul serta checked saat pilihan UI menjadi Selesai, kemudian dikembalikan tanpa menyimpan. Gambar/toggle aktif-nonaktif dan cetak diuji penuh menggunakan DB testing. Screenshot crop produksi tidak merespons; bukti UI diambil sebagai screenshot viewport dengan data uji, dan verifikasi produksi dicatat melalui DOM serta doctor.

Bukti: `signature-browser-results.json`, `signature-toggle-mobile.png`, `invoice-final-print-1.png`, `signature-production-migration.png`, `signature-production-doctor.png`, dan `production-smoke-results.json` dalam tests/artifacts. CLI migrasi memiliki guard nama DB dan dapat diulang; hak runtime tetap SELECT/INSERT/UPDATE tanpa ALTER.

MariaDB **11.4.13** pada container khusus Faeyza, database `faeyza_service`, user `faeyza_runtime`. Hak runtime yang diperiksa: **SELECT, INSERT, UPDATE** hanya pada database Faeyza. Root tidak diberikan kepada container web.

Volume database `usuqy7yd16bjf9olu6nn7cys_faeyza-db` dan volume session/log `usuqy7yd16bjf9olu6nn7cys_faeyza-storage` dibuat baru. Tidak ada mount volume database aplikasi lain. Schema v1 diinisialisasi hanya pada volume kosong. Restart/redeploy mempertahankan volume.

Jaringan resource `usuqy7yd16bjf9olu6nn7cys` beranggotakan web, database Faeyza, dan coolify-proxy. **Isolated network only** dipertahankan. Hasil inspect: `HostConfig.PortBindings={}` pada kedua container; 3306 tidak dipublikasikan. Web menerima trafik melalui proxy ke port internal 80. Batas memory web/database 256/384 MiB dan masing-masing 0,5 CPU terverifikasi.

24 resource lama dibandingkan dengan pengamatan awal: **24 status sama, 0 perubahan**. Seluruh database lama tetap healthy. Lima aplikasi lama yang sudah exited/unhealthy dan dua aplikasi running/unknown tetap pada status awalnya; bukan kegagalan baru deployment ini. Tidak dilakukan operasi SQL, reset, migrasi, restart, prune, atau perubahan konfigurasi pada resource lama.

## Akun dan data awal

Admin produksi **08170002466** dibuat setelah persetujuan pemilik, memakai password acak baru. Password dimasukkan melalui stdin terminal dengan echo dimatikan, bukan argv atau Git. Kredensial disimpan dalam file privat komputer pemilik di luar repository, dengan ACL hanya akun Windows pemilik.

Login, logout, pengakhiran sesi, penolakan akses sesudah logout, dan login ulang admin berhasil. Database produksi berisi **1 user admin, 0 konsumen, 0 servis** saat pemeriksaan akhir. Akun demo dan data simulasi development tidak disalin. Secret database dibuat/disimpan Coolify dan tidak dimasukkan ke repository/laporan.

## Pembaruan UI ringkas

Slogan, paragraf promosi, ilustrasi banner, dan keterangan teknis yang tidak diperlukan sudah dihapus dari halaman aplikasi. Admin memakai antrean konfirmasi, angka ringkasan, tabel servis, dan akses stok/invoice. Pelanggan memakai navigasi Beranda / Servis baru / Invoice serta kartu perangkat dan status.

Form pelanggan langsung meminta perangkat dan keluhan. Nama, nomor HP, alamat, dan tanggal masuk terisi dari akun/server, dengan bagian kontak yang dapat dibuka. Serial, kelengkapan, dan catatan bersifat opsional dan dilipat. Pada mobile, empat isian pendek memakai dua kolom; tombol kirim tetap terjangkau. Kontak yang invalid otomatis terbuka saat validasi. Seluruh field dan otorisasi server tetap berlaku.

Verifikasi revisi ini: **111 native/HTTP PASS, 56 browser PASS, 30 HTTP produksi PASS, 0 FAIL**. Simulasi bisnis lengkap dijalankan ulang pada DB testing. Tiga pemeriksaan baru menguji kontak terisi/lipat, field opsional, serta pembukaan otomatis kontak invalid. Ukuran 375/768/1440 px diperiksa. Perbaikan lanjutan mencegah filter kartu statistik mengubah pesan empty state; render kosong dan halaman produksi sama-sama menampilkan “Belum ada servis”.

Rilis UI melalui Coolify selesai dan sukses. Session admin bertahan sesudah redeploy. Verifikasi halaman produksi: antrean baru terlihat, banner promosi tidak ada, MDI masih digunakan, dan lebar dokumen 1440 px sesuai viewport 1440 px. Tidak ada migrasi atau perubahan skema/volume database pada revisi ini.

Bukti lokal tambahan: `tests/artifacts/minimal-browser-results.json`, `production-smoke-results.json`, `minimal-live-checks.json`, `minimal-production-admin-1440.png`, `minimal-customer-dashboard-375.png`, dan `minimal-service-form-mobile.png`.

## Hasil deployment awal

| Pemeriksaan | PASS | FAIL | BLOCKED | Bukti |
| --- | ---: | ---: | ---: | --- |
| Regresi native PHP/MySQL + HTTP pada DB testing terpisah | 111 | 0 | 0 | `tests/artifacts/integration-results.json` |
| HTTP produksi tanpa membuat data bisnis | 30 | 0 | 0 | `tests/artifacts/production-smoke-results.json` |
| Browser produksi: admin, navigasi, pencarian, form, viewport, sesi | 20 | 0 | 0 | `tests/artifacts/production-browser-results.json` |
| Perbandingan status resource yang sudah ada | 24 | 0 | 0 | `tests/artifacts/coolify-resource-comparison.json` |

33 file PHP lulus syntax check. JavaScript aplikasi lulus `node --check public/assets/app.js`; Node tidak diperlukan oleh container produksi. Docker build dan Apache `apache2ctl -t` benar-benar dijalankan. Runtime PHP **8.4.27**, mysqli/mbstring/session, sembilan tabel, zona Asia/Jakarta, dan direktori log/session writable semuanya lolos `php bin/doctor.php`. Kedua container healthy. Pemeriksaan log PHP akhir: **no errors recorded**.

Perintah yang dijalankan:

```powershell
.\tests\test.ps1 -Php C:\project\bengkel-runtime\php\php.exe
node --check public/assets/app.js
& C:\project\bengkel-runtime\php\php.exe tests/production-smoke.php https://faeyza.dirgasatya.id
```

Di Terminal container web Faeyza:

```sh
php bin/doctor.php
apache2ctl -t
```

Smoke test memeriksa HTTPS dengan verifikasi sertifikat aktif, cookie Secure/HttpOnly/SameSite, CSP, no-store, penolakan CSRF, akses tamu ke endpoint admin/invoice, input route invalid, login invalid, aset, serta penutupan akses `.env`, source, schema, log, tests dan `.git`. Tidak memakai opsi bypass TLS.

## Perbaikan berdasarkan pengujian

1. Domain awal hasil generator Coolify diganti menjadi **faeyza.dirgasatya.id** sesuai arahan pemilik; HTTP diarahkan ke HTTPS dan port tujuan web 80. Respons 503 sebelum perubahan route diterapkan sudah teratasi.
2. Rate limit di belakang proxy: Apache sekarang hanya mempercayai alamat aktual coolify-proxy yang ditemukan lewat Docker DNS resource. Alamat ini terverifikasi, bukan menerima header IP mentah dari client.
3. Pengujian produksi awal menemukan body penolakan CSRF 419 tetapi Apache mengirim HTTP 500. `abort_page()` diperbaiki dengan status line dan reason phrase eksplisit untuk 419. Regresi 111/111 dan HTTP produksi 30/30 kemudian lulus.
4. Assertion cookie smoke awal hanya membaca respons login, padahal cookie sudah dibuat pada redirect pertama. Pemeriksa diperbaiki agar memeriksa kedua respons. Ini perbaikan test harness; cookie aplikasi sejak awal Secure/HttpOnly/SameSite.
5. Otomasi browser menunggu URL dashboard sebelum memastikan login ulang. Pengambilan screenshot mobile menggunakan ukuran viewport saat capture full-page tidak merespons. Semua pemeriksaan responsive aktual lulus.

## UI dan simulasi bisnis

Pada domain produksi diperiksa dashboard admin, tombol antrean, pencarian invoice berdasarkan nomor HP, data konsumen, persediaan, form konsumen baru, form sparepart baru, dan form servis. Dashboard dan form servis diperiksa pada **375, 768, dan 1440 px**, tanpa overflow horizontal. Menu mobile berhasil membuka halaman persediaan.

Bukti visual lokal (sengaja tidak dikirim ke Git):

- `tests/artifacts/production-dashboard-1440.png`
- `tests/artifacts/production-dashboard-375.png`
- `tests/artifacts/production-dashboard-768.png`
- `tests/artifacts/production-service-new-375.png`
- `tests/artifacts/coolify-isolation.png`
- `tests/artifacts/coolify-final-doctor.png`
- `tests/artifacts/coolify-database-privileges.png`

Simulasi bisnis lengkap sebelumnya sudah lulus pada database testing: registrasi → penerimaan TV → invoice biaya kosong → penggunaan 2 dari stok 10 menjadi 8 → biaya Rp150.000 → selesai/garansi → invoice final → penolakan IDOR. Suite 53 browser, 24 cetak/PDF, dan 7 visual MDI adalah hasil lokal yang terdokumentasi di TEST_REPORT.md. Suite tersebut **tidak diklaim dijalankan ulang di produksi**. Tidak dibuat servis/pendapatan sintetis di database produksi.

## Operasional dan batas verifikasi

Tidak ada kegagalan aplikasi yang diketahui setelah perbaikan dan pengujian akhir. Printer fisik tidak diuji di server; pencetakan browser/PDF sudah diverifikasi lokal. Backup terjadwal/offsite belum dikonfigurasi karena tujuan backup belum diberikan. Prosedur backup, migrasi terkontrol, dan rollback tersedia di DEPLOYMENT.md. Jangan menghapus volume atau memakai reset-test pada produksi.

**FINAL VERDICT:** deployment GitHub → Coolify selesai, domain HTTPS aktif, database khusus healthy, admin dapat login, dan resource lama tetap pada status semula.

## Pembaruan kontak dan invoice

Runtime 983638100e309e6f070715d5ebedfe67a5bebf1c dirilis manual ke resource Faeyza yang sama melalui Chrome profil Imam. Deployment enpjhqf2vcfolqvaqmcruope berstatus Success, durasi 29 detik. Log menunjukkan database Faeyza Healthy dan web Faeyza Started. Tidak ada perubahan compose, migrasi, volume, kredensial, atau resource aplikasi lain.

Servis baru admin mengambil nama/HP/alamat dari konsumen terpilih; koreksi kontak opsional. Invoice mencetak www.faeyzateknik.com dan penanda tangan Asmat. Pemeriksaan produksi read-only: 31 HTTP dan 8 browser PASS. Pemilihan Indra diperiksa tanpa submit; invoice yang sudah ada dibuka untuk memastikan website/nama benar. Semua pengajuan simulasi hanya dibuat pada database testing. Detail regresi lokal 134 native/HTTP, 70 browser, 32 PDF berada di TEST_REPORT.md.

Pembaruan berikutnya: runtime 64dc7c9723d9386709899234dcab9f09c78381cc membawa konteks konsumen dari halaman detail ke servis baru. Deployment 09cpbx2mfvswbufowmy42e1u Success, 34 detik, hanya resource Faeyza yang sama. Tidak ada perubahan skema/compose/volume/kredensial. Regresi lokal 144 native/HTTP + 73 browser PASS, produksi 31 HTTP + 8 browser PASS. Alur Indra benar-benar diklik tanpa submit; pilihan dan kontak tetap benar setelah reload. Tombol servis baru pada riwayat berisi juga diverifikasi.

## Pengaturan nomor dan password

Runtime ad00792136c6e20452d56eac45cd5e2dd9696f84 dirilis melalui Chrome profil Imam ke resource Faeyza yang sama. Deployment ivakvdcmcd0wlrpuchclzw6q Success dalam 37 detik. Admin dapat mengganti nomor unik/password sendiri dan mereset password pelanggan; pelanggan dapat mengganti password sendiri. Minimum enam karakter konsisten pada UI/backend/CLI.

Tidak ada migrasi atau perubahan compose/database/volume/kredensial produksi. Stamp sesi yang terikat pada kredensial mencabut sesi lama; pengguna versi sebelumnya perlu login kembali sekali, tanpa perubahan password. Admin produksi berhasil login ulang dengan kredensial yang sama, membuka Akun serta form reset Indra. Tidak ada perubahan kredensial produksi yang dikirim untuk pengujian. Local 186 native/HTTP + 90 browser PASS; produksi 34 HTTP + 14 browser PASS, 0 FAIL. Bukti dan batas verifikasi pada TEST_REPORT.md.

## Login admin /admin dan tombol password

Runtime 7870e1cf12efd551410562bda61fe20e7114f30c dirilis manual melalui profil Imam ke resource Faeyza yang sama. Deployment slm1k1prlfr1g6qj5egzvcot Success dalam 29 detik. Entry public/admin/index.php bekerja dengan DirectoryIndex Apache tanpa perubahan konfigurasi/mod_rewrite; /admin diteruskan ke /admin/. Pelanggan tetap memakai index.php?page=login. Login memverifikasi role dan logout admin kembali ke /admin/.

Toggle mata MDI lokal pada login admin, login pelanggan, password dan konfirmasi registrasi telah dioperasikan di domain HTTPS. Default/reload tersembunyi, tampil/sembunyi mandiri dan tidak submit. Login/logout admin aktual lulus. Produksi 40 HTTP + 22 browser PASS, termasuk viewport 375/768/1440, tanpa console error. Lokal 203 native/HTTP + 120 browser + 9 tata letak PASS. Tidak ada migrasi, perubahan data pesanan/kredensial, compose, volume atau resource aplikasi lain. Bukti pada TEST_REPORT.md.

## Pesanan dari detail konsumen tanpa dropdown

Runtime 46e285a86b20bbfa23d067eae28805e7cdeed6e0 dirilis ke resource Faeyza yang sama melalui Chrome profil Imam. Deployment yf0uerxbz8a5nc5qml7jifxl Success dalam 2 menit 4 detik; database khusus Healthy dan web Started. Tidak ada migrasi atau perubahan compose/volume/resource lain.

Konsumen dari halaman detail sekarang tetap pada form pesanan, tanpa dropdown/Tambah konsumen. Backend memvalidasi konteks pada GET/POST dan menolak ID pemilik yang berbeda. Form umum tetap menyediakan pemilihan konsumen. Regresi lokal 207 native/HTTP + 124 browser PASS; produksi 40 HTTP + 9 browser PASS. Simon → Servis baru diperiksa tanpa submit pesanan/koreksi data nyata. Screenshot locked-production-simon.png dan rincian hasil ada pada TEST_REPORT.md.
