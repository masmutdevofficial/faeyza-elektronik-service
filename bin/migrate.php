<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require ROOT.'/app/Migrations.php';
if (PHP_SAPI!=='cli') exit(1);
try {
    $target=null;
    foreach($argv as $arg) if(str_starts_with($arg,'--only-db='))$target=substr($arg,10);
    if (!$target || $target!==getenv('DB_NAME')) throw new RuntimeException('Gunakan --only-db=NAMA_DB yang cocok dengan konfigurasi.');
    if (getenv('APP_ENV')==='testing' && !str_ends_with($target,'_test')) throw new RuntimeException('Database testing harus berakhiran _test.');
    Migrations::apply(); echo 'Migrasi v2 siap: '.$target.PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,'Migrasi gagal. Periksa target database dan hak ALTER/INSERT akun migrasi.'.PHP_EOL); exit(1); }
