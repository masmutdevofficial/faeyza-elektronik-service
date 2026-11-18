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
- [x] Regresi 90 browser PASS, termasuk seluruh halaman baru pada 375/768/1440px.
- [x] Rilis runtime ad00792 ke resource Faeyza; produksi 34 HTTP + 14 browser PASS, tanpa perubahan kredensial produksi.

## Login admin terpisah dan toggle password

- [x] Entry point /admin, login role-specific, logout/redirect sesuai role, tanpa registrasi admin publik.
- [x] Tombol mata MDI lokal untuk login admin/pelanggan dan dua kolom registrasi; default hide, tidak submit, reset saat pageshow/pagehide.
- [x] Basis URL/aset kompatibel dengan direktori aplikasi dan pemisah path Windows/Linux.
- [x] Regresi 203 native/HTTP PASS setelah perbaikan path Windows.
- [x] 120 browser + 9 tata letak PASS; rilis runtime 7870e1c ke resource Faeyza.
- [x] Produksi 40 HTTP + 22 browser PASS, termasuk login/logout admin aktual dan ketiga halaman pada 375/768/1440px.

## Form khusus konsumen tanpa dropdown

- [x] Form dari detail konsumen hanya menampilkan kontak; hapus dropdown dan Tambah konsumen.
- [x] Pemilik mengikuti konteks server pada GET/POST; perbedaan hidden ID ditolak tanpa penyimpanan.
- [x] Koreksi kontak tetap bekerja; Kembali menuju detail konsumen; form umum tetap memiliki dropdown.
- [x] Regresi 207 native/HTTP PASS, termasuk validasi gagal dan POST tanpa hidden ID.
- [x] 124 browser PASS; rilis runtime 46e285a ke resource Faeyza.
- [x] Produksi 40 HTTP + 9 browser PASS; alur Simon diverifikasi tanpa submit pesanan.

## Rincian perangkat pada halaman pelanggan

- [x] Tambahkan baris Nama barang, Merek, dan Model pada detail servis/halaman sukses.
- [x] Tetap memakai nilai pesanan dan output escaping; tampilan admin memakai rincian yang sama.
- [x] Regresi 214 native/HTTP PASS termasuk data custom, XSS, dan kepemilikan.
- [x] 14 browser PASS pada akun pelanggan, termasuk data normal/custom di 375/768/1440px.
- [x] Runtime 65ee1c7 dirilis; produksi 40 HTTP + 3 rincian data PASS tanpa perubahan data servis.

## Tanggal selesai dan penghapusan yang dapat dipulihkan

- [x] Server/UI membatasi tanggal selesai/garansi pada Selesai/Sudah Diambil; status lain mengosongkan tanggal saat disimpan.
- [x] Invoice/detail tidak menampilkan tanggal lama yang tidak sesuai status.
- [x] Hapus/pulihkan konsumen dan sparepart, konfirmasi, filter Dihapus, audit operator/waktu.
- [x] Akun konsumen dihapus tidak bisa login; versi sesi mencegah sesi lama hidup kembali sesudah restore.
- [x] Sparepart dihapus tetap menyimpan stok/histori, menolak stok masuk/penggunaan baru, dan menerima pengembalian/pembatalan.
- [x] Migration v3 additive/idempotent di development/testing; SQL pre-deploy diverifikasi lokal.
- [x] 264 native/HTTP PASS, termasuk concurrency hapus/stok masuk dan label konfirmasi.
- [x] 124 browser bisnis + 33 interaksi/tata letak hapus/pulihkan + 42 cetak PDF PASS.
- [x] Backup privat produksi, migrasi khusus DB Faeyza, runtime 5cd0e22; doctor dan healthcheck OK.
- [x] Produksi 45 HTTP + 14 browser PASS; akun/konsumen/servis/sparepart tetap 4/3/3/1 dan audit penghapusan 0.
- [x] Dokumentasi/laporan akhir; total 522 PASS, 0 FAIL, 0 BLOCKED. Server QA 8081 dihentikan, 8080 tetap berjalan.

## Ubah status cepat

- [x] Dialog Ubah status pada daftar/detail servis dan halaman ringkas cadangan tanpa JavaScript.
- [x] Endpoint khusus admin, CSRF, validasi tanggal, revision locking, pembatalan/pengembalian stok atomik.
- [x] 297 backend/native/HTTP + 124 browser bisnis + 29 browser dialog PASS; 375/768/1440px, validasi, race, stok dan role.
- [x] Dokumentasi dan source 4238066 dipush ke main; tidak menjalankan deployment manual Coolify. QA 8081 dihentikan, development 8080 tetap berjalan.
- [x] Sesuai permintaan berikutnya, redeploy runtime 62cc1d2 ke Coolify; deployment ugya9ldmwfbpta4nmp0blrp4 Success, healthcheck Healthy, doctor OK, 47 HTTP + 25 browser produksi PASS.

## Keluhan filter status dan cetak invoice

- [x] Reproduksi produksi: tanpa Cari status campuran, setelah Cari hanya Selesai; terapkan auto-submit dan pertahankan konteks.
- [x] Invoice produksi terbuka tanpa error JS; modul cetak terpisah/berversi, bantuan menu browser, tanpa mengklaim printer/HP fisik telah diuji.
- [x] Lokal 322 native/HTTP + 14 unit JavaScript + 21 browser filter/cetak + 124 bisnis + 29 dialog + 42 PDF PASS (552). Tidak mengubah pesanan produksi.
- [x] Push runtime 63c99fb, deployment yedzbqd9xh6sphhl5shtwqnc Success, 65 pemeriksaan produksi PASS; health dan doctor OK.
- [ ] Verifikasi manual dialog cetak pada perangkat Android klien (BLOCKED: belum tersedia).

## Kelompok status ringkasan

- [x] Dalam proses mencakup Menunggu Konfirmasi/Diterima/Dalam Pengerjaan/Menunggu Sparepart; Selesai mencakup Selesai/Sudah Diambil. Dibatalkan di luar keduanya.
- [x] 349 native + 17 browser kelompok + 124 bisnis + 29 dialog + 21 filter/cetak + 14 unit JS PASS (554).
- [x] Runtime 998a1f0 dipush main, deployment oaifw50yvtjbrsejurrnuwqc Success/29 detik; 79 produksi PASS, health dan doctor OK.
- [x] Dokumentasi hasil dan cleanup server QA 8081 selesai. Pemeriksaan Android manual tetap dicatat pada fase keluhan cetak.
