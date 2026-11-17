<?php $title='Ganti password pelanggan'; require ROOT.'/views/header.php'; ?>
<div class="page-heading"><h1>Ganti password pelanggan</h1><a class="text-link" href="<?=e(url('customer',['id'=>$customer['id']]))?>"><?=mdi('arrow-left')?> Kembali</a></div>
<form method="post" class="panel form-panel narrow-form"><?=csrf()?>
<div class="section-heading"><div><h2><?=e($customer['name'])?></h2><p class="muted"><?=e($customer['phone'])?></p></div></div>
<div class="form-stack"><?php $currentPasswordLabel='Password admin saat ini'; require ROOT.'/views/password-fields.php'; ?><button class="button" type="submit">Simpan password pelanggan</button></div></form>
<?php require ROOT.'/views/footer.php'; ?>
