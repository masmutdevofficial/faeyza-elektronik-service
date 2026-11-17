<?php
function device_details(string $html): array {
    preg_match_all('~<dt>(.*?)</dt>\s*<dd>(.*?)</dd>~s',$html,$matches,PREG_SET_ORDER);
    $values=[];
    foreach($matches as $match)$values[html_entity_decode(strip_tags($match[1]),ENT_QUOTES|ENT_HTML5,'UTF-8')]=html_entity_decode(strip_tags($match[2]),ENT_QUOTES|ENT_HTML5,'UTF-8');
    return array_intersect_key($values,array_flip(['Nama barang','Merek','Model']));
}
Database::query('DELETE FROM login_limits');
$deviceClient=new HttpClient(); $deviceAdmin=new HttpClient(); $deviceOther=new HttpClient();
$deviceClient->login(Orders::customer($a)['phone'],$password);
$deviceRow=Orders::get($webOrder,$a);
$deviceExpected=['Nama barang'=>$deviceRow['item_name'],'Merek'=>$deviceRow['brand'],'Model'=>$deviceRow['model']];
$deviceResponse=$deviceClient->request('order','GET',[],['id'=>$webOrder]); $deviceShown=device_details($deviceResponse['body']);
foreach($deviceExpected as $label=>$value)check($deviceResponse['code']===200&&($deviceShown[$label]??null)===$value,'21 rincian pelanggan menampilkan '.$label.' sesuai data servis');
check(device_details($deviceClient->request('success','GET',[],['id'=>$webOrder])['body'])===$deviceExpected,'21 halaman sukses juga menampilkan rincian perangkat');
$deviceAdmin->login('08170002466',$password,true);
check(device_details($deviceAdmin->request('order','GET',[],['id'=>$webOrder])['body'])===$deviceExpected,'21 rincian admin dan pelanggan konsisten');
$deviceMalicious='<img src=x onerror=alert(1)>';
$deviceEdgeId=Orders::create(fixture(['item_name'=>'Lainnya','item_name_custom'=>'Perangkat '.$deviceMalicious,'brand'=>'Lainnya','brand_custom'=>'Merek '.$deviceMalicious,'model'=>'Model '.$deviceMalicious]),$a);
$deviceEdgeExpected=['Nama barang'=>'Perangkat '.$deviceMalicious,'Merek'=>'Merek '.$deviceMalicious,'Model'=>'Model '.$deviceMalicious];
$deviceEdgeHtml=$deviceClient->request('order','GET',[],['id'=>$deviceEdgeId])['body'];
check(device_details($deviceEdgeHtml)===$deviceEdgeExpected&&!str_contains($deviceEdgeHtml,$deviceMalicious),'21 rincian barang merek model custom di-escape tanpa mengubah isi');
$deviceOther->login(Orders::customer($b)['phone'],$password);
check($deviceOther->request('order','GET',[],['id'=>$deviceEdgeId])['code']===404,'21 rincian perangkat tetap menolak akun pelanggan lain');
Database::query('DELETE FROM login_limits');
