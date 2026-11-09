const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const source = fs.readFileSync(__dirname + '/../public/mobile/index.html', 'utf8')

function setup() {
  const c = vm.createContext({ console: { warn() {} }, URLSearchParams })
  vm.runInContext(`
    const DPKGS=[],PKGS=[],OFFERS=[],STORE_CATS=[],PRODUCTS=[],BRANCHES=[],TSTS=[],WHEEL=[],B_CATS=[],B_SERVICES=[],HSVCS=[],B_UPSELL=[],B_BRANCHES=[],MOBILE_SERVICES=[];
    let HOME_CONTENT={},HOME_FAQS=[],BRANCH_BANNER=null;
    const S={};const document={querySelector:()=>null};const location={search:''};
    const STYLE_PKG=[{}],STYLE_HOME_PKG=[{}],STYLE_OFFER=[{}];const IMG={};
    const localizedField=(o,k)=>o[k]||'';
    const escHtml=s=>String(s??'').replace(/"/g,'&quot;');
    const localized=s=>s;const getLang=()=>lang;
    const syncBranchHeader=()=>{};
    let lang='ar',renders=0;
    const render=()=>{renders++};
  `, c)
  const mappings = source.slice(source.indexOf('function mapPackage('), source.indexOf('/* بنخزّن نتيجة'))
  vm.runInContext(mappings, c)
  const start = source.indexOf('let _rawApiData=null;')
  const end = source.indexOf('async function loadDynamicEmployees()')
  vm.runInContext(source.slice(start, end), c)
  return { c, run: code => vm.runInContext(code, c) }
}

test('mobile inline scripts parse', () => {
  for (const match of source.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g)) {
    if (!match[1].includes('src=')) new vm.Script(match[2])
  }
})

test('pending requests show a placeholder without images', async () => {
  const { c, run } = setup()
  c.apiGet = () => new Promise(() => {})
  c.loadAllPickerBranches = c.apiGet
  run('loadDynamicData()')
  assert.equal(run('dynamicLoading'), true)
  assert.doesNotMatch(run('dynamicStateHTML()'), /<img/)
  assert.match(run('dynamicStateHTML()'), /role="status"/)
})

test('home uses desktop payload, preserves order, and clears empty lists', async () => {
  const { c, run } = setup()
  const home = {
    products: [{ id: 7, name: 'Featured API product', product_image: '/api-product.png', feature_image: '/old.png', min_price: 45 }],
    packages: [{ id: 9, name: 'Home package', package_price: 80 }],
    offers: [{ id: 8, name: 'Home offer', feature_image: '/offer.png' }],
    categories: [{ id: 12, name: 'Home category', feature_image: '/category.png' }],
  }
  c.apiGet = async path => ({ data: path === '/Home/all' ? home : path === '/shop' ? { categories: [] } : [] })
  c.loadAllPickerBranches = async () => ({ data: [] })
  await run('loadDynamicData()')
  assert.equal(run('dynamicLoading'), false)
  assert.equal(run('HOME_PRODUCTS[0].img'), home.products[0].product_image)
  assert.equal(run('HOME_PRODUCTS[0].pr'), 45)
  assert.equal(run('HOME_PACKAGES[0].n'), 'Home package')
  assert.match(run('HOME_OFFERS[0].t'), /Home offer/)
  assert.equal(run('HOME_SERVICES[0].id'), '12')
  home.products = []; home.packages = []; home.offers = []; home.categories = []
  await run('loadDynamicData()')
  assert.equal(run('HOME_PRODUCTS.length+HOME_PACKAGES.length+HOME_OFFERS.length+HOME_SERVICES.length+BRANCHES.length+TSTS.length'), 0)
})

test('failed requests show retry instead of static records', async () => {
  const { c, run } = setup()
  c.apiGet = c.loadAllPickerBranches = async () => { throw Error('offline') }
  await run('loadDynamicData()')
  assert.equal(run('dynamicLoading'), false)
  assert.equal(run('dynamicError'), true)
  assert.match(run('dynamicStateHTML()'), /retryDynamicData/)
  assert.equal(run('PRODUCTS.length+DPKGS.length+OFFERS.length'), 0)
})

