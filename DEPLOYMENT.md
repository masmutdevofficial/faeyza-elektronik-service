# Deployment Coolify — Faeyza Elektronik Service

Repository: https://github.com/masmutdevofficial/faeyza-elektronik-service (private).

## Batas isolasi

- Gunakan project **Faeyza Elektronik Service** tersendiri, environment production.
- Deploy repository main dengan build strategy **Docker Compose**, lokasi `/docker-compose.yml`, base `/`.
- Biarkan **Connect to predefined network** nonaktif. Jangan memakai mysql-shared, postgres-shared, atau database aplikasi lain.
- Compose tidak memiliki `ports`, `container_name`, `external` network/volume, atau mount direktori host. Nama volume diprefix project/resource oleh Docker/Coolify.
- MariaDB 11.4 khusus dengan database `faeyza_service`, user `faeyza_runtime`, volume `faeyza-db`. Root hanya localhost; runtime hanya SELECT/INSERT/UPDATE pada database tersebut. Tidak menerima akses root atau kredensial DB lain.
- Secret `SERVICE_PASSWORD_FAEYZADB` dan `SERVICE_PASSWORD_FAEYZAROOT` dibuat dan disimpan Coolify; jangan salin nilainya ke Git atau screenshot.
- Volume `faeyza-storage` menyimpan session/log aplikasi. Batas runtime web 256 MiB/0.5 CPU; database 384 MiB/0.5 CPU. Build memakai `-j2` untuk mengurangi beban kompilasi.
- Domain HTTPS diarahkan hanya ke service `web` port internal 80; database tidak mempunyai domain atau publikasi port.
- Domain yang diminta pemilik: **https://faeyza.dirgasatya.id**. Jangan mengubah DNS/domain aplikasi lain.
- Apache hanya mempercayai X-Forwarded-For dari alamat `coolify-proxy` yang di-resolve melalui Docker DNS jaringan resource saat startup. Ini menjaga rate limit dari penggabungan semua login ke IP proxy. Tidak mempercayai header IP sembarang dari client.

## Inisialisasi dan admin

Schema v1 diimpor oleh image MariaDB **hanya pada volume baru yang kosong**. Init script kemudian mengurangi hak user runtime. Restart/redeploy tidak mengosongkan data dan tidak membuat ulang schema pada volume yang sudah terisi.

Tidak ada akun demo atau password admin default pada image. Dari Terminal **service web Faeyza**, jalankan:

```sh
php bin/doctor.php
php bin/create-admin.php NOMOR_HP_ADMIN
```

Masukkan password unik minimal 12 karakter melalui stdin privat ketika diminta. Jangan mencantumkan password di argv, Git, issue, screenshot, atau log. Akun admin produksi harus menggunakan nomor HP pemilik yang benar. Admin lokal/testing tidak otomatis disalin.

## Verifikasi dan pemeliharaan

1. Periksa Deployment Logs, status kedua service, dan `php bin/doctor.php`.
2. Buka domain HTTPS, login/registrasi, cek secure cookie, dan pastikan `.env`, `/app`, `/database`, `/storage`, `/tests` tidak dapat diunduh.
3. Lakukan smoke test menggunakan data sintetis yang jelas berlabel uji. Jangan menjalankan reset-test atau seed pada produksi.
4. Bandingkan status resource lama sebelum/sesudah. Jangan restart proxy/global service, menjalankan Docker prune, atau memodifikasi resource milik project lain.
5. Backup database dan volume penyimpanan khusus Faeyza sebelum migrasi. Gunakan root **database Faeyza** untuk migrasi terkontrol, bukan runtime user. Jangan mengganti nama volume atau mengaktifkan auto-upgrade major database.
6. Rollback source dengan commit sebelumnya dan deploy ulang. Jangan menghapus volume database untuk rollback. Batasi operasi stop/restart hanya ke UUID resource Faeyza.

Referensi resmi: [Coolify Docker Compose](https://coolify.io/docs/applications/builds/docker-compose), [networking](https://coolify.io/docs/core/networking-in-coolify), [PHP image](https://hub.docker.com/_/php), [MariaDB image](https://hub.docker.com/_/mariadb).

Hasil deployment aktual dicatat di `DEPLOYMENT_REPORT.md`; panduan ini sendiri bukan bukti bahwa deployment sudah berhasil.
