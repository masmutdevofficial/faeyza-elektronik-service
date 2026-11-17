// Dapat dijalankan melalui Browser skill (runBrowserE2E) atau adapter Playwright CLI.
// Seluruh data dibuat di server testing 8081. Jalankan tests/run.php terlebih dahulu.
export async function runBrowserE2E({tab,viewport,fixture,save,base='http://127.0.0.1:8081/'}) {
  const results=[]; const metrics=[];
  const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw new Error(name);};
  const goto=page=>tab.goto(base+(page==='admin-login'?'admin/':'index.php?page='+page));
  const text=selector=>tab.playwright.locator(selector).innerText();
  const currentUrl=()=>tab.playwright.evaluate(()=>location.href);
  const fill=(name,value)=>tab.playwright.locator('[name="'+name+'"]').fill(value);
  const click=async name=>{
    const action=()=>tab.playwright.getByRole('button',{name,exact:true}).click();
    if(name==='Buka navigasi'||name==='Tutup navigasi')return action();
    const adminLogout=name==='Keluar'&&await tab.playwright.evaluate(()=>document.body.classList.contains('admin-body'));
    await tab.playwright.expectNavigation(action,{waitUntil:'domcontentloaded'});
    const expected=({'Buat akun':'dashboard','Masuk':'dashboard','Keluar':'login'})[name];
    if(expected)await tab.playwright.waitForURL(adminLogout?base+'admin/':base+'index.php?page='+expected,{waitUntil:'domcontentloaded'});
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
  const login=async phone=>{await goto(phone===fixture.admin_phone?'admin-login':'login');await fill('phone',phone);await fill('password',fixture.password);await click('Masuk');};
  try {
    await size(1440); await goto('dashboard');
    if(await tab.playwright.locator('.logout').count())await click('Keluar');
    for(const [route,heading,count] of [['admin-login','Masuk admin',1],['login','Masuk',1],['register','Daftar akun',2]]) {
      await goto(route);
      check('19 '+route+' heading benar',(await text('h1'))===heading);
      check('19 '+route+' password tersembunyi sejak awal',await tab.playwright.evaluate(expected=>document.querySelectorAll('[data-password-toggle]').length===expected&&[...document.querySelectorAll('.password-control input')].every(input=>input.type==='password'),count));
      await tab.playwright.getByRole('button',{name:'Tampilkan password',exact:true}).click();
      check('19 '+route+' tampilkan password tanpa submit',await tab.playwright.evaluate(()=>document.querySelector('[name="password"]').type==='text'&&document.querySelector('[aria-controls="auth-password"]').getAttribute('aria-pressed')==='true')&&(await currentUrl()).includes(route==='admin-login'?'/admin/':'page='+route));
      await tab.playwright.getByRole('button',{name:'Sembunyikan password',exact:true}).click();
      check('19 '+route+' sembunyikan password kembali',await tab.playwright.evaluate(()=>document.querySelector('[name="password"]').type==='password'&&document.querySelector('[aria-controls="auth-password"]').getAttribute('aria-pressed')==='false'));
      if(route==='register') {
        await tab.playwright.getByRole('button',{name:'Tampilkan konfirmasi password',exact:true}).click();
        check('19 toggle konfirmasi berdiri sendiri',await tab.playwright.evaluate(()=>document.querySelector('[name="password_confirm"]').type==='text'&&document.querySelector('[name="password"]').type==='password'));
        await tab.playwright.getByRole('button',{name:'Sembunyikan konfirmasi password',exact:true}).click();
        check('19 konfirmasi kembali tersembunyi',await tab.playwright.evaluate(()=>document.querySelector('[name="password_confirm"]').type==='password'));
      }
      check('19 '+route+' logo CSS dan ikon lokal termuat',await tab.playwright.evaluate(()=>document.querySelector('.brand img').naturalWidth>0&&getComputedStyle(document.querySelector('[data-password-toggle]')).position==='absolute'&&document.querySelector('[data-mdi="eye-outline"] path')!==null));
      await tab.playwright.getByRole('button',{name:'Tampilkan password',exact:true}).click();await tab.reload();
      check('19 '+route+' reload kembali tersembunyi',await tab.playwright.evaluate(()=>document.querySelector('[name="password"]').type==='password'));
      if(route==='admin-login')check('19 admin tidak memiliki tautan daftar',await tab.playwright.getByRole('link',{name:'Daftar',exact:true}).count()===0);
      for(const width of [375,768,1440]){await responsive(route,width);if(width===375)await save(route+'-mobile.jpg');}
    }
    await goto('register');
    const phone='0857'+String(Date.now()).slice(-8);
    await fill('name','Rina Simulasi');await fill('address','Jl. Simulasi No. 17, Jakarta');await fill('phone',phone);await fill('password',fixture.password);await fill('password_confirm',fixture.password);await click('Buat akun');
    check('13 registrasi konsumen browser',(await currentUrl()).includes('dashboard'));
    await click('Keluar');await login(phone);check('13 login konsumen baru',(await currentUrl()).includes('dashboard'));
    await goto('service-new');await size(375);
    check('12 form servis 375px',await tab.playwright.evaluate(()=>innerWidth===375&&document.documentElement.scrollWidth<=innerWidth));
    check('12 kontak terisi otomatis dan dilipat',await tab.playwright.evaluate(()=>!!document.querySelector('[name="customer_name"]').value&&!document.querySelector('.contact-details').open));
    check('12 field opsional dilipat',await tab.playwright.evaluate(()=>!document.querySelector('.optional-details').open));
    check('12 navigasi pelanggan langsung tersedia',await tab.playwright.getByRole('link',{name:'Beranda',exact:true}).isVisible());
    await save('service-form-mobile.jpg');
    await tab.playwright.locator('[name="item_name"]').selectOption('Lainnya');check('12 field custom muncul',await tab.playwright.locator('[name="item_name_custom"]').isVisible());
    await tab.playwright.locator('[name="item_name"]').selectOption('TV');await tab.playwright.locator('[name="brand"]').selectOption('SONY');await fill('model','Bravia 43 E2E');await tab.playwright.locator('.optional-details summary').click();await fill('serial_number','E2E-TV-001');
    await tab.playwright.getByLabel('Kabel Power',{exact:true}).check();await tab.playwright.getByLabel('Remote',{exact:true}).check();
    await fill('complaint','TV tidak menyala, indikator merah berkedip.');await fill('notes','Mohon konfirmasi sebelum perbaikan.');
    await tab.playwright.locator('.contact-details summary').click();await fill('customer_name','');await tab.playwright.locator('.contact-details summary').click();
    await tab.playwright.getByRole('button',{name:'Kirim pengajuan servis',exact:true}).click();await tab.ax.get();
    check('12 kontak invalid terbuka otomatis',await tab.playwright.evaluate(()=>document.querySelector('.contact-details').open));await fill('customer_name','Rina Simulasi');await click('Kirim pengajuan servis');
    check('13 submit TV berhasil',(await currentUrl()).includes('success'));
    const invoice=await text('h1');check('13 format invoice otomatis',/^INV-\d{8}-\d{3,}$/.test(invoice));
    const orderId=new URL(await currentUrl()).searchParams.get('id');
    await link('Cetak invoice');check('13 invoice awal biaya -',(await text('[data-testid="total-cost"]'))==='-');
    check('14 invoice awal tanpa tanda tangan',await tab.playwright.locator('[data-testid="workshop-signature"]').count()===0);
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
    check('14 toggle tersembunyi sebelum selesai',!await tab.playwright.getByRole('switch',{name:'Tampilkan tanda tangan bengkel',exact:true}).isVisible());
    await fill('total_cost','150000');await tab.playwright.locator('[name="status"]').selectOption('Selesai');await fill('completed_date',day);await fill('warranty_date',warrantyDate);await fill('technician_notes','Penggantian kapasitor. Perangkat normal setelah pengujian.');
    check('14 selesai memunculkan toggle aktif secara default',await tab.playwright.getByRole('switch',{name:'Tampilkan tanda tangan bengkel',exact:true}).isVisible()&&await tab.playwright.evaluate(()=>document.querySelector('input[type="checkbox"][name="show_workshop_signature"]').checked));
    await click('Simpan perubahan');
    check('13 admin menyelesaikan servis Rp150.000',(await text('.badge'))==='Selesai'&&(await text('[data-testid="total-cost"]'))==='Rp150.000');
    await link('Edit servis');await tab.playwright.getByRole('switch',{name:'Tampilkan tanda tangan bengkel',exact:true}).uncheck();await click('Simpan perubahan');
    await goto('invoice&id='+orderId);check('14 toggle nonaktif menghilangkan tanda tangan',await tab.playwright.locator('[data-testid="workshop-signature"]').count()===0);
    await goto('service-edit&id='+orderId);check('14 pilihan nonaktif bertahan setelah reload',await tab.playwright.evaluate(()=>!document.querySelector('input[type="checkbox"][name="show_workshop_signature"]').checked));
    await tab.playwright.getByRole('switch',{name:'Tampilkan tanda tangan bengkel',exact:true}).check();await click('Simpan perubahan');await goto('invoice&id='+orderId);
    check('14 tanda tangan kembali dan gambar termuat',await tab.playwright.evaluate(()=>document.querySelector('[data-testid="workshop-signature"]')?.naturalWidth>0));await save('invoice-signature-desktop.jpg');
    await goto('part&id='+partId);check('07 histori stok memuat pembelian dan penggunaan',(await text('tbody')).includes('Pembelian simulasi E2E')&&(await text('tbody')).includes('Penyesuaian penggunaan'));await save('stock-history-desktop.jpg');
    await click('Keluar');await login(phone);await goto('invoices');await fill('q',invoice);await click('Cari');check('13 konsumen mencari invoice sendiri',(await text('.service-cards')).includes(invoice)&&(await text('.service-cards')).includes('Selesai'));
    await link('Lihat invoice '+invoice);check('13 invoice final Rp150.000',(await text('[data-testid="total-cost"]'))==='Rp150.000');check('13 invoice memuat garansi',(await text('main')).includes(warrantyDate.split('-').reverse().join('/')));check('14 konsumen melihat tanda tangan yang dipilih admin',await tab.playwright.locator('[data-testid="workshop-signature"]').count()===1);await save('invoice-final-desktop.jpg');
    await goto('dashboard');await link('Pengaturan akun');
    check('18 pelanggan dapat membuka pengaturan akun',(await currentUrl()).includes('page=account'));
    check('18 form pelanggan meminta password saat ini dan minimum enam',await tab.playwright.evaluate(()=>!!document.querySelector('[name="current_password"]')&&document.querySelector('[name="new_password"]').minLength===6&&!document.querySelector('[name="phone"]')));
    for(const width of [375,768,1440]) {await responsive('account',width);if(width===375)await save('customer-account-mobile.jpg');}
    await goto('invoice&id='+fixture.other_order);check('13 IDOR invoice lain ditolak',(await text('main')).includes('404'));await save('idor-denied.jpg');
    for(const width of [375,768,1440]) {await responsive('dashboard',width);await save('customer-dashboard-'+width+'.jpg');await responsive('invoice&id='+orderId,width);}
    await size(1440);await goto('dashboard');await click('Keluar');await login(fixture.admin_phone);
    await goto('customers');await tab.playwright.expectNavigation(()=>tab.playwright.locator('tbody .table-primary').first().click(),{waitUntil:'domcontentloaded'});
    const contextName=await text('h1');
    const contextProfileUrl=await currentUrl();
    const contextHref=await tab.playwright.getByRole('link',{name:'Servis baru',exact:true}).getAttribute('href');
    check('17 tombol detail membawa ID konsumen',contextHref.includes('customer_id='));
    await link('Servis baru');
    check('17 konsumen dari detail sudah terpilih',await text('[data-contact-name]')===contextName);
    await tab.reload();
    check('17 pilihan awal bertahan setelah reload',await tab.playwright.evaluate(()=>{const option=document.querySelector('[data-customer-picker]').selectedOptions[0];return !!option.value&&document.querySelector('[data-contact-name]').textContent===option.dataset.customerName&&!document.querySelector('[data-customer-preview]').hidden;}));
    const customerOptions=await tab.playwright.locator('[data-customer-picker]').evaluate(el=>[...el.options].filter(o=>o.value).map(o=>({id:o.value,name:o.dataset.customerName,phone:o.dataset.customerPhone,address:o.dataset.customerAddress})));
    await tab.playwright.locator('[data-customer-picker]').selectOption(customerOptions[0].id);
    check('15 pilihan konsumen langsung mengisi tiga kontak',await tab.playwright.evaluate(()=>{const option=document.querySelector('[data-customer-picker]').selectedOptions[0];return document.querySelector('[name="customer_name"]').value===option.dataset.customerName&&document.querySelector('[name="address"]').value===option.dataset.customerAddress&&document.querySelector('[data-contact-phone]').textContent===option.dataset.customerPhone;}));
    check('15 kontak tidak perlu diisi ulang',await tab.playwright.evaluate(()=>!document.querySelector('[data-customer-editor]').open&&!document.querySelector('[data-customer-preview]').hidden));
    await tab.playwright.locator('[data-customer-editor] summary').click();await fill('customer_name','Kontak koreksi');
    check('15 pratinjau mengikuti koreksi kontak',(await text('[data-contact-name]'))==='Kontak koreksi');
    await tab.playwright.locator('[data-customer-picker]').selectOption(customerOptions[1].id);
    check('15 mengganti konsumen mengganti seluruh kontak',await tab.playwright.evaluate(()=>{const option=document.querySelector('[data-customer-picker]').selectedOptions[0];return document.querySelector('[name="customer_name"]').value===option.dataset.customerName&&document.querySelector('[name="address"]').value===option.dataset.customerAddress&&document.querySelector('[data-contact-phone]').textContent===option.dataset.customerPhone;}));
    await tab.playwright.locator('[data-customer-picker]').selectOption('');
    check('15 pilihan kosong membersihkan kontak sebelumnya',await tab.playwright.evaluate(()=>!document.querySelector('[name="customer_name"]').value&&document.querySelector('[data-customer-preview]').hidden));
    await tab.playwright.locator('[data-customer-picker]').selectOption(customerOptions[0].id);await size(375);
    check('15 pemilihan konsumen mobile tanpa overflow',await tab.playwright.evaluate(()=>innerWidth===375&&document.documentElement.scrollWidth<=innerWidth));await tab.playwright.locator('[data-customer-preview]').click();await save('customer-autofill-mobile.jpg');
    await tab.playwright.locator('[name="item_name"]').selectOption('TV');await tab.playwright.locator('[name="brand"]').selectOption('LG');await fill('model','Autofill QA');await fill('complaint','Uji pengisian kontak otomatis');await click('Kirim pengajuan servis');
    check('15 pengajuan admin berhasil tanpa mengetik ulang kontak',(await text('main')).includes(customerOptions[0].name)&&(await currentUrl()).includes('success'));
    await tab.goto(contextProfileUrl);await link('Ganti password');
    check('18 admin dapat membuka reset password dari detail konsumen',await tab.playwright.getByRole('button',{name:'Simpan password pelanggan',exact:true}).isVisible());
    check('18 reset pelanggan meminta password admin dan minimum enam',await tab.playwright.evaluate(()=>document.querySelector('[name="current_password"]').type==='password'&&document.querySelector('[name="new_password"]').minLength===6&&document.querySelector('[name="password_confirm"]').minLength===6));
    const resetCustomerId=new URL(contextProfileUrl).searchParams.get('id');
    for(const width of [375,768,1440]){await responsive('customer-password&id='+resetCustomerId,width);await responsive('customer&id='+resetCustomerId,width);}
    await goto('account');check('18 form akun admin memiliki nomor HP dan password',await tab.playwright.getByRole('button',{name:'Simpan nomor HP',exact:true}).isVisible()&&await tab.playwright.getByRole('button',{name:'Simpan password',exact:true}).isVisible());
    await save('admin-account-desktop.jpg');
    for(const width of [375,768,1440])for(const route of ['dashboard','orders','invoices','customers','parts','service-new','customer-new','part-new','account']) await responsive(route,width);
    await size(1440);await goto('dashboard');await save('admin-desktop.jpg');
    return {results,metrics,orderId,partId,invoice,warrantyDate};
  } catch(error) { error.results=results;throw error; }
}