test('a previous language response cannot overwrite the current request', async () => {
  const { c, run } = setup()
  let finishOld
  c.apiGet = path => path === '/Home/all'
    ? new Promise(resolve => { finishOld = resolve })
    : Promise.resolve({ data: [] })
  c.loadAllPickerBranches = async () => ({ data: [] })
  const old = run('loadDynamicData()')
  run("lang='en'")
  c.apiGet = async path => ({ data: path === '/Home/all'
    ? { products: [{ id: 2, name: 'Current', product_image: '/current.png' }] }
    : [] })
  await run('loadDynamicData()')
  finishOld({ data: { products: [{ id: 1, name: 'Old' }] } })
  await old
  assert.equal(run('HOME_PRODUCTS[0].n'), 'Current')
})

test('images are configured before insertion while the first hero stays eager', () => {
  const start = source.indexOf(' html=html.replace(/<img')
  const end = source.indexOf(' scr.innerHTML=', start)
  const c = vm.createContext({ html: '<img src="/below.png"><img loading="eager" fetchpriority="high" src="/hero.png"><img loading="lazy" src="/api.png">' })
  vm.runInContext(source.slice(start, end), c)
  assert.match(c.html, /loading="lazy" src="\/below.png"/)
  assert.match(c.html, /loading="eager" fetchpriority="high"/)
  assert.equal((c.html.match(/decoding="async"/g) || []).length, 3)
})

test('home renders its FAQs without waiting for any secondary API', async () => {
  const { c, run } = setup()
  const calls = []
  c.apiGet = path => {
    calls.push(path)
    return path === '/Home/all'
      ? Promise.resolve({ data: { faqs: [{ question: 'Current question', answer: 'Current answer' }] } })
      : new Promise(() => {})
  }
  await run('loadDynamicData()')
  assert.equal(run('dynamicLoading'), false)
  assert.equal(run('HOME_FAQS[0].question'), 'Current question')
  assert.deepEqual(calls, ['/Home/all'])
})

test('secondary catalogue reads are on demand and deduplicated', async () => {
  const { c, run } = setup()
  const calls = []
  c.apiGet = async path => { calls.push(path); return { data: path === '/shop' ? { categories: [] } : {} } }
  c.loadAllPickerBranches = async () => { calls.push('/branch-list'); return { data: [] } }
  await run('loadDynamicData()')
  assert.equal(run('requiredDynamicResources().length'), 0)
  run("S.tab='store'")
  await Promise.all([run('loadDynamicResources(requiredDynamicResources())'), run('loadDynamicResources(requiredDynamicResources())')])
  assert.deepEqual(calls, ['/Home/all', '/shop', '/branch-list'])
  assert.equal(run('dynamicResourceStates.shopRes'), 'fulfilled')
  assert.equal(run('dynamicLoading'), false)
})

test('secondary failures do not hide home and can be retried', async () => {
  const { c, run } = setup()
  c.apiGet = async path => {
    if (path === '/shop') throw Error('offline')
    return { data: { faqs: [{ question: 'Live home' }] } }
  }
  await run('loadDynamicData()')
  assert.equal(await run("loadDynamicResources(['shopRes'])"), false)
  assert.equal(run('dynamicError'), false)
  assert.equal(run('HOME_FAQS[0].question'), 'Live home')
  run("S.tab='store'; retryDynamicResources()")
  c.apiGet = async () => ({ data: { categories: [] } })
  assert.equal(await run("loadDynamicResources(['shopRes'])"), true)
})

test('a previous language catalogue cannot replace current data', async () => {
  const { c, run } = setup()
  let oldShop
  c.apiGet = path => path === '/shop'
    ? new Promise(resolve => { oldShop = resolve })
    : Promise.resolve({ data: {} })
  await run('loadDynamicData()')
  const pending = run("loadDynamicResources(['shopRes'])")
  await Promise.resolve()
  run("lang='en'")
  await run('loadDynamicData()')
  oldShop({ data: { categories: [] } })
  await pending
  assert.equal(run('dynamicResourceStates.shopRes'), undefined)
  assert.equal(run('_rawApiData.shopRes.status'), 'idle')
})

test('package cards use actual savings and do not invent a discount', () => {
  const { c, run } = setup()
  c.rs = String
  assert.match(run("packageSavingsHTML({savings:35.5})"), /35.5/)
  assert.equal(run('packageSavingsHTML({savings:0})'), '')
  assert.equal(run('packageSavingsHTML({})'), '')
})
