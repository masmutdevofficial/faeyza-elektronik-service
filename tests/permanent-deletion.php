<?php
if(getenv('APP_ENV')!=='testing'||!str_ends_with((string)getenv('DB_NAME'),'_test'))throw new RuntimeException('Permanent deletion QA requires testing database.');
Database::query('DELETE FROM login_limits');
$purgeAdmin=new HttpClient();$purgeAdmin->login('08170002466',$password,true);
$purgeNumber=0;
$purgeCustomer=function()use(&$purgeNumber,$password){
    $phone='08129977'.str_pad((string)++$purgeNumber,4,'0',STR_PAD_LEFT);
    $uid=Auth::register(['phone'=>$phone,'password'=>$password,'name'=>'Hapus permanen QA '.$purgeNumber,'address'=>'Alamat hapus QA']);
    return Database::one('SELECT u.*,c.id customer_id FROM users u JOIN customers c ON c.user_id=u.id WHERE u.id=?',[$uid]);
};
$purgeOrder=fn($owner,$status='Diterima')=>Orders::create(fixture(['customer_id'=>$owner['customer_id'],'customer_name'=>'Hapus permanen QA','contact_phone'=>$owner['phone'],'status'=>$status,'completed_date'=>in_array($status,Orders::COMPLETED_STATUSES,true)?Clock::today():null]),$admin);
$purgeRevision=fn($oid)=>(int)Database::one('SELECT revision FROM service_orders WHERE id=?',[$oid])['revision'];
$purgeOwner=$purgeCustomer();$purgeOther=$purgeCustomer();
$purgePending=$purgeOrder($purgeOwner);$purgeFinal=$purgeOrder($purgeOwner,'Selesai');$purgePicked=$purgeOrder($purgeOwner,'Sudah Diambil');$purgeCancelled=$purgeOrder($purgeOwner);
$purgeForeign=$purgeOrder($purgeOther);$purgeInvoice=Orders::get($purgePending,$admin)['invoice_number'];
$purgePart=Inventory::save(null,partData('PURGE-QA'),$admin);Inventory::receive($purgePart,20,'QA hapus permanen',token(),$admin);
foreach([[$purgePending,2],[$purgeFinal,3],[$purgePicked,1],[$purgeCancelled,1]] as [$oid,$qty])Inventory::allocate($oid,$purgePart,$qty,0,$admin);
Orders::changeStatus($purgeCancelled,['revision'=>$purgeRevision($purgeCancelled),'new_status'=>'Dibatalkan','confirm_cancel'=>'1'],$admin);
check(stock($purgePart)===14,'33 setup stok mencatat penggunaan final dan pengembalian batal');
$purgeClient=new HttpClient();$purgeClient->login($purgeOwner['phone'],$password);$purgePeer=new HttpClient();$purgePeer->login($purgeOther['phone'],$password);
$purgeGuest=new HttpClient();
check($purgeGuest->request('invoice-delete','GET',[],['id'=>$purgePending])['code']===303,'33 hapus invoice memerlukan login admin');
foreach([$purgeClient,$purgePeer] as $client)foreach(['GET','POST'] as $verb)
    check($client->request('invoice-delete',$verb,$verb==='POST'?['csrf'=>$client->form('dashboard'),'confirm'=>'1','revision'=>'1']:[],['id'=>$purgePending])['code']===403,'33 pelanggan tidak dapat menghapus invoice '.$verb);
