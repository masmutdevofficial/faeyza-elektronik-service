<?php
declare(strict_types=1);
putenv('APP_ENV=testing'); require dirname(__DIR__).'/app/bootstrap.php'; require __DIR__.'/reset.php';
if(!in_array('--reset-test',$argv,true)) { fwrite(STDERR,"Gunakan --reset-test. Hanya database _test yang dikosongkan.\n"); exit(1); }
reset_test_database();
$results=[];
function check(bool $ok,string $name): void { global $results; $results[]=['name'=>$name,'status'=>$ok?'PASS':'FAIL']; echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL; }
function rejects(callable $fn,string $name): void { try{$fn();check(false,$name);}catch(ValidationException){check(true,$name);} }
function fixture(array $changes=[]): array { return array_replace(['submission_key'=>token(),'received_date'=>Clock::today(),'customer_name'=>'Konsumen Uji','contact_phone'=>'081811122233','address'=>'Jl. Pengujian 1, Jakarta','item_name'=>'TV','brand'=>'SONY','model'=>'Bravia 43','serial_number'=>'SN-001','accessories'=>['Kabel Power','Remote'],'complaint'=>'Layar mati','location'=>'Bengkel','notes'=>'Hubungi setelah selesai.'],$changes); }
function editData(int $id,array $admin,array $changes=[]): array { $row=Orders::get($id,$admin); $row['accessories']=json_decode($row['accessories'],true); return array_replace($row,$changes); }
function partData(string $sku): array { return ['sku'=>$sku,'name'=>'Kapasitor '.$sku,'minimum_stock'=>3,'unit'=>'pcs','purchase_price'=>'5000','sale_price'=>'10000']; }
function stock(int $id): int { return (int)Database::one('SELECT stock FROM spare_parts WHERE id=?',[$id])['stock']; }
function concurrent(array $jobs): array {
    $handles=[]; $barrier=(string)(microtime(true)+1.2);
    foreach($jobs as $job) {
        $pipes=[];$p=proc_open([PHP_BINARY,__DIR__.'/worker.php',...array_map('strval',$job),$barrier],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,ROOT);
        fclose($pipes[0]);$handles[]=[$p,$pipes];
    }
    $out=[];
    foreach($handles as [$p,$pipes]){$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);$out[]=json_decode($stdout,true)??['ok'=>false,'exit'=>$code,'stderr'=>$stderr];}
    return $out;
}
final class HttpClient {
    private string $cookie;
    public function __construct(){ $this->cookie=tempnam(sys_get_temp_dir(),'faeyza_'); }
    public function __destruct(){ if(is_file($this->cookie))unlink($this->cookie); }
    public function request(string $page,string $method='GET',array $data=[],array $params=[]): array {
        $base=getenv('TEST_BASE_URL')?:'http://127.0.0.1:8081/';
        $h=curl_init($base.'index.php?'.http_build_query(['page'=>$page]+$params));
        curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_COOKIEJAR=>$this->cookie,CURLOPT_COOKIEFILE=>$this->cookie,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);
        if($method==='POST')curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($data)]);
        $raw=curl_exec($h); if($raw===false) throw new RuntimeException(curl_error($h));
        $headerSize=curl_getinfo($h,CURLINFO_HEADER_SIZE); $code=curl_getinfo($h,CURLINFO_HTTP_CODE);
        return ['code'=>$code,'headers'=>substr($raw,0,$headerSize),'body'=>substr($raw,$headerSize)];
    }
    public function form(string $page,array $params=[]): string {
        $r=$this->request($page,'GET',[],$params); if(!preg_match('/name="csrf" value="([a-f0-9]+)"/',$r['body'],$m))throw new RuntimeException('CSRF token tidak ada di '.$page.' ('.$r['code'].')'); return $m[1];
    }
    public function login(string $phone,string $password): array { return $this->request('login','POST',['csrf'=>$this->form('login'),'phone'=>$phone,'password'=>$password]); }
}
try {
    $password=bin2hex(random_bytes(12));
    Database::query('INSERT INTO users(phone,password_hash,role) VALUES (?,?,?)',['628170002466',password_hash($password,PASSWORD_DEFAULT),'admin']);
    $admin=Database::one('SELECT * FROM users WHERE phone=?',['628170002466']);
    $aId=Auth::register(['name'=>'Konsumen A','phone'=>'081811122233','address'=>'Jakarta A','password'=>$password]);
    $bId=Auth::register(['name'=>'Konsumen B','phone'=>'+6281911122233','address'=>'Jakarta B','password'=>$password]);
    $a=Database::one('SELECT * FROM users WHERE id=?',[$aId]);$b=Database::one('SELECT * FROM users WHERE id=?',[$bId]);
    check(Validate::phone('08170002466')===Validate::phone('+628170002466') && Validate::phone('628170002466')==='628170002466','01 normalisasi tiga format HP');
    check(Auth::login('+6281811122233',$password,'unit-a')['id']==$aId,'01 login konsumen');
    check(Auth::login('08170002466',$password,'unit-admin')['role']==='admin','01 login admin');
    rejects(fn()=>Auth::login('081811122233','salah','unit-a'),'01 password salah ditolak');
    rejects(fn()=>Auth::register(['name'=>'Duplikat','phone'=>'+6281811122233','address'=>'Jakarta','password'=>$password]),'01 duplikat nomor format berbeda ditolak');
    rejects(fn()=>Auth::register(['name'=>'Invalid','phone'=>'081299988877','address'=>'Jakarta','password'=>"abcdefghi\0jkl"]),'11 password null byte ditolak tanpa runtime error');
    rejects(fn()=>Auth::register(['name'=>'Invalid','phone'=>'081299988877','address'=>'Jakarta','password'=>str_repeat('🔧',30)]),'11 password Unicode di atas 72 byte ditolak aman');
    check(Validate::password(['password'=>'  spasiPassword  '])==='  spasiPassword  ','11 spasi password dipertahankan');
    check($a['password_hash']!==$password && password_verify($password,$a['password_hash']),'11 hash password bukan plaintext');
    rejects(fn()=>Customers::save(null,[],$a),'11 otorisasi admin domain');
    putenv('TEST_DATE=2031-01-01');
    $oa=Orders::create(fixture(['received_date'=>'2020-02-02']),$a); $ob=Orders::create(fixture(['customer_name'=>'Konsumen B','contact_phone'=>$b['phone']]),$b);
    check(Orders::get($oa,$a)['invoice_number']==='INV-20310101-001','04 sequence pertama memakai clock server bukan tanggal masuk');
    check(Orders::get($ob,$b)['invoice_number']==='INV-20310101-002','04 sequence kedua berurutan');
    check(json_decode(Orders::get($oa,$a)['accessories'],true)===['Kabel Power','Remote'],'02 kelengkapan multi-select tersimpan');
    check(Orders::get($oa,$a)['total_cost']===null,'06 biaya awal NULL di MySQL');
    check(money(null)==='-' && money('0.00')==='Rp0' && money('150000.00')==='Rp150.000','06 format NULL nol dan Rupiah');
    $same=fixture();$once=Orders::create($same,$a);check(Orders::create($same,$a)===$once,'03 pengiriman token sama idempotent');
    putenv('TEST_DATE=2031-01-02');$next=Orders::create(fixture(),$b);check(Orders::get($next,$b)['invoice_number']==='INV-20310102-001','04 pergantian hari reset ke 001');
    putenv('TEST_DATE=2031-01-03');Database::query('INSERT INTO invoice_sequences(business_date,last_number) VALUES (?,999)',['2031-01-03']);
    $large=Orders::create(fixture(),$a);check(Orders::get($large,$a)['invoice_number']==='INV-20310103-1000','04 sequence di atas 999 didukung');
    putenv('TEST_DATE=2031-01-04');
    $parallel=concurrent([['invoice',$aId,0,0,0],['invoice',$bId,0,0,0],['invoice',$aId,0,0,0],['invoice',$bId,0,0,0],['invoice',$aId,0,0,0],['invoice',$bId,0,0,0]]);
    $numbers=array_column($parallel,'invoice');sort($numbers);
    check(count($numbers)===6 && count(array_unique($numbers))===6 && $numbers[0]==='INV-20310104-001' && $numbers[5]==='INV-20310104-006','04 enam proses DB bersamaan menghasilkan invoice unik berurutan');
    putenv('TEST_DATE=2031-01-05');$doubleKey=token();
    $doubleResult=concurrent([['invoice',$aId,0,0,$doubleKey],['invoice',$aId,0,0,$doubleKey]]);
    check(count(array_column($doubleResult,'invoice'))===2 && $doubleResult[0]['invoice']===$doubleResult[1]['invoice'] && (int)Database::one('SELECT COUNT(*) n FROM service_orders WHERE submission_key=?',[$doubleKey])['n']===1,'03 dua pengiriman bersamaan token sama hanya satu servis');
    putenv('TEST_DATE');
    rejects(fn()=>Orders::create(fixture(['customer_name'=>'']),$a),'03 field wajib kosong ditolak');
    rejects(fn()=>Orders::create(fixture(['contact_phone'=>'123']),$a),'03 HP tidak valid ditolak');
    rejects(fn()=>Orders::create(fixture(['received_date'=>'2026-02-30']),$a),'03 tanggal tidak valid ditolak');
    foreach(['total_cost'=>'150000','status'=>'Selesai','role'=>'admin','customer_id'=>2,'invoice_number'=>'INV-X'] as $field=>$value)rejects(fn()=>Orders::create(fixture([$field=>$value]),$a),'03/11 manipulasi '.$field.' ditolak');
    check(Orders::get($ob,$a)===null && Orders::get($oa,$b)===null,'05 domain menolak IDOR kedua arah');
    check(Orders::get($oa,$admin)!==null && Orders::get($ob,$admin)!==null,'05 admin mengakses semua invoice');
    check(count(Orders::listing($a,$b['phone']))===0,'05 pencarian HP B oleh A tidak membocorkan data');
    check(count(Orders::listing($admin,$b['phone']))>=1,'05 admin mencari nomor HP');
    $malicious="<script>alert('XSS')</script>";
    $ox=Orders::create(fixture(['complaint'=>$malicious,'notes'=>'<img src=x onerror=alert(1)>']),$a);
    check(Orders::get($ox,$a)['complaint']===$malicious && !str_contains(e($malicious),'<script>'),'11 stored XSS di-escape saat ditampilkan');
    Orders::update($oa,editData($oa,$admin,['total_cost'=>'0']),$admin);check(Orders::get($oa,$a)['total_cost']==='0.00','06 admin menyimpan nol secara eksplisit');
    $stale=editData($oa,$admin);Orders::update($oa,editData($oa,$admin,['total_cost'=>'150000','status'=>'Dalam Pengerjaan']),$admin);
    rejects(fn()=>Orders::update($oa,$stale,$admin),'09 edit stale revision ditolak');
    rejects(fn()=>Orders::update($oa,editData($oa,$admin),$a),'06 konsumen tidak dapat mengubah biaya');
    rejects(fn()=>Orders::update($oa,editData($oa,$admin,['warranty_date'=>'2026-12-17','completed_date'=>'']),$admin),'09 garansi tanpa tanggal selesai ditolak');
    $part=Inventory::save(null,partData('CAP-001'),$admin);$key=token();Inventory::receive($part,10,'Pembelian uji',$key,$admin);
    Inventory::receive($part,10,'Retry',$key,$admin);check(stock($part)===10,'07 stok masuk dan retry tidak double increment');
    Inventory::allocate($oa,$part,2,0,$admin);check(stock($part)===8,'07 alokasi 2 dari 10 menjadi 8');
    Inventory::allocate($oa,$part,2,0,$admin);check(stock($part)===8,'07 retry alokasi tidak double deduction');
    Inventory::allocate($oa,$part,3,2,$admin);check(stock($part)===7,'07 kuantitas 2 ke 3 mengurangi selisih 1');
    Inventory::allocate($oa,$part,1,3,$admin);check(stock($part)===9,'07 kuantitas 3 ke 1 mengembalikan 2');
    Inventory::allocate($oa,$part,0,1,$admin);check(stock($part)===10,'07 hapus alokasi mengembalikan stok');
    check(Database::one('SELECT quantity FROM service_order_parts WHERE service_order_id=? AND spare_part_id=?',[$oa,$part])['quantity']==0,'07 baris alokasi nol tetap tersimpan');
    Inventory::allocate($oa,$part,2,0,$admin);
    Orders::update($oa,editData($oa,$admin,['status'=>'Dibatalkan']),$admin);check(stock($part)===10,'07 pembatalan servis memulihkan stok');
    Orders::update($oa,editData($oa,$admin,['status'=>'Dibatalkan']),$admin);check(stock($part)===10,'07 pembatalan ulang tidak double return');
    rejects(fn()=>Inventory::allocate($oa,$part,1,0,$admin),'07 servis batal tidak boleh mengalokasikan stok');
    check(count(Database::all('SELECT * FROM stock_movements WHERE spare_part_id=?',[$part]))===7,'07 histori masuk penggunaan selisih dan pembatalan lengkap');
    $scarce=Inventory::save(null,partData('LIMIT-002'),$admin);Inventory::receive($scarce,2,'Stok terbatas',token(),$admin);
    rejects(fn()=>Inventory::allocate($ob,$scarce,5,0,$admin),'08 stok 2 ditolak untuk penggunaan 5');
    check(stock($scarce)===2 && Database::one('SELECT id FROM service_order_parts WHERE service_order_id=? AND spare_part_id=?',[$ob,$scarce])===null,'08 rollback tanpa penggunaan parsial');
    $zero=Inventory::save(null,partData('ZERO'),$admin);rejects(fn()=>Inventory::allocate($ob,$zero,1,0,$admin),'08 stok habis ditolak');
    $race=Inventory::save(null,partData('RACE'),$admin);Inventory::receive($race,2,'Uji balapan',token(),$admin);
    $raceResult=concurrent([['stock',$admin['id'],$ob,$race,2],['stock',$admin['id'],$next,$race,2]]);
    check(count(array_filter($raceResult,fn($r)=>$r['ok']??false))===1 && stock($race)===0,'08 dua request bersamaan hanya satu menghabiskan stok 2');
    check((int)Database::one('SELECT COALESCE(SUM(quantity),0) n FROM service_order_parts WHERE spare_part_id=?',[$race])['n']===2,'08 kuantitas alokasi konsisten sesudah race');
    $atomicPart=Inventory::save(null,partData('ATOMIC'),$admin);Inventory::receive($atomicPart,10,'Atomicity',token(),$admin);
    try { Database::tx(function()use($atomicPart,$admin){Inventory::allocateLocked(999999,$atomicPart,1,$admin,'FK gagal');});check(false,'08 rollback foreign key'); } catch(mysqli_sql_exception){check(stock($atomicPart)===10,'08 rollback foreign key');}
    check((int)Database::one('SELECT MIN(stock) n FROM spare_parts')['n']>=0,'08 tidak ada stok negatif');
    $profile=Orders::customer($a);
    Customers::save((int)$profile['id'],['name'=>'Konsumen A Diperbarui','phone'=>'081822233344','address'=>'Alamat baru'],$admin);
    check(Orders::get($oa,$a)!==null && Orders::get($oa,$a)['contact_phone']==='6281811122233','09 perubahan HP akun tidak memutus kepemilikan atau snapshot');
    check(count(Orders::listing($a,'INV-20310101-001'))===1,'05 pencarian nomor invoice milik sendiri');
    $oldInvoice=Orders::get($ob,$admin)['invoice_number'];
    Orders::update($ob,editData($ob,$admin,['invoice_number'=>'INV-DIGANTI']),$admin);
    check(Orders::get($ob,$admin)['invoice_number']===$oldInvoice,'04 nomor invoice immutable walaupun POST admin memuat nomor lain');
    $bProfile=Orders::customer($b);
    $adminOrder=Orders::create(fixture(['customer_id'=>$bProfile['id'],'status'=>'Diterima','total_cost'=>'0']),$admin);
    check(Orders::get($adminOrder,$b)!==null && Orders::get($adminOrder,$a)===null,'02 admin menambahkan servis terhubung pemilik sah');
    rejects(fn()=>Customers::save((int)$bProfile['id'],['name'=>'Tidak tersimpan','phone'=>'081822233344','address'=>'Berubah'],$admin),'09 perubahan HP duplikat ditolak');
    check(Orders::customer($b)['name']==='Konsumen B','09 perubahan konsumen rollback bila nomor bentrok');
    $newPart=Database::one('SELECT * FROM spare_parts WHERE id=?',[$part]); Inventory::save($part,array_replace(partData('CAP-001'),['name'=>'Kapasitor terbaru','revision'=>$newPart['revision'],'stock'=>999]),$admin);
    check(stock($part)===10,'07 edit metadata tidak mengubah stok diam-diam');
    rejects(fn()=>Inventory::save(null,partData('CAP-001'),$admin),'07 SKU duplikat ditolak');
    foreach(range(1,8)as $i){try{Auth::login('08999999999','salah','rate-limit-test');}catch(ValidationException){}}
    try{Auth::login('08999999999','salah','rate-limit-test');check(false,'11 rate limit login');}catch(ValidationException $e){check(str_contains($e->getMessage(),'15 menit'),'11 rate limit login');}
    Database::query('DELETE FROM login_limits');
    // Integrasi HTTP: form asli, CSRF, cookie, logout dan server-side authorization.
    $http=new HttpClient(); $adminHttp=new HttpClient(); $otherHttp=new HttpClient();
    $pre=$http->request('dashboard');
    check($pre['code']===303,'01 tamu diarahkan ke login');
    check(str_contains(strtolower($pre['headers']),'httponly') && str_contains(strtolower($pre['headers']),'samesite=lax'),'11 cookie HttpOnly SameSite');
    check($http->request('login','POST',['phone'=>'081822233344','password'=>$password])['code']===419,'11 CSRF tanpa token ditolak');
    check($http->login("' OR 1=1 --",'invalid')['code']===422,'11 SQL injection login ditolak');
    $loginHttp=$http->login('081822233344',$password);
    check($loginHttp['code']===303,'01 login HTTP konsumen');
    check(str_contains($loginHttp['headers'],'Set-Cookie: faeyza_test='),'11 login meregenerasi session cookie');
    foreach(['customers','customer-edit','parts','part-edit','service-edit'] as $endpoint)check($http->request($endpoint,'GET',[],['id'=>$oa])['code']===403,'01/11 endpoint admin '.$endpoint.' ditolak');
    check($http->request('invoice','GET',[],['id'=>$ob])['code']===404 && $http->request('order','GET',[],['id'=>$ob])['code']===404,'05 HTTP IDOR invoice dan detail servis ditolak');
    check($http->request('invoices','GET',[],['q'=>$b['phone']])['body'] && !str_contains($http->request('invoices','GET',[],['q'=>$b['phone']])['body'],'INV-20310101-002'),'05 pencarian HTTP HP B aman');
    $newForm=$http->request('service-new');preg_match('/name="submission_key" value="([a-f0-9]+)"/',$newForm['body'],$m);
    $post=fixture(['submission_key'=>$m[1],'csrf'=>$http->form('service-new'),'complaint'=>$malicious]);
    $bad=$http->request('service-new','POST',array_replace($post,['model'=>'','notes'=>'Data tetap ada']));
    check($bad['code']===422 && str_contains($bad['body'],'Data tetap ada'),'03 error validasi mempertahankan isi form');
    check($http->request('service-new','POST',$post+['total_cost'=>'1'])['code']===422,'03 POST biaya palsu ditolak');
    $submit=$http->request('service-new','POST',$post);check($submit['code']===303 && str_contains($submit['headers'],'page=success'),'02 submit HTTP berhasil dengan halaman sukses');
    preg_match('/[?&]id=(\d+)/',$submit['headers'],$idMatch);$webOrder=(int)$idMatch[1];
    $duplicate=$http->request('service-new','POST',$post);check(str_contains($duplicate['headers'],'id='.$webOrder),'03 double submit HTTP menghasilkan ID sama');
    check($http->request('success','GET',[],['id'=>$webOrder])['code']===200,'02 halaman sukses dapat dibuka');
    $invoice=$http->request('invoice','GET',[],['id'=>$webOrder]);
    check($invoice['code']===200 && str_contains($invoice['body'],'data-testid="total-cost">-</strong>'),'06/10 invoice HTTP menampilkan biaya kosong -');
    check(str_contains($invoice['body'],'&lt;script&gt;') && !str_contains($invoice['body'],$malicious),'11 invoice tidak mengeksekusi stored XSS');
    check(str_contains($invoice['body'],'assets/print.css') && str_contains($invoice['body'],'data-print') && !str_contains($invoice['body'],'class="sidebar"'),'10 invoice tanpa navigasi dan tombol cetak tersedia');
    $beforeRefresh=stock($part);$http->request('order','GET',[],['id'=>$webOrder]);$http->request('order','GET',[],['id'=>$webOrder]);check(stock($part)===$beforeRefresh,'07 refresh detail tidak mengurangi stok');
    check($adminHttp->login('08170002466',$password)['code']===303,'01 login HTTP admin');
    foreach(['dashboard','orders','invoices','customers','parts','customer-new','part-new','service-new']as $endpoint)check($adminHttp->request($endpoint)['code']===200,'09 halaman admin '.$endpoint.' bekerja');
    check($adminHttp->request('invoices','GET',[],['q'=>'081911122233'])['code']===200,'05 admin mencari HP ternormalisasi via HTTP');
    check($adminHttp->request('service-edit','POST',editData($webOrder,$admin,['csrf'=>'wrong']),['id'=>$webOrder])['code']===419,'11 perubahan admin tanpa CSRF benar ditolak');
    $csrf=$adminHttp->form('service-edit',['id'=>$webOrder]);
    $edit=editData($webOrder,$admin,['csrf'=>$csrf,'status'=>'Dalam Pengerjaan','total_cost'=>'0']);
    check($adminHttp->request('service-edit','POST',$edit,['id'=>$webOrder])['code']===303,'09 HTTP update servis admin');
    check(str_contains($http->request('invoice','GET',[],['id'=>$webOrder])['body'],'data-testid="total-cost">Rp0</strong>'),'06 konsumen melihat Rp0');
    $edit=editData($webOrder,$admin,['csrf'=>$csrf,'status'=>'Selesai','completed_date'=>Clock::today(),'warranty_date'=>date('Y-m-d',strtotime('+30 days')),'total_cost'=>'150000']);
    check($adminHttp->request('service-edit','POST',$edit,['id'=>$webOrder])['code']===303,'09 admin menyelesaikan servis dengan garansi dan biaya');
    $final=$http->request('invoice','GET',[],['id'=>$webOrder]);
    check(str_contains($final['body'],'Rp150.000') && str_contains($final['body'],'Selesai'),'09 konsumen melihat biaya dan status terbaru');
    check($http->request('service-edit','POST',$edit,['id'=>$webOrder])['code']===403,'11 konsumen tidak dapat POST edit melalui URL');
    $logout=$http->request('logout','POST',['csrf'=>$http->form('orders')]);check($logout['code']===303,'01 logout HTTP berhasil');
    check($http->request('invoice','GET',[],['id'=>$webOrder])['code']===303,'11 sesi setelah logout tidak dapat membuka invoice');
    Database::query('DELETE FROM login_limits');check($http->login('+6281822233344',$password)['code']===303 && $http->request('invoice','GET',[],['id'=>$webOrder])['code']===200,'02 data permanen setelah logout/login');
    $registration=new HttpClient();$regData=['csrf'=>$registration->form('register'),'name'=>'Registrasi HTTP','address'=>'Bandung','phone'=>'081233344455','password'=>$password,'password_confirm'=>$password];
    check($registration->request('register','POST',$regData)['code']===303,'01 registrasi HTTP berhasil');
    $duplicateClient=new HttpClient();$regData['csrf']=$duplicateClient->form('register');$regData['phone']='+6281233344455';check($duplicateClient->request('register','POST',$regData)['code']===422,'01 registrasi HTTP format duplikat ditolak');
    $notFound=$http->request('unknown');check($notFound['code']===404,'11 halaman 404 aman');
    check($http->request('orders','GET',[],['q'=>"' OR 1=1 --"])['code']===200,'11 SQL injection pencarian tidak merusak query');
    check($http->request('orders','GET',[],['q'=>['invalid']])['code']===400,'11 input array tidak valid ditolak aman');
    $custom=Orders::create(fixture(['item_name'=>'Lainnya','item_name_custom'=>'Mesin kopi','brand'=>'Lainnya','brand_custom'=>'Merek uji','accessories'=>['Remote','Lainnya'],'accessories_custom'=>'Tas perangkat']),$a);
    check(Orders::get($custom,$a)['item_name']==='Mesin kopi' && str_contains(Orders::get($custom,$a)['accessories'],'Tas perangkat'),'02 barang merk kelengkapan custom berfungsi');
    // Fixture untuk browser: hanya testing, password acak tidak ditulis ke laporan.
    Database::query('DELETE FROM login_limits');
    file_put_contents(__DIR__.'/local-fixtures.json',json_encode(['password'=>$password,'admin_phone'=>'08170002466','customer_phone'=>'081822233344','other_phone'=>'081911122233','own_order'=>$webOrder,'other_order'=>$ob,'part'=>$part],JSON_PRETTY_PRINT));
} catch(Throwable $e) { check(false,'RUNTIME '.get_class($e).' '.$e->getMessage().' '.$e->getFile().':'.$e->getLine()); }
$summary=['pass'=>count(array_filter($results,fn($r)=>$r['status']==='PASS')),'fail'=>count(array_filter($results,fn($r)=>$r['status']==='FAIL')),'blocked'=>0];
file_put_contents(__DIR__.'/artifacts/integration-results.json',json_encode(['run_at'=>date(DATE_ATOM),'database'=>getenv('DB_NAME'),'summary'=>$summary,'results'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode($summary).PHP_EOL;exit($summary['fail']?1:0);
