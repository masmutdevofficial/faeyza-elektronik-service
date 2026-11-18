<?php $title='Ubah status'; require ROOT.'/views/header.php'; ?>
<div class="page-heading"><div><h1>Ubah status</h1><p><?=e($statusOrder['invoice_number'])?></p></div><a class="text-link" href="<?=e(url($returnTo,$returnTo==='order'?['id'=>$statusOrder['id']]:[]))?>">Batal</a></div>
<form method="post" class="panel form-panel narrow-form status-form" data-status-form data-current-status="<?=e($statusOrder['status'])?>">
<?php require ROOT.'/views/status-fields.php'; ?>
<button class="button" type="submit">Simpan status</button>
<?php if($error): ?><a class="text-link" href="<?=e(url('service-status',['id'=>$statusOrder['id'],'return_to'=>$returnTo]))?>">Muat ulang</a><?php endif; ?>
</form>
<?php require ROOT.'/views/footer.php'; ?>