rejects(fn()=>RecordDeletion::purgeInvoice($purgePending,$purgeRevision($purgePending),$purgeOwner),'33 domain hapus invoice menolak pelanggan');
rejects(fn()=>RecordDeletion::purgeCustomer((int)$purgeOwner['customer_id'],$purgeOwner),'33 domain hapus akun menolak pelanggan');
check(!str_contains($purgeClient->request('invoice','GET',[],['id'=>$purgePending])['body'],'Hapus invoice'),'33 tombol hapus invoice tersembunyi untuk pelanggan');
foreach([$webOrder,$purgePending] as $oid)check(str_contains($purgeAdmin->request('invoice','GET',[],['id'=>$oid])['body'],'page=invoice-delete'),'33 tombol hapus tersedia untuk invoice lama maupun baru');
check(str_contains($purgeAdmin->request('invoices','GET',[],['q'=>$purgeInvoice])['body'],'Hapus invoice '.$purgeInvoice),'33 daftar invoice menyediakan tombol hapus');
$purgeParams=['id'=>$purgePending];$purgePage=$purgeAdmin->request('invoice-delete','GET',[],$purgeParams);
check($purgePage['code']===200&&str_contains($purgePage['body'],'Tidak bisa dipulihkan')&&stock($purgePart)===14,'33 GET konfirmasi invoice tidak mengubah data');
$purgeData=['csrf'=>$purgeAdmin->form('invoice-delete',$purgeParams),'confirm'=>'1','revision'=>$purgeRevision($purgePending)];
check($purgeAdmin->request('invoice-delete','POST',array_replace($purgeData,['csrf'=>'bad']),$purgeParams)['code']===419,'33 hapus invoice dilindungi CSRF');
$purgeNoConfirm=$purgeData;unset($purgeNoConfirm['confirm']);check($purgeAdmin->request('invoice-delete','POST',$purgeNoConfirm,$purgeParams)['code']===422,'33 hapus invoice wajib konfirmasi');
check($purgeAdmin->request('invoice-delete','POST',array_replace($purgeData,['revision'=>['bad']]),$purgeParams)['code']===422,'33 revision invoice invalid ditolak');
Orders::update($purgePending,editData($purgePending,$admin,['notes'=>'Perubahan sesudah konfirmasi']),$admin);
check($purgeAdmin->request('invoice-delete','POST',$purgeData,$purgeParams)['code']===422&&stock($purgePart)===14,'33 formulir hapus stale ditolak tanpa perubahan stok');
$purgeData['revision']=$purgeRevision($purgePending);
check($purgeAdmin->request('invoice-delete','POST',$purgeData,$purgeParams)['code']===303&&Orders::get($purgePending,$admin)===null&&stock($purgePart)===16,'33 hapus invoice pending menghapus servis dan mengembalikan stok');
check(Database::one('SELECT id FROM users WHERE id=?',[$purgeOwner['id']])!==null&&Orders::get($purgeFinal,$admin)!==null,'33 hapus satu invoice mempertahankan akun dan invoice lain');
check(Database::one('SELECT id FROM service_order_parts WHERE service_order_id=?',[$purgePending])===null&&Database::one('SELECT id FROM stock_movements WHERE service_order_id=?',[$purgePending])===null,'33 alokasi dihapus dan histori diputus dari invoice');
check((int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE spare_part_id=? AND reason LIKE ? AND service_order_id IS NULL',[$purgePart,'Pengembalian: servis dihapus%'])['n']===1,'33 histori pengembalian anonim tetap tersimpan');
check($purgeAdmin->request('invoice-delete','POST',$purgeData,$purgeParams)['code']===404&&stock($purgePart)===16,'33 replay hapus invoice tidak mengembalikan stok dua kali');
foreach(['invoice','order','success','service-edit','service-status','invoice-delete'] as $route)check($purgeAdmin->request($route,'GET',[],$purgeParams)['code']===404,'33 URL invoice terhapus tidak dapat dibuka '.$route);
check(Orders::listing($admin,$purgeInvoice,null,1,true)===[],'33 invoice terhapus hilang dari pencarian');
$purgeNew=$purgeOrder($purgeOwner);check(Orders::get($purgeNew,$admin)['invoice_number']!==$purgeInvoice,'33 nomor invoice terhapus tidak digunakan ulang');
RecordDeletion::purgeInvoice($purgeFinal,$purgeRevision($purgeFinal),$admin);check(stock($purgePart)===16&&Orders::get($purgeFinal,$admin)===null,'33 hapus invoice selesai mempertahankan pemakaian stok');
RecordDeletion::purgeInvoice($purgePicked,$purgeRevision($purgePicked),$admin);check(stock($purgePart)===16,'33 hapus invoice sudah diambil tidak menambah stok');
RecordDeletion::purgeInvoice($purgeCancelled,$purgeRevision($purgeCancelled),$admin);check(stock($purgePart)===16,'33 hapus invoice batal tidak mengembalikan stok ulang');

$purgeLegacy=$purgeCustomer();$purgeLegacyOrder=$purgeOrder($purgeLegacy);Inventory::allocate($purgeLegacyOrder,$purgePart,2,0,$admin);RecordDeletion::change('customer',(int)$purgeLegacy['customer_id'],true,$admin);
$purgeLegacyView=$purgeAdmin->request('customer','GET',[],['id'=>$purgeLegacy['customer_id']]);
check(str_contains($purgeLegacyView['body'],'Hapus permanen')&&$purgeAdmin->request('customer-delete','GET',[],['id'=>$purgeLegacy['customer_id']])['code']===200,'33 konsumen arsip versi lama memiliki aksi hapus permanen');
$purgeLegacyData=['csrf'=>$purgeAdmin->form('customer-delete',['id'=>$purgeLegacy['customer_id']]),'confirm'=>'1'];
check($purgeAdmin->request('customer-delete','POST',$purgeLegacyData,['id'=>$purgeLegacy['customer_id']])['code']===303&&Database::one('SELECT id FROM users WHERE id=?',[$purgeLegacy['id']])===null&&stock($purgePart)===16,'33 hapus permanen konsumen arsip membersihkan akun dan mengembalikan stok');
check(Database::one('SELECT id FROM record_events WHERE customer_id=?',[$purgeLegacy['customer_id']])===null&&Orders::get($purgeLegacyOrder,$admin)===null,'33 audit profil lama dan invoice konsumen arsip dibersihkan');

$purgeActive=$purgeOrder($purgeOwner);$purgeDone=$purgeOrder($purgeOwner,'Selesai');Inventory::allocate($purgeActive,$purgePart,2,0,$admin);Inventory::allocate($purgeDone,$purgePart,1,0,$admin);
$purgeBefore=(int)Orders::summary($admin)['total'];$purgeCount=(int)Database::one('SELECT COUNT(*) n FROM service_orders WHERE customer_id=?',[$purgeOwner['customer_id']])['n'];
$purgeCustomerParams=['id'=>$purgeOwner['customer_id']];$purgeCustomerForm=$purgeAdmin->request('customer-delete','GET',[],$purgeCustomerParams);
check(str_contains($purgeCustomerForm['body'],'seluruh '.$purgeCount.' invoice')&&str_contains($purgeCustomerForm['body'],'dihapus permanen'),'33 konfirmasi konsumen menyebut seluruh invoice dan permanen');
$purgeCustomerData=['csrf'=>$purgeAdmin->form('customer-delete',$purgeCustomerParams),'confirm'=>'1'];
check($purgeAdmin->request('customer-delete','POST',array_replace($purgeCustomerData,['csrf'=>'bad']),$purgeCustomerParams)['code']===419,'33 hapus akun dilindungi CSRF');
check($purgeAdmin->request('customer-delete','POST',['csrf'=>$purgeCustomerData['csrf']],$purgeCustomerParams)['code']===422,'33 hapus akun memerlukan konfirmasi');
check($purgeAdmin->request('customer-delete','POST',$purgeCustomerData,$purgeCustomerParams)['code']===303,'33 hapus akun aktif berhasil melalui HTTP');
check(Database::one('SELECT id FROM users WHERE id=?',[$purgeOwner['id']])===null&&Database::one('SELECT id FROM customers WHERE id=?',[$purgeOwner['customer_id']])===null,'33 akun hash password kontak dan profil benar-benar dihapus');
check(Database::one('SELECT id FROM service_orders WHERE customer_id=?',[$purgeOwner['customer_id']])===null&&Database::one('SELECT id FROM service_order_parts WHERE service_order_id IN (?,?,?)',[$purgeNew,$purgeActive,$purgeDone])===null,'33 semua invoice servis dan alokasi milik akun dihapus');
check(stock($purgePart)===15&&(int)Database::one('SELECT SUM(delta) n FROM stock_movements WHERE spare_part_id=?',[$purgePart])['n']===15,'33 hapus akun mengembalikan pending dan mempertahankan stok final dengan ledger utuh');
check($purgeClient->request('dashboard')['code']===303&&$purgeClient->request('account')['code']===303,'33 sesi lama akun terhapus tidak dapat dipakai');
check(Database::one('SELECT bucket FROM login_limits WHERE bucket=?',[hash('sha256','phone:'.$purgeOwner['phone'])])===null,'33 bucket nomor akun dihapus tanpa menghapus rate limit IP');
rejects(fn()=>Auth::login($purgeOwner['phone'],$password,'purge-login'),'33 akun terhapus tidak dapat login');
check((int)Orders::summary($admin)['total']===$purgeBefore-$purgeCount&&Orders::get($purgeForeign,$admin)!==null&&Database::one('SELECT role FROM users WHERE id=?',[$admin['id']])['role']==='admin','33 ringkasan berkurang dan data konsumen lain serta admin tetap utuh');
$purgeRecreated=Auth::register(['phone'=>$purgeOwner['phone'],'password'=>$password,'name'=>'Daftar ulang QA','address'=>'QA']);check($purgeRecreated!==(int)$purgeOwner['id'],'33 nomor akun terhapus dapat daftar baru tanpa mewarisi invoice atau sesi');

$purgeRollback=$purgeCustomer();$purgeRollbackOrder=$purgeOrder($purgeRollback);Inventory::allocate($purgeRollbackOrder,$purgePart,2,0,$admin);$purgeRollbackStock=stock($purgePart);$purgeRollbackLedger=Database::all('SELECT * FROM stock_movements WHERE spare_part_id=? ORDER BY id',[$purgePart]);
// Simulate an unexpected foreign reference to prove the whole transaction rolls back.
Database::query('INSERT INTO record_events(spare_part_id,actor_id,action) VALUES (?,?,?)',[$purgePart,$purgeRollback['id'],'deleted']);$purgeBlock=Database::id();
rejects(fn()=>RecordDeletion::purgeCustomer((int)$purgeRollback['customer_id'],$admin),'33 kegagalan relasi menolak hapus akun secara menyeluruh');
check(Orders::get($purgeRollbackOrder,$admin)!==null&&Database::one('SELECT id FROM users WHERE id=?',[$purgeRollback['id']])!==null&&stock($purgePart)===$purgeRollbackStock&&Database::all('SELECT * FROM stock_movements WHERE spare_part_id=? ORDER BY id',[$purgePart])===$purgeRollbackLedger,'33 rollback memulihkan akun invoice alokasi dan histori tanpa perubahan sebagian');
Database::query('DELETE FROM record_events WHERE id=?',[$purgeBlock]);RecordDeletion::purgeCustomer((int)$purgeRollback['customer_id'],$admin);

$purgeRaceOwner=$purgeCustomer();$purgeRaceOrder=$purgeOrder($purgeRaceOwner);Inventory::allocate($purgeRaceOrder,$purgePart,2,0,$admin);$purgeRaceStock=stock($purgePart);$purgeRaceRev=$purgeRevision($purgeRaceOrder);
$purgeRace=concurrent([['purge-invoice',$admin['id'],$purgeRaceOrder,0,$purgeRaceRev],['purge-invoice',$admin['id'],$purgeRaceOrder,0,$purgeRaceRev]]);
check(count(array_filter($purgeRace,fn($r)=>$r['ok']??false))===2&&Orders::get($purgeRaceOrder,$admin)===null&&stock($purgePart)===$purgeRaceStock+2,'33 dua request hapus invoice hanya mengembalikan stok satu kali');
$purgeRaceOrder=$purgeOrder($purgeRaceOwner);Inventory::allocate($purgeRaceOrder,$purgePart,2,0,$admin);$purgeRaceStock=stock($purgePart);
$purgeRace=concurrent([['purge-customer',$admin['id'],0,0,$purgeRaceOwner['customer_id']],['allocate',$admin['id'],$purgeRaceOrder,$purgePart,3]]);
check(($purgeRace[0]['ok']??false)&&Orders::get($purgeRaceOrder,$admin)===null&&stock($purgePart)===$purgeRaceStock+2,'33 race hapus akun dan ubah alokasi tidak kehilangan stok');
$purgeRaceOwner=$purgeCustomer();
$purgeRace=concurrent([['purge-customer',$admin['id'],0,0,$purgeRaceOwner['customer_id']],['create-order',$admin['id'],0,0,$purgeRaceOwner['id']]]);
check(($purgeRace[0]['ok']??false)&&Database::one('SELECT id FROM service_orders WHERE customer_id=?',[$purgeRaceOwner['customer_id']])===null&&Database::one('SELECT id FROM users WHERE id=?',[$purgeRaceOwner['id']])===null,'33 race hapus akun dan submit servis tidak menyisakan invoice orphan');
Database::query('INSERT INTO customers(user_id,name,address) VALUES (?,?,?)',[$admin['id'],'Admin guard QA','QA']);$purgeAdminGuard=Database::id();
rejects(fn()=>RecordDeletion::purgeCustomer($purgeAdminGuard,$admin),'33 akun admin dilindungi meskipun ada relasi profil konsumen invalid');
check(Database::one('SELECT role FROM users WHERE id=?',[$admin['id']])['role']==='admin','33 penolakan hapus akun admin tidak mengubah akun');Database::query('DELETE FROM customers WHERE id=?',[$purgeAdminGuard]);
// Independent records for the browser suite; no production data or account is used.
$purgeUiLegacy=$purgeCustomer();$purgeUiLegacyOrder=$purgeOrder($purgeUiLegacy);RecordDeletion::change('customer',(int)$purgeUiLegacy['customer_id'],true,$admin);
$purgeUiOwner=$purgeCustomer();$purgeUiPending=$purgeOrder($purgeUiOwner);$purgeUiDone=$purgeOrder($purgeUiOwner,'Selesai');
$purgeUiPart=Inventory::save(null,partData('PURGE-UI'),$admin);Inventory::receive($purgeUiPart,10,'Browser QA',token(),$admin);Inventory::allocate($purgeUiPending,$purgeUiPart,2,0,$admin);Inventory::allocate($purgeUiDone,$purgeUiPart,3,0,$admin);
file_put_contents(__DIR__.'/local-purge-fixtures.json',json_encode(['legacy_customer'=>$purgeUiLegacy['customer_id'],'legacy_order'=>$purgeUiLegacyOrder,'legacy_invoice'=>Orders::get($purgeUiLegacyOrder,$admin)['invoice_number'],'customer'=>$purgeUiOwner['customer_id'],'phone'=>$purgeUiOwner['phone'],'pending'=>$purgeUiPending,'done'=>$purgeUiDone,'pending_invoice'=>Orders::get($purgeUiPending,$admin)['invoice_number'],'part'=>$purgeUiPart],JSON_PRETTY_PRINT));
Database::query('DELETE FROM login_limits');
