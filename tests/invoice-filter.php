<?php
$filterPhone='081256780001';$filterUid=Auth::register(['name'=>'Filter QA','phone'=>$filterPhone,'password'=>$password,'address'=>'Alamat filter QA']);
$filterActor=Database::one('SELECT * FROM users WHERE id=?',[$filterUid]);$filterOwner=Orders::customer($filterActor);
$filterIds=[];
foreach(Orders::STATUSES as $filterStatus) {
    $filterIds[$filterStatus]=Orders::create(fixture(['customer_id'=>$filterOwner['id'],'customer_name'=>'Filter QA','status'=>$filterStatus,'completed_date'=>$today,'warranty_date'=>$warranty]),$admin);
}
$filterForeign=Orders::create(fixture(['customer_id'=>Orders::customer($b)['id'],'customer_name'=>'Filter QA','status'=>'Diterima']),$admin);
Database::query('DELETE FROM login_limits');
$filterAdminHttp=new HttpClient();$filterAdminHttp->login('08170002466',$password,true);
$filterCustomerHttp=new HttpClient();$filterCustomerHttp->login($filterPhone,$password);
foreach(['admin'=>$filterAdminHttp,'customer'=>$filterCustomerHttp] as $filterRole=>$filterHttp) {
    foreach(Orders::STATUSES as $filterStatus) {
        $response=$filterHttp->request('orders','GET',[],['q'=>'Filter QA','status'=>$filterStatus]);
        preg_match_all('~<span class="badge [^"]*">([^<]+)</span>~',$response['body'],$matches);
        $expected=$filterRole==='admin'&&$filterStatus==='Diterima'?2:1;
        check($response['code']===200&&count($matches[1])===$expected&&array_unique($matches[1])===[$filterStatus],'27 filter '.$filterRole.' hanya menampilkan '.$filterStatus);
    }
}
$allFilters=$filterAdminHttp->request('orders','GET',[],['q'=>'Filter QA']);preg_match_all('~<span class="badge [^"]*">([^<]+)</span>~',$allFilters['body'],$allBadges);
check(count($allBadges[1])===8,'27 Semua status mengembalikan seluruh hasil pencarian admin');
check(str_contains($allFilters['body'],'data-status-filter'),'27 select filter admin terhubung ke auto-submit');
$ownFilters=$filterCustomerHttp->request('orders','GET',[],['q'=>'Filter QA']);preg_match_all('~<span class="badge [^"]*">([^<]+)</span>~',$ownFilters['body'],$ownBadges);
check(count($ownBadges[1])===7&&!str_contains($ownFilters['body'],'page=order&amp;id='.$filterForeign.'"'),'27 filter pelanggan tidak membuka servis akun lain');
check($filterAdminHttp->request('orders','GET',[],['status'=>['Selesai']])['code']===400&&$filterAdminHttp->request('orders','GET',[],['status'=>'Status palsu'])['code']===400,'27 filter status invalid ditolak aman');
$savedQuery=$_GET;$_GET=['page'=>'orders','q'=>'Filter QA','status'=>'Diterima','p'=>2];
$statusLink=status_action(Orders::get($filterIds['Diterima'],$admin));$_GET=$savedQuery;
preg_match('~href="([^"]+)"~',$statusLink,$hrefMatch);parse_str(parse_url(html_entity_decode($hrefMatch[1]),PHP_URL_QUERY),$linkQuery);
check(($linkQuery['q']??'')==='Filter QA'&&($linkQuery['filter_status']??'')==='Diterima'&&($linkQuery['p']??'')==='2','27 aksi status menyimpan pencarian status dan halaman asal');
$context=['id'=>$filterIds['Diterima'],'return_to'=>'orders','q'=>'Filter QA','filter_status'=>'Diterima','p'=>2];
$contextPage=$filterAdminHttp->request('service-status','GET',[],$context);
check(str_contains($contextPage['body'],e(url('orders',['q'=>'Filter QA','status'=>'Diterima','p'=>2]))),'27 Batal pada halaman status mempertahankan filter dan halaman');
$csrf=$filterAdminHttp->form('service-status',$context);
$missingDate=$filterAdminHttp->request('service-status','POST',['csrf'=>$csrf,'return_to'=>'orders','revision'=>Orders::get($filterIds['Diterima'],$admin)['revision'],'new_status'=>'Selesai'],$context);
check($missingDate['code']===422&&str_contains($missingDate['body'],'filter_status=Diterima'),'27 validasi gagal tetap menyimpan konteks untuk Muat ulang');
$updated=$filterAdminHttp->request('service-status','POST',['csrf'=>$csrf,'return_to'=>'orders','revision'=>Orders::get($filterIds['Diterima'],$admin)['revision'],'new_status'=>'Dalam Pengerjaan'],$context);
check($updated['code']===303&&str_contains($updated['headers'],'Location: '.url('orders',['q'=>'Filter QA','status'=>'Diterima','p'=>2])),'27 selesai ubah status kembali ke pencarian filter dan halaman semula');
$invalidContext=$context;$invalidContext['filter_status']=['Selesai'];
check($filterAdminHttp->request('service-status','GET',[],$invalidContext)['code']===400,'27 konteks filter berbentuk array ditolak');
$invoiceMarkup=$filterCustomerHttp->request('invoice','GET',[],['id'=>$filterIds['Selesai']]);
check($invoiceMarkup['code']===200&&str_contains($invoiceMarkup['body'],'assets/invoice-print.js?v=1')&&!str_contains($invoiceMarkup['body'],'assets/app.js'),'28 invoice memakai skrip cetak terpisah dengan cache-busting');
check(str_contains($invoiceMarkup['body'],'data-print-help')&&str_contains($invoiceMarkup['body'],'Bagikan'),'28 petunjuk cetak alternatif tetap tersedia tanpa JavaScript');
file_put_contents(__DIR__.'/local-filter-fixtures.json',json_encode(['phone'=>$filterPhone,'orders'=>$filterIds],JSON_PRETTY_PRINT));
