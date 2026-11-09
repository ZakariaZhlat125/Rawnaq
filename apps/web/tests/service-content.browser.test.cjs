
const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs');
let chromium;try{({chromium}=require(process.env.PLAYWRIGHT_MODULE||'playwright'))}catch{}
const base=process.env.LANGUAGE_TEST_URL,opts={skip:!chromium||!base,timeout:60000};
const source=fs.readFileSync(__dirname+'/dynamic-language.test.cjs','utf8'),fixture=new Function(source.slice(source.indexOf('const names ='),source.indexOf('async function open'))+';return fixture;')();
const content={why_title:{ar:'لماذا خدمتنا',en:'Why our service'},why_intro:{ar:'مقدمة',en:'Introduction'},why:[{title:{ar:'ميزة خاصة',en:'Special feature'},text:{ar:'وصف الميزة',en:'Feature description'},icon:'shield'}],benefits_title:{ar:'الفوائد',en:'Benefits'},benefits:[{title:{ar:'فائدة خاصة',en:'Special benefit'},text:{ar:'وصف الفائدة',en:'Benefit description'},icon:'leaf'}],faq:[{q:{ar:'سؤال خاص',en:'Custom question'},a:{ar:'إجابة خاصة',en:'Custom answer'}}],banner_title:{ar:'جرب الخدمة',en:'Try this service'},banner_text:{ar:'احجز تجربتك',en:'Book your experience'},show_why:true,show_benefits:true,show_faq:true,show_banner:true};
for(const width of [1440,390])test('dynamic service sections, language, FAQ and booking at '+width,opts,async()=>{
 const b=await chromium.launch(),p=await b.newPage({viewport:{width,height:900}}),errors=[];
 p.on('pageerror',e=>errors.push(e.message));
 await p.route('**/*',r=>{const q=r.request(),u=q.url();if(['fetch','xhr'].includes(q.resourceType())&&!u.includes('/src/')&&!u.includes('/node_modules/')){let data=fixture(u,'ar');if(u.includes('/Home/categories'))data=data.map(c=>({...c,page_content:content}));return r.fulfill({json:{status:true,data}})}return u.startsWith(base)?r.continue():r.abort()});
 try{
  await p.goto(base+'/services/3');const root=width===390?p.frameLocator('iframe'):p;
  await root.locator('.sc-why h2').waitFor();assert.equal(await root.locator('.sc-why h2').innerText(),'لماذا خدمتنا');
  await root.locator('.sc-faq summary').click();assert.equal(await root.locator('.sc-faq details').evaluate(e=>e.open),true);
  if(width===390){await p.locator('iframe').evaluate(frame=>frame.contentWindow.postMessage({type:'sami:language',lang:'en'},location.origin))}else await p.locator('.lang-toggle').click();
  await root.locator('.sc-why h2').filter({hasText:'Why our service'}).waitFor();
  assert.equal(await root.locator('.sc-banner h2').innerText(),'Try this service');
  assert.equal(await root.locator('.service-page-content').getAttribute('dir'),'ltr');
  assert.equal(await root.locator('.sc-benefits h3').innerText(),'Special benefit');
  await root.locator('[data-category-book]').click();
  if(width===390){await root.locator('[data-booking-launch-branch]').first().click();assert.equal(await root.locator('[data-w-cat].sel').count(),1)}
  assert.deepEqual(errors,[]);
 }finally{await b.close()}
});
test('renderer respects disabled/empty sections and escapes dashboard text',()=>{
 const vm=require('node:vm'),context=vm.createContext({});
 for(const file of ['data-i18n.js','service-page-defaults.js','service-page-content.js'])vm.runInContext(fs.readFileSync(__dirname+'/../public/'+file,'utf8'),context);
 const html=context.SamiServiceContent.render({name:{en:'Care'},page_content:{show_why:false,why:[{title:'hidden',text:'hidden'}],benefits:[],faq:[{q:'<img src=x onerror=alert(1)>',a:'Safe'}],show_banner:false}},'en');
 assert.ok(!html.includes('sc-why'));assert.ok(!html.includes('sc-banner'));assert.ok(html.includes('&lt;img'));assert.ok(!html.includes('<img src=x'));
});
