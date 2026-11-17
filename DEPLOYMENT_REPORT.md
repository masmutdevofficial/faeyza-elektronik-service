# Laporan deployment — 17 November 2026

**PROJECT STATUS: COMPLETE — aplikasi aktif dan siap diperiksa.**

- Aplikasi: https://faeyza.dirgasatya.id
- Repository privat: https://github.com/masmutdevofficial/faeyza-elektronik-service
- Coolify: https://server1.polontos.com
- Project: Faeyza Elektronik Service, production.
- Project UUID: `a0yuccdnmbmxcqiogeuoq2v7`.
- Environment UUID: `tpelhtw4jiayh6clerrnseli`.
- Application UUID: `usuqy7yd16bjf9olu6nn7cys`.
- Runtime source commit: `f6d9ea1bbec02ad76e156ad594dc7538b66fca7d` (UI ringkas).
- Build strategy: GitHub App + Docker Compose. Deployment berikutnya **manual**; push dokumentasi tidak merestart container.
- Pengoperasian Coolify dilakukan melalui Chrome profil **Imam**, sesuai permintaan pemilik.

## Database dan isolasi

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
