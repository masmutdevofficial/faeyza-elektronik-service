<?php
declare(strict_types=1);
// Offline helper for hosting without a PHP terminal. Never exposed through HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
try {
    $phone=Validate::phone($argv[1]??'');
    fwrite(STDERR,"Password admin (minimal 6 karakter; terminal privat): ");
    $password=rtrim((string)fgets(STDIN),"\r\n");
    Validate::password(['password'=>$password],'password',Auth::PASSWORD_MIN);
    $hash=password_hash($password,PASSWORD_DEFAULT);
    // Both values are validated/generated locally. Output is for manual SQL import.
    $hash=str_replace("'","''",$hash);
    fwrite(STDOUT,"-- Import sekali ke database Faeyza yang benar. Nomor duplikat ditolak.\n".
        "INSERT INTO users (phone,password_hash,role) VALUES ('".$phone."','".$hash."','admin');\n");
} catch (Throwable $e) {
    fwrite(STDERR,($e instanceof ValidationException?$e->getMessage():'Gagal membuat SQL admin.').PHP_EOL);
    exit(1);
}
