const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const path = require('node:path')
const { reactive, computed, ref, watch } = require('vue')
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8')
function helpers() {
  const ctx = vm.createContext({})
  vm.runInContext(read('src/utils/bookingProducts.js').replaceAll('export function', 'function'), ctx)
  return ctx
}
test('suggestions are unique, random, in stock, and empty for an empty store', () => {
  const { randomProducts } = helpers()
  const products = Array.from({ length: 8 }, (_, id) => ({ id, stockQty: id === 0 ? 0 : 5 }))
  const chosen = randomProducts([...products, products[1]], 3, () => 0)
  assert.equal(chosen.length, 3)
  assert.equal(new Set(chosen.map(p => p.id)).size, 3)
  assert(chosen.every(p => p.stockQty > 0))
  assert.notDeepEqual(chosen.map(p => p.id), [1, 2, 3])
  assert.equal(randomProducts([]).length, 0)
  assert.equal(randomProducts([{ id: 1, stockQty: 2 }]).length, 1)
  assert.equal(products.length, 8)
})
test('cart synchronization keeps quantities and excludes removed products', () => {
  const payload = helpers().productCartPayload({ 2: 3, 4: 0, 9: 1 })
  assert.deepEqual(JSON.parse(JSON.stringify(payload)), [{ product_id: 2, qty: 3 }, { product_id: 9, qty: 1 }])
})
test('booking total reacts to cart changes and preserves paid product totals after clearing the cart', () => {
  const total = ref(40), items = ref([{ id: 1, n: 'Product', pr: 20, qty: 2 }])
  const ctx = vm.createContext({ reactive, computed, watch, servicePrice: (service) => Number(service.default_price ?? service.price), useServiceLocation: () => ({ current: ref({ id: 1 }) }), useStore: () => ({ cartTotal: total, cartItems: items }),
    useLanguage: () => ({ state: { lang: 'ar' } }), paymentPolicy: {} })
  vm.runInContext(read('src/composables/useBooking.js').replace(/^import .*$/gm, '').replace(/export /g, ''), ctx)
  const booking = ctx.useBooking()
  booking.state.services = [{ id: 1, price: 100, dur: 30 }]
  assert.equal(booking.priceParts.value.total, 161)
  total.value = 0
  assert.equal(booking.priceParts.value.total, 115)
  total.value = 40
  booking.state.purchasedProducts = items.value.map(item => ({ ...item }))
  booking.state.done = true
  total.value = 0; items.value = []
  assert.equal(booking.priceParts.value.total, 161)
  assert.equal(booking.selectedProducts.value.length, 1)
})
test('favorites loading failures stay separate from individual button failures', async () => {
  const auth = { isAuthenticated: ref(true), token: ref('token'), openAuthModal() {} }
  const ctx = vm.createContext({ ref, watch: (_source, callback) => callback(), useAuth: () => auth,
    authFetch: async () => { throw new Error('Request failed') } })
  vm.runInContext(read('src/composables/useFavorites.js').replace(/^import .*$/gm, '').replace(/export /g, ''), ctx)
  const favorites = ctx.useFavorites()
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(favorites.error.value, 'Request failed')
  assert.equal(Object.keys(favorites.itemErrors.value).length, 0)
  await favorites.toggle('service', 3)
  assert.equal(favorites.itemErrors.value['service:3'], 'Request failed')
  assert.equal(favorites.itemErrors.value['service:4'], undefined)
})
test('mobile booking includes products and preserves its paid total', () => {
  const ctx = vm.createContext({ S: { bookDone: false }, bSubTotal: () => 100, cartTotal: () => 40 })
  vm.runInContext(read('public/mobile/index.html').match(/function bPriceParts\(\)\{[^\n]+/)[0], ctx)
  assert.equal(ctx.bPriceParts().total, 161)
  ctx.S.bookDone = true; ctx.S.bookProductTotal = 40; ctx.cartTotal = () => 0
  assert.equal(ctx.bPriceParts().total, 161)
})

test('mobile suggestions clear placeholder products when the live store is empty', () => {
  const source = read('public/mobile/index.html')
  const start = source.indexOf(' B_UPSELL.length=0;')
  const end = source.indexOf('\n /*', start)
  const ctx = vm.createContext({ B_UPSELL: [{ id: 'demo' }], PRODUCTS: [{ id: 'demo' }],
    shopRes: { status: 'fulfilled', value: { data: { categories: [] } } }, mapProduct: p => p })
  vm.runInContext(source.slice(start, end), ctx)
  assert.equal(ctx.B_UPSELL.length, 0)
  assert.equal(ctx.PRODUCTS.length, 0)
})

test('mobile public POST and authenticated PUT use the intended HTTP methods', async () => {
  const methods = []
  const ctx = vm.createContext({ API_BASE: 'https://example.test/api', getLang: () => 'ar', getAuth: () => ({ api_token: 'test' }),
    fetch: async (_url, options) => { methods.push(options.method); return { ok: true, json: async () => ({ status: true }) } } })
  const html = read('public/mobile/index.html')
  for (const name of ['apiPost', 'authPost']) {
    const start = html.indexOf(`async function ${name}(`)
    const end = html.indexOf('\n}', start) + 2
    vm.runInContext(html.slice(start, end), ctx)
  }
  await ctx.apiPost('/public', {})
  await ctx.authPost('/mobile/cart/products', { products: [] }, 'PUT')
  await ctx.authPost('/payments/init', {})
  assert.deepEqual(methods, ['POST', 'PUT', 'POST'])
})
