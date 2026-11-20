// Isolated headless browser QA. Never controls the user's browser or remote desktop.
import {createRequire} from 'node:module';
import fs from 'node:fs/promises';
import {runBrowserE2E} from './browser-e2e.mjs';
import {runQuickStatus} from './quick-status.mjs';
import {runInvoiceFilter} from './invoice-filter.mjs';
const require=createRequire(import.meta.url);
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.TEST_BASE_URL||'http://127.0.0.1:8081/';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw new Error('QA only allows a local testing server');
const fixture=JSON.parse(await fs.readFile(new URL('./local-fixtures.json',import.meta.url),'utf8'));
const fix=JSON.parse(await fs.readFile(new URL('./local-summary-stock-fixtures.json',import.meta.url),'utf8'));
const filterFixture=JSON.parse(await fs.readFile(new URL('./local-filter-fixtures.json',import.meta.url),'utf8'));
const artifact=name=>new URL('./artifacts/'+name,import.meta.url);
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:1440,height:900}});const page=await context.newPage();const errors=[];
page.on('pageerror',error=>errors.push({level:'error',message:error.message}));
const api=new Proxy(page,{get(target,key){if(key==='expectNavigation')return async(action)=>{const wait=page.waitForNavigation({waitUntil:'domcontentloaded'});await action();return wait;};const value=target[key];return typeof value==='function'?value.bind(target):value;}});
const tab={playwright:api,ax:{get:()=>page.waitForLoadState('networkidle')},goto:url=>page.goto(url),url:async()=>page.url(),reload:()=>page.reload(),back:()=>page.goBack(),dev:{logs:async()=>errors}};
const viewport={set:({width,height})=>page.setViewportSize({width,height})};
const save=name=>page.screenshot({path:artifact(name).pathname.replace(/^\/(\w:)/,'$1'),fullPage:false});
const results=[];const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});console.log((ok?'PASS ':'FAIL ')+name);if(!ok)throw new Error(name);};
const goto=route=>page.goto(base+'index.php?page='+route);
const click=async name=>{await Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.getByRole('button',{name,exact:true}).click()]);await tab.ax.get();};
const loginAdmin=async()=>{await page.goto(base+'admin/');await page.locator('[name="phone"]').fill(fixture.admin_phone);await page.locator('[name="password"]').fill(fixture.password);await click('Masuk');};
const balance=async()=>Number((await page.locator('.inventory-balance').innerText()).trim().split(/\s/)[0]);
const cards=()=>page.locator('.stat-card').evaluateAll(nodes=>Object.fromEntries(nodes.map(node=>[node.querySelector('p').textContent,Number(node.querySelector('strong').textContent)])));
const correct=async(quantity,reason)=>{await page.locator('[name="stock_quantity"]').fill(String(quantity));await page.locator('[name="stock_reason"]').fill(reason);await click('Simpan penyesuaian stok');};
const write=async(name,data)=>fs.writeFile(artifact(name),JSON.stringify(data,null,2));
let business,dialog,filters;
try {
  await loginAdmin();await goto('part&id='+fix.part);
  check('31 form koreksi tersedia dan stok awal 2',await balance()===2&&await page.getByRole('button',{name:'Simpan penyesuaian stok',exact:true}).isVisible());
  await correct(1,'Barang rusak saat hitung fisik');check('31 UI mengurangi stok 2 ke 1',await balance()===1);
  check('31 histori memuat alasan koreksi dan delta minus',await page.locator('tbody').innerText().then(text=>text.includes('Barang rusak saat hitung fisik')&&text.includes('−1')||text.includes('Barang rusak saat hitung fisik')&&text.includes('-1')));
  const historyCount=await page.locator('tbody tr').count();await page.reload();check('31 refresh tidak mengulang koreksi',await balance()===1&&await page.locator('tbody tr').count()===historyCount);
  await correct(0,'Koreksi seluruh stok habis');check('31 UI dapat menyimpan stok habis nol',await balance()===0&&await page.getByText('Stok habis',{exact:true}).isVisible());
  await goto('parts&q=ADJUST-QA');check('31 daftar menampilkan stok habis',await page.locator('tbody').innerText().then(text=>text.includes('0 pcs')&&text.includes('Stok habis')));
  await goto('part-edit&id='+fix.part);check('31 edit sparepart menyediakan tautan koreksi stok',await page.getByRole('link',{name:'Sesuaikan stok',exact:true}).isVisible());
  await page.getByRole('link',{name:'Sesuaikan stok',exact:true}).click();await page.waitForURL(base+'index.php?page=part&id='+fix.part+'#stock-adjustment');
  check('31 tautan edit mengarah ke form penyesuaian',await page.locator('#stock-adjustment').isVisible());
  await page.locator('[name="stock_quantity"]').fill('1');await page.locator('[name="stock_reason"]').fill('');await page.getByRole('button',{name:'Simpan penyesuaian stok',exact:true}).click();
  check('31 alasan wajib mencegah submit kosong',await page.locator('[name="stock_reason"]').evaluate(node=>!node.validity.valid)&&await balance()===0);
  const peer=await context.newPage();await peer.goto(base+'index.php?page=part&id='+fix.part);await peer.locator('[name="quantity"]').fill('5');await peer.locator('[name="part_type"]').fill('TPA3116D2');await Promise.all([peer.waitForNavigation(),peer.getByRole('button',{name:'Tambah stok',exact:true}).click()]);
  await page.locator('[name="stock_quantity"]').fill('0');await page.locator('[name="stock_reason"]').fill('Koreksi formulir lama');await click('Simpan penyesuaian stok');
  check('31 perubahan stale ditolak dengan stok baru tetap 5',await balance()===5&&(await page.locator('main').innerText()).includes('sudah berubah'));
  check('31 kegagalan mempertahankan jumlah dan alasan',await page.locator('[name="stock_quantity"]').inputValue()==='0'&&await page.locator('[name="stock_reason"]').inputValue()==='Koreksi formulir lama');
  await page.getByRole('link',{name:'Muat ulang',exact:true}).click();await tab.ax.get();check('31 muat ulang mengambil stok dan revisi terkini',await page.locator('[name="stock_quantity"]').inputValue()==='5');await peer.close();
  await correct(0,'Stock opname final stok habis');
  for(const width of [375,768,1440]) {
    await viewport.set({width,height:900});await goto('part&id='+fix.part);
    check('31 form koreksi '+width+'px tanpa overflow',await page.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));
    check('31 tombol koreksi '+width+'px dapat digunakan',await page.getByRole('button',{name:'Simpan penyesuaian stok',exact:true}).isEnabled());
    if(width===375){await page.locator('#stock-adjustment').scrollIntoViewIfNeeded();await save('summary-stock-adjustment-mobile.jpg');}
  }
  await goto('dashboard');const before=await cards();await goto('customer-delete&id='+fix.customer_id);await page.locator('[name="confirm"]').check();await click('Hapus konsumen');await goto('dashboard');const after=await cards();
  check('30 hapus konsumen mengurangi total servis dan kelompok',before['Total servis']-after['Total servis']===8&&before['Dalam proses']-after['Dalam proses']===4&&before.Selesai-after.Selesai===2);
  check('30 servis terbaru tidak memuat konsumen dihapus',!(await page.locator('main').innerText()).includes('Ringkasan Hapus QA')&&!(await page.locator('main').innerText()).includes('Koreksi stok QA'));
  await goto('orders&q=Ringkasan+Hapus+QA');check('30 daftar servis aktif tidak menampilkan riwayat konsumen dihapus',(await page.locator('main').innerText()).includes('Servis tidak ditemukan'));
  await goto('invoices&q=Ringkasan+Hapus+QA');check('30 invoice historis tetap tersedia',await page.locator('main .badge').count()===7);
  await goto('invoice&id='+fix.orders.Selesai);check('30 invoice konsumen dihapus tetap dapat dicetak admin',await page.getByRole('button',{name:'Cetak invoice',exact:true}).isEnabled());
  await goto('customer&id='+fix.customer_id);await click('Pulihkan');await goto('dashboard');check('30 pemulihan mengembalikan angka semula',JSON.stringify(await cards())===JSON.stringify(before));
  for(const width of [375,768,1440]) {await viewport.set({width,height:900});await goto('dashboard');check('30 ringkasan '+width+'px tanpa overflow',await page.evaluate(w=>innerWidth===w&&document.documentElement.scrollWidth<=w,width));}
  check('30/31 tanpa runtime error JavaScript',errors.length===0);await write('summary-stock-browser-results.json',results);
  business=await runBrowserE2E({tab,viewport,fixture,save:name=>save('summary-stock-regression-'+name)});await write('summary-stock-business-results.json',business);console.log('PASS regression business '+business.results.length);
  dialog=await runQuickStatus({tab,viewport,fixture,business,save:name=>save('summary-stock-status-'+name)});await write('summary-stock-status-results.json',dialog);console.log('PASS regression dialog '+dialog.length);
  // Reset rate buckets only in testing through the guarded CLI helper, never production.
  const {execFileSync}=await import('node:child_process');execFileSync(process.env.PHP_PATH||'C:/project/bengkel-runtime/php/php.exe',['tests/reset-login-limits.php'],{cwd:new URL('..',import.meta.url),stdio:'pipe'});
  await goto('dashboard');await click('Keluar');await loginAdmin();
  filters=await runInvoiceFilter({tab,viewport,fixture,filterFixture,save:name=>save('summary-stock-filter-'+name)});await write('summary-stock-filter-results.json',filters);console.log('PASS regression filters '+filters.length);
  await write('summary-stock-ui-summary.json',{custom:results.length,business:business.results.length,dialog:dialog.length,filters:filters.length,fail:0});
} catch(error) {
  await write('summary-stock-ui-failure.json',{message:error.message,results:error.results??results});await save('summary-stock-ui-failure.jpg');throw error;
} finally {await browser.close();}
