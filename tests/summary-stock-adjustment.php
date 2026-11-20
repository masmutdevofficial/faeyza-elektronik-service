<?php
// New records only, in the guarded faeyza_test database managed by run.php.
Database::query('DELETE FROM login_limits');
$fixHttp=new HttpClient();$fixHttp->login('08170002466',$password,true);
$fixUid=Auth::register(['name'=>'Ringkasan Hapus QA','phone'=>'081288880020','address'=>'Alamat QA','password'=>$password]);
$fixActor=Database::one('SELECT * FROM users WHERE id=?',[$fixUid]);$fixCustomer=Orders::customer($fixActor);
$fixBefore=Orders::summary($admin);$fixOrders=[];
foreach(Orders::STATUSES as $fixStatus) $fixOrders[$fixStatus]=Orders::create(fixture(['customer_id'=>$fixCustomer['id'],'customer_name'=>'Ringkasan Hapus QA','status'=>$fixStatus,'completed_date'=>$today,'warranty_date'=>$warranty]),$admin);
$fixAdded=Orders::summary($admin);
check((int)$fixAdded['total']===(int)$fixBefore['total']+7&&(int)$fixAdded['working']===(int)$fixBefore['working']+4&&(int)$fixAdded['done']===(int)$fixBefore['done']+2,'30 sebelum hapus tujuh status dihitung dalam kelompok yang benar');
RecordDeletion::change('customer',(int)$fixCustomer['id'],true,$admin);
check(Orders::summary($admin)===$fixBefore,'30 menghapus konsumen mengeluarkan seluruh servisnya dari ringkasan');
check(Orders::listing($admin,'Ringkasan Hapus QA')===[],'30 daftar servis aktif tidak memuat konsumen dihapus');
check(count(Orders::listing($admin,'Ringkasan Hapus QA',null,1,true))===7,'30 admin tetap dapat mencari tujuh invoice historis');
check(Orders::listing($fixActor,'',null,1,true)===[]&&array_sum(array_map('intval',Orders::summary($fixActor)))===0,'30 flag arsip tidak membuka akses pelanggan yang dihapus');
$fixDashboard=$fixHttp->request('dashboard');$fixList=$fixHttp->request('orders','GET',[],['q'=>'Ringkasan Hapus QA']);$fixInvoiceList=$fixHttp->request('invoices','GET',[],['q'=>'Ringkasan Hapus QA']);
check(!str_contains($fixDashboard['body'],'Ringkasan Hapus QA')&&!str_contains($fixList['body'],'>Ringkasan Hapus QA<'),'30 HTTP ringkasan dan servis terbaru tidak menampilkan konsumen dihapus');
check(str_contains($fixInvoiceList['body'],'Ringkasan Hapus QA')&&$fixHttp->request('invoice','GET',[],['id'=>$fixOrders['Selesai']])['code']===200,'30 HTTP invoice lama tetap tersedia untuk admin');
RecordDeletion::change('customer',(int)$fixCustomer['id'],false,$admin);
check(Orders::summary($admin)===$fixAdded&&count(Orders::listing($admin,'Ringkasan Hapus QA'))===7,'30 pulihkan konsumen mengembalikan servis ke ringkasan tanpa duplikasi');
$fixActiveCustomers=Database::all('SELECT id FROM customers WHERE deleted_at IS NULL');
try {
    foreach($fixActiveCustomers as $fixRow)RecordDeletion::change('customer',(int)$fixRow['id'],true,$admin);
    check(array_sum(array_map('intval',Orders::summary($admin)))===0&&Orders::listing($admin)===[],'30 semua konsumen dihapus menghasilkan semua hitungan servis nol');
    $fixEmpty=$fixHttp->request('dashboard');
    preg_match_all('~<a class="stat-card"[^>]*><p>[^<]+</p><strong>(\d+)</strong>~',$fixEmpty['body'],$fixEmptyCards);
    check($fixEmpty['code']===200&&$fixEmptyCards[1]===['0','0','0','0']&&str_contains($fixEmpty['body'],'Belum ada servis'),'30 HTTP seluruh kartu nol dan empty state setelah semua konsumen dihapus');
    check((int)Database::one('SELECT COUNT(*) n FROM customers WHERE deleted_at IS NULL')['n']===0&&$fixHttp->request('invoice','GET',[],['id'=>$fixOrders['Selesai']])['code']===200,'30 menghapus semua konsumen tetap mempertahankan invoice dan relasi');
} finally { foreach($fixActiveCustomers as $fixRow)RecordDeletion::change('customer',(int)$fixRow['id'],false,$admin); }

