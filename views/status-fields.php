<?php $statusComplete=Orders::isComplete(['status'=>$data['new_status']??'']); $statusCancelling=($data['new_status']??'')==='Dibatalkan' && ($statusOrder['status']??'')!=='Dibatalkan'; ?>
<?=csrf()?>
<input type="hidden" name="revision" value="<?=e($data['revision']??'')?>">
<input type="hidden" name="return_to" value="<?=e($returnTo??'order')?>">
<label class="field"><span>Status servis <b>*</b></span><select name="new_status" required><?php foreach(Orders::STATUSES as $statusOption): ?><option <?=($data['new_status']??'')===$statusOption?'selected':''?>><?=e($statusOption)?></option><?php endforeach; ?></select></label>
<div class="status-dates" data-status-dates <?=$statusComplete?'':'hidden'?>>
<?php input_field('completed_date','Selesai service',$data,'date',$statusComplete,$statusComplete?'':'disabled'); input_field('warranty_date','Garansi sampai',$data,'date',false,$statusComplete?'':'disabled'); ?>
</div>
<label class="status-cancel-confirm" data-status-cancel <?=$statusCancelling?'':'hidden'?>><input type="checkbox" name="confirm_cancel" value="1" <?=($data['confirm_cancel']??'')==='1'?'checked':''?> <?=$statusCancelling?'required':'disabled'?>> <span>Batalkan servis dan kembalikan sparepart ke stok.</span></label>
