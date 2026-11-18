<?php
declare(strict_types=1);
// Artefak QA memakai template produksi, data nyata dari database testing, tanpa endpoint publik.
putenv('APP_ENV=testing'); require dirname(__DIR__).'/app/bootstrap.php';
if(!str_ends_with((string)getenv('DB_NAME'),'_test'))exit(1);
$admin=Database::one("SELECT * FROM users WHERE role='admin' LIMIT 1");
$browser=is_file(__DIR__.'/local-browser-fixtures.json')?json_decode(file_get_contents(__DIR__.'/local-browser-fixtures.json'),true):[];
$final=Orders::get((int)($browser['orderId']??0),$admin);
// Tes penghapusan dapat membatalkan pesanan browser setelah simulasi selesai.
if (!$final || !Orders::isComplete($final)) $final=Database::one("SELECT * FROM service_orders WHERE status='Selesai' AND total_cost=150000 AND show_workshop_signature=1 ORDER BY id DESC LIMIT 1");
$pending=Database::one("SELECT * FROM service_orders WHERE status='Menunggu Sparepart' AND completed_date='2032-04-05' LIMIT 1");
if (!$final || !$pending) throw new RuntimeException('Jalankan tests/run.php sebelum QA cetak.');
file_put_contents(__DIR__.'/artifacts/print-fixtures.json',json_encode(['warrantyDate'=>$final['warranty_date'],'invoice'=>$final['invoice_number']]));
$initial=Database::one('SELECT * FROM service_orders WHERE total_cost IS NULL ORDER BY id DESC LIMIT 1');
$long=$final;
// Stres layout: panjang maksimum masing-masing field, disertai sentinel di akhir untuk cek tidak terpotong.
foreach(['address','complaint','notes','technician_notes'] as $field) $long[$field]=str_repeat('Teks panjang uji cetak perangkat. ',115).' AKHIR-'.strtoupper($field);
foreach(['invoice-initial'=>$initial,'invoice-final'=>$final,'invoice-long'=>$long,'invoice-pending'=>$pending] as $name=>$order) {
    ob_start(); require ROOT.'/views/invoice.php'; $html=ob_get_clean();
    foreach(['invoice','print']as $css) $html=preg_replace('~<link rel="stylesheet" href="assets/'.$css.'\.css(?:\?v=\d+)?">~','<style>'.file_get_contents(ROOT.'/public/assets/'.$css.'.css').'</style>',$html);
    $logo=brand()['logo'];
    if($logo && is_file(ROOT.'/public/'.$logo)) $html=str_replace('src="'.e($logo).'"','src="data:image/jpeg;base64,'.base64_encode(file_get_contents(ROOT.'/public/'.$logo)).'"',$html);
    $html=preg_replace('~<script src="assets/(?:app|invoice-print)\.js(?:\?v=\d+)?" defer></script>~','',$html);
    file_put_contents(__DIR__.'/artifacts/'.$name.'.html',$html);
    echo $name.'.html siap'.PHP_EOL;
}
