export async function runDeviceDetails({tab,viewport,fixture,save,base='http://127.0.0.1:8081/'}) {
  const results=[];const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});if(!ok)throw new Error(name);};
  await tab.goto(base+'index.php?page=dashboard');
  if(await tab.playwright.locator('.logout').count())await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name:'Keluar',exact:true}).click(),{waitUntil:'domcontentloaded'});
  await tab.goto(base+'index.php?page=login');
  await tab.playwright.locator('[name="phone"]').fill(fixture.customer_phone);await tab.playwright.locator('[name="password"]').fill(fixture.password);
  await tab.playwright.expectNavigation(()=>tab.playwright.getByRole('button',{name:'Masuk',exact:true}).click(),{waitUntil:'domcontentloaded'});
  await tab.playwright.waitForURL(base+'index.php?page=dashboard',{waitUntil:'domcontentloaded'});
  check('21 login pelanggan untuk rincian servis',await tab.playwright.locator('.customer-header').count()===1);
  for(const width of [375,768,1440]) {
    await viewport.set({width,height:900}); const actual=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));
    if(actual.w!==width)await viewport.set({width:Math.round(width*width/actual.w),height:Math.round(900*900/actual.h)});
    for(const [id,expected,kind] of [[fixture.own_order,fixture.own_device,'normal'],[fixture.device_edge_order,fixture.device_edge_expected,'custom']]) {
      await tab.goto(base+'index.php?page=order&id='+id); await tab.ax.get();
      const shown=await tab.playwright.evaluate(()=>Object.fromEntries([...document.querySelectorAll('.detail-list>div')].map(row=>[row.querySelector('dt').textContent,row.querySelector('dd').textContent])));
      check('21 '+kind+' '+width+'px tiga rincian berlabel benar',Object.entries(expected).every(([label,value])=>shown[label]===value));
      check('21 '+kind+' '+width+'px tanpa overflow atau HTML input aktif',await tab.playwright.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w&&!document.querySelector('.detail-list [onerror]'),width));
      if(width===375&&kind==='normal')await save('device-details-mobile.png');
    }
  }
  await tab.goto(base+'index.php?page=order&id='+fixture.other_order);await tab.ax.get();
  check('21 rincian akun lain tetap ditolak',(await tab.playwright.locator('main').innerText()).includes('404'));
  return results;
}
