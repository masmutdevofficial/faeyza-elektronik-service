<?php $title='Ringkasan'; require ROOT.'/views/header.php'; $admin=$user['role']==='admin'; ?>
<div class="page-heading">
    <div>
        <span class="eyebrow">OPERASIONAL / HARI INI</span>
        <h1><?=$admin?'Semua servis, dalam kendali.':'Pantau perangkat Anda.'?></h1>
        <p class="muted"><?=$admin?'Selamat datang. Berikut aktivitas bengkel Anda.':'Lihat perkembangan perbaikan dan invoice Anda di sini.'?></p>
    </div>
    <a class="button" href="<?=e(url('service-new'))?>"><?=mdi('plus')?> Pengajuan servis</a>
</div>

<?php if($admin): ?>
<section class="admin-hero" aria-labelledby="admin-hero-title">
    <div class="admin-hero-copy">
        <span class="admin-hero-kicker"><?=mdi('lightning-bolt')?> RUANG KERJA FAEYZA</span>
        <h2 id="admin-hero-title">Bengkel lebih tertata.<br><span>Servis lebih terjaga.</span></h2>
        <p>Dari perangkat masuk sampai kembali ke pemiliknya. Kelola setiap proses dalam satu tempat.</p>
        <div class="admin-hero-actions">
            <a class="hero-primary-action" href="<?=e(url('orders'))?>"><?=mdi('clipboard-text-outline')?> Lihat antrean servis <?=mdi('arrow-right')?></a>
            <a class="hero-secondary-action" href="<?=e(url('invoices'))?>"><?=mdi('receipt-text-outline')?> Cari invoice <?=mdi('arrow-top-right')?></a>
        </div>
        <div class="admin-hero-summary">
            <a href="<?=e(url('orders',['status'=>'Menunggu Konfirmasi']))?>"><?=mdi('clock-outline')?><span><strong><?=e($stats['waiting'])?></strong> perlu konfirmasi</span></a>
            <span class="hero-summary-divider" aria-hidden="true"></span>
            <span><?=mdi('progress-wrench')?><span><strong><?=e($stats['working'])?></strong> dalam proses</span></span>
        </div>
    </div>
    <div class="admin-hero-visual" aria-hidden="true">
        <div class="hero-orbit hero-orbit-outer"></div>
        <div class="hero-orbit hero-orbit-inner"></div>
        <span class="hero-spark hero-spark-one"></span><span class="hero-spark hero-spark-two"></span>
        <div class="hero-workbench">
            <div class="hero-workbench-label"><span class="hero-workbench-dot"></span> PERAWATAN PERANGKAT <?=mdi('cog-outline')?></div>
            <div class="hero-device"><?=mdi('television')?><span class="hero-device-tool"><?=mdi('wrench-outline')?></span></div>
            <div class="hero-workbench-caption">Diperiksa. Diperbaiki. Dipercaya.</div>
        </div>
        <div class="hero-finished-card"><span><?=mdi('clipboard-check-outline')?></span><div><small>SERVIS SELESAI</small><strong><?=e($stats['done'])?> <span>perangkat</span></strong></div><?=mdi('check-circle-outline')?></div>
        <div class="hero-assurance"><?=mdi('shield-check-outline')?> Setiap proses tercatat</div>
    </div>
</section>
<?php else: ?>
<section class="welcome-banner"><div><span class="eyebrow light">FAEYZA ELEKTRONIK SERVICE</span><h2>Perbaikan yang teliti.<br>Pelayanan sepenuh hati.</h2><p>Dari penerimaan hingga selesai, ikuti setiap langkah servis perangkat Anda.</p><a href="<?=e(url('orders'))?>">Lihat semua servis <?=mdi('arrow-top-right')?></a></div><div class="banner-art" aria-hidden="true"><div class="screen-shape"><?=mdi('television')?><i></i></div><span class="floating-chip"><?=mdi('check-circle-outline')?> Tercatat dengan baik</span></div></section>
<?php endif; ?>

<section class="stat-grid" aria-label="Ringkasan servis">
<?php $cards=[
    ['clipboard-text-outline','Total servis',$stats['total'],'Seluruh perangkat tercatat',''],
    ['clock-outline','Servis baru',$stats['waiting'],'Menunggu konfirmasi','amber'],
    ['tools','Dalam proses',$stats['working'],'Diterima & sedang ditangani','blue'],
    ['clipboard-check-outline','Servis selesai',$stats['done'],'Selesai & sudah diambil','green'],
]; foreach($cards as [$icon,$label,$value,$note,$color]): ?>
    <a class="stat-card" href="<?=e(url('orders', $label==='Servis baru'?['status'=>'Menunggu Konfirmasi']:[]))?>">
        <div><span class="stat-icon <?=e($color)?>"><?=mdi($icon)?></span><span class="stat-arrow"><?=mdi('arrow-top-right')?></span></div>
        <p><?=e($label)?></p><strong><?=e($value)?></strong><small><?=e($note)?></small>
    </a>
<?php endforeach; ?>
</section>

<div class="dashboard-columns">
    <section class="panel">
        <div class="panel-heading"><div><h2>Servis terbaru</h2><p>Aktivitas penerimaan perangkat terkini.</p></div><a class="text-link" href="<?=e(url('orders'))?>">Lihat semua <?=mdi('arrow-top-right')?></a></div>
        <?php $orders=$recent; require ROOT.'/views/order-table.php'; ?>
    </section>
    <aside class="dashboard-side">
    <?php if($admin): ?>
        <section class="panel compact"><div class="mini-icon"><?=mdi('account-group-outline')?></div><h3>Konsumen terhubung</h3><strong class="large-number"><?=e($customers)?></strong><p class="muted">Akun konsumen yang terdaftar.</p><a class="text-link" href="<?=e(url('customers'))?>">Kelola konsumen <?=mdi('arrow-top-right')?></a></section>
        <section class="stock-alert"><span class="eyebrow">PERHATIAN STOK</span><h3><?=e($low)?> sparepart menipis</h3><p>Pastikan komponen tersedia agar perbaikan tetap berjalan.</p><a href="<?=e(url('parts',['low'=>'1']))?>">Periksa persediaan <?=mdi('arrow-top-right')?></a></section>
    <?php else: ?>
        <section class="panel compact"><div class="mini-icon"><?=mdi('receipt-text-outline')?></div><h3>Bukti servis, selalu tersedia.</h3><p class="muted">Temukan invoice milik Anda dan cetak kapan saja.</p><a class="text-link" href="<?=e(url('invoices'))?>">Cari invoice <?=mdi('arrow-top-right')?></a></section>
    <?php endif; ?>
    </aside>
</div>
<?php require ROOT.'/views/footer.php'; ?>
