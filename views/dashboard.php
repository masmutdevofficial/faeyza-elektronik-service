<?php $admin=$user['role']==='admin'; $title=$admin?'Ringkasan':'Servis Anda'; require ROOT.'/views/header.php'; ?>
<div class="page-heading"><h1><?=e($title)?></h1><?php if($admin||$recent): ?><a class="button" href="<?=e(url('service-new'))?>"><?=mdi('plus')?> Servis baru</a><?php endif; ?></div>
<?php if($admin): ?>
<section class="queue-strip"><div><strong class="queue-count"><?=e($stats['waiting'])?></strong><div><h2>Menunggu konfirmasi</h2><a href="<?=e(url('orders',['status'=>'Menunggu Konfirmasi']))?>">Buka antrean <?=mdi('arrow-right')?></a></div></div><a class="queue-inventory" href="<?=e(url('parts',['low'=>'1']))?>"><?=mdi('package-variant-closed')?> <?=e($low)?> stok menipis <?=mdi('arrow-right')?></a></section>
<section class="stat-grid" aria-label="Ringkasan servis">
<?php foreach([['Total servis',$stats['total'],''],['Baru',$stats['waiting'],'Menunggu Konfirmasi'],['Dalam proses',$stats['working'],''],['Selesai',$stats['done'],'Selesai']] as [$label,$value,$status]): ?>
<a class="stat-card" href="<?=e(url('orders',$status?['status'=>$status]:[]))?>"><p><?=e($label)?></p><strong><?=e($value)?></strong></a>
<?php endforeach; ?></section>
<div class="dashboard-columns"><section class="panel"><div class="panel-heading"><h2>Servis terbaru</h2><a class="text-link" href="<?=e(url('orders'))?>">Lihat semua <?=mdi('arrow-right')?></a></div><?php $orders=$recent; require ROOT.'/views/order-table.php'; ?></section><aside class="dashboard-side"><section class="panel compact"><h3>Konsumen</h3><strong class="large-number"><?=e($customers)?></strong><a class="text-link" href="<?=e(url('customers'))?>">Lihat konsumen <?=mdi('arrow-right')?></a></section><a class="dashboard-shortcut" href="<?=e(url('invoices'))?>"><?=mdi('receipt-text-outline')?> Cari invoice <?=mdi('arrow-right')?></a></aside></div>
<?php else: ?>
<?php $orders=$recent; require ROOT.'/views/order-table.php'; ?>
<?php if((int)$stats['total']>count($recent)): ?><a class="button secondary wide" href="<?=e(url('orders'))?>">Lihat semua servis <?=mdi('arrow-right')?></a><?php endif; ?>
<?php endif; ?>
<?php require ROOT.'/views/footer.php'; ?>