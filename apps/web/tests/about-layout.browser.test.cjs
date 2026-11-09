
const {test}=require('node:test'),assert=require('node:assert/strict');
let chromium;try{({chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright'))}catch{}
const base=process.env.LANGUAGE_TEST_URL;
for(const width of [1440,390])test('About English content and direction at '+width,{skip:!chromium||!base,timeout:60000},async()=>{
 const browser=await chromium.launch(),page=await browser.newPage({viewport:{width,height:900}}),errors=[];
 page.on('pageerror',e=>errors.push(e.message));
 await page.route('**/*',r=>{
  const q=r.request(),url=q.url();
  if(['fetch','xhr'].includes(q.resourceType())&&!url.includes('/src/')&&!url.includes('/node_modules/')){
   let data=[];
   if(url.includes('about-page'))data={title:'رونق',english_title:'RAWNAQ',description:'الوصف العربي',description_en:'English description',features:[{name:'ميزة عربية',name_en:'English feature',detail_en:'English detail',icon:'star'}],stats:[{value:'10',label:'سنوات خبرة'}]};
   if(url.includes('/Home/all'))data={categories:[],reviews:[],branches:[],products:[],packages:[]};
   return r.fulfill({json:{status:true,data}});
  }
  return url.startsWith(base)?r.continue():r.abort();
 });
 try{
  await page.goto(base+'/page-about');
  await page.locator('.about-language-toggle').click();
  await page.waitForFunction(()=>document.querySelector('.about-design-page')?.dir==='ltr'&&document.body.textContent.includes('English description'));
  await page.waitForFunction(()=>document.body.textContent.includes('About the center'));
  const text=await page.locator('.about-app-shell').innerText();
  assert.match(text,/English feature/);
  assert.doesNotMatch(text.replace('العربية',''),/[\u0600-\u06ff]/);
  assert.equal(await page.locator('.about-design-page').evaluate(e=>getComputedStyle(e).direction),'ltr');
  assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  await page.locator('.about-language-toggle').click();
  await page.waitForFunction(()=>document.querySelector('.about-design-page')?.dir==='rtl'&&document.body.textContent.includes('الوصف العربي'));
  if(width===1440){
   await page.goto(base+'/');await page.locator('.lang-toggle').click();
   await page.waitForFunction(()=>document.querySelector('.home-page')?.dir==='ltr');
   for(const selector of ['.home-page','.about-copy','.catalog-section','.site-header']){
    assert.equal(await page.locator(selector).first().evaluate(e=>getComputedStyle(e).direction),'ltr',selector);
   }
   await page.locator('.lang-toggle').click();
   await page.waitForFunction(()=>document.querySelector('.home-page')?.dir==='rtl');
  }
  assert.deepEqual(errors,[]);
 }finally{await browser.close()}
});
