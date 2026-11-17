<?php
declare(strict_types=1);
// Artefak QA memakai template produksi, data nyata dari database testing, tanpa endpoint publik.
putenv('APP_ENV=testing'); require dirname(__DIR__).'/app/bootstrap.php';
if(!str_ends_with((string)getenv('DB_NAME'),'_test'))exit(1);
$admin=Database::one("SELECT * FROM users WHERE role='admin' LIMIT 1");
$browser=json_decode(file_get_contents(__DIR__.'/local-browser-fixtures.json'),true);
$final=Orders::get((int)$browser['orderId'],$admin);
$initial=Database::one('SELECT * FROM service_orders WHERE total_cost IS NULL ORDER BY id DESC LIMIT 1');
$long=$final;
// Stres layout: panjang maksimum masing-masing field, disertai sentinel di akhir untuk cek tidak terpotong.
foreach(['address','complaint','notes','technician_notes'] as $field) $long[$field]=str_repeat('Teks panjang uji cetak perangkat. ',115).' AKHIR-'.strtoupper($field);
foreach(['invoice-initial'=>$initial,'invoice-final'=>$final,'invoice-long'=>$long] as $name=>$order) {
    ob_start(); require ROOT.'/views/invoice.php'; $html=ob_get_clean();
    foreach(['invoice','print']as $css) $html=str_replace('<link rel="stylesheet" href="assets/'.$css.'.css">','<style>'.file_get_contents(ROOT.'/public/assets/'.$css.'.css').'</style>',$html);
    $logo=brand()['logo'];
    if($logo && is_file(ROOT.'/public/'.$logo)) $html=str_replace('src="'.e($logo).'"','src="data:image/jpeg;base64,'.base64_encode(file_get_contents(ROOT.'/public/'.$logo)).'"',$html);
    $html=str_replace('<script src="assets/app.js" defer></script>','',$html);
    file_put_contents(__DIR__.'/artifacts/'.$name.'.html',$html);
    echo $name.'.html siap'.PHP_EOL;
}
