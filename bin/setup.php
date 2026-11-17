<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if (PHP_SAPI!=='cli') exit(1);
try {
    $name=getenv('DB_NAME')?:'faeyza';
    if (!preg_match('/^[a-zA-Z0-9_]+$/D',$name)) throw new RuntimeException('Nama database tidak valid.');
    if (getenv('APP_ENV')==='testing' && !str_ends_with($name,'_test')) throw new RuntimeException('Database testing wajib berakhiran _test.');
    $c=Database::connection(false);
    if (in_array('--create-db',$argv,true)) $c->query('CREATE DATABASE IF NOT EXISTS `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $c->select_db($name);
    $c->multi_query(file_get_contents(ROOT.'/database/schema.sql'));
    do { if($res=$c->store_result())$res->free(); } while($c->more_results() && $c->next_result());
    echo 'Migrasi v1 siap: '.$name.PHP_EOL;
} catch(Throwable $e) { fwrite(STDERR,'Setup gagal ('.get_class($e).', '.$e->getCode().'). Periksa konfigurasi DB dan izin CREATE/ALTER.'.PHP_EOL); exit(1); }
