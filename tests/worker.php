<?php
declare(strict_types=1);
putenv('APP_ENV=testing'); require dirname(__DIR__).'/app/bootstrap.php';
[$file,$mode,$uid,$order,$part,$target,$barrier]=$argv;
while(microtime(true)<(float)$barrier) usleep(1000);
try {
    $actor=Database::one('SELECT * FROM users WHERE id=?',[$uid]);
    if($mode==='invoice') {
        $c=Orders::customer($actor);
        $submission=preg_match('/^[a-f0-9]{64}$/D',$target)?$target:token();
        $id=Orders::create(['submission_key'=>$submission,'received_date'=>'2026-11-17','customer_name'=>$c['name'],'contact_phone'=>$c['phone'],'address'=>$c['address'],'item_name'=>'TV','brand'=>'SONY','model'=>'Concurrent','accessories'=>[],'complaint'=>'Uji request bersamaan','location'=>'Bengkel'],$actor);
        echo json_encode(['ok'=>true,'invoice'=>Orders::get($id,$actor)['invoice_number']]);
    } elseif($mode==='quick-status') {
        Orders::changeStatus((int)$order,['revision'=>$part,'new_status'=>$target],$actor); echo json_encode(['ok'=>true]);
    } elseif($mode==='archive-part') {
        RecordDeletion::change('part',(int)$part,true,$actor); echo json_encode(['ok'=>true]);
    } elseif($mode==='receive-part') {
        Inventory::receive((int)$part,(int)$target,'Penerimaan race',token(),$actor); echo json_encode(['ok'=>true]);
    } elseif($mode==='purge-invoice') {
        RecordDeletion::purgeInvoice((int)$order,(int)$target,$actor); echo json_encode(['ok'=>true]);
    } elseif($mode==='purge-customer') {
        RecordDeletion::purgeCustomer((int)$target,$actor); echo json_encode(['ok'=>true]);
    } elseif($mode==='create-order') {
        $owner=Database::one('SELECT * FROM users WHERE id=?',[(int)$target]);
        if (!$owner) throw new ValidationException('Konsumen tidak ditemukan.');
        $customer=Orders::customer($owner);
        $data=['submission_key'=>token(),'received_date'=>Clock::today(),'customer_name'=>'Race deletion','contact_phone'=>$customer['phone']??'081299900001','address'=>'QA','item_name'=>'TV','brand'=>'SONY','model'=>'QA','complaint'=>'QA','location'=>'Bengkel','customer_id'=>$customer['id']??0,'status'=>'Diterima'];
        $id=Orders::create($data,$actor); echo json_encode(['ok'=>true,'id'=>$id]);
    } elseif($mode==='receive-typed') {
        $receipt=json_decode($target,true,512,JSON_THROW_ON_ERROR);
        Inventory::receive((int)$part,(int)$receipt['quantity'],$receipt['type'],$receipt['key'],$actor,$receipt['type']); echo json_encode(['ok'=>true]);
    } elseif($mode==='adjust-part') {
        $adjust=json_decode($target,true,512,JSON_THROW_ON_ERROR);
        Inventory::adjust((int)$part,(int)$adjust['quantity'],(int)$order,'Koreksi race',$adjust['key'],$actor); echo json_encode(['ok'=>true]);
    } else {
        Inventory::allocate((int)$order,(int)$part,(int)$target,0,$actor);
        echo json_encode(['ok'=>true]);
    }
} catch(ValidationException $e){ echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
catch(Throwable $e){ echo json_encode(['ok'=>false,'exception'=>get_class($e),'code'=>$e->getCode()]); exit(1); }
