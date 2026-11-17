# Rencana dan kemajuan

- [x] A: periksa workspace, spesifikasi dan runtime; aplikasi baru pada C:\project\bengkel.
- [x] B: skema, autentikasi, otorisasi, servis, invoice atomik, inventaris teraudit.
- [x] C: UI Indonesia responsif, dashboard, formulir, invoice/cetak.
- [x] D: jalankan PHP + MariaDB, migrasi dan integrasi.
- [x] E/F: 111 native/HTTP + 53 browser + 24 print assertion lulus; keamanan, concurrency, UI, simulasi bisnis dan regresi setelah penerapan referensi.
- [x] G: README, environment examples, XAMPP/shared hosting, bukti JSON/screenshot/PDF dan TEST_REPORT.md.

Milestone lokal: COMPLETE. Logo asli dan desain nota mengikuti referensi yang diberikan pemilik pada tindak lanjut. Dialog cetak native tidak dapat diinspeksi oleh monitor helper; hasil print diverifikasi lewat PDF A4 aktual, ekstraksi, dan PNG. Deployment publik dilakukan pada fase berikut di bawah ini. Tidak ada kegagalan fitur wajib yang diketahui.

Keputusan: relasi pemilik melalui customer_id/user_id, snapshot identitas di invoice; nomor invoice immutable. Pembatalan servis mengembalikan seluruh alokasi aktif, menyimpan baris penggunaan dengan kuantitas nol, dan menambah stock_movements. Membuka kembali servis tidak mengalokasikan stok otomatis. Tidak ada penghapusan invoice atau histori.

- [x] Tindak lanjut warna: dominan biru logo (#0756b5), aksen biru muda, navigasi/kartu/form/login selaras. Sembilan pemeriksaan browser lulus pada desktop, tablet, dan mobile; bukti di tests/artifacts/blue-theme-results.json.
- [x] Tindak lanjut ikon/banner: SVG resmi MDI lokal, lisensi disertakan, seluruh simbol ikon antarmuka diganti, dan banner admin dirancang ulang dengan gradasi biru serta data antrean aktual. Regresi 111 native/HTTP, 53 browser, 24 cetak, serta 7 pemeriksaan visual/interaksi banner lulus.

## Deployment yang diminta 17 November 2026

- [x] Repository privat masmutdevofficial/faeyza-elektronik-service dibuat.
- [x] Konfigurasi container PHP/Apache dan MariaDB khusus, tanpa host port/database bersama.
- [x] Regresi sebelum push: 32 PHP lint dan 111 native/HTTP PASS, 0 FAIL.
- [x] Commit/push source yang bebas rahasia.
- [x] Project/resource Coolify khusus melalui profil Imam.
- [x] Build, deploy HTTPS faeyza.dirgasatya.id, verifikasi aplikasi, DB, dan isolasi.
- [x] Laporan deployment aktual dan akun admin produksi baru 08170002466 setelah persetujuan pemilik; password privat di luar repo.
- [x] Perbaikan respons CSRF Apache, regresi 111 PASS, HTTP produksi 30 PASS, browser produksi 20 PASS, status 24 resource lama sama.

Status deployment: COMPLETE. Kedua container healthy, admin login/logout/login ulang berhasil, dan tidak ada data demo dipindahkan ke produksi. Lihat DEPLOYMENT_REPORT.md.
