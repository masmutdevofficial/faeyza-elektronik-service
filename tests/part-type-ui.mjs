// Runs only against the isolated local testing server, using a temporary browser profile.
import {createRequire} from 'node:module';
import fs from 'node:fs/promises';
import {execFileSync} from 'node:child_process';
const require=createRequire(import.meta.url);
const {chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright');
const base=process.env.TEST_BASE_URL||'http://127.0.0.1:8081/';
if(!['127.0.0.1','localhost'].includes(new URL(base).hostname))throw new Error('Local testing only');
const fixture=JSON.parse(await fs.readFile(new URL('./local-fixtures.json',import.meta.url),'utf8'));
execFileSync(process.env.PHP_PATH||'C:/project/bengkel-runtime/php/php.exe',['tests/reset-login-limits.php'],{cwd:new URL('..',import.meta.url),stdio:'pipe'});
const browser=await chromium.launch({headless:true,executablePath:process.env.CHROME_PATH||'C:/Program Files/Google/Chrome/Application/chrome.exe'});
const context=await browser.newContext({viewport:{width:1440,height:950}});
const page=await context.newPage(),errors=[],results=[];
page.on('pageerror',error=>errors.push(error.message));
const artifact=name=>new URL('./artifacts/'+name,import.meta.url);
const check=(name,ok)=>{results.push({name,status:ok?'PASS':'FAIL'});console.log((ok?'PASS ':'FAIL ')+name);if(!ok)throw new Error(name);};
const goto=route=>page.goto(base+'index.php?page='+route);
const fill=(name,value)=>page.locator('[name="'+name+'"]').fill(value);
const click=async name=>Promise.all([page.waitForNavigation({waitUntil:'domcontentloaded'}),page.getByRole('button',{name,exact:true}).click()]);
const balance=async()=>Number((await page.locator('.inventory-balance').innerText()).trim().split(/\s/)[0]);
try {
  await page.goto(base+'admin/');await fill('phone',fixture.admin_phone);await fill('password',fixture.password);await click('Masuk');
  const sku='TYPE-UI-'+Date.now();
  await goto('part-new');await fill('sku',sku);await fill('name','IC Amplifier');await fill('part_type','TPA3116D2');await fill('minimum_stock','2');await fill('unit','pcs');await click('Simpan sparepart');
  const id=new URL(page.url()).searchParams.get('id');
  check('32 tambah sparepart menyimpan tipe awal',Boolean(id)&&await balance()===0&&(await page.locator('main').innerText()).includes('Tipe: TPA3116D2'));
  check('32 form stok masuk berisi tipe bebas dan tanpa keterangan sumber',await page.locator('[name="part_type"]').inputValue()==='TPA3116D2'&&await page.locator('[name="reason"]').count()===0&&await page.getByLabel(/^Tipe sparepart/).isVisible());
  await fill('quantity','4');await fill('part_type','PAM8610');await click('Tambah stok');
  check('32 penerimaan menyimpan stok dan tipe baru',await balance()===4&&await page.locator('[name="part_type"]').inputValue()==='PAM8610');
  check('32 histori menyimpan tipe penerimaan',(await page.locator('tbody').innerText()).includes('Stok masuk: Tipe sparepart: PAM8610'));
  await page.reload();check('32 reload mempertahankan tipe tanpa menambah stok',await balance()===4&&await page.locator('[name="part_type"]').inputValue()==='PAM8610');
  await goto('part-edit&id='+id);check('32 edit menampilkan tipe tersimpan',await page.getByLabel(/^Tipe sparepart/).inputValue()==='PAM8610');
  await fill('part_type','PAM8403');await click('Simpan sparepart');
  check('32 edit tipe tidak mengubah stok',await balance()===4&&await page.locator('[name="part_type"]').inputValue()==='PAM8403');
  check('32 edit tipe mempertahankan histori lama',(await page.locator('tbody').innerText()).includes('Stok masuk: Tipe sparepart: PAM8610'));
  await fill('quantity','1');await click('Tambah stok');check('32 penerimaan berikutnya memakai tipe hasil edit',await balance()===5&&(await page.locator('tbody').innerText()).includes('Stok masuk: Tipe sparepart: PAM8403'));
  await fill('part_type','');await page.getByRole('button',{name:'Tambah stok',exact:true}).click();
  check('32 tipe penerimaan wajib diisi',await page.locator('[name="part_type"]').evaluate(node=>!node.validity.valid)&&await balance()===5);
  await fill('part_type','PAM8403');
  for(const width of [375,768,1440]) {
    await page.setViewportSize({width,height:950});await goto('part&id='+id);
    check('32 detail '+width+'px tanpa overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
    check('32 input tipe '+width+'px dapat digunakan',await page.getByLabel(/^Tipe sparepart/).isEditable()&&await page.getByRole('button',{name:'Tambah stok',exact:true}).isEnabled());
    if(width===375)await page.screenshot({path:artifact('part-type-mobile.jpg').pathname.replace(/^\/(\w:)/,'$1'),fullPage:false});
    await goto('part-edit&id='+id);
    check('32 edit '+width+'px tanpa overflow dan tipe sesuai',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)&&await page.locator('[name="part_type"]').inputValue()==='PAM8403');
  }
  await goto('parts&q=PAM8403');check('32 pencarian tipe menemukan sparepart',(await page.locator('tbody').innerText()).includes(sku));
  check('32 tanpa error runtime JavaScript',errors.length===0);
} catch(error) {await page.screenshot({path:artifact('part-type-ui-failure.jpg').pathname.replace(/^\/(\w:)/,'$1'),fullPage:false});throw error;}
finally {
  await fs.writeFile(artifact('part-type-browser-results.json'),JSON.stringify({results,errors,pass:results.filter(r=>r.status==='PASS').length,fail:results.filter(r=>r.status==='FAIL').length},null,2));
  await browser.close();
}
