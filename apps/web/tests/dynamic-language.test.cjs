const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs'), path = require('node:path'), vm = require('node:vm')
const context = vm.createContext({})
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/data-i18n.js'), 'utf8'), context)
const { field, record } = context.SamiDataI18n
test('translation fields support maps, JSON, sibling translations and safe fallbacks', () => {
  assert.equal(field({ ar: 'عربي', en: 'English' }, 'en'), 'English')
  assert.equal(field('{"ar":"عربي","en":"English"}', 'en'), 'English')
  assert.equal(record({ name: 'قديم', name_translations: { en: 'English' } }, 'name', 'en'), 'English')
  assert.equal(record({ name: 'Fallback', name_translations: {} }, 'name', 'en'), 'Fallback')
  assert.equal(field({ ar: 'عربي', en: ' ' }, 'en'), 'عربي')
  assert.equal(field({ id: 2 }, 'en'), '')
  assert.equal(field(['unexpected'], 'en'), '')
})
let chromium
try { ({ chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright')) } catch (e) { if (e.code !== 'MODULE_NOT_FOUND') throw e }
const base = process.env.LANGUAGE_TEST_URL
const options = { skip: (!chromium || !base) && 'Requires Playwright and LANGUAGE_TEST_URL pointing to Vite dev', timeout: 60000 }
const names = { product: { ar:'زيت اختبار العنبر', en:'Amber test oil' }, category:{ar:'قسم اختبار العناية',en:'Care test category'}, service:{ar:'جلسة اختبار العناية',en:'Care test session'}, package:{ar:'باقة اختبار العنبر',en:'Amber test package'} }
function fixture(url,lang) {
  const product={id:101,name:JSON.stringify(names.product),short_description:{ar:'وصف الزيت',en:'Oil description'},min_price:50,max_price:50,stock_qty:10}
  const category={id:3,name:names.category,products:[product],services:[{id:301,category_id:3,name:JSON.stringify(names.service),description:{ar:'وصف الجلسة',en:'Session description'},duration_min:30,default_price:90,status:1}]}
  const pkg={id:201,name:names.package[lang],name_translations:names.package,description:JSON.stringify({ar:'وصف الباقة',en:'Package description'}),package_price:100,services:[{service_id:301,service_name:names.service[lang],service_name_translations:names.service,duration_min:30}]}
  const branch={id:1,name:{ar:'فرع اختبار',en:'Test branch'},address:{ar:'عنوان الاختبار',en:'Test address'}}
  if(url.includes('/Home/all'))return {categories:[category],products:[{...product,name:names.product[lang]}],packages:[pkg],offers:[],branches:[branch],reviews:[]}
  if(url.includes('/Home/categories'))return [category]
  if(url.includes('/Home/packages')||url.includes('/offers'))return [pkg]
  if(url.includes('/Home/products'))return [product]
  if(url.includes('/shop'))return {categories:[category]}
  if(url.includes('/branches')||url.includes('/branch-list'))return [branch]
  if(url.includes('/point-value'))return {point_value:.5,points_per_100:5}
  if(url.includes('/wheel/'))return {prizes:[],enabled:false}
  return []
}
async function open(browser,route,width=1440) {
 const page=await browser.newPage({viewport:{width,height:900}}), errors=[]
 page.on('pageerror',e=>errors.push(e.message))
 await page.route('**/*',async route=>{
  const req=route.request(),url=req.url()
  if(['fetch','xhr'].includes(req.resourceType())&&!url.includes('/src/')&&!url.includes('/node_modules/')){
   const lang=req.headers()['frezka-localization']||'ar'
   if(url.includes('/Home/all')&&lang==='en')await new Promise(r=>setTimeout(r,150))
   return route.fulfill({status:200,contentType:'application/json',body:JSON.stringify({status:true,data:fixture(url,lang)})})
  }
  return url.startsWith(base)?route.continue():route.abort()
 })
 await page.goto(base+route)
 return {page,errors}
}
test('desktop cart translates without changing quantities or totals',options,async()=>{
 const browser=await chromium.launch()
 try{
  const {page,errors}=await open(browser,'/store')
  await page.waitForFunction(()=>document.body.textContent.includes('زيت اختبار العنبر'))
  await page.evaluate(async()=>{window.store=(await import('/src/composables/useStore.js')).useStore();store.state.cart[101]=2})
  await page.locator('.lang-toggle').click()
  await page.waitForFunction(()=>store.cartItems.value[0]?.n==='Amber test oil')
  assert.equal(await page.evaluate(()=>store.cartTotal.value),100)
  assert.equal(await page.evaluate(()=>store.state.cart[101]),2)
  assert.match(await page.locator('body').innerText(),/Amber test oil/)
  assert.doesNotMatch(await page.locator('body').innerText(),/\[object Object\]/)
  await page.locator('.lang-toggle').click()
  await page.waitForFunction(()=>store.cartItems.value[0]?.n==='زيت اختبار العنبر')
  assert.deepEqual(errors,[])
 }finally{await browser.close()}
})
test('selected gifts retain live names after selection is unmounted',options,async()=>{
 const browser=await chromium.launch()
 try{
  const {page,errors}=await open(browser,'/gifts')
  await page.evaluate(async()=>{window.gift=(await import('/src/composables/useGifts.js')).useGifts();window.packages=(await import('/src/composables/usePackages.js')).usePackages();gift.state.gtype='svc';gift.state.step=1;gift.state.activeCat=3})
  await page.locator('.sub[data-sv="301"]').click()
  await page.evaluate(()=>{gift.state.step=2;gift.state.name='اسم خاص';gift.state.msg='رسالة شخصية'})
  await page.locator('.lang-toggle').click()
  await page.waitForFunction(()=>gift.giftLabel.value==='Care test session')
  assert.equal(await page.evaluate(()=>gift.state.msg),'رسالة شخصية')
  await page.evaluate(()=>{gift.state.pkg=packages.packages.value[0];gift.state.gtype='pkg'})
  assert.equal(await page.evaluate(()=>gift.giftLabel.value),'Amber test package')
  await page.locator('.lang-toggle').click()
  await page.waitForFunction(()=>gift.giftLabel.value==='باقة اختبار العنبر')
  assert.equal(await page.evaluate(()=>gift.state.pkg.price),100)
  assert.deepEqual(errors,[])
 }finally{await browser.close()}
})
test('home ignores late replies for a previous language',options,async()=>{
 const browser=await chromium.launch()
 try{
  const {page,errors}=await open(browser,'/')
  await page.waitForFunction(()=>document.body.textContent.includes('زيت اختبار العنبر'))
  await page.locator('.lang-toggle').click()
  await page.waitForFunction(()=>document.body.textContent.includes('Amber test oil'))
  await page.locator('.lang-toggle').click()
  await page.waitForFunction(()=>document.body.textContent.includes('زيت اختبار العنبر'))
  await page.locator('.lang-toggle').click();await page.waitForTimeout(20);await page.locator('.lang-toggle').click();await page.waitForTimeout(300)
  assert.match(await page.locator('body').innerText(),/زيت اختبار العنبر/)
  assert.doesNotMatch(await page.locator('body').innerText(),/Amber test oil/)
  assert.deepEqual(errors,[])
 }finally{await browser.close()}
})
test('both mobile sources translate real package and product shapes in both directions',options,async()=>{
 const browser=await chromium.launch()
 try{for(const file of ['index.html','index.dynamic.html']){
  const {page,errors}=await open(browser,'/mobile/'+file,390)
  await page.waitForFunction(()=>document.body.textContent.includes('باقة اختبار العنبر'))
  await page.locator('#langBtn').click()
  await page.waitForFunction(()=>document.body.textContent.includes('Amber test package'))
  assert.match(await page.locator('body').innerText(),/Care test session/)
  assert.match(await page.locator('body').innerText(),/Amber test oil/)
  assert.doesNotMatch(await page.locator('body').innerText(),/\[object Object\]/)
  await page.locator('#langBtn').click()
  await page.waitForFunction(()=>document.body.textContent.includes('باقة اختبار العنبر'))
  await page.waitForTimeout(250)
  assert.doesNotMatch(await page.locator('body').innerText(),/Amber test package/)
  assert.deepEqual(errors,[])
  await page.close()
 }}finally{await browser.close()}
})