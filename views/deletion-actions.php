<div class="page-actions">
<?php if($deletionRecord['deleted_at']!==null): ?><span class="badge red">Dihapus</span><form method="post" action="<?=e(url($deletionKind.'-restore',['id'=>$deletionRecord['id']]))?>"><?=csrf()?><button class="button secondary" type="submit"><?=mdi('restore')?> Pulihkan</button></form>
<?php else: ?>
<?php if($deletionKind==='customer'): ?><a class="button secondary" href="<?=e(url('customer-password',['id'=>$deletionRecord['id']]))?>">Ganti password</a><?php endif; ?>
<a class="button secondary" href="<?=e(url($deletionKind.'-edit',['id'=>$deletionRecord['id']]))?>">Edit <?=$deletionKind==='customer'?'konsumen':'sparepart'?></a>
<a class="button danger-outline" href="<?=e(url($deletionKind.'-delete',['id'=>$deletionRecord['id']]))?>"><?=mdi('delete-outline')?> Hapus <?=$deletionKind==='customer'?'konsumen':'sparepart'?></a>
<?php endif; ?></div>
