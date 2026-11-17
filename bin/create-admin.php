<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if (PHP_SAPI!=='cli') exit(1);
try {
    $phone=Validate::phone($argv[1]??'');
    fwrite(STDOUT,"Password admin (minimal 6 karakter; gunakan terminal privat): ");
    // Dapat dipasok melalui stdin oleh password manager. Tidak masuk argv/history.
    $password=rtrim((string)fgets(STDIN),"\r\n");
    Validate::password(['password'=>$password],'password',Auth::PASSWORD_MIN);
    Database::query('INSERT INTO users(phone,password_hash,role) VALUES (?,?,?)',[$phone,password_hash($password,PASSWORD_DEFAULT),'admin']);
    echo 'Admin berhasil dibuat. Tidak ada akun production default.'.PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,($e instanceof ValidationException?$e->getMessage():'Admin gagal dibuat. Periksa nomor unik, koneksi dan skema.').PHP_EOL); exit(1); }
