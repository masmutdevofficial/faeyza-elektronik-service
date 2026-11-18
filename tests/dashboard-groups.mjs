// Read-only UI checks. Local fixtures add coverage for every member of each group.
export async function runDashboardGroups({tab,viewport,save,base='http://127.0.0.1:8081/',fixtures=true}) {
  const results=[];const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw Object.assign(new Error(name),{results});};
  const goto=route=>tab.goto(base+'index.php?page='+route);
  const allowed={working:['Menunggu Konfirmasi','Diterima','Dalam Pengerjaan','Menunggu Sparepart'],done:['Selesai','Sudah Diambil']};
  const only=async group=>(await tab.playwright.locator('main .badge').allTextContents()).every(value=>allowed[group].includes(value.trim()));
  const size=async width=>{await viewport.set({width,height:900});const actual=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));if(actual.w!==width)await viewport.set({width:Math.round(width*width/actual.w),height:Math.round(900*900/actual.h)});};
  await goto('dashboard');
  const cards=await tab.playwright.evaluate(()=>[...document.querySelectorAll('.stat-card')].map(card=>({label:card.querySelector('p').textContent,value:Number(card.querySelector('strong').textContent),href:card.getAttribute('href')})));
  for(const [label,group] of [['Dalam proses','working'],['Selesai','done']]) {
    await goto('dashboard');
    await tab.playwright.expectNavigation(()=>tab.playwright.locator('.stat-card').filter({hasText:label}).click(),{waitUntil:'domcontentloaded'});await tab.ax.get();
    check('29 kartu '+label+' membuka kelompok yang tepat',new URL(await tab.url()).searchParams.get('status')===group&&await tab.playwright.evaluate(value=>document.querySelector('[data-status-filter]').value===value,group));
    check('29 daftar '+label+' hanya memuat anggota kelompok',await only(group));
    check('29 jumlah hasil awal sesuai angka kartu '+label,await tab.playwright.locator('main .badge').count()===Math.min(cards.find(card=>card.label===label).value,20));
  }
  await tab.playwright.expectNavigation(()=>tab.playwright.locator('[data-status-filter]').selectOption('Selesai'),{waitUntil:'domcontentloaded'});await tab.ax.get();
  check('29 filter Selesai tunggal tetap dapat dipilih',(await tab.playwright.locator('main .badge').allTextContents()).every(value=>value.trim()==='Selesai'));
  await tab.back();await tab.ax.get();
  check('29 Back mengembalikan pilihan dan hasil kelompok selesai',await tab.playwright.evaluate(()=>document.querySelector('[data-status-filter]').value==='done')&&await only('done'));
  await tab.playwright.expectNavigation(()=>tab.playwright.locator('[data-status-filter]').selectOption('working'),{waitUntil:'domcontentloaded'});await tab.ax.get();
  check('29 dropdown kelompok Dalam proses langsung menyaring',new URL(await tab.url()).searchParams.get('status')==='working'&&await only('working'));
  if(await tab.playwright.locator('[data-status-open]').count()) {
    const statusHref=await tab.playwright.locator('[data-status-open]').first().getAttribute('href');await tab.goto(new URL(statusHref,base).href);await tab.ax.get();
    await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('link',{name:'Batal',exact:true}).click(),{waitUntil:'domcontentloaded'});await tab.ax.get();
    check('29 Batal ubah status kembali ke kelompok yang sama',new URL(await tab.url()).searchParams.get('status')==='working'&&await only('working'));
  }
  if(fixtures) {
    await goto('orders&q=Filter+QA&status=working');await tab.ax.get();const working=await tab.playwright.locator('main .badge').allTextContents();
    check('29 empat status proses termasuk Menunggu Konfirmasi',working.length===5&&await only('working')&&working.includes('Menunggu Konfirmasi'));
    await goto('orders&q=Filter+QA&status=done');await tab.ax.get();const done=await tab.playwright.locator('main .badge').allTextContents();
    check('29 selesai menggabungkan Selesai dan Sudah Diambil',done.length===2&&done.includes('Selesai')&&done.includes('Sudah Diambil'));
    await goto('invoices&q=Filter+QA&status=done');await tab.ax.get();check('29 pencarian invoice mempertahankan kelompok selesai',await tab.playwright.locator('main .badge').count()===2&&await only('done'));
  }
  for(const width of [375,768,1440]) {
    await size(width);await goto('dashboard');await tab.ax.get();check('29 ringkasan '+width+'px tanpa overflow',await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
    if(width===375)await save('dashboard-groups-mobile.jpg');
  }
  check('29 tidak ada error JavaScript',(await tab.dev.logs({levels:['error'],limit:50})).length===0);
  return results;
}
