<dialog class="status-dialog" data-status-dialog aria-labelledby="status-dialog-title" aria-describedby="status-dialog-invoice">
<div class="status-dialog-heading"><div><h2 id="status-dialog-title">Ubah status</h2><p id="status-dialog-invoice" class="muted" data-status-invoice></p></div><button class="text-link" type="button" data-status-close>Tutup</button></div>
<form method="post" class="status-form" data-status-form>
<?php $data=[]; $statusOrder=[]; $returnTo='order'; require ROOT.'/views/status-fields.php'; ?>
<div class="status-dialog-actions"><button class="button secondary" type="button" data-status-close>Batal</button><button class="button" type="submit">Simpan status</button></div>
</form>
</dialog>
