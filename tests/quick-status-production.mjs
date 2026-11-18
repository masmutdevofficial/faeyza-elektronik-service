// Authenticated admin session required. Read-only: never submits a service form.
export async function verifyLiveQuickStatus({tab,viewport,base='https://faeyza.dirgasatya.id/'}) {
  const results=[];
  const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw Object.assign(new Error(name),{results});};
  const goto=route=>tab.goto(base+'index.php?page='+route);
  const dialog=tab.playwright.locator('[data-status-dialog]');
  const size=async width=>{await viewport.set({width,height:900});const s=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));if(s.w!==width)await viewport.set({width:Math.round(width*width/s.w),height:Math.round(900*900/s.h)});};
  const first=tab.playwright.locator('tbody .table-primary').first();
  const invoice=await first.innerText();const href=await first.getAttribute('href');const id=new URL(href,base).searchParams.get('id');
  const currentStatus=await tab.playwright.locator('tbody tr').first().locator('.badge').innerText();
  check('Produksi aset versi 8 dimuat',await tab.playwright.evaluate(()=>!!document.querySelector('script[src="assets/app.js?v=8"]')&&!!document.querySelector('link[href="assets/app.css?v=8"]')));
  check('Produksi setiap baris servis memiliki aksi status',await tab.playwright.locator('[data-status-open]').count()===await tab.playwright.locator('tbody tr').count());
  await tab.playwright.getByRole('link',{name:'Ubah status '+invoice,exact:true}).click();await tab.ax.get();
  check('Produksi dialog terbuka langsung dari daftar',await dialog.isVisible());
  check('Produksi dialog hanya memuat tujuh status dan tanggal',await dialog.locator('[name="new_status"] option').count()===7&&await dialog.locator('[name="model"], [name="total_cost"]').count()===0);
  for(const status of ['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan']) {
    await dialog.locator('[name="new_status"]').selectOption(status);
    check('Produksi aturan tanggal/konfirmasi '+status,await tab.playwright.evaluate(({status,currentStatus})=>{
      const root=document.querySelector('[data-status-dialog]');const complete=['Selesai','Sudah Diambil'].includes(status);
      return root.querySelector('[name="completed_date"]').required===complete&&root.querySelector('[name="completed_date"]').disabled===!complete&&root.querySelector('[name="warranty_date"]').disabled===!complete&&root.querySelector('[name="confirm_cancel"]').required===(status==='Dibatalkan'&&currentStatus!=='Dibatalkan');
    },{status,currentStatus}));
  }
  await dialog.getByRole('button',{name:'Batal',exact:true}).click();check('Produksi Batal menutup dialog',!await dialog.isVisible());
  await tab.playwright.getByRole('link',{name:invoice,exact:true}).click();await tab.ax.get();
  const originalDetail=await tab.playwright.locator('.detail-grid').innerText();
  check('Produksi detail memisahkan Ubah status dan Edit servis',await tab.playwright.getByRole('link',{name:'Ubah status',exact:true}).count()===1&&await tab.playwright.getByRole('link',{name:'Edit servis',exact:true}).count()===1);
  for(const width of [375,768,1440]) {
    await size(width);await goto('orders&q='+encodeURIComponent(invoice));await tab.playwright.locator('[data-status-open]').click();await dialog.locator('[name="new_status"]').selectOption('Selesai');await tab.ax.get();
    check('Produksi dialog daftar '+width+'px terlihat tanpa overflow',await dialog.isVisible()&&await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
    await dialog.getByRole('button',{name:'Tutup',exact:true}).click();await goto('order&id='+id);await tab.playwright.getByRole('link',{name:'Ubah status',exact:true}).click();await dialog.locator('[name="new_status"]').selectOption('Dibatalkan');await tab.ax.get();
    check('Produksi dialog detail '+width+'px terlihat tanpa overflow',await dialog.isVisible()&&await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
    await dialog.locator('[name="new_status"]').press('Escape');check('Produksi Escape '+width+'px menutup dialog',!await dialog.isVisible());
  }
  const fallback=await tab.playwright.getByRole('link',{name:'Ubah status',exact:true}).getAttribute('href');await tab.goto(new URL(fallback,base).href);await tab.ax.get();
  check('Produksi halaman status ringkas tersedia',await tab.playwright.getByRole('button',{name:'Simpan status',exact:true}).count()===1&&await tab.playwright.locator('[name="model"]').count()===0);
  await goto('order&id='+id);await tab.ax.get();check('Produksi status biaya dan rincian tetap utuh setelah pratinjau',(await tab.playwright.locator('.detail-grid').innerText())===originalDetail);
  check('Produksi tanpa error console',(await tab.dev.logs({levels:['error'],limit:50})).length===0);
  return {results,id,invoice};
}
