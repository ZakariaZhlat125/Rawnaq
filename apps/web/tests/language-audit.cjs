
const fs=require('fs'),{chromium}=require(process.env.PLAYWRIGHT_MODULE);
const source=fs.readFileSync(__dirname+'/dynamic-language.test.cjs','utf8');
const fixture=new Function(source.slice(source.indexOf('const names ='),source.indexOf('async function open'))+';return fixture;')();
(async()=>{
 const b=await chromium.launch(),results={};
 for(const path of ['/','/services','/services/3','/gifts','/store','/packages-gifts',...['home','services','gifts','store','packages'].map(v=>'/mobile/index.html?view='+v)]){
 const p=await b.newPage({viewport:{width:path.includes('mobile')?390:1440,height:900}});
 await p.addInitScript(()=>{localStorage.setItem('samiSiteLang','en');localStorage.setItem('samiMobileLang','en')});
 await p.route('**/*',r=>{const q=r.request(),u=q.url();if(['fetch','xhr'].includes(q.resourceType())&&!u.includes('/src/')&&!u.includes('/node_modules/'))return r.fulfill({json:{status:true,data:fixture(u,'en')}});return u.startsWith('http://127.0.0.1:5174')?r.continue():r.abort()});
 await p.goto('http://127.0.0.1:5174'+path);await p.waitForTimeout(1300);
 results[path]=await p.evaluate(()=>{
 const out=new Set(),w=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);let n;
 while(n=w.nextNode()){const e=n.parentElement;if(e.closest('script,style,[translate=no]')||!e.checkVisibility())continue;if(/[\u0600-\u06ff]/.test(n.data))out.add(n.data.trim())}return {arabic:[...out],rtl:[...document.querySelectorAll('body *')].filter(e=>!e.closest('script,style')&&e.checkVisibility()&&getComputedStyle(e).direction==='rtl').map(e=>e.tagName+'.'+e.className).slice(0,30)}});
 await p.close();
 }
 console.log(JSON.stringify(results,null,2));fs.writeFileSync(__dirname+'/language-audit.json',JSON.stringify(results,null,2));await b.close()
})().catch(e=>{console.error(e);process.exit(1)})
