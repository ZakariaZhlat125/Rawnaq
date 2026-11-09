const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const path = require('node:path')
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8').replace(/\r\n/g, '\n')

test('receipt URL preserves Arabic booking details and matches only the returning payment attempt', () => {
  const storage = new Map()
  const ctx = vm.createContext({ window: { location: { origin: 'https://booking.example' } },
    btoa: s => Buffer.from(s, 'binary').toString('base64'),
    sessionStorage: { setItem: (k, v) => storage.set(k, v), getItem: k => storage.get(k) },
  })
  vm.runInContext(read('src/utils/bookingReceipt.js').replaceAll('export function', 'function'), ctx)
  const payload = { b: 'فرع البغدادية', d: '22 سبتمبر', s: [['قص الشعر', '12:00', 'أحمد', 100]], p: 115 }
  ctx.savePendingReceipt(payload, 42)
  assert.equal(ctx.returnedReceiptUrl(9, 41), '')
  assert.equal(ctx.returnedReceiptUrl(9, undefined), '')
  const url = new URL(ctx.returnedReceiptUrl(9, 42))
  assert.equal(url.pathname, '/booking-receipt')
  assert.deepEqual(JSON.parse(Buffer.from(url.searchParams.get('receipt'), 'base64url').toString('utf8')), { ...payload, r: 9 })
})

test('local QR encoder generates a real matrix containing the booking link', () => {
  const ctx = vm.createContext({ navigator: { userAgent: 'test' }, document: { documentElement: { tagName: 'html' } } })
  vm.runInContext(read('src/vendor/qrcode.js').replace('export default QRCode;', ''), ctx)
  const url = 'https://booking.example/booking-receipt?receipt=' + 'abc123'.repeat(100)
  const host = { childNodes: [{ offsetWidth: 320, offsetHeight: 320, style: {} }] }
  const qr = new ctx.QRCode(host, { text: url, width: 320, height: 320, correctLevel: ctx.QRCode.CorrectLevel.M })
  assert.equal(qr._oQRCode.dataList[0].data, url)
  assert(qr._oQRCode.getModuleCount() > 21)
  assert.equal(qr._oQRCode.isDark(0, 0), true)
  assert.equal(qr._oQRCode.isDark(1, 1), false)
  assert.equal(qr._oQRCode.isDark(3, 3), true)
  assert(host.innerHTML.includes('<table'))
})

test('desktop booking validation agrees with the mobile payment methods', () => {
  const ctx = vm.createContext({})
  vm.runInContext(read('public/payment-policy.js'), ctx)
  const html = read('public/mobile/index.html')
  vm.runInContext(html.match(/function mobileBookMethods\(\)\{[^\n]+/)[0], ctx)
  const source = read('src/composables/useBooking.js')
  const body = source.slice(source.indexOf('      case 4:') + '      case 4:'.length, source.indexOf('\n    }\n    return false', source.indexOf('      case 4:')))
  for (const balance of [0, 30, 100]) for (const wallet of [0, 10]) for (const pay of ['cod', 'card', 'urpay', 'mada', 'tabby']) {
    Object.assign(ctx, { state: { pay, walletBalance: balance }, paymentPolicy: ctx.SamiPaymentPolicy,
      priceParts: { value: { total: 100 } }, payableTotal: { value: 100 - wallet },
      walletDiscount: { value: wallet }, loyaltyPointsUsed: { value: 0 },
      bPriceParts: () => ({ total: 100 }), bookWalletBalance: () => balance,
      bookPayableTotal: () => 100 - wallet, bookWalletApplied: () => wallet, bookLoyaltyAppliedPoints: () => 0,
    })
    const desktop = vm.runInContext(`(function(){${body}})()`, ctx)
    const mobile = ctx.mobileBookMethods().some(m => m.id === (pay === 'cod' ? 'cash' : pay) && m.enabled)
    assert.equal(desktop, mobile, `${pay}, balance ${balance}, wallet ${wallet}`)
  }
})

test('QR canvas renderer runs in strict mode on Android browsers', () => {
  const canvasContext = { clearRect() {}, fillRect() {}, strokeRect() {} }
  const ctx = vm.createContext({ navigator: { userAgent: 'Android 13.0' },
    CanvasRenderingContext2D: function () {},
    document: { documentElement: { tagName: 'html' }, createElement: () => ({ style: {}, getContext: () => canvasContext }) },
  })
  vm.runInContext('"use strict";\n' + read('src/vendor/qrcode.js').replace('export default QRCode;', ''), ctx)
  const children = []
  const qr = new ctx.QRCode({ appendChild: el => children.push(el) }, { text: 'https://booking.example/booking-receipt?receipt=abc' })
  assert.equal(children.length, 2)
  assert.equal(qr._oDrawing.isPainted(), true)
})
