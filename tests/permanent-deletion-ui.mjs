// Local headless QA only; never touches client data or an existing browser profile.
import {createRequire} from 'node:module';
import fs from 'node:fs/promises';
import {execFileSync} from 'node:child_process';
const require=createRequire(import.meta.url),{chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.TEST_BASE_URL||'http://127.0.0.1:8081/';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw new Error('Local testing only');
const fixture=JSON.parse(await fs.readFile(new URL('./local-fixtures.json',import.meta.url),'utf8'));
const fix=JSON.parse(await fs.readFile(new URL('./local-purge-fixtures.json',import.meta.url),'utf8'));
execFileSync(process.env.PHP_PATH||'C:/project/bengkel-runtime/php/php.exe',['tests/reset-login-limits.php'],{cwd:new URL('..',import.meta.url),stdio:'pipe'});
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:1440,height:950}}),customerContext=await browser.newContext({viewport:{width:375,height:900}});
const page=await context.newPage(),customer=await customerContext.newPage(),errors=[],results=[];
for(const tab of [page,customer])tab.on('pageerror',e=>errors.push(e.message));
const artifact=name=>new URL('./artifacts/'+name,import.meta.url),goto=route=>page.goto(base+'index.php?page='+route);
const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});console.log((ok?'PASS ':'FAIL ')+name);if(!ok)throw new Error(name);};
const click=async name=>Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.getByRole('button',{name,exact:true}).click()]);
const balance=async()=>Number((await page.locator('.inventory-balance').innerText()).trim().split(/\s/)[0]);
const screenshot=name=>page.screenshot({path:artifact(name).pathname.replace(/^\/(\w:)/,'$1'),fullPage:false});
try {
  await page.goto(base+'admin/');await page.locator('[name="phone"]').fill(fixture.admin_phone);await page.locator('[name="password"]').fill(fixture.password);await click('Masuk');
  await customer.goto(base+'index.php?page=login');await customer.locator('[name="phone"]').fill(fix.phone);await customer.locator('[name="password"]').fill(fixture.password);await Promise.all([customer.waitForNavigation(),customer.getByRole('button',{name:'Masuk',exact:true}).click()]);
  await customer.goto(base+'index.php?page=invoice&id='+fix.pending);
  check('33 pelanggan dapat membaca invoice tanpa tombol hapus',await customer.getByRole('button',{name:'Cetak invoice',exact:true}).isVisible()&&await customer.getByRole('link',{name:'Hapus invoice',exact:true}).count()===0);
  const forbidden=await customer.goto(base+'index.php?page=invoice-delete&id='+fix.pending);check('33 pelanggan ditolak pada URL hapus invoice',forbidden.status()===403);
  for(const width of [375,768,1440]) {
    await page.setViewportSize({width,height:950});
    await goto('invoice&id='+fix.legacy_order);
    check('33 nota invoice lama '+width+'px tanpa overflow dan tombol hapus tampil',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)&&await page.getByRole('link',{name:'Hapus invoice',exact:true}).isVisible());
    await page.emulateMedia({media:'print'});check('33 toolbar hapus/cetak tersembunyi saat print '+width+'px',await page.locator('.print-toolbar').isHidden());await page.emulateMedia({media:'screen'});
    if(width===375)await screenshot('purge-invoice-mobile.jpg');
    await page.getByRole('link',{name:'Hapus invoice',exact:true}).click();
    check('33 konfirmasi invoice '+width+'px tanpa overflow dan menyebut permanen',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)&&(await page.locator('main').innerText()).includes('Tidak bisa dipulihkan'));
    if(width===375)await screenshot('purge-invoice-confirm-mobile.jpg');
    await goto('customer-delete&id='+fix.customer);
    check('33 konfirmasi seluruh data '+width+'px tanpa overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)&&(await page.locator('main').innerText()).includes('seluruh 2 invoice'));
  }
  await goto('invoice-delete&id='+fix.legacy_order);await page.getByRole('link',{name:'Batal',exact:true}).click();
  check('33 batal mempertahankan invoice lama',await page.getByRole('button',{name:'Cetak invoice',exact:true}).isVisible());
  await page.getByRole('link',{name:'Hapus invoice',exact:true}).click();await page.getByRole('button',{name:'Hapus invoice',exact:true}).click();
  check('33 checkbox wajib mencegah hapus tanpa konfirmasi',await page.locator('[name="confirm"]').evaluate(node=>!node.validity.valid));
  await page.locator('[name="confirm"]').check();await click('Hapus invoice');
  check('33 hapus invoice lama berhasil kembali ke daftar',new URL(page.url()).searchParams.get('page')==='invoices');
  await goto('invoice&id='+fix.legacy_order);check('33 invoice lama terhapus tidak dapat dicetak',(await page.locator('main').innerText()).includes('404'));
  await goto('customer&id='+fix.legacy_customer);check('33 akun arsip tetap ada setelah satu invoice dihapus',await page.getByRole('link',{name:'Hapus permanen',exact:true}).isVisible());
  await page.getByRole('link',{name:'Hapus permanen',exact:true}).click();await page.locator('[name="confirm"]').check();await click('Hapus konsumen');
  await goto('customer&id='+fix.legacy_customer);check('33 akun arsip lama dapat dibersihkan permanen',(await page.locator('main').innerText()).includes('404'));
  await goto('invoices&q='+encodeURIComponent(fix.pending_invoice));await page.getByRole('link',{name:'Hapus invoice '+fix.pending_invoice,exact:true}).click();
  check('33 tombol daftar invoice membuka konfirmasi yang benar',(await page.locator('main').innerText()).includes(fix.pending_invoice));
  await page.locator('[name="confirm"]').check();await click('Hapus invoice');await goto('part&id='+fix.part);
  check('33 penghapusan invoice baru mengembalikan stok 5 ke 7',await balance()===7);
  await customer.goto(base+'index.php?page=invoice&id='+fix.pending);check('33 invoice baru terhapus hilang juga dari akun pelanggan',(await customer.locator('main').innerText()).includes('404'));
  await goto('invoice&id='+fix.done);check('33 invoice lain masih dapat dibuka',await page.getByRole('button',{name:'Cetak invoice',exact:true}).isVisible());
  await goto('customer-delete&id='+fix.customer);check('33 penghapusan akun menyebut satu invoice tersisa',(await page.locator('main').innerText()).includes('seluruh 1 invoice'));
  await page.locator('[name="confirm"]').check();await click('Hapus konsumen');await goto('invoice&id='+fix.done);
  check('33 hapus akun menghapus invoice terakhir',(await page.locator('main').innerText()).includes('404'));
  await goto('part&id='+fix.part);check('33 invoice selesai terhapus tetap menyisakan stok 7 dan histori',await balance()===7&&await page.locator('tbody tr').count()>=4);
  await customer.goto(base+'index.php?page=account');check('33 akun dihapus kehilangan sesi lama',new URL(customer.url()).searchParams.get('page')==='login');
  await goto('invoice&id='+fixture.own_order);check('33 invoice pelanggan lain tetap dapat dibaca',await page.getByRole('button',{name:'Cetak invoice',exact:true}).isVisible());
  check('33 tanpa error runtime JavaScript',errors.length===0);
} catch(error) {await screenshot('purge-ui-failure.jpg');throw error;}
finally {
  await fs.writeFile(artifact('purge-browser-results.json'),JSON.stringify({results,errors,pass:results.filter(r=>r.status==='PASS').length,fail:results.filter(r=>r.status==='FAIL').length},null,2));await browser.close();
}
