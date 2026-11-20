<?php $title='Hapus invoice'; require ROOT.'/views/header.php'; ?>
<div class="page-heading"><h1>Hapus invoice</h1><a class="text-link" href="<?=e(url('invoice',['id'=>$order['id']]))?>"><?=mdi('arrow-left')?> Batal</a></div>
<form method="post" class="panel form-panel narrow-form"><?=csrf()?>
<input type="hidden" name="revision" value="<?=e($order['revision'])?>">
<h2><?=e($order['invoice_number'])?></h2>
<p><?=e($order['customer_name'])?> · <?=e($order['item_name'].' '.$order['brand'].' '.$order['model'])?></p>
<p class="muted deletion-copy">Invoice dan data servis ini akan dihapus permanen. Akun konsumen tetap ada. Tidak bisa dipulihkan.</p>
<p class="muted deletion-copy"><?=Orders::isComplete($order)?'Sparepart yang sudah dipakai tetap mengurangi stok.':'Alokasi sparepart dikembalikan ke stok.'?> Histori stok tetap tercatat tanpa tautan invoice.</p>
<label class="filter-check deletion-copy"><input type="checkbox" name="confirm" value="1" required> Hapus invoice dan data servis ini secara permanen.</label>
<button class="button danger" type="submit">Hapus invoice</button>
</form>
<?php require ROOT.'/views/footer.php'; ?>
