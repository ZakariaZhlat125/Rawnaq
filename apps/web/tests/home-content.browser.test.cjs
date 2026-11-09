
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs');
const {chromium}=require(process.env.PLAYWRIGHT_MODULE);
const base=process.env.LANGUAGE_TEST_URL;
const source=fs.readFileSync(__dirname+'/dynamic-language.test.cjs','utf8'),fixture=new Function(source.slice(source.indexOf('const names ='),source.indexOf('async function open'))+';return fixture;')();
const content=JSON.parse(fs.readFileSync(__dirname+'/../../../backend/resources/data/home-page-defaults.json','utf8'));
content.hero_title={ar:'CUSTOM AR',en:'CUSTOM EN'};content.slide_0_title=content.hero_title;content.about_title={ar:'ABOUT AR',en:'ABOUT EN'};content.about_image='/custom-about.png';content.home_service_image='/custom-home.png';
for(const width of [1440,390])test('home content, images, language and lazy loading '+width,{timeout:90000},async()=>{
 const b=await chromium.launch();const p=await b.newPage({viewport:{width,height:900}});const errors=[];p.on('pageerror',e=>errors.push(e.message));
 await p.route('**/*',r=>{const q=r.request(),u=q.url();if(['fetch','xhr'].includes(q.resourceType())&&!u.includes('/src/')&&!u.includes('/node_modules/')){let data=fixture(u,'ar');if(u.includes('/Home/all'))data={...data,home_content:content};return r.fulfill({json:{status:true,data}})}if(u.endsWith('/custom-about.png')||u.endsWith('/custom-home.png'))return r.fulfill({contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><rect width="600" height="400" fill="tan"/></svg>'});return u.startsWith(base)?r.continue():r.abort()});
 try{await p.goto(base);const root=width===390?p.frameLocator('iframe'):p;
 const hero=root.locator(width===390?'.hslide h1':'.hero-box h1').first();await hero.filter({hasText:'CUSTOM AR'}).waitFor();
 const about=root.locator(width===390?'.mobile-home-about h2':'#about h2');assert.equal(await about.innerText(),'ABOUT AR');
 assert.equal(await root.locator('img[src="/custom-about.png"]').count(),1);assert.equal(await root.locator('img[src="/custom-home.png"]').getAttribute('loading'),'lazy');
 if(width===390){assert.equal(await root.locator('.hslide img').first().getAttribute('loading'),'eager');assert.equal(await root.locator('.hslide img').nth(1).getAttribute('loading'),'lazy');await p.locator('iframe').evaluate(f=>f.contentWindow.postMessage({type:'sami:language',lang:'en'},location.origin))}else await p.locator('.lang-toggle').click();
 await hero.filter({hasText:'CUSTOM EN'}).waitFor();assert.equal(await about.innerText(),'ABOUT EN');assert.deepEqual(errors,[]);
 }finally{await b.close()}
});
