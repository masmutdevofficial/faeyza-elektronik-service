// Actual UI interactions against the isolated testing database only.
export async function runInvoiceFilter({tab,viewport,fixture,filterFixture,save,base='http://127.0.0.1:8081/'}) {
  const results=[];const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw Object.assign(new Error(name),{results});};
  const goto=route=>tab.goto(base+'index.php?page='+route);
  const select=async (status,page='orders')=>{
    await tab.playwright.expectNavigation(()=>tab.playwright.locator('[data-status-filter]').selectOption(status),{waitUntil:'domcontentloaded'});
    await tab.playwright.waitForURL(base+'index.php?'+new URLSearchParams({page,q:'Filter QA',status}),{waitUntil:'domcontentloaded'});await tab.ax.get();
  };
  const only=async status=>{const badges=await tab.playwright.locator('main .badge').allTextContents();return badges.length>0&&badges.every(value=>value.trim()===status);};
  const size=async width=>{await viewport.set({width,height:900});const v=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));if(v.w!==width)await viewport.set({width:Math.round(width*width/v.w),height:Math.round(900*900/v.h)});};
  try {
    if((await tab.url())!==base+'index.php?page=orders&q=Filter+QA')await goto('orders&q=Filter+QA');
    const initialCount=await tab.playwright.locator('main .badge').count();
    for(const status of ['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan']) {
      await select(status);check('27 pilih '+status+' langsung menyaring tanpa Cari',await only(status));
    }
    await select('');check('27 Semua status mengembalikan seluruh hasil awal',(await tab.playwright.locator('main .badge').count())===initialCount&&initialCount>=8);
    const allUrl=await tab.url();await select('Selesai');await tab.back();await tab.playwright.waitForURL(allUrl,{waitUntil:'domcontentloaded'});await tab.ax.get();
    check('27 Back tidak meninggalkan pilihan Selesai pada daftar Semua status',await tab.playwright.locator('main .badge').count()===initialCount&&await tab.playwright.evaluate(()=>document.querySelector('[data-status-filter]').value===''));
    await select('Diterima');const targetInvoice=await tab.playwright.locator('tbody .table-primary').first().innerText();
    await goto('orders&q='+encodeURIComponent(targetInvoice)+'&status=Diterima');await tab.playwright.locator('[data-status-open]').click();await tab.ax.get();
    const dialog=tab.playwright.locator('[data-status-dialog]');await dialog.locator('[name="new_status"]').selectOption('Dalam Pengerjaan');
    await tab.playwright.expectNavigation(()=>dialog.getByRole('button',{name:'Simpan status',exact:true}).click(),{waitUntil:'domcontentloaded'});
    await tab.playwright.waitForURL(base+'index.php?'+new URLSearchParams({page:'orders',q:targetInvoice,status:'Diterima',p:'1'}),{waitUntil:'domcontentloaded'});await tab.ax.get();
    check('27 setelah ubah status pencarian dan filter tetap aktif',await tab.playwright.evaluate(invoice=>document.querySelector('[name="q"]').value===invoice&&document.querySelector('[data-status-filter]').value==='Diterima',targetInvoice));
    check('27 baris yang tidak cocok hilang dan empty state tampil',await tab.playwright.locator('main .badge').count()===0&&(await tab.playwright.locator('main').innerText()).includes('Servis tidak ditemukan'));
    await goto('invoices&q=Filter+QA');await select('Selesai','invoices');check('27 filter daftar invoice juga otomatis',await only('Selesai'));
    for(const width of [375,768,1440]) {
      await size(width);await goto('orders&q=Filter+QA');await select('Selesai');
      check('27 filter Selesai '+width+'px konsisten tanpa overflow',await only('Selesai')&&await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
      if(width===375)await save('invoice-filter-mobile.png');
    }
    await goto('invoice&id='+filterFixture.orders.Selesai);await tab.ax.get();
    check('28 halaman invoice memuat modul cetak terpisah',await tab.playwright.evaluate(()=>!!document.querySelector('script[src="assets/invoice-print.js?v=1"]')&&!document.querySelector('script[src^="assets/app.js"]')));
    check('28 tombol cetak dapat dipakai admin',await tab.playwright.getByRole('button',{name:'Cetak invoice',exact:true}).isEnabled());
    await tab.playwright.getByText('Cetak lewat menu browser',{exact:true}).click();await tab.ax.get();check('28 bantuan alternatif cetak dapat dibuka',(await tab.playwright.locator('.print-help').innerText()).includes('Bagikan'));
    await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('link',{name:'Detail servis',exact:true}).click(),{waitUntil:'domcontentloaded'});await tab.ax.get();
    await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name:'Keluar',exact:true}).click(),{waitUntil:'domcontentloaded'});await tab.playwright.waitForURL(base+'admin/',{waitUntil:'domcontentloaded'});
    await goto('login');await tab.playwright.locator('[name="phone"]').fill(filterFixture.phone);await tab.playwright.locator('[name="password"]').fill(fixture.password);
    await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name:'Masuk',exact:true}).click(),{waitUntil:'domcontentloaded'});await tab.playwright.waitForURL(base+'index.php?page=dashboard',{waitUntil:'domcontentloaded'});
    await goto('invoices&q=Filter+QA');await select('Selesai','invoices');check('27 filter pelanggan otomatis dan hanya status pilihan',await only('Selesai')&&await tab.playwright.locator('main .badge').count()===1);
    await goto('invoice&id='+filterFixture.orders.Selesai);await tab.ax.get();check('28 pemilik pelanggan dapat membuka invoice dengan tombol cetak',await tab.playwright.getByRole('button',{name:'Cetak invoice',exact:true}).isEnabled());
    check('27/28 tidak ada error JavaScript',(await tab.dev.logs({levels:['error'],limit:50})).length===0);
    return results;
  } catch(error) {error.results=results;throw error;}
}
