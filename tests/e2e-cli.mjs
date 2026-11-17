// Opsi development di luar ChatGPT: npm install --no-save playwright; npx playwright install chromium
import {chromium} from 'playwright';
import fs from 'node:fs/promises';
import {runBrowserE2E} from './browser-e2e.mjs';
const browser=await chromium.launch({headless:true});
const context=await browser.newContext();const page=await context.newPage();
const fixture=JSON.parse(await fs.readFile(new URL('./local-fixtures.json',import.meta.url),'utf8'));
const api=new Proxy(page,{get(target,key){if(key==='expectNavigation')return async(action)=>{const wait=page.waitForNavigation({waitUntil:'domcontentloaded'});await action();return wait;};const value=target[key];return typeof value==='function'?value.bind(target):value;}});
const adapter={playwright:api,ax:{get:()=>page.waitForLoadState('networkidle')},goto:url=>page.goto(url),url:()=>page.url(),reload:()=>page.reload()};
try {
 const result=await runBrowserE2E({tab:adapter,fixture,viewport:{set:({width,height})=>page.setViewportSize({width,height})},save:name=>page.screenshot({path:new URL('./artifacts/'+name,import.meta.url).pathname.replace(/^\/(\w:)/,'$1')})});
 await fs.writeFile(new URL('./artifacts/browser-results.json',import.meta.url),JSON.stringify(result,null,2));
 await fs.writeFile(new URL('./local-browser-fixtures.json',import.meta.url),JSON.stringify({orderId:result.orderId,partId:result.partId,invoice:result.invoice,warrantyDate:result.warrantyDate}));
 console.log(result.results.length+' browser checks PASS');
} finally {await browser.close();}
