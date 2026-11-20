<?php
// Migration simulation and all data changes are limited to the guarded testing DB.
if(getenv('APP_ENV')!=='testing'||!str_ends_with((string)getenv('DB_NAME'),'_test'))throw new RuntimeException('Part type QA requires testing database.');
require_once ROOT.'/app/Migrations.php';
$typePart=Inventory::save(null,partData('TYPE-QA'),$admin);Inventory::receive($typePart,5,'Pemasok lama',token(),$admin);
$typeOldPart=Database::one('SELECT * FROM spare_parts WHERE id=?',[$typePart]);$typeOldMovements=Database::all('SELECT * FROM stock_movements WHERE spare_part_id=?',[$typePart]);
Database::connection()->query('ALTER TABLE spare_parts DROP COLUMN part_type');Database::query('DELETE FROM schema_migrations WHERE version=?',[4]);
Migrations::apply();
$typeMigrated=Database::one('SELECT * FROM spare_parts WHERE id=?',[$typePart]);
check($typeMigrated['part_type']===''&&stock($typePart)===5&&$typeMigrated['name']===$typeOldPart['name'],'32 upgrade v3 ke v4 menambah tipe kosong tanpa mengubah data lama');
check(Database::all('SELECT * FROM stock_movements WHERE spare_part_id=?',[$typePart])===$typeOldMovements,'32 migrasi tidak menafsirkan ulang atau mengubah histori pemasok lama');
check((int)Database::one('SELECT COUNT(*) n FROM schema_migrations WHERE version=?',[4])['n']===1,'32 migrasi mencatat versi 4 satu kali');
$typeRevision=fn()=> (int)Database::one('SELECT revision FROM spare_parts WHERE id=?',[$typePart])['revision'];
$typedKey=token();Inventory::receive($typePart,2,'TPA3116D2',$typedKey,$admin,'TPA3116D2');
check(stock($typePart)===7&&Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='TPA3116D2','32 stok masuk menyimpan tipe dan jumlah secara bersamaan');
$typeMovement=Database::one('SELECT * FROM stock_movements WHERE operation_key=?',[$typedKey]);
check($typeMovement['reason']==='Stok masuk: Tipe sparepart: TPA3116D2'&&(int)$typeMovement['delta']===2,'32 histori penerimaan mencatat tipe sebagai snapshot');
Inventory::receive($typePart,2,'TPA3116D2',$typedKey,$admin,'TPA3116D2');check(stock($typePart)===7&&(int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE operation_key=?',[$typedKey])['n']===1,'32 retry penerimaan tipe tidak menggandakan stok atau histori');
rejects(fn()=>Inventory::receive($typePart,2,'PAM8610',$typedKey,$admin,'PAM8610'),'32 token penerimaan tipe berbeda ditolak');
Inventory::save($typePart,partData('TYPE-QA')+['part_type'=>'PAM8610','revision'=>$typeRevision()],$admin);
check(Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PAM8610'&&stock($typePart)===7,'32 edit tipe menyimpan perubahan tanpa mengubah stok');
Inventory::receive($typePart,2,'TPA3116D2',$typedKey,$admin,'TPA3116D2');
check(Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PAM8610'&&stock($typePart)===7,'32 retry lama tidak mengembalikan tipe yang telah diedit');
check(Database::one('SELECT reason FROM stock_movements WHERE operation_key=?',[$typedKey])['reason']==='Stok masuk: Tipe sparepart: TPA3116D2','32 mengedit tipe tidak mengganti snapshot histori sebelumnya');
Inventory::save($typePart,partData('TYPE-QA')+['revision'=>$typeRevision()],$admin);
check(Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PAM8610','32 request metadata lama tanpa field tipe mempertahankan nilai yang sudah ada');
Migrations::apply();check(Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PAM8610'&&(int)Database::one('SELECT COUNT(*) n FROM schema_migrations WHERE version=?',[4])['n']===1,'32 migrasi ulang idempoten dan mempertahankan tipe');
Inventory::receive($typePart,1,'Pemasok untuk form lama',token(),$admin);check(stock($typePart)===8&&Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PAM8610','32 penerimaan format lama tidak menimpa tipe sparepart');
$typedNew=Inventory::save(null,partData('TYPE-NEW')+['part_type'=>'470 µF 25 V'],$admin);check(Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typedNew])['part_type']==='470 µF 25 V','32 tambah sparepart mendukung tipe bebas Unicode');
foreach(['',str_repeat('x',181)] as $badType)rejects(fn()=>Inventory::receive($typePart,1,'Invalid',token(),$admin,$badType),'32 tipe stok masuk kosong atau terlalu panjang ditolak');
rejects(fn()=>Inventory::save($typePart,partData('TYPE-QA')+['part_type'=>['bad'],'revision'=>$typeRevision()],$admin),'32 metadata tipe array ditolak');
$typeBeforeRevision=$typeRevision();
try{Inventory::receive($typePart,1,'NEW-TYPE',token(),['id'=>999999999,'role'=>'admin'],'NEW-TYPE');check(false,'32 gagal audit rollback tipe dan stok');}
catch(mysqli_sql_exception){check(stock($typePart)===8&&$typeRevision()===$typeBeforeRevision&&Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PAM8610','32 gagal audit rollback tipe dan stok');}
rejects(fn()=>Inventory::receive($typePart,1,'TPA',token(),$fixActor,'TPA'),'32 domain tipe stok masuk ditolak untuk pelanggan');
RecordDeletion::change('part',$typePart,true,$admin);rejects(fn()=>Inventory::receive($typePart,1,'TPA',token(),$admin,'TPA'),'32 tipe dan stok sparepart dihapus tidak dapat diubah');RecordDeletion::change('part',$typePart,false,$admin);

Database::query('DELETE FROM login_limits');$typeAdminHttp=new HttpClient();$typeAdminHttp->login('08170002466',$password,true);$typeParams=['id'=>$typePart];
$typePage=$typeAdminHttp->request('part','GET',[],$typeParams);
check(str_contains($typePage['body'],'Tipe sparepart')&&str_contains($typePage['body'],'name="part_type"')&&!str_contains($typePage['body'],'Keterangan / sumber')&&!str_contains($typePage['body'],'name="reason"'),'32 form stok masuk mengganti sumber dengan input tipe bebas');
check(str_contains($typePage['body'],'value="PAM8610"'),'32 form penerimaan mengisi tipe saat ini secara otomatis');
preg_match('~name="operation_key" value="([^"]+)"~',$typePage['body'],$typeKey);
$typePayload=['csrf'=>$typeAdminHttp->form('part',$typeParams),'action'=>'receive','operation_key'=>$typeKey[1],'quantity'=>'3','part_type'=>'T-CON V500'];
check($typeAdminHttp->request('part','POST',array_replace($typePayload,['csrf'=>'bad']),$typeParams)['code']===419&&stock($typePart)===8,'32 penerimaan tipe dilindungi CSRF');
check($typeAdminHttp->request('part','POST',$typePayload,$typeParams)['code']===303&&stock($typePart)===11&&Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='T-CON V500','32 penerimaan tipe HTTP tersimpan bersama stok');
$typeEditPage=$typeAdminHttp->request('part-edit','GET',[],$typeParams);check(str_contains($typeEditPage['body'],'name="part_type" value="T-CON V500"'),'32 edit sparepart menampilkan tipe tersimpan');
$typeEditPayload=partData('TYPE-QA')+['part_type'=>'PANEL-4K','revision'=>$typeRevision(),'csrf'=>$typeAdminHttp->form('part-edit',$typeParams)];
check($typeAdminHttp->request('part-edit','POST',$typeEditPayload,$typeParams)['code']===303&&Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typePart])['part_type']==='PANEL-4K'&&stock($typePart)===11,'32 admin dapat mengedit tipe melalui form tanpa mengubah jumlah');
check(str_contains($typeAdminHttp->request('parts','GET',[],['q'=>'PANEL-4K'])['body'],'Kapasitor TYPE-QA'),'32 daftar sparepart dapat dicari berdasarkan tipe');
$typeInvalidPayload=array_replace($typePayload,['quantity'=>'-1','part_type'=>'TYPE-PRESERVED','operation_key'=>token()]);$typeInvalidPage=$typeAdminHttp->request('part','POST',$typeInvalidPayload,$typeParams);
check($typeInvalidPage['code']===422&&str_contains($typeInvalidPage['body'],'value="TYPE-PRESERVED"')&&stock($typePart)===11,'32 validasi gagal mempertahankan input tipe tanpa mengubah stok');
foreach(['',str_repeat('x',181),['x']] as $badType){$badPayload=array_replace($typePayload,['part_type'=>$badType,'operation_key'=>token()]);check($typeAdminHttp->request('part','POST',$badPayload,$typeParams)['code']===422&&stock($typePart)===11,'32 HTTP menolak tipe tidak valid tanpa perubahan sebagian');}
$typeXss='<script>alert(32)</script>';$typeXssPayload=array_replace($typePayload,['part_type'=>$typeXss,'operation_key'=>token(),'quantity'=>'1']);$typeAdminHttp->request('part','POST',$typeXssPayload,$typeParams);$typeXssPage=$typeAdminHttp->request('part','GET',[],$typeParams);
check(str_contains($typeXssPage['body'],'&lt;script&gt;alert(32)&lt;/script&gt;')&&!str_contains($typeXssPage['body'],$typeXss),'32 tipe di-escape pada detail input dan histori');
$typedRacePart=Inventory::save(null,partData('TYPE-RACE'),$admin);$typedRacePayload=json_encode(['quantity'=>2,'type'=>'TPA3116D2','key'=>token()]);
$typedRace=concurrent([['receive-typed',$admin['id'],0,$typedRacePart,$typedRacePayload],['receive-typed',$admin['id'],0,$typedRacePart,$typedRacePayload]]);
check(count(array_filter($typedRace,fn($r)=>$r['ok']??false))===2&&stock($typedRacePart)===2&&(int)Database::one('SELECT COUNT(*) n FROM stock_movements WHERE spare_part_id=?',[$typedRacePart])['n']===1&&Database::one('SELECT part_type FROM spare_parts WHERE id=?',[$typedRacePart])['part_type']==='TPA3116D2','32 dua request tipe identik bersamaan hanya satu penambahan stok');
Database::query('DELETE FROM login_limits');
