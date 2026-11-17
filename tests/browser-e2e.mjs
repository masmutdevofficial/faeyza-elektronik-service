// Dapat dijalankan melalui Browser skill (runBrowserE2E) atau adapter Playwright CLI.
// Seluruh data dibuat di server testing 8081. Jalankan tests/run.php terlebih dahulu.
export async function runBrowserE2E({tab,viewport,fixture,save,base='http://127.0.0.1:8081/'}) {
  const results=[]; const metrics=[];
  const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw new Error(name);};
  const goto=page=>tab.goto(base+'index.php?page='+page);
  const text=selector=>tab.playwright.locator(selector).innerText();
  const currentUrl=()=>tab.playwright.evaluate(()=>location.href);
  const fill=(name,value)=>tab.playwright.locator('[name="'+name+'"]').fill(value);
  const click=async name=>{
    const action=()=>tab.playwright.getByRole('button',{name,exact:true}).click();
    if(name==='Buka navigasi'||name==='Tutup navigasi')return action();
    await tab.playwright.expectNavigation(action,{waitUntil:'domcontentloaded'});
    const expected=({'Buat akun':'dashboard','Masuk ke workspace':'dashboard','Keluar':'login'})[name];
    if(expected)await tab.playwright.waitForURL(base+'index.php?page='+expected,{waitUntil:'domcontentloaded'});
    await tab.ax.get();
  };
  const link=name=>tab.playwright.expectNavigation(()=>tab.playwright.getByRole('link',{name,exact:true}).click(),{waitUntil:'domcontentloaded'});
  const size=async width=>{
    await viewport.set({width,height:900});
    const actual=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));
    // Chrome extension menafsirkan ukuran dengan skala DPI host. Kalibrasi ke CSS pixels.
    if(actual.w!==width) await viewport.set({width:Math.round(width*width/actual.w),height:Math.round(900*900/actual.h)});
  };
  const responsive=async (route,width)=>{
    await size(width); await goto(route);
    const m=await tab.playwright.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth}));
    metrics.push({route,requested:width,...m});check('12 '+route+' '+width+'px tanpa overflow',m.width===width&&m.scroll<=m.width);
  };
  const login=async phone=>{await fill('phone',phone);await fill('password',fixture.password);await click('Masuk ke workspace');};
  try {
    await size(1440); await goto('dashboard');
    if(await tab.playwright.locator('.logout').count())await click('Keluar');
    await goto('register');
    const phone='0857'+String(Date.now()).slice(-8);
    await fill('name','Rina Simulasi');await fill('address','Jl. Simulasi No. 17, Jakarta');await fill('phone',phone);await fill('password',fixture.password);await fill('password_confirm',fixture.password);await click('Buat akun');
    check('13 registrasi konsumen browser',(await currentUrl()).includes('dashboard'));
    await click('Keluar');await login(phone);check('13 login konsumen baru',(await currentUrl()).includes('dashboard'));
    await goto('service-new');await size(375);
    check('12 form servis 375px',await tab.playwright.evaluate(()=>innerWidth===375&&document.documentElement.scrollWidth<=innerWidth));
    await click('Buka navigasi');check('12 menu mobile terbuka',await tab.playwright.getByRole('button',{name:'Tutup navigasi',exact:true}).isVisible());await click('Tutup navigasi');
    await save('service-form-mobile.jpg');
    await tab.playwright.locator('[name="item_name"]').selectOption('Lainnya');check('12 field custom muncul',await tab.playwright.locator('[name="item_name_custom"]').isVisible());
    await tab.playwright.locator('[name="item_name"]').selectOption('TV');await tab.playwright.locator('[name="brand"]').selectOption('SONY');await fill('model','Bravia 43 E2E');await fill('serial_number','E2E-TV-001');
    await tab.playwright.getByLabel('Kabel Power',{exact:true}).check();await tab.playwright.getByLabel('Remote',{exact:true}).check();
    await fill('complaint','TV tidak menyala, indikator merah berkedip.');await fill('notes','Mohon konfirmasi sebelum perbaikan.');await click('Kirim pengajuan servis');
    check('13 submit TV berhasil',(await currentUrl()).includes('success'));
    const invoice=await text('h1');check('13 format invoice otomatis',/^INV-\d{8}-\d{3,}$/.test(invoice));
    const orderId=new URL(await currentUrl()).searchParams.get('id');
    await link('Lihat / cetak invoice');check('13 invoice awal biaya -',(await text('[data-testid="total-cost"]'))==='-');
    check('10 tombol cetak tersedia',await tab.playwright.locator('[data-print]').isVisible());
    check('12 invoice mobile tanpa overflow',await tab.playwright.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await save('invoice-mobile-initial.jpg');
    await size(1440);await goto('order&id='+orderId);await click('Keluar');await login(fixture.admin_phone);check('13 login admin',(await currentUrl()).includes('dashboard'));await save('admin-desktop.jpg');
    await goto('invoices');await fill('q',phone);await click('Cari');check('13 admin mencari HP',(await text('tbody')).includes(invoice));
    await tab.playwright.expectNavigation(()=>tab.playwright.locator('tbody .table-primary').click(),{waitUntil:'domcontentloaded'});await link('Edit servis');await tab.playwright.locator('[name="status"]').selectOption('Dalam Pengerjaan');await click('Simpan perubahan');
    check('13 status Dalam Pengerjaan',(await text('.badge'))==='Dalam Pengerjaan');
    await goto('part-new');const sku='E2E-'+String(Date.now()).slice(-8);await fill('sku',sku);await fill('name','Kapasitor TV Simulasi');await fill('minimum_stock','3');await fill('purchase_price','5000');await fill('sale_price','10000');await click('Simpan sparepart');
    check('13 sparepart baru stok 0',(await text('.inventory-balance')).startsWith('0'));const partId=new URL(await currentUrl()).searchParams.get('id');
    await fill('quantity','10');await fill('reason','Pembelian simulasi E2E');await click('Tambah stok');check('13 stok masuk 10',(await text('.inventory-balance')).startsWith('10'));
    await goto('order&id='+orderId);await tab.playwright.locator('select[name="part_id"]').selectOption(partId);await tab.playwright.locator('.inline-form input[name="quantity"]').fill('2');await click('Gunakan sparepart');
    check('13 penggunaan 2 stok menjadi 8',(await text('.allocation-list')).includes('Tersedia 8 pcs'));await tab.reload();check('13 refresh stok tetap 8',(await text('.allocation-list')).includes('Tersedia 8 pcs'));
    await link('Edit servis');const day=await tab.playwright.locator('[name="received_date"]').getAttribute('value');
    const warranty=new Date(day+'T12:00:00Z');warranty.setUTCDate(warranty.getUTCDate()+30);const warrantyDate=warranty.toISOString().slice(0,10);
    await fill('total_cost','150000');await tab.playwright.locator('[name="status"]').selectOption('Selesai');await fill('completed_date',day);await fill('warranty_date',warrantyDate);await fill('technician_notes','Penggantian kapasitor. Perangkat normal setelah pengujian.');await click('Simpan perubahan');
    check('13 admin menyelesaikan servis Rp150.000',(await text('.badge'))==='Selesai'&&(await text('[data-testid="total-cost"]'))==='Rp150.000');
    await goto('part&id='+partId);check('07 histori stok memuat pembelian dan penggunaan',(await text('tbody')).includes('Pembelian simulasi E2E')&&(await text('tbody')).includes('Penyesuaian penggunaan'));await save('stock-history-desktop.jpg');
    await click('Keluar');await login(phone);await goto('invoices');await fill('q',invoice);await click('Cari');check('13 konsumen mencari invoice sendiri',(await text('tbody')).includes(invoice)&&(await text('tbody')).includes('Selesai'));
    await link('Lihat invoice '+invoice);check('13 invoice final Rp150.000',(await text('[data-testid="total-cost"]'))==='Rp150.000');check('13 invoice memuat garansi',(await text('main')).includes(warrantyDate.split('-').reverse().join('/')));await save('invoice-final-desktop.jpg');
    await goto('invoice&id='+fixture.other_order);check('13 IDOR invoice lain ditolak',(await text('main')).includes('404'));await save('idor-denied.jpg');
    for(const width of [375,768,1440]) {await responsive('dashboard',width);await save('customer-dashboard-'+width+'.jpg');await responsive('invoice&id='+orderId,width);}
    await size(1440);await goto('dashboard');await click('Keluar');await login(fixture.admin_phone);
    for(const width of [375,768,1440])for(const route of ['dashboard','orders','invoices','customers','parts','service-new','customer-new','part-new']) await responsive(route,width);
    await size(1440);await goto('dashboard');await save('admin-desktop.jpg');
    return {results,metrics,orderId,partId,invoice,warrantyDate};
  } catch(error) { error.results=results;throw error; }
}
