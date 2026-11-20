<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$ok=version_compare(PHP_VERSION,'8.2','>=');
echo 'PHP '.PHP_VERSION.($ok?' OK':' FAIL').PHP_EOL;
foreach(['mysqli','mbstring','session']as $ext){$loaded=extension_loaded($ext);$ok=$ok&&$loaded;echo 'Extension '.$ext.($loaded?' OK':' FAIL').PHP_EOL;}
foreach(['storage/logs','storage/sessions']as $path){$writable=is_writable(ROOT.'/'.$path);$ok=$ok&&$writable;echo $path.($writable?' writable':' NOT WRITABLE').PHP_EOL;}
$signature=brand()['invoice_signature']??'';$asset=$signature!==''&&is_readable(ROOT.'/'.$signature);$ok=$ok&&$asset;echo 'Workshop signature asset'.($asset?' OK':' FAIL').PHP_EOL;
try {
    $db=Database::one('SELECT VERSION() version,DATABASE() db');
    echo 'DB '.$db['version'].' / '.$db['db'].PHP_EOL;
    foreach(['users','customers','service_orders','spare_parts','service_order_parts','stock_movements','invoice_sequences','login_limits','schema_migrations']as $table){Database::one('SELECT COUNT(*) n FROM '.$table);echo $table.' OK'.PHP_EOL;}
    Database::one('SELECT show_workshop_signature FROM service_orders LIMIT 1'); echo 'Workshop signature preference OK'.PHP_EOL;
    foreach(['customers','spare_parts'] as $table) Database::one('SELECT deleted_at,deleted_by FROM '.$table.' LIMIT 1');
    Database::one('SELECT session_version FROM users LIMIT 1'); Database::one('SELECT id FROM record_events LIMIT 1'); echo 'Recoverable deletion schema OK'.PHP_EOL;
    Database::one('SELECT part_type FROM spare_parts LIMIT 1'); echo 'Sparepart type schema OK'.PHP_EOL;
}catch(Throwable $e){$ok=false;echo 'DB FAIL: periksa konfigurasi dan jalankan migrasi.'.PHP_EOL;}
echo 'Timezone '.date_default_timezone_get().PHP_EOL;exit($ok?0:1);
