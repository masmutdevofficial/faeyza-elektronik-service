<?php
// Isolated fixtures are created by invoice-filter.php; no production database.
$groupStatuses=['working'=>['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart'],'done'=>['Selesai','Sudah Diambil']];
foreach(['admin'=>[$admin,$filterAdminHttp],'customer'=>[$filterActor,$filterCustomerHttp]] as $groupRole=>[$groupActor,$groupHttp]) {
    foreach($groupStatuses as $group=>$expectedStatuses) {
        $expectedCount=$group==='working'?($groupRole==='admin'?5:4):2;
        $groupRows=Orders::listing($groupActor,'Filter QA',$group);
        check(count($groupRows)===$expectedCount&&!array_diff(array_column($groupRows,'status'),$expectedStatuses),'29 kelompok domain '.$groupRole.' '.$group.' tepat dan Dibatalkan dikecualikan');
        foreach(['orders','invoices'] as $groupRoute) {
            $groupResponse=$groupHttp->request($groupRoute,'GET',[],['q'=>'Filter QA','status'=>$group]);
            preg_match_all('~<span class="badge [^"]*">([^<]+)</span>~',$groupResponse['body'],$groupBadges);
            check($groupResponse['code']===200&&count($groupBadges[1])===$expectedCount&&!array_diff($groupBadges[1],$expectedStatuses),'29 filter '.$groupRoute.' '.$groupRole.' '.$group.' konsisten dan sesuai kepemilikan');
            check(str_contains($groupResponse['body'],'value="'.$group.'" selected'),'29 dropdown '.$groupRoute.' '.$groupRole.' mengenali kelompok '.$group);
        }
    }
}
$summaryPage=$filterAdminHttp->request('dashboard');
preg_match_all('~<a class="stat-card" href="([^"]+)"><p>([^<]+)</p><strong>(\d+)</strong>~',$summaryPage['body'],$summaryCards,PREG_SET_ORDER);
$summaryExpected=Database::one("SELECT SUM(status IN ('Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart')) working,SUM(status IN ('Selesai','Sudah Diambil')) done FROM service_orders");
foreach(['Dalam proses'=>'working','Selesai'=>'done'] as $label=>$group) {
    $card=array_values(array_filter($summaryCards,fn($card)=>$card[2]===$label))[0]??null;
    check($card&&(int)$card[3]===(int)$summaryExpected[$group]&&str_contains(html_entity_decode($card[1]),'status='.$group),'29 kartu '.$label.' menghitung seluruh kelompok dan membuka filter yang sama');
}
$groupContext=['id'=>$filterForeign,'return_to'=>'orders','q'=>'Filter QA','filter_status'=>'working','p'=>1];
$groupStatusPage=$filterAdminHttp->request('service-status','GET',[],$groupContext);
check($groupStatusPage['code']===200&&str_contains($groupStatusPage['body'],e(url('orders',['q'=>'Filter QA','status'=>'working','p'=>1]))),'29 halaman status menerima dan mempertahankan konteks kelompok');
$groupPayload=['csrf'=>$filterAdminHttp->form('service-status',$groupContext),'revision'=>Orders::get($filterForeign,$admin)['revision'],'new_status'=>'Selesai','completed_date'=>$today,'return_to'=>'orders'];
$groupChanged=$filterAdminHttp->request('service-status','POST',$groupPayload,$groupContext);
check($groupChanged['code']===303&&str_contains($groupChanged['headers'],'status=working')&&count(Orders::listing($admin,'Filter QA','working'))===4&&count(Orders::listing($admin,'Filter QA','done'))===3,'29 perubahan Selesai keluar dari proses dan masuk kelompok selesai dengan redirect tetap');
Orders::changeStatus($filterForeign,['revision'=>Orders::get($filterForeign,$admin)['revision'],'new_status'=>'Dibatalkan','confirm_cancel'=>'1'],$admin);
check(count(Orders::listing($admin,'Filter QA','working'))===4&&count(Orders::listing($admin,'Filter QA','done'))===2,'29 pembatalan tidak dihitung dalam proses maupun selesai');
rejects(fn()=>Orders::changeStatus($filterForeign,['revision'=>Orders::get($filterForeign,$admin)['revision'],'new_status'=>'working'],$admin),'29 kelompok ringkasan bukan status yang dapat disimpan pada pesanan');
Orders::changeStatus($filterForeign,['revision'=>Orders::get($filterForeign,$admin)['revision'],'new_status'=>'Diterima'],$admin);
check($filterAdminHttp->request('orders','GET',[],['status'=>"working') OR 1=1 --"])['code']===400,'29 nilai kelompok palsu ditolak aman');
