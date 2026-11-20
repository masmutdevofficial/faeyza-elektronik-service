# Perbaikan ringkasan konsumen dan koreksi stok

Pembaruan 20 November 2026 untuk Faeyza Elektronik Service. Memerlukan instalasi aplikasi yang sudah berjalan pada PHP 8.2 atau lebih baru. Tidak memerlukan perubahan skema database, impor SQL, pembuatan akun baru atau reset data.

## Perubahan

1. Konsumen yang dihapus beserta seluruh servisnya tidak lagi masuk hitungan ringkasan, antrean dan daftar servis aktif. Jika semua konsumen dihapus, seluruh kartu servis bernilai 0 dan daftar terbaru kosong. Pemulihan konsumen mengembalikan hitungan secara otomatis.
2. Invoice lama tetap tersimpan. Admin dapat mencarinya melalui menu Invoice atau membuka nomor invoice yang sudah diterbitkan. Penghapusan konsumen tidak membatalkan servis atau mengubah alokasi sparepart dengan sendirinya.
3. Halaman detail sparepart menyediakan form Sesuaikan stok. Admin dapat mengurangi atau menaikkan stok tersedia, termasuk mengisi 0 untuk stok habis. Alasan wajib diisi, dan setiap perubahan mencatat selisih, saldo, waktu dan admin pada histori.
4. Jika stok berubah karena transaksi lain, form lama ditolak. Muat ulang dahulu agar koreksi tidak menimpa stok terbaru. Pengiriman ulang permintaan yang sama tidak menggandakan koreksi. Stok tidak dapat bernilai negatif.

## Memasang paket pembaruan

1. Backup database dan enam file yang tercantum di bawah. Simpan backup di tempat privat.
2. Ekstrak ZIP pembaruan di komputer. Upload isi folder app dan views ke akar aplikasi Faeyza, dengan mengganti file yang namanya sama. Pasang keenam file sebagai satu pembaruan ketika tidak ada transaksi aktif.
3. Pada susunan document root public, akar aplikasinya adalah folder yang berisi app, views, config dan public. Contoh: public_html/app.faeyzateknik.com. Folder tujuan bukan subfolder public dan bukan akar Laravel di public_html.
4. Pada susunan source privat dengan wrapper, upload ke direktori source privat Faeyza, bukan public_html atau folder subdomain yang hanya berisi aset publik.
5. Tidak perlu menimpa .env, .htaccess, branding, logo, tanda tangan, storage atau database. Jangan menjalankan reset testing pada hosting.
6. Muat ulang aplikasi. Jika hosting mengaktifkan OPcache yang tidak memeriksa perubahan file, gunakan fasilitas restart PHP milik hosting atau minta pengelola menyegarkannya.

File runtime dalam pembaruan:

```text
app/Domain.php
app/controller.php
views/part.php
views/parts.php
views/part-form.php
views/record-delete.php
```

MANIFEST-UPDATE.json berisi checksum file sebelum dan sesudah perubahan. Jika versi source hosting berbeda atau file sudah dikustomisasi, bandingkan dengan backup sebelum menimpa. Paket ini ditujukan untuk versi Faeyza yang sebelumnya dikirim pada percakapan proyek ini.

## Cara memakai koreksi stok

Buka Sparepart, pilih nama sparepart, lalu isi form Sesuaikan stok:

- Stok tersedia yang benar: jumlah akhir hasil hitung fisik, bukan jumlah pengurang. Contoh stok 10 dan ingin mengurangi 2, isi 8.
- Alasan penyesuaian: misalnya hasil hitung fisik, barang rusak, atau koreksi pencatatan.
- Klik Simpan penyesuaian stok. Untuk stok habis, isi jumlah 0. Label stok berubah menjadi Stok habis.

Menu Edit sparepart juga memiliki tautan Sesuaikan stok. Form Stok masuk tetap digunakan untuk mencatat penerimaan baru. Koreksi stok tersedia tidak menghapus pemakaian servis yang telah dicatat; jika servis kemudian dibatalkan, barang yang sebelumnya dipakai tetap dikembalikan ke stok sesuai histori.

## Pemeriksaan setelah pemasangan

Pastikan konsumen yang sebelumnya dihapus sudah tidak muncul di ringkasan. Periksa bahwa invoice lamanya masih dapat dibuka admin melalui menu Invoice. Uji koreksi pada sparepart uji yang memang boleh diubah, periksa histori, dan pastikan refresh tidak mengubah stok lagi. Hindari menghapus konsumen atau mengoreksi sparepart nyata hanya untuk pengujian.

Paket disiapkan dan diuji pada database testing lokal. Pemasangan pada hosting klien belum dilakukan; kontrol komputer klien tetap dihentikan sesuai permintaan.
