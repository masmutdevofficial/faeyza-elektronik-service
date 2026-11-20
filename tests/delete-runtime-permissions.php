<?php
// Explicit local QA with an administrator credential file outside the repository.
putenv('APP_ENV=testing');require dirname(__DIR__).'/app/bootstrap.php';
if(!str_ends_with((string)getenv('DB_NAME'),'_test')||!in_array(getenv('DB_HOST'),['127.0.0.1','localhost'],true))exit(1);
$config=$argv[1]??'';if(!is_file($config)){fwrite(STDERR,"Provide a private local DB administrator JSON path.\n");exit(1);}
$results=[];$check=function($ok,$name)use(&$results){$results[]=['name'=>$name,'status'=>$ok?'PASS':'FAIL'];echo($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)throw new RuntimeException('QA assertion failed');};
$dbName=getenv('DB_NAME');if(!preg_match('/^[a-zA-Z0-9_]+_test$/D',$dbName))exit(1);
$qaUser='faeyza_del_'.bin2hex(random_bytes(4));$qaPass=bin2hex(random_bytes(24));$root=null;$created=false;
try {
    $credentials=json_decode(file_get_contents($config),true,512,JSON_THROW_ON_ERROR);
    if(!in_array($credentials['host'],['127.0.0.1','localhost'],true))throw new RuntimeException('Local administrator only');
    $root=new mysqli($credentials['host'],$credentials['user'],$credentials['password'],$dbName,(int)$credentials['port']);
    $root->query("CREATE USER '$qaUser'@'127.0.0.1' IDENTIFIED BY '$qaPass'");$created=true;
    $root->query("GRANT SELECT,INSERT,UPDATE ON `$dbName`.* TO '$qaUser'@'127.0.0.1'");
    $admin=Database::one("SELECT * FROM users WHERE role='admin' ORDER BY id LIMIT 1");
    $phone='0813'.str_pad((string)random_int(1,99999999),8,'0',STR_PAD_LEFT);
    $uid=Auth::register(['phone'=>$phone,'password'=>bin2hex(random_bytes(10)),'name'=>'Runtime DELETE QA','address'=>'QA']);$cid=(int)Database::one('SELECT id FROM customers WHERE user_id=?',[$uid])['id'];
    $fields=['customer_id'=>$cid,'submission_key'=>token(),'received_date'=>Clock::today(),'customer_name'=>'Runtime DELETE QA','contact_phone'=>$phone,'address'=>'QA','item_name'=>'TV','brand'=>'SONY','model'=>'QA','complaint'=>'QA','location'=>'Bengkel','status'=>'Diterima'];
    $pending=Orders::create($fields,$admin);$fields['submission_key']=token();$fields['status']='Selesai';$fields['completed_date']=Clock::today();$done=Orders::create($fields,$admin);
    $part=Inventory::save(null,['sku'=>'PERM-'.bin2hex(random_bytes(5)),'name'=>'Runtime permissions QA','unit'=>'pcs','minimum_stock'=>0],$admin);Inventory::receive($part,10,'QA permissions',token(),$admin);Inventory::allocate($pending,$part,2,0,$admin);Inventory::allocate($done,$part,3,0,$admin);
    $worker=function($mode,$oid,$target)use($qaUser,$qaPass,$admin){
        $pipes=[];$env=array_replace(getenv(),['DB_USER'=>$qaUser,'DB_PASSWORD'=>$qaPass]);
        $process=proc_open([PHP_BINARY,__DIR__.'/worker.php',$mode,(string)$admin['id'],(string)$oid,'0',(string)$target,'0'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,ROOT,$env);
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);
        if($err!=='')throw new RuntimeException('Worker stderr; private output withheld');return json_decode($out,true,512,JSON_THROW_ON_ERROR);
    };
    $revision=(int)Database::one('SELECT revision FROM service_orders WHERE id=?',[$pending])['revision'];
    $denied=$worker('purge-invoice',$pending,$revision);
    $check(!($denied['ok']??false)&&str_contains($denied['error']??'','Izin hapus database'),'33 runtime lama tanpa DELETE menerima pesan aman');
    $check(Orders::get($pending,$admin)!==null&&(int)Database::one('SELECT stock FROM spare_parts WHERE id=?',[$part])['stock']===5&&(int)Database::one('SELECT quantity FROM service_order_parts WHERE service_order_id=?',[$pending])['quantity']===2,'33 kegagalan izin rollback stok audit dan invoice');
    $grants=preg_replace('/^--.*$/m','',file_get_contents(ROOT.'/database/runtime-delete-permissions.sql'));
    $grants=str_replace(['`faeyza_service`',"'faeyza_runtime'@'%'"],['`'.$dbName.'`',"'$qaUser'@'127.0.0.1'"],$grants);
    for($repeat=0;$repeat<2;$repeat++)foreach(explode(';',$grants) as $sql)if(trim($sql)!=='')$root->query($sql);
    $allowed=$worker('purge-invoice',$pending,$revision);$check(($allowed['ok']??false)&&Orders::get($pending,$admin)===null&&(int)Database::one('SELECT stock FROM spare_parts WHERE id=?',[$part])['stock']===7,'33 enam grant terbatas mengaktifkan hapus invoice dan pengembalian stok');
    RecordDeletion::change('customer',$cid,true,$admin);
    $allowed=$worker('purge-customer',0,$cid);$check(($allowed['ok']??false)&&Database::one('SELECT id FROM users WHERE id=?',[$uid])===null&&Orders::get($done,$admin)===null&&(int)Database::one('SELECT stock FROM spare_parts WHERE id=?',[$part])['stock']===7,'33 runtime terbatas dapat membersihkan akun arsip beserta invoice selesai');
    $runtime=new mysqli(getenv('DB_HOST'),$qaUser,$qaPass,$dbName,(int)getenv('DB_PORT'));
    foreach(['stock_movements','invoice_sequences'] as $table){$blocked=false;try{$runtime->query('DELETE FROM `'.$table.'` WHERE 1=0');}catch(mysqli_sql_exception $e){$blocked=$e->getCode()===1142;}$check($blocked,'33 runtime tidak mendapat izin DELETE '.$table);}
    $check((int)Database::one('SELECT SUM(delta) n FROM stock_movements WHERE spare_part_id=?',[$part])['n']===7,'33 ledger tetap konsisten setelah operasi runtime terbatas');
} catch(Throwable $e){$results[]=['name'=>'Runtime permissions QA exception '.get_class($e).' code '.$e->getCode(),'status'=>'FAIL'];echo "FAIL runtime permissions; private details withheld\n";}
finally{if($root&&$created)$root->query("DROP USER '$qaUser'@'127.0.0.1'");file_put_contents(__DIR__.'/artifacts/purge-permissions-results.json',json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));}
$summary=['pass'=>count(array_filter($results,fn($r)=>$r['status']==='PASS')),'fail'=>count(array_filter($results,fn($r)=>$r['status']==='FAIL'))];echo json_encode($summary).PHP_EOL;exit($summary['fail']?1:0);
