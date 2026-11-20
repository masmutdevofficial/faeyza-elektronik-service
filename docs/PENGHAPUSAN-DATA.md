# Hapus konsumen dan invoice

Fitur ini hanya untuk admin dan memerlukan konfirmasi. Tidak ada penghapusan data otomatis saat aplikasi diperbarui.

- **Hapus konsumen** menghapus permanen akun (termasuk hash password), profil/kontak, seluruh invoice/servis, alokasi sparepart, audit arsip profil, dan bucket pembatas login nomor tersebut. Akun lain dan pembatas login IP tetap ada. Sesi lama ditolak karena akun sudah tidak ada. Nomor HP boleh didaftarkan ulang sebagai akun baru tanpa riwayat lama.
- **Hapus invoice** pada nota atau daftar Invoice menghapus invoice beserta servis dan alokasinya. Akun konsumen dan invoice lainnya tetap ada. Berlaku pada invoice lama dan baru.
- Konsumen yang diarsipkan versi lama masih tersedia melalui filter **Dihapus**. Buka detail lalu **Hapus permanen** untuk membersihkan akun dan seluruh invoice. Data lama tidak dipurge secara massal saat upgrade.
- **Hapus sparepart** tetap berupa arsip yang bisa dipulihkan; tidak berubah menjadi hapus permanen.

Invoice dan servis disimpan dalam satu baris transaksi. Karena itu, menghapus invoice juga menghapus rincian servis tersebut. Data yang dihapus permanen tidak dapat dipulihkan lewat aplikasi.

## Stok dan nomor invoice

Untuk servis yang belum Selesai/Sudah Diambil, alokasi yang masih tersisa dikembalikan ke stok. Untuk Selesai/Sudah Diambil, sparepart dianggap sudah dipakai sehingga stok tidak ditambahkan kembali. Servis Dibatalkan umumnya sudah memiliki alokasi nol dan tidak mendapat pengembalian ganda.

Histori stok dipertahankan untuk audit saldo tanpa tautan invoice atau snapshot identitas konsumen. Nomor urut pada invoice_sequences dipertahankan supaya nomor yang pernah diterbitkan tidak dipakai ulang. Tidak ada reset AUTO_INCREMENT atau sequence. Jika saldo fisik berbeda setelah menghapus servis selesai, gunakan Sesuaikan stok dengan alasan yang benar.

Pembersihan dilakukan dalam transaksi MySQL dengan row lock. Kesalahan izin, relasi, stok atau audit membatalkan seluruh perubahan. Dua request penghapusan tidak mengembalikan stok dua kali. Form hapus invoice memakai revision; perubahan servis setelah form dibuka memerlukan konfirmasi ulang.

## Sebelum memperbarui hosting

1. Backup database Faeyza dan source secara privat. Pertahankan `.env`, branding dan volume database.
2. Pastikan migrasi yang diperlukan source sudah diterapkan, termasuk v4 untuk tipe sparepart.
3. Berikan SELECT/INSERT/UPDATE pada database Faeyza serta **DELETE hanya pada** `service_order_parts`, `service_orders`, `record_events`, `customers`, `users`, dan `login_limits` kepada akun runtime Faeyza. Jangan berikan DELETE pada stock_movements atau invoice_sequences, maupun izin ke database aplikasi lain.
4. Coolify menggunakan database `faeyza_service` dan akun `'faeyza_runtime'@'%'`. Dari akun administrator database Faeyza, jalankan isi `database/runtime-delete-permissions.sql`. Redeploy pada volume lama tidak menjalankan ulang init script, jadi izin harus diperbarui secara terpisah. Database baru dari image sudah mengatur izin ini.
5. Pada shared hosting/XAMPP, sesuaikan nama database, nama akun dan host pada script izin. Jika panel tidak mendukung izin per tabel, minta pengelola mengaturnya; jangan mengganti akun runtime menjadi root. Tidak perlu membuat ulang tabel atau menghapus data untuk mengaktifkan fitur.
6. Rilis source dan jalankan `php bin/doctor.php`. Uji penghapusan memakai data sintetis yang memang boleh dihapus. Pastikan akun lain dan stok sesuai aturan di atas.

Jika aplikasi menampilkan **Izin hapus database belum disiapkan**, tidak ada data yang dihapus. Perbarui izin akun runtime, lalu ulangi. Restore backup dilakukan oleh pengelola secara terkontrol; jangan menggunakan fitur hapus pada data nyata hanya untuk mencoba.