$adjustPart=Inventory::save(null,partData('ADJUST-QA'),$admin);Inventory::receive($adjustPart,10,'Stok penyesuaian QA',token(),$admin);
$adjustOrder=Orders::create(fixture(['customer_id'=>$fixCustomer['id'],'customer_name'=>'Koreksi stok QA','status'=>'Diterima']),$admin);
Inventory::allocate($adjustOrder,$adjustPart,2,0,$admin);
$adjustRevision=fn()=> (int)Database::one('SELECT revision FROM spare_parts WHERE id=?',[$adjustPart])['revision'];
$adjustKey=token();$adjustOriginalRevision=$adjustRevision();
Inventory::adjust($adjustPart,3,$adjustOriginalRevision,'Hasil hitung fisik',$adjustKey,$admin);
check(stock($adjustPart)===3,'31 koreksi stok tersedia 8 menjadi 3 mengurangi selisih 5');
$adjustMove=Database::one('SELECT * FROM stock_movements WHERE operation_key=?',[$adjustKey]);
check((int)$adjustMove['delta']===-5&&(int)$adjustMove['balance_after']===3&&(int)$adjustMove['actor_id']===(int)$admin['id']&&str_contains($adjustMove['reason'],'8 → 3'),'31 koreksi mencatat delta saldo alasan dan admin');
Inventory::adjust($adjustPart,3,$adjustOriginalRevision,'Hasil hitung fisik',$adjustKey,$admin);
check((int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE operation_key=?',[$adjustKey])['n']===1&&stock($adjustPart)===3,'31 retry koreksi tidak mengurangi stok atau menggandakan audit');
Inventory::receive($adjustPart,1,'Stok sesudah koreksi',token(),$admin);
Inventory::adjust($adjustPart,3,$adjustOriginalRevision,'Hasil hitung fisik',$adjustKey,$admin);
check(stock($adjustPart)===4,'31 retry koreksi lama tidak menimpa stok dari transaksi berikutnya');
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustRevision(),'Alasan berbeda',$adjustKey,$admin),'31 penggunaan ulang token untuk payload berbeda ditolak');
$adjustOther=Inventory::save(null,partData('ADJUST-OTHER'),$admin);
rejects(fn()=>Inventory::adjust($adjustOther,3,1,'Hasil hitung fisik',$adjustKey,$admin),'31 token koreksi tidak dapat dipakai pada sparepart lain');
Inventory::adjust($adjustPart,0,$adjustRevision(),'Stok habis setelah pemeriksaan',token(),$admin);
check(stock($adjustPart)===0,'31 stok tersedia dapat diset menjadi nol');
check((int)Database::one('SELECT quantity FROM service_order_parts WHERE service_order_id=? AND spare_part_id=?',[$adjustOrder,$adjustPart])['quantity']===2,'31 koreksi stok tidak menghapus atau mengganti pemakaian servis');
rejects(fn()=>Inventory::allocate($adjustOrder,$adjustPart,3,2,$admin),'31 alokasi baru ditolak setelah stok diset habis');
Orders::changeStatus($adjustOrder,['revision'=>Orders::get($adjustOrder,$admin)['revision'],'new_status'=>'Dibatalkan','confirm_cancel'=>'1'],$admin);
check(stock($adjustPart)===2,'31 pembatalan tetap mengembalikan dua barang yang sebelumnya dipakai');
Inventory::adjust($adjustPart,5,$adjustRevision(),'Koreksi barang ditemukan',token(),$admin);check(stock($adjustPart)===5,'31 penyesuaian juga mendukung koreksi kenaikan stok');
$adjustStale=$adjustRevision();Inventory::receive($adjustPart,1,'Membuat formulir kedaluwarsa',token(),$admin);
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustStale,'Stale',token(),$admin),'31 formulir stok kedaluwarsa tidak menimpa transaksi baru');
check(stock($adjustPart)===6,'31 penolakan formulir stale mempertahankan stok aktual');
foreach([-1,1000000001] as $invalidStock)rejects(fn()=>Inventory::adjust($adjustPart,$invalidStock,$adjustRevision(),'Invalid',token(),$admin),'31 stok di luar batas ditolak '.$invalidStock);
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustRevision(),' ',token(),$admin),'31 alasan koreksi wajib diisi');
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustRevision(),str_repeat('x',181),token(),$admin),'31 alasan terlalu panjang ditolak');
rejects(fn()=>Inventory::adjust($adjustPart,6,$adjustRevision(),'Tidak berubah',token(),$admin),'31 saldo sama tidak membuat pergerakan stok palsu');
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustRevision(),'Token salah','invalid',$admin),'31 token koreksi tidak valid ditolak');
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustRevision(),'Bukan admin',token(),$fixActor),'31 konsumen tidak dapat melakukan koreksi stok');
RecordDeletion::change('part',$adjustPart,true,$admin);
rejects(fn()=>Inventory::adjust($adjustPart,0,$adjustRevision(),'Sparepart dihapus',token(),$admin),'31 sparepart dihapus harus dipulihkan sebelum dikoreksi');
check(!str_contains($fixHttp->request('part','GET',[],['id'=>$adjustPart])['body'],'Simpan penyesuaian stok'),'31 form koreksi tidak tersedia untuk sparepart dihapus');
RecordDeletion::change('part',$adjustPart,false,$admin);
$atomicStock=stock($adjustPart);$atomicRevision=$adjustRevision();$atomicMoves=(int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE spare_part_id=?',[$adjustPart])['n'];
try {Inventory::adjust($adjustPart,0,$atomicRevision,'Audit FK gagal',token(),['id'=>999999999,'role'=>'admin']);check(false,'31 gagal audit rollback seluruh perubahan');}
catch(mysqli_sql_exception){check(stock($adjustPart)===$atomicStock&&$adjustRevision()===$atomicRevision&&(int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE spare_part_id=?',[$adjustPart])['n']===$atomicMoves,'31 gagal audit rollback saldo revisi dan histori');}

$adjustParams=['id'=>$adjustPart];$adjustPage=$fixHttp->request('part','GET',[],$adjustParams);
check($adjustPage['code']===200&&str_contains($adjustPage['body'],'name="stock_quantity"')&&str_contains($adjustPage['body'],'min="0"'),'31 form HTTP menyediakan stok akhir termasuk nol');
preg_match('~name="stock_key" value="([^"]+)"~',$adjustPage['body'],$adjustWebKey);preg_match('~name="stock_revision" value="([^"]+)"~',$adjustPage['body'],$adjustWebRevision);
$adjustPayload=['csrf'=>$fixHttp->form('part',$adjustParams),'action'=>'adjust','stock_key'=>$adjustWebKey[1],'stock_revision'=>$adjustWebRevision[1],'stock_quantity'=>'2','stock_reason'=>'Koreksi <script>alert(1)</script>'];
check($fixHttp->request('part','POST',array_replace($adjustPayload,['csrf'=>'bad']),$adjustParams)['code']===419&&stock($adjustPart)===6,'31 POST koreksi tanpa CSRF ditolak tanpa mengubah stok');
$adjustConsumerHttp=new HttpClient();$adjustConsumerHttp->login('081288880020',$password);
check($adjustConsumerHttp->request('part','POST',array_replace($adjustPayload,['csrf'=>$adjustConsumerHttp->form('dashboard')]),$adjustParams)['code']===403,'31 endpoint koreksi menolak POST pelanggan');
check($fixHttp->request('part','POST',$adjustPayload,$adjustParams)['code']===303&&stock($adjustPart)===2,'31 admin menyimpan koreksi melalui form HTTP');
check($fixHttp->request('part','POST',$adjustPayload,$adjustParams)['code']===303&&stock($adjustPart)===2,'31 double submit form HTTP idempoten');
$adjustHistory=$fixHttp->request('part','GET',[],$adjustParams);
check(str_contains($adjustHistory['body'],'&lt;script&gt;alert(1)&lt;/script&gt;')&&!str_contains($adjustHistory['body'],'<script>alert(1)</script>'),'31 alasan koreksi di-escape pada histori stok');
foreach(['stock_quantity'=>['-1','1.5',['0']],'stock_revision'=>['0'],'stock_reason'=>[''],'stock_key'=>[['x']],'action'=>['unknown']] as $field=>$values)foreach($values as $badValue) {
    $badPayload=array_replace($adjustPayload,[$field=>$badValue,'stock_key'=>token()]);if($field==='stock_key')$badPayload[$field]=$badValue;
    check($fixHttp->request('part','POST',$badPayload,$adjustParams)['code']===422&&stock($adjustPart)===2,'31 HTTP menolak input koreksi invalid '.$field);
}
check($fixHttp->request('part-edit','POST',['csrf'=>$fixHttp->form('part-edit',$adjustParams),'sku'=>'','name'=>'','minimum_stock'=>'3','unit'=>'pcs','revision'=>$adjustRevision()],$adjustParams)['code']===422,'31 validasi edit metadata tidak menyebabkan warning field stok hilang');
check((int)Database::one('SELECT COALESCE(SUM(delta),0) n FROM stock_movements WHERE spare_part_id=?',[$adjustPart])['n']===stock($adjustPart),'31 total delta audit tetap sama dengan stok setelah seluruh koreksi');

$adjustRacePart=Inventory::save(null,partData('ADJUST-RACE'),$admin);Inventory::receive($adjustRacePart,10,'Race',token(),$admin);
$raceRevision=(int)Database::one('SELECT revision FROM spare_parts WHERE id=?',[$adjustRacePart])['revision'];
$adjustRace=concurrent([['adjust-part',$admin['id'],$raceRevision,$adjustRacePart,json_encode(['quantity'=>6,'key'=>token()])],['adjust-part',$admin['id'],$raceRevision,$adjustRacePart,json_encode(['quantity'=>3,'key'=>token()])]]);
check(count(array_filter($adjustRace,fn($r)=>$r['ok']??false))===1&&in_array(stock($adjustRacePart),[3,6],true)&&(int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE spare_part_id=?',[$adjustRacePart])['n']===2,'31 dua koreksi bersamaan hanya satu formulir menang tanpa lost update');
$raceRevision=(int)Database::one('SELECT revision FROM spare_parts WHERE id=?',[$adjustRacePart])['revision'];$sameRacePayload=json_encode(['quantity'=>0,'key'=>token()]);
$sameRace=concurrent([['adjust-part',$admin['id'],$raceRevision,$adjustRacePart,$sameRacePayload],['adjust-part',$admin['id'],$raceRevision,$adjustRacePart,$sameRacePayload]]);
check(count(array_filter($sameRace,fn($r)=>$r['ok']??false))===2&&stock($adjustRacePart)===0&&(int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE spare_part_id=?',[$adjustRacePart])['n']===3,'31 request koreksi identik bersamaan hanya satu pergerakan stok');
Inventory::receive($adjustRacePart,5,'Race koreksi dan penerimaan',token(),$admin);$raceRevision=(int)Database::one('SELECT revision FROM spare_parts WHERE id=?',[$adjustRacePart])['revision'];
$receiveRace=concurrent([['adjust-part',$admin['id'],$raceRevision,$adjustRacePart,json_encode(['quantity'=>1,'key'=>token()])],['receive-part',$admin['id'],0,$adjustRacePart,2]]);
check(($receiveRace[1]['ok']??false)&&stock($adjustRacePart)===(($receiveRace[0]['ok']??false)?3:7)&&(int)Database::one('SELECT SUM(delta) n FROM stock_movements WHERE spare_part_id=?',[$adjustRacePart])['n']===stock($adjustRacePart),'31 race penerimaan dan koreksi tidak menghilangkan stok masuk');
$allocationRacePart=Inventory::save(null,partData('ADJUST-ALLOCATE-RACE'),$admin);Inventory::receive($allocationRacePart,10,'Race penggunaan',token(),$admin);$raceRevision=(int)Database::one('SELECT revision FROM spare_parts WHERE id=?',[$allocationRacePart])['revision'];
$allocationRace=concurrent([['adjust-part',$admin['id'],$raceRevision,$allocationRacePart,json_encode(['quantity'=>0,'key'=>token()])],['allocate',$admin['id'],$fixOrders['Diterima'],$allocationRacePart,2]]);
check(count(array_filter($allocationRace,fn($r)=>$r['ok']??false))===1&&stock($allocationRacePart)===(($allocationRace[0]['ok']??false)?0:8)&&(int)Database::one('SELECT SUM(delta) n FROM stock_movements WHERE spare_part_id=?',[$allocationRacePart])['n']===stock($allocationRacePart),'31 race alokasi dan koreksi tidak membuat stok negatif atau menimpa penggunaan');
file_put_contents(__DIR__.'/local-summary-stock-fixtures.json',json_encode(['customer_id'=>$fixCustomer['id'],'customer_phone'=>'081288880020','orders'=>$fixOrders,'part'=>$adjustPart,'race_part'=>$adjustRacePart],JSON_PRETTY_PRINT));
Database::query('DELETE FROM login_limits');
