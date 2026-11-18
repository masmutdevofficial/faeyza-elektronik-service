<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
putenv('APP_ENV=testing');
require dirname(__DIR__).'/app/bootstrap.php';
if (getenv('APP_ENV')!=='testing' || !str_ends_with((string)getenv('DB_NAME'),'_test')) {
    fwrite(STDERR,"Ditolak: hanya CLI dan database _test.\n"); exit(1);
}
Database::query('DELETE FROM login_limits');
echo "Bucket login testing dikosongkan; data akun/pesanan tetap tersimpan.\n";
