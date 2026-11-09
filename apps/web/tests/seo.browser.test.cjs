
const {test}=require('node:test'),assert=require('node:assert/strict');
let chromium;try{({chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright'))}catch{}
const base=process.env.LANGUAGE_TEST_URL;
const opts={skip:(!chromium||!base)&&'Requires Playwright and Vite',timeout:60000};
async function open(browser,path,width=1440){
 const page=await browser.newPage({viewport:{width,height:900}}),errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.route('**/*',r=>{
  const q=r.request(),u=new URL(q.url());
  if(u.pathname.endsWith('/seo')){
   const path=u.searchParams.get('path'),lang=u.searchParams.get('lang');
   return r.fulfill({json:{status:true,data:{title:lang+' title '+path,description:lang+' description '+path,keywords:lang+', '+path}}});
  }
  if(['fetch','xhr'].includes(q.resourceType())&&!u.pathname.startsWith('/src/')&&!u.pathname.includes('/node_modules/')){
   let data=[];
   if(u.pathname.includes('/Home/all'))data={categories:[],reviews:[],branches:[],products:[],packages:[]};
   return r.fulfill({json:{status:true,data}});
  }
  return q.url().startsWith(base)?r.continue():r.abort();
 });
 await page.goto(base+path);return {page,errors};
}
async function metadata(page,path,lang){
 try { await page.waitForFunction(({path,lang})=>document.title===lang+' title '+path,{path,lang},{timeout:5000}); } catch { throw new Error('Expected '+lang+' title '+path+'; got '+await page.title()); }
 assert.equal(await page.locator('head meta[name="description"]').getAttribute('content'),lang+' description '+path);
 assert.equal(await page.locator('head meta[name="keywords"]').getAttribute('content'),lang+', '+path);
 assert.equal(await page.locator('head meta[name="keywords"]').count(),1);
 assert.equal(await page.locator('head title').count(),1);
 assert.equal(await page.locator('head meta[name="description"]').count(),1);
}
test('Vue metadata follows route, alias, language and saved changes on reload',opts,async()=>{
 const browser=await chromium.launch();
 try{
  const {page,errors}=await open(browser,'/services.html');await metadata(page,'/services','ar');
  await page.locator('.lang-toggle').click();await metadata(page,'/services','en');
  await page.locator('.site-header nav a[href="/store"]').click();
  await metadata(page,'/store','en');
  await page.route('**/seo?**',r=>r.fulfill({json:{status:true,data:{title:'Updated dashboard title',description:'Updated dashboard description'}}}));
  await page.reload();await page.waitForFunction(()=>document.title==='Updated dashboard title');
  assert.equal(await page.locator('meta[name="keywords"]').count(),0);
  assert.equal(await page.locator('meta[name="description"]').getAttribute('content'),'Updated dashboard description');
  assert.deepEqual(errors,[]);
 }finally{await browser.close()}
});
for(const file of ['index.html','index.dynamic.html'])test('standalone mobile metadata follows views and language: '+file,opts,async()=>{
 const browser=await chromium.launch();
 try{
  const {page,errors}=await open(browser,'/mobile/'+file+'?view=services',390);
  await metadata(page,'/services','ar');
  await page.evaluate(()=>{localStorage.setItem('samiSiteLang','en');window.dispatchEvent(new StorageEvent('storage',{key:'samiSiteLang',newValue:'en'}))});
  await metadata(page,'/services','en');
  await page.locator('[data-tab="store"]').click();await metadata(page,'/store','en');
  await page.goto(base+'/mobile/'+file+'?view=packages');await metadata(page,'/packages-gifts','en');
  assert.deepEqual(errors,[]);
 }finally{await browser.close()}
});
test('mobile iframe updates host metadata and rejects unrelated messages',opts,async()=>{
 const browser=await chromium.launch();
 try{
  const {page,errors}=await open(browser,'/services',390);await metadata(page,'/services','ar');
  const frame=page.frameLocator('.global-mobile-frame');
  await frame.locator('[data-tab="store"]').click();await metadata(page,'/store','ar');
  await page.evaluate(()=>window.postMessage({type:'sami:seo-path',path:'/contact'},location.origin));
  await page.waitForTimeout(80);assert.equal(await page.title(),'ar title /store');
  assert.deepEqual(errors,[]);
 }finally{await browser.close()}
});
test('metadata ignores late responses, stays safe and falls back when offline',opts,async()=>{
 const browser=await chromium.launch();
 try{
  const page=await browser.newPage();await page.goto(base);
  await page.addScriptTag({url:base+'/site-seo.js'});
  const result=await page.evaluate(async()=>{
   const pending=[];
   const seo=SamiSeo.create({endpoint:'/api/seo',fetcher:()=>new Promise(resolve=>pending.push(resolve))});
   const first=seo.update('/services','ar'),second=seo.update('/store','en');
   pending[1]({ok:true,json:async()=>({status:true,data:{title:'<img src=x onerror=alert(1)>',description:'" quoted <script>text</script>'}})});
   await second;
   pending[0]({ok:true,json:async()=>({status:true,data:{title:'Old title',description:'Old description',keywords:'old keywords'}})});
   await first;
   const safe={title:document.title,description:document.querySelector('meta[name=description]').content,images:document.head.querySelectorAll('img').length};
   seo.dispose();
   const offline=SamiSeo.create({endpoint:'/api/seo',fetcher:async()=>{throw new Error('offline')}});
   await offline.update('/gifts','en');
   return {safe,fallback:document.title,keywords:document.querySelectorAll('meta[name=keywords]').length};
  });
  assert.equal(result.safe.title,'<img src=x onerror=alert(1)>');assert.equal(result.safe.images,0);
  assert.equal(result.safe.description,'" quoted <script>text</script>');
  assert.equal(result.fallback,'Gifts | Rawnaq');
  assert.equal(result.keywords,0);
 }finally{await browser.close()}
});
