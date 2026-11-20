# Tipe sparepart

Di detail sparepart, **Catat stok masuk** memakai kolom **Tipe sparepart** menggantikan **Keterangan / sumber**. Admin dapat mengetik tipe/model, misalnya `TPA3116D2` atau `470 µF 25 V`, maksimum 180 karakter. Tipe wajib diisi saat mencatat stok masuk.

Saat penerimaan disimpan, stok bertambah dan tipe tersimpan pada sparepart dalam transaksi yang sama. Tipe terakhir otomatis terisi pada penerimaan berikutnya. Histori mencatat tipe pada saat transaksi; perubahan tipe berikutnya tidak menulis ulang histori lama.

**Tambah/Edit sparepart** menyediakan kolom tipe opsional. Mengedit tipe tidak mengubah stok. Pencarian daftar sparepart mendukung tipe, nama, dan SKU. Satu SKU memiliki satu tipe saat ini; gunakan SKU terpisah untuk tipe yang perlu dikelola sebagai stok berbeda.

## Memperbarui database lama

Versi ini memerlukan migrasi v4 sebelum kode baru diaktifkan. Backup source dan database aplikasi Faeyza terlebih dahulu. Jangan menimpa `.env`, konfigurasi branding, atau volume database.

Dengan terminal dan akun migrasi yang memiliki izin pada database Faeyza:

```sh
php bin/migrate.php --only-db=NAMA_DATABASE_FAEYZA
php bin/doctor.php
```

Ganti nama database sesuai `DB_NAME` (termasuk prefix hosting). Di XAMPP, gunakan `C:\xampp\php\php.exe` sebagai pengganti `php`, dan jalankan dari folder aplikasi. Coolify memakai `--only-db=faeyza_service`; gunakan akun migrasi database Faeyza, lalu kembalikan akun runtime terbatas. Migrasi CLI memeriksa kolom dan aman dijalankan ulang.

Untuk shared hosting tanpa terminal, pilih **database Faeyza** di phpMyAdmin. Pastikan kolom `part_type` belum ada pada tabel `spare_parts`, lalu jalankan SQL dari `database/migrations/004_part_type.sql` satu kali:

```sql
ALTER TABLE spare_parts
ADD COLUMN part_type VARCHAR(180) NOT NULL DEFAULT '' AFTER name;
```

SQL langsung ini hanya untuk database yang sudah memakai versi v3. Jika kolom sudah ada, lewati perintah ALTER. Database versi lebih lama memerlukan seluruh migrasi berurutan melalui pengelola hosting. Jangan mengimpor ulang seluruh schema sebagai pengganti upgrade.

Setelah migrasi, unggah/rilis kode baru dan cek detail sparepart, penerimaan stok, serta Edit sparepart. Data lama mempunyai tipe kosong sampai admin mengisinya. Jumlah stok dan histori lama tetap sama. Untuk instalasi baru, gunakan `bin/setup.php` atau skema terbaru, yang sudah memuat kolom tipe.
