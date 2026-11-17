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

## Penyederhanaan tampilan

- [x] Hapus slogan, paragraf promosi, dekorasi banner, dan teks implementasi dari halaman aplikasi.
- [x] Beranda admin memakai antrean, angka ringkasan, dan tabel servis.
- [x] Pelanggan memakai navigasi Beranda / Servis baru / Invoice, dengan kartu perangkat.
- [x] Form perangkat didahulukan; kontak terisi otomatis, field opsional dilipat, tombol kirim tetap terjangkau.
- [x] Validasi membuka bagian kontak yang invalid; menu admin dapat ditutup dengan Escape.
- [x] Regresi 111 native/HTTP dan 56 pemeriksaan browser lulus, termasuk simulasi bisnis dan viewport 375/768/1440.
- [x] Rilis UI ke resource Coolify Faeyza dan verifikasi produksi: f6d9ea1, HTTP 30/30 PASS, antrean baru dan empty state diperiksa di domain aktif.

## Tanda tangan bengkel per pesanan

- [x] Aset asli PNG privat, konfigurasi terpusat, dan gambar di invoice/cetak.
- [x] Toggle admin khusus edit servis selesai/diambil; default aktif; preferensi tersimpan per pesanan.
- [x] Validasi backend, CSRF, otorisasi, dan penolakan manipulasi konsumen.
- [x] Migrasi v2 lokal/development/testing, uji pengulangan dan guard target.
- [x] Regresi 125 native/HTTP, 63 browser, 26 cetak/PDF, serta interaksi toggle mobile lulus.
- [x] Backup privat dan penambahan kolom hanya pada database produksi Faeyza; jumlah pesanan tetap.
- [x] Rilis cdb42b5 dan verifikasi produksi tanda tangan: HTTP 31/31, doctor/aset/kolom OK, preview toggle default aktif dan status pesanan asli tetap Diterima.

## Kontak konsumen dan identitas invoice

- [x] Pilihan konsumen pada servis baru admin otomatis mengisi nama, HP, dan alamat.
- [x] Ringkasan kontak menggantikan pengisian ulang; koreksi opsional melalui Ubah data kontak.
- [x] Backend mengambil kontak akun jika field tidak dikirim/kosong; kepemilikan tetap berdasarkan ID konsumen.
- [x] Perubahan pilihan membersihkan kontak sebelumnya; koreksi manual dan input saat validasi gagal tetap dipertahankan.
- [x] Website www.faeyzateknik.com dan nama penanda tangan Asmat pada invoice/cetak.
- [x] Regresi 134 native/HTTP, 70 browser, 32 PDF PASS; layout A4 diperiksa visual.
- [x] Rilis 9836381 ke resource Faeyza; 31 HTTP dan 8 browser produksi PASS tanpa submit/perubahan data pesanan.

## Servis baru dari detail konsumen

- [x] Tautan servis baru membawa ID konsumen, termasuk riwayat kosong.
- [x] Form langsung memilih konsumen dan menampilkan kontak; pilihan masih dapat diganti.
- [x] Tombol servis baru juga tersedia saat konsumen sudah memiliki riwayat.
- [x] Validasi konteks GET, pemisahan dari POST, serta batas role diuji: 144 native/HTTP PASS.
- [x] Regresi 73 browser PASS; runtime 64dc7c9 dirilis; 31 HTTP dan 8 pemeriksaan browser produksi PASS tanpa submit pesanan.

## Pengaturan akun

- [x] Admin mengubah nomor sendiri dengan normalisasi/constraint unik dan verifikasi password saat ini.
- [x] Admin/pelanggan mengubah password sendiri; admin dapat mereset password pelanggan.
- [x] Minimum 6 karakter pada registrasi, tambah akun, pengaturan dan CLI.
- [x] CSRF, role, hash password, rate limit verifikasi, konfirmasi password, dan pencabutan sesi lama.
- [x] Regresi 186 native/HTTP PASS pada database testing terpisah, termasuk rehash login dan pembaruan sesi atomik.
- [ ] Verifikasi browser/responsif dan rilis ke resource Faeyza.
