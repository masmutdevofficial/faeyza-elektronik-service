// Read-only verification. Never clicks Simpan status or submits order mutations.
export async function verifyInvoiceFilterProduction({tab,viewport,save,base='https://faeyza.dirgasatya.id/'}) {
  const results=[];const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw Object.assign(new Error(name),{results});};
  const filter=async status=>{
    await tab.playwright.expectNavigation(()=>tab.playwright.locator('[data-status-filter]').selectOption(status),{waitUntil:'domcontentloaded'});
    await tab.playwright.waitForURL(base+'index.php?'+new URLSearchParams({page:'orders',q:'',status}),{waitUntil:'domcontentloaded'});await tab.ax.get();
  };
  const size=async width=>{await viewport.set({width,height:900});const v=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));if(v.w!==width)await viewport.set({width:Math.round(width*width/v.w),height:Math.round(900*900/v.h)});};
  check('Produksi filter otomatis dan aset versi 10 tersedia',await tab.playwright.locator('[data-status-filter]').count()===1&&await tab.playwright.locator('script[src="assets/app.js?v=10"]').count()===1);
  for(const status of ['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart','Selesai','Sudah Diambil','Dibatalkan']) {
    await filter(status);const badges=await tab.playwright.locator('main .badge').allTextContents();
    check('Produksi pilih '+status+' tanpa Cari hanya memuat hasil cocok',badges.every(value=>value.trim()===status)&&(badges.length>0||(await tab.playwright.locator('main').innerText()).includes('Servis tidak ditemukan')));
  }
  await filter('');check('Produksi Semua status mengembalikan daftar',await tab.playwright.locator('main .badge').count()>0);
  const href=await tab.playwright.locator('tbody .table-primary').first().getAttribute('href');const id=new URL(href,base).searchParams.get('id');
  await tab.goto(base+'index.php?page=invoice&id='+id);await tab.ax.get();
  check('Produksi invoice memakai modul cetak mandiri versi baru',await tab.playwright.locator('script[src="assets/invoice-print.js?v=1"]').count()===1&&await tab.playwright.locator('script[src^="assets/app.js"]').count()===0);
  check('Produksi tombol Cetak invoice aktif',await tab.playwright.getByRole('button',{name:'Cetak invoice',exact:true}).isEnabled());
  await tab.playwright.getByText('Cetak lewat menu browser',{exact:true}).click();await tab.ax.get();
  check('Produksi bantuan cetak dapat dibuka',await tab.playwright.locator('.print-help[open]').count()===1&&(await tab.playwright.locator('.print-help').innerText()).includes('Bagikan'));
  for(const width of [375,768,1440]) {
    await size(width);check('Produksi invoice '+width+'px tanpa overflow',await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
  }
  await size(375);await tab.goto(base+'index.php?page=orders');await filter('Selesai');
  check('Produksi filter Selesai 375px tanpa overflow',await tab.playwright.evaluate(()=>innerWidth===375&&document.documentElement.scrollWidth<=375));
  if(await tab.playwright.locator('tbody tr').count())await tab.playwright.getByRole('columnheader',{name:'STATUS',exact:true}).click();
  await tab.ax.get();await save('invoice-filter-production-mobile.jpg');
  check('Produksi tanpa error JavaScript',(await tab.dev.logs({levels:['error'],limit:50})).length===0);
  return {results,id};
}
