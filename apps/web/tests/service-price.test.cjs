const { test } = require('node:test')
const assert = require('node:assert/strict')

test('mobile prices follow real branch IDs, update variants and totals, and preserve fallback', () => {
  const fs = require('node:fs'), path = require('node:path'), vm = require('node:vm')
  const source = fs.readFileSync(path.join(__dirname, '../public/mobile/index.html'), 'utf8')
  const context = vm.createContext({ S: { bookBranch: 'bg' }, B_BRANCHES: [
    { id: 'bg', realId: 10 }, { id: 'qr', realId: 20 }, { id: 'hm', home: true },
  ] })
  const start = source.indexOf('function mobileServicePrice(')
  const end = source.indexOf('function mobileServiceBranchFilterHTML(', start)
  vm.runInContext(source.slice(start, end), context)
  const service = { default_price: 100, branches: [{ branch_id: 10, service_price: 150 }, { branch_id: 20, service_price: 0 }] }
  Object.defineProperty(service, 'price', { get: () => context.mobileServicePrice(service) })
  context.bSvc = () => service
  const variant = ['Service', 30, 150]; variant.id = '1'
  assert.equal(context.mobileVariantPrice(variant), 150)
  context.S.bookBranch = 'qr'
  assert.equal(context.mobileVariantPrice(variant), 0)
  context.S.bookBranch = 'hm'
  assert.equal(context.mobileVariantPrice(variant), 100)
  context.S.bookBranch = null
  assert.equal(context.mobileVariantPrice(variant), 100)
  assert(source.includes('${mobileVariantPrice(v)}'))
  assert(source.includes("pickBranch(event.target.value,'services')"))
})

test('branch pricing preserves zero and falls back for missing prices and home service', async () => {
  const { servicePrice } = await import('../src/utils/servicePrice.js')
  const service = { default_price: 100, branches: [
    { branch_id: 1, service_price: 150 },
    { branch_id: 2, service_price: 0 },
    { branch_id: 3, service_price: null },
    { branch_id: 4, service_price: '' },
  ] }
  assert.equal(servicePrice(service, { id: '1' }), 150)
  assert.equal(servicePrice(service, { id: 2 }), 0)
  for (const id of [3, 4, 5]) assert.equal(servicePrice(service, { id }), 100)
  assert.equal(servicePrice(service, null), 100)
  assert.equal(servicePrice(service, { id: 1, home: true }), 100)
})

test('selected services and totals react to branch changes and clear stale appointment choices', async () => {
  const { reactive, computed, ref, watch, nextTick } = require('vue')
  const { servicePrice } = await import('../src/utils/servicePrice.js')
  const fs = require('node:fs'), path = require('node:path'), vm = require('node:vm')
  const current = ref({ id: 1 })
  const context = vm.createContext({ reactive, computed, watch, servicePrice,
    useServiceLocation: () => ({ current }), useStore: () => ({ cartTotal: ref(0), cartItems: ref([]) }),
  })
  const source = fs.readFileSync(path.join(__dirname, '../src/composables/useBooking.js'), 'utf8')
  vm.runInContext(source.replace(/^import .*$/gm, '').replace(/export /g, ''), context)
  const booking = context.useBooking()
  booking.state.services = [{ id: 1, default_price: 100, dur: 30, branches: [{ branch_id: 1, service_price: 150 }] }]
  booking.state.emp = { 1: { id: 4 } }
  booking.state.time = { 1: '10:00' }
  booking.state.step = 2
  assert.equal(booking.priceParts.value.total, 172.5)
  current.value = { id: 2 }
  await nextTick()
  assert.equal(booking.selSvcs.value[0].price, 100)
  assert.equal(booking.priceParts.value.total, 115)
  assert.equal(booking.state.step, 0)
  assert.equal(Object.keys(booking.state.emp).length, 0)
  assert.equal(Object.keys(booking.state.time).length, 0)
  current.value = null
  await nextTick()
  assert.equal(booking.canProceed.value, false)
})
