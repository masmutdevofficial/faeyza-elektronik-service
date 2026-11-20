export async function runCompletionDeletion({tab,viewport,fixture,business,save,progress=async()=>{},base='http://127.0.0.1:8081/'}) {
  const results=[]; const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw Object.assign(new Error(name),{results});};
  const goto=route=>tab.goto(base+'index.php?page='+route);
  const click=async name=>{await progress('click '+name);await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name,exact:true}).click(),{waitUntil:'domcontentloaded'});await progress('navigation '+name);await tab.ax.get();await progress('ready '+name);};
  const link=name=>tab.playwright.expectNavigation(()=>tab.playwright.getByRole('link',{name,exact:true}).click(),{waitUntil:'domcontentloaded'});
  const responsive=async (route,label)=>{
    for(const width of [375,768,1440]) {
      await progress(label+' '+width);
      await viewport.set({width,height:900});const actual=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));
      if(actual.w!==width)await viewport.set({width:Math.round(width*width/actual.w),height:Math.round(900*900/actual.h)});
      await goto(route);await tab.ax.get();check('25 '+label+' '+width+'px tanpa overflow',await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
      if(width===375)await save(label+'-mobile.png');
    }
  };
  await goto('service-edit&id='+business.orderId);
  for(const status of ['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan']) {
    await tab.playwright.locator('[name="status"]').selectOption(status);
    const expected=['Selesai','Sudah Diambil'].includes(status);
    check('22 UI tanggal mengikuti '+status,await tab.playwright.evaluate(complete=>document.querySelector('[name="completed_date"]').disabled===!complete&&document.querySelector('[name="warranty_date"]').disabled===!complete&&document.querySelector('[name="completed_date"]').required===complete,expected));
  }
  await tab.playwright.locator('[name="status"]').selectOption('Menunggu Sparepart');await click('Simpan perubahan');
  await tab.playwright.waitForURL(base+'index.php?page=order&id='+business.orderId,{waitUntil:'domcontentloaded'});
  check('22 tanggal kosong tersimpan setelah status kembali nonfinal',await tab.playwright.evaluate(()=>[...document.querySelectorAll('.summary-panel .detail-list dd')].every(dd=>dd.textContent.trim()==='-')));
  await responsive('service-edit&id='+business.orderId,'service-dates');
  await goto('customer&id='+business.customerId);await link('Hapus konsumen');
  await responsive('customer-delete&id='+business.customerId,'delete-customer-confirm');
  check('23 konfirmasi hapus konsumen tampil',await tab.playwright.getByRole('checkbox',{name:'Saya ingin menghapus konsumen ini.',exact:true}).count()===1);
  await goto('part&id='+business.partId);await link('Hapus sparepart');
  await responsive('part-delete&id='+business.partId,'delete-part-confirm');
  await tab.playwright.getByRole('checkbox',{name:'Saya ingin menghapus sparepart ini.',exact:true}).check();await click('Hapus sparepart');
  await goto('part&id='+business.partId);await responsive('part&id='+business.partId,'deleted-part');check('24 sparepart dihapus mempertahankan stok delapan',(await tab.playwright.locator('.inventory-balance').innerText()).startsWith('8')&&await tab.playwright.getByRole('button',{name:'Tambah stok',exact:true}).count()===0);
  await save('deleted-part-desktop.png');
  await goto('service-edit&id='+business.orderId);await tab.playwright.locator('[name="status"]').selectOption('Dibatalkan');await click('Simpan perubahan');
  await goto('part&id='+business.partId);check('24 pembatalan mengembalikan stok sparepart dihapus menjadi sepuluh',(await tab.playwright.locator('.inventory-balance').innerText()).startsWith('10'));
  await click('Pulihkan');check('24 sparepart dipulihkan dapat menerima stok',await tab.playwright.getByRole('button',{name:'Tambah stok',exact:true}).isVisible());
  await click('Keluar');await tab.playwright.waitForURL(base+'admin/',{waitUntil:'domcontentloaded'});await goto('login');
  await tab.playwright.locator('[name="phone"]').fill(business.customerPhone);await tab.playwright.locator('[name="password"]').fill(fixture.password);await click('Masuk');await tab.playwright.waitForURL(base+'index.php?page=dashboard',{waitUntil:'domcontentloaded'});
  await goto('order&id='+business.orderId);check('22 pelanggan melihat status batal tanpa tanggal selesai/garansi',(await tab.playwright.locator('.badge').innerText())==='Dibatalkan'&&await tab.playwright.evaluate(()=>[...document.querySelectorAll('.summary-panel .detail-list dd')].every(dd=>dd.textContent.trim()==='-')));
  await goto('customer-delete&id='+business.customerId);check('23 halaman penghapusan tetap ditolak untuk pelanggan',(await tab.playwright.locator('main').innerText()).includes('403'));
  await click('Keluar');await goto('admin-login');await tab.playwright.locator('[name="phone"]').fill(fixture.admin_phone);await tab.playwright.locator('[name="password"]').fill(fixture.password);await click('Masuk');
  await goto('customer-delete&id='+business.customerId);await tab.playwright.locator('[name="confirm"]').check();await click('Hapus konsumen');
  check('33 hapus permanen menuju daftar konsumen aktif',!(await tab.url()).includes('deleted=1'));
  await goto('customer&id='+business.customerId);check('33 profil konsumen terhapus tidak dapat dipulihkan',(await tab.playwright.locator('main').innerText()).includes('404'));
  await goto('invoice&id='+business.orderId);check('33 invoice konsumen ikut terhapus',(await tab.playwright.locator('main').innerText()).includes('404'));
  return results;
}
