# Hasil verifikasi paket klien

Tanggal pembaruan: 20 November 2026. Paket berisi source aplikasi, skema kosong, aset logo/tanda tangan, lisensi MDI, panduan PDF dan panduan teks. Tidak ada akun/password bawaan, database pelanggan, `.env`, sesi, log atau riwayat Git. Versi dasar source dan checksum tiap file tercatat di MANIFEST.json.

Pembaruan ringkasan konsumen dihapus dan koreksi stok: **618 pemeriksaan lokal PASS** (402 native/domain/HTTP, 28 UI fitur, 124 UI bisnis, 29 dialog status, 21 filter/invoice, 14 unit JavaScript). Tampilan diuji 375/768/1440px. Skenario mencakup semua konsumen dihapus menghasilkan ringkasan nol, pemulihan, invoice historis, stok nol, rollback audit, formulir stale, retry dan race stok. Panduan pemasangan pembaruan tersedia pada PERBAIKAN-RINGKASAN-STOK.md. Tidak ada migrasi skema baru.

## Pemeriksaan yang dijalankan

**37 PASS, 0 FAIL pada pemeriksaan akhir instalasi paket.** Pengujian dilakukan dari hasil ekstraksi ZIP, menggunakan PHP 8.5.12 dan MariaDB 11.4.5 pada komputer pengembangan. Database sementara dibuat khusus dengan nama unik berakhiran `_test`, lalu dihapus setelah pengujian. Database aplikasi aktif dan produksi tidak dipakai untuk pengujian paket.

- Integritas ZIP, path arsip, manifest SHA256, dan keberadaan file wajib.
- Tidak ada konfigurasi/kredensial privat, fixture, history Git, log atau sesi pengguna di arsip.
- Seluruh PHP dalam paket lulus syntax lint.
- Setup database baru, setup berulang dan doctor.
- Pembuatan admin dengan password enam karakter.
- Helper SQL admin offline: hash password, impor, penolakan nomor duplikat, password terlalu pendek dan nomor tidak valid.
- Login admin, registrasi pelanggan, pengajuan servis, invoice pertama dan biaya awal kosong.
- Pembatasan akses konsumen dan kedua filter kelompok ringkasan.
- Document root public serta susunan aplikasi privat dengan public_html dan wrapper.
- Susunan subfolder seperti XAMPP: base URL aset, login admin, redirect dan invoice.

HTTP pengujian memakai server development PHP pada loopback; susunan public/public_html menggunakan router development yang disertakan. Pemeriksaan ini tidak menggantikan pengujian aturan `.htaccess` pada Apache sebenarnya.

PDF panduan berukuran A4, enam halaman. Seluruh halaman dirender ke gambar dan diperiksa keterbacaannya. PDF invoice serta regresi aplikasi telah diuji pada fase pengembangan sebelumnya; pemeriksaan paket ini fokus pada kelengkapan dan instalasi baru.

## Pemeriksaan yang perlu dilakukan di tujuan

**BLOCKED di lingkungan pengemasan:** XAMPP/Apache dan panel shared hosting klien tidak tersedia untuk pengujian langsung. Jalankan langkah pada panduan, termasuk pengecekan URL privat, izin folder, versi PHP, HTTPS dan cetak pada perangkat klien. Dialog cetak Android/printer fisik belum diverifikasi langsung.

Paket ini tidak mengubah konfigurasi atau melakukan redeploy website produksi. Pemindahan data lama memerlukan backup dan proses migrasi tersendiri. Semua file konfigurasi produksi harus diisi oleh pengelola hosting dengan kredensial baru yang sesuai.
