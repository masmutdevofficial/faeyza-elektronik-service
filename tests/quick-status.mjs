// Run through the Browser skill, against the separate testing server only.
export async function runQuickStatus({tab,viewport,fixture,business,save,base='http://127.0.0.1:8081/'}) {
  const results=[];
  const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw Object.assign(new Error(name),{results});};
  const goto=route=>tab.goto(base+'index.php?page='+route);
  const dialog=tab.playwright.locator('[data-status-dialog]');
  const open=async ()=>{await tab.playwright.locator('[data-status-open]').click();await tab.ax.get();};
  const select=status=>dialog.locator('[name="new_status"]').selectOption(status);
  const submit=async route=>{
    await tab.playwright.expectNavigation(()=>dialog.getByRole('button',{name:'Simpan status',exact:true}).click(),{waitUntil:'domcontentloaded'});
    await tab.playwright.waitForURL(base+'index.php?page='+route,{waitUntil:'domcontentloaded'});await tab.ax.get();
  };
  const size=async width=>{await viewport.set({width,height:900});const current=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));if(current.w!==width)await viewport.set({width:Math.round(width*width/current.w),height:Math.round(900*900/current.h)});};
  try {
    await goto('orders&q='+encodeURIComponent(business.invoice));await open();
    check('26 dialog dibuka langsung dari daftar servis',await dialog.isVisible()&&(await tab.url()).includes('page=orders'));
    check('26 dialog hanya memuat status dan tanggal',await dialog.locator('[name="new_status"] option').count()===7&&await dialog.locator('[name="model"], [name="total_cost"]').count()===0);
    await select('Diterima');await submit('orders');
    await goto('order&id='+business.orderId);
    check('26 status cepat tersimpan dari daftar',(await tab.playwright.locator('.detail-panel .badge').innerText())==='Diterima');
    check('26 biaya dan perangkat tetap utuh',(await tab.playwright.getByTestId('total-cost').innerText())==='Rp150.000'&&(await tab.playwright.locator('.detail-device').innerText()).includes('Bravia 43 E2E'));
    await open();
    for(const status of ['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan']) {
      await select(status);const complete=['Selesai','Sudah Diambil'].includes(status);
      check('26 dialog tanggal dan konfirmasi mengikuti '+status,await tab.playwright.evaluate(({complete,status})=>{
        const form=document.querySelector('[data-status-dialog] form');const done=form.querySelector('[name="completed_date"]');return done.disabled===!complete&&done.required===complete&&form.querySelector('[name="warranty_date"]').disabled===!complete&&form.querySelector('[name="confirm_cancel"]').required===(status==='Dibatalkan');
      },{complete,status}));
    }
    await select('Selesai');await dialog.getByRole('button',{name:'Simpan status',exact:true}).click();await tab.ax.get();
    check('26 tanggal kosong dicegah oleh validasi browser',await dialog.isVisible()&&await tab.playwright.evaluate(()=>!document.querySelector('[data-status-dialog] [name="completed_date"]').validity.valid));
    const today=new Date().toLocaleDateString('en-CA',{timeZone:'Asia/Jakarta'});
    await dialog.locator('[name="completed_date"]').fill(today);await dialog.locator('[name="warranty_date"]').fill(business.warrantyDate);await submit('order&id='+business.orderId);
    check('26 selesai tersimpan melalui dialog',(await tab.playwright.locator('.detail-panel .badge').innerText())==='Selesai');
    await open();await select('Dibatalkan');await dialog.getByRole('button',{name:'Simpan status',exact:true}).click();await tab.ax.get();
    check('26 pembatalan tidak terkirim tanpa konfirmasi',await dialog.isVisible()&&await dialog.locator('[name="confirm_cancel"]').isVisible());
    await dialog.locator('[name="confirm_cancel"]').check();await submit('order&id='+business.orderId);
    await goto('part&id='+business.partId);
    check('26 pembatalan cepat mengembalikan stok delapan menjadi sepuluh',(await tab.playwright.locator('.inventory-balance').innerText()).startsWith('10'));
    await goto('order&id='+business.orderId);await open();await select('Diterima');await submit('order&id='+business.orderId);
    await open();await dialog.getByRole('button',{name:'Batal',exact:true}).click();await tab.ax.get();
    check('26 tombol batal menutup dialog tanpa menyimpan',!await dialog.isVisible());
    await open();await dialog.locator('[name="new_status"]').press('Escape');await tab.ax.get();
    check('26 Escape menutup dialog dan mengembalikan fokus',!await dialog.isVisible()&&await tab.playwright.evaluate(()=>document.activeElement.hasAttribute('data-status-open')));
    for(const width of [375,768,1440]) {
      await size(width);await goto('orders&q='+encodeURIComponent(business.invoice));await open();await select('Selesai');await tab.ax.get();
      check('26 dialog daftar '+width+'px terbuka tanpa overflow',await dialog.isVisible()&&await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w&&document.querySelector('[data-status-dialog]').getBoundingClientRect().width<=w,width));
      if(width===375)await save('quick-status-dialog-mobile.png');
      await dialog.getByRole('button',{name:'Batal',exact:true}).click();
      await goto('order&id='+business.orderId);await open();await select('Dibatalkan');await tab.ax.get();
      check('26 dialog detail '+width+'px terbuka tanpa overflow',await dialog.isVisible()&&await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
      if(width===1440)await save('quick-status-dialog-desktop.png');
      await dialog.getByRole('button',{name:'Batal',exact:true}).click();
      const statusUrl=await tab.playwright.locator('[data-status-open]').getAttribute('href');
      await tab.goto(new URL(statusUrl,base).href);await tab.ax.get();
      check('26 halaman status cadangan '+width+'px tanpa overflow',await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w&&!!document.querySelector('[data-status-form]')&&!document.querySelector('[name="model"]'),width));
    }
    await goto('order&id='+business.orderId);await open();await select('Selesai');await dialog.locator('[name="completed_date"]').fill(today);await dialog.locator('[name="warranty_date"]').fill(business.warrantyDate);await submit('order&id='+business.orderId);
    await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name:'Keluar',exact:true}).click(),{waitUntil:'domcontentloaded'});await tab.playwright.waitForURL(base+'admin/',{waitUntil:'domcontentloaded'});
    await goto('login');await tab.playwright.locator('[name="phone"]').fill(business.customerPhone);await tab.playwright.locator('[name="password"]').fill(fixture.password);
    await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name:'Masuk',exact:true}).click(),{waitUntil:'domcontentloaded'});await tab.playwright.waitForURL(base+'index.php?page=dashboard',{waitUntil:'domcontentloaded'});
    await goto('order&id='+business.orderId);await tab.ax.get();
    check('26 pelanggan melihat status terbaru dan biaya tetap',(await tab.playwright.locator('.detail-panel .badge').innerText())==='Selesai'&&(await tab.playwright.getByTestId('total-cost').innerText())==='Rp150.000');
    check('26 pelanggan tidak mendapat aksi atau dialog status',await tab.playwright.locator('[data-status-open], [data-status-dialog]').count()===0);
    check('26 tidak ada error console',(await tab.dev.logs({levels:['error'],limit:50})).length===0);
    return results;
  } catch(error) { error.results=results;throw error; }
}
