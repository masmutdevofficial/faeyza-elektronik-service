<?php $recordLabel=$label; $title='Hapus '.$recordLabel; require ROOT.'/views/header.php'; ?>
<div class="page-heading"><h1><?=e(ucfirst($title))?></h1><a class="text-link" href="<?=e(url($kind,['id'=>$record['id']]))?>"><?=mdi('arrow-left')?> Batal</a></div>
<form method="post" class="panel form-panel narrow-form"><?=csrf()?>
<h2><?=e($record['name'])?></h2>
<p class="muted deletion-copy"><?php if($kind==='customer'): ?>Konsumen dan servisnya tidak tampil di ringkasan atau daftar servis aktif. Akun tidak bisa login. Riwayat tetap tersimpan dan invoice dapat dibuka admin melalui menu Invoice.<?php else: ?>Sparepart tidak tersedia untuk penggunaan baru. Stok <?=e($record['stock'].' '.$record['unit'])?> dan histori tetap tersimpan.<?php endif; ?> Data bisa dipulihkan dari filter Dihapus.</p>
<label class="filter-check deletion-copy"><input type="checkbox" name="confirm" value="1" required> Saya ingin menghapus <?=e($recordLabel)?> ini.</label>
<button class="button danger" type="submit">Hapus <?=e($recordLabel)?></button>
</form>
<?php require ROOT.'/views/footer.php'; ?>
