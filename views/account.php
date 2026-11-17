<?php $title='Pengaturan akun'; require ROOT.'/views/header.php'; ?>
<div class="page-heading"><h1>Pengaturan akun</h1></div>
<div class="account-settings">
<?php if($user['role']==='admin'): ?>
<form method="post" class="panel form-panel"><?=csrf()?><input type="hidden" name="action" value="phone"><div class="section-heading"><h2>Nomor HP</h2></div><div class="form-stack">
<?php input_field('phone','Nomor HP',($data['action']??'')==='phone'?$data:$user,'tel',true,'maxlength="25" inputmode="tel" autocomplete="tel"'); ?>
<label class="field"><span>Password saat ini <b>*</b></span><input name="current_password" type="password" required maxlength="72" autocomplete="current-password"></label>
<button class="button" type="submit">Simpan nomor HP</button></div></form>
<?php else: ?><p class="muted">Nomor HP: <?=e($user['phone'])?></p><?php endif; ?>
<form method="post" class="panel form-panel"><?=csrf()?><input type="hidden" name="action" value="password"><div class="section-heading"><h2>Ganti password</h2></div><div class="form-stack">
<?php require ROOT.'/views/password-fields.php'; ?>
<button class="button" type="submit">Simpan password</button></div></form>
</div>
<?php require ROOT.'/views/footer.php'; ?>
