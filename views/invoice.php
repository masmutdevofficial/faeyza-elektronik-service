<?php
$b=brand();
$title='Invoice '.$order['invoice_number'];
$accessories=json_decode($order['accessories'],true);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=e($title)?> · <?=e($b['name'])?></title>
    <link rel="stylesheet" href="assets/invoice.css">
    <link rel="stylesheet" href="assets/print.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="invoice-body">
<div class="print-toolbar">
    <a class="button secondary" href="<?=e(url('order',['id'=>$order['id']]))?>"><?=mdi('arrow-left')?> Detail servis</a>
    <span>Nota service / Invoice · A4</span>
    <button type="button" class="button" data-print><?=mdi('printer-outline')?> Cetak invoice</button>
</div>
<main class="invoice-paper">
    <header class="nota-header">
        <div class="nota-logo">
            <?php if($b['logo']): ?><img src="<?=e($b['logo'])?>" alt="Logo <?=e($b['name'])?>"><?php else: ?><strong><?=e($b['short_name'])?></strong><?php endif; ?>
        </div>
        <div class="nota-business">
            <h1><?=e($b['name'])?></h1>
            <p class="nota-subtitle"><?=e($b['invoice_heading']??$b['description'])?></p>
            <?php if($b['address']): ?><p><?=e($b['address'])?></p><?php endif; ?>
            <?php if(!empty($b['website'])): ?><p><?=e($b['website'])?></p><?php endif; ?>
            <?php if($b['phone']): ?><p class="nota-phone">Phone/WA : <?=e($b['phone'])?></p><?php endif; ?>
        </div>
        <div class="nota-document">
            <p><?=e($b['city']??'')?><?=!empty($b['city'])?' : ':''?><?=e(human_date($order['issued_on']))?></p>
            <h2>NOTA SERVICE</h2>
        </div>
    </header>
    <div class="nota-meta">
        <span>No. invoice : <strong><?=e($order['invoice_number'])?></strong></span>
        <span>Tanggal masuk : <strong><?=e(human_date($order['received_date']))?></strong></span>
    </div>
    <table class="nota-table nota-intake" aria-label="Data penerimaan servis">
        <colgroup><col style="width:32%"><col style="width:37%"><col style="width:31%"></colgroup>
        <thead><tr><th scope="col">Data Konsumen</th><th scope="col">Data Perangkat</th><th scope="col">Kelengkapan</th></tr></thead>
        <tbody><tr>
            <td data-heading="Data Konsumen">
                <p><span>Nama</span> : <?=e($order['customer_name'])?></p>
                <p><span>Alamat</span> : <?=nl2br(e($order['address']))?></p>
                <p class="nota-contact-phone"><span>No HP</span> : <?=e($order['contact_phone'])?></p>
            </td>
            <td data-heading="Data Perangkat">
                <dl class="nota-fields">
                    <div><dt>Nama Barang</dt><dd><?=e($order['item_name'])?></dd></div>
                    <div><dt>Merk</dt><dd><?=e($order['brand'])?></dd></div>
                    <div><dt>No. Model</dt><dd><?=e($order['model'])?></dd></div>
                    <div><dt>Serial Number</dt><dd><?=e($order['serial_number']?:'-')?></dd></div>
                </dl>
            </td>
            <td data-heading="Kelengkapan"><?=e(implode(', ',is_array($accessories)?$accessories:[])?:'-')?></td>
        </tr></tbody>
    </table>
    <table class="nota-table nota-work" aria-label="Status dan biaya servis">
        <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
        <thead><tr><th scope="col">Status</th><th scope="col">Biaya</th></tr></thead>
        <tbody><tr>
            <td data-heading="Status">
                <dl class="nota-fields">
                    <div><dt>Kerusakan</dt><dd><?=nl2br(e($order['complaint']))?></dd></div>
                    <div><dt>Pengerjaan di</dt><dd><?=e($order['location'])?></dd></div>
                    <div><dt>Status servis</dt><dd><?=e($order['status'])?></dd></div>
                    <div><dt>Selesai service</dt><dd><?=e(human_date($order['completed_date']))?></dd></div>
                    <div><dt>Garansi sampai</dt><dd><?=e(human_date($order['warranty_date']))?></dd></div>
                </dl>
            </td>
            <td data-heading="Biaya" class="nota-cost-cell">
                <dl class="nota-fields nota-cost-lines">
                    <div><dt>Jasa dan sparepart</dt><dd>-</dd></div>
                    <div><dt>Pengecekan</dt><dd>-</dd></div>
                </dl>
                <div class="nota-cost-total"><span>Total biaya keseluruhan</span><strong data-testid="total-cost"><?=e(money($order['total_cost']))?></strong></div>
                <?php if($order['total_cost']===null): ?><p class="nota-unset">Biaya belum ditetapkan oleh admin.</p><?php endif; ?>
            </td>
        </tr></tbody>
    </table>
    <section class="nota-notes" aria-label="Catatan servis">
        <p><strong>Catatan :</strong> <?=nl2br(e($order['notes']?:'-'))?></p>
        <?php if($order['technician_notes']!==''): ?><p><strong>Teknisi :</strong> <?=nl2br(e($order['technician_notes']))?></p><?php endif; ?>
    </section>
    <div class="nota-bottom">
        <section class="nota-terms">
            <h3>Syarat dan ketentuan :</h3>
            <?php if(!empty($b['invoice_terms'])): ?><ul><?php foreach($b['invoice_terms'] as $term): ?><li><?=e($term)?></li><?php endforeach; ?></ul><?php else: ?><p><?=e($b['invoice_note'])?></p><?php endif; ?>
        </section>
        <table class="nota-table nota-signatures" aria-label="Tanda tangan penerimaan">
            <thead><tr><th scope="col">Konsumen</th><th scope="col">Pihak bengkel</th></tr></thead>
            <tbody><tr><td><?=e($order['customer_name'])?></td><td><?=e($b['invoice_signatory']?:$b['short_name'])?></td></tr></tbody>
        </table>
    </div>
    <footer class="nota-footer">Dokumen ini diterbitkan oleh <?=e($b['name'])?> · <?=e($order['invoice_number'])?></footer>
</main>
</body>
</html>
