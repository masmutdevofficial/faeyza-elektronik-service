<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli' || !in_array(getenv('APP_ENV'),['development','testing'],true)) { fwrite(STDERR,"Seed hanya untuk development/testing.\n"); exit(1); }
$credentialFile=ROOT.'/tests/local-credentials.json';
if(is_file($credentialFile)) { echo "Seed sudah ada. Kredensial development ada di file lokal yang diabaikan Git.\n"; exit; }
$password=bin2hex(random_bytes(12));
try {
    Database::tx(function() use($password) {
        Database::query('INSERT INTO users(phone,password_hash,role) VALUES (?,?,?)',['628170002466',password_hash($password,PASSWORD_DEFAULT),'admin']);
    });
    $uid=Auth::register(['phone'=>'081811122233','name'=>'Nadia Putri','address'=>'Jakarta','password'=>$password]);
    $actor=['id'=>$uid,'role'=>'customer'];
    Orders::create(['submission_key'=>token(),'received_date'=>Clock::today(),'customer_name'=>'Nadia Putri','contact_phone'=>'081811122233','address'=>'Jakarta','item_name'=>'TV','brand'=>'SONY','model'=>'Bravia 43','serial_number'=>'','accessories'=>['Kabel Power','Remote'],'complaint'=>'Layar tidak menyala, indikator berkedip.','location'=>'Bengkel','notes'=>'Hubungi setelah pemeriksaan.'],$actor);
    file_put_contents($credentialFile,json_encode(['environment'=>getenv('APP_ENV'),'admin_phone'=>'08170002466','customer_phone'=>'081811122233','password'=>$password],JSON_PRETTY_PRINT));
    echo "Seed selesai. Kredensial acak tersimpan di tests/local-credentials.json (jangan bagikan/unggah).\n";
} catch(Throwable $e) { fwrite(STDERR,'Seed gagal: '.get_class($e).' code='.$e->getCode().PHP_EOL); exit(1); }
