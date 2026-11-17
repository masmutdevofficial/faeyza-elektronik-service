// Pemeriksaan tata letak kontrol password setelah penyesuaian lebar input.
export async function runAuthLayout({tab,viewport,save,base='http://127.0.0.1:8081/'}) {
  const results=[];
  for(const route of ['admin-login','login','register'])for(const width of [375,768,1440]) {
    await viewport.set({width,height:900});
    const actual=await tab.playwright.evaluate(()=>({w:innerWidth,h:innerHeight}));
    if(actual.w!==width)await viewport.set({width:Math.round(width*width/actual.w),height:Math.round(900*900/actual.h)});
    await tab.goto(base+(route==='admin-login'?'admin/':'index.php?page='+route));
    await tab.ax.get();
    const ok=await tab.playwright.evaluate(expected=>innerWidth===expected&&document.documentElement.scrollWidth<=innerWidth&&[...document.querySelectorAll('.password-control')].every(control=>{
      const input=control.querySelector('input').getBoundingClientRect();
      const button=control.querySelector('button').getBoundingClientRect();
      return Math.abs(input.width-control.getBoundingClientRect().width)<=1&&button.left>=input.left&&button.right<=input.right&&button.top>=input.top&&button.bottom<=input.bottom;
    }),width);
    results.push({name:'19 '+route+' '+width+'px tombol mata berada dalam input penuh',status:ok?'PASS':'FAIL'});
    if(!ok)throw new Error(results.at(-1).name);
    if(width===375)await save(route+'-mobile.png');
  }
  return results;
}
