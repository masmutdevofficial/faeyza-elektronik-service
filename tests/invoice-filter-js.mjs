import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const results=[];
const check=(name,run)=>{try{run();results.push({name,status:'PASS'});}catch(error){results.push({name,status:'FAIL',error:error.message});}};
const printSource=fs.readFileSync(new URL('../public/assets/invoice-print.js',import.meta.url),'utf8');
function printFixture(mode='events') {
  const button={dataset:{},listeners:{},addEventListener(name,fn){this.listeners[name]=fn;}};
  const help={open:false};const events={};const timers=[];let calls=0;
  const browser={addEventListener:(name,fn)=>{events[name]=fn;},setTimeout:fn=>timers.push(fn),matchMedia:()=>({addEventListener:(name,fn)=>{events.media=fn;}})};
  if(mode!=='missing')browser.print=()=>{calls++;if(mode==='throw')throw new Error('Print blocked');if(mode==='events'){events.beforeprint();events.afterprint();}};
  const document={querySelector:()=>help,querySelectorAll:()=>[button]};
  vm.runInNewContext(printSource,{window:browser,document});
  return {button,help,events,click:()=>button.listeners.click(),flush:()=>timers.splice(0).forEach(fn=>fn()),calls:()=>calls};
}
check('28 skrip invoice mendaftarkan handler cetak mandiri',()=>assert.equal(typeof printFixture().button.listeners.click,'function'));
check('28 klik memanggil window.print tepat sekali secara sinkron',()=>{const f=printFixture();f.click();assert.equal(f.calls(),1);});
check('28 print normal selesai tanpa bantuan keliru',()=>{const f=printFixture();f.click();f.flush();assert.equal(f.button.dataset.printState,'complete');assert.equal(f.help.open,false);});
check('28 browser mengabaikan print menampilkan bantuan',()=>{const f=printFixture('silent');f.click();f.flush();assert.equal(f.help.open,true);});
check('28 kegagalan print tidak menyebabkan exception halaman',()=>{const f=printFixture('throw');assert.doesNotThrow(()=>f.click());assert.equal(f.help.open,true);});
check('28 browser tanpa API print tetap mendapat bantuan',()=>{const f=printFixture('missing');f.click();assert.equal(f.help.open,true);});
check('28 event print media dikenali sebagai dialog berjalan',()=>{const f=printFixture('silent');f.click();f.events.media({matches:true});f.flush();assert.equal(f.help.open,false);assert.equal(f.button.dataset.printState,'printing');});
check('28 klik ulang tetap bekerja setelah print pertama',()=>{const f=printFixture();f.click();f.flush();f.click();f.flush();assert.equal(f.calls(),2);});
const appSource=fs.readFileSync(new URL('../public/assets/app.js',import.meta.url),'utf8');
function filterFixture(fallback=false) {
  const counts=[0,0];const selects=counts.map((_,i)=>({listeners:{},form:fallback?{submit:()=>counts[i]++}:{requestSubmit:()=>counts[i]++},addEventListener(name,fn){this.listeners[name]=fn;}}));
  const document={querySelectorAll:selector=>selector==='[data-status-filter]'?selects:[],querySelector:()=>null,addEventListener(){}};
  vm.runInNewContext(appSource,{document,window:{addEventListener(){}}});return {counts,selects};
}
check('27 ubah filter langsung mengirim form tanpa klik Cari',()=>{const f=filterFixture();f.selects[0].listeners.change();assert.deepEqual(f.counts,[1,0]);});
check('27 filter hanya mengirim form milik select terkait',()=>{const f=filterFixture();f.selects[1].listeners.change();assert.deepEqual(f.counts,[0,1]);});
check('27 requestSubmit fallback tetap bekerja',()=>{const f=filterFixture(true);f.selects[0].listeners.change();assert.deepEqual(f.counts,[1,0]);});
check('27 form tidak dikirim saat inisialisasi halaman',()=>assert.deepEqual(filterFixture().counts,[0,0]));
for(const r of results)console.log(r.status+' '+r.name);
const summary={pass:results.filter(r=>r.status==='PASS').length,fail:results.filter(r=>r.status==='FAIL').length,blocked:0};
fs.writeFileSync(new URL('./artifacts/invoice-filter-js-results.json',import.meta.url),JSON.stringify({summary,results},null,2));
console.log(JSON.stringify(summary));process.exitCode=summary.fail?1:0;
