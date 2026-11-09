const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')

const source = fs.readFileSync(__dirname + '/../src/composables/useSiteContact.js', 'utf8')
  .replace(/^import .*$/gm, '').replace('export function', 'function')
const settle = () => new Promise(resolve => setImmediate(resolve))

function setup(fetch) {
  const context = vm.createContext({ fetch, console: { warn() {} }, setTimeout: callback => callback(), ref: value => ({ value }), computed: read => ({ get value() { return read() } }), resolveBackendUrl: path => 'https://backend.example' + path })
  vm.runInContext(fs.readFileSync(__dirname + '/../public/site-contact.js', 'utf8'), context)
  vm.runInContext(source, context)
  return context.useSiteContact
}

test('support links use the dashboard number and share one request', async () => {
  let calls = 0
  const use = setup(async (url, options) => {
    calls++
    assert.equal(url, 'https://backend.example/api/public-contact')
    assert.equal(options.cache, 'no-store')
    return { ok: true, json: async () => ({ data: { whatsapp_number: '201234567890' } }) }
  })
  const first = use(), second = use()
  assert.equal(first.whatsappUrl.value, '')
  await settle()
  assert.equal(first.whatsappUrl.value, 'https://wa.me/201234567890')
  assert.equal(second.whatsappUrl.value, first.whatsappUrl.value)
  assert.equal(calls, 1)
})

for (const number of ['', 'bad-number', 'https://example.com']) {
  test('missing or invalid contact hides support links: ' + number, async () => {
    const contact = setup(async () => ({ ok: true, json: async () => ({ data: { whatsapp_number: number } }) }))()
    await settle()
    assert.equal(contact.whatsappUrl.value, '')
  })
}

test('failed contact request hides links and allows a later retry', async () => {
  let calls = 0
  const use = setup(async () => {
    if (++calls <= 3) throw new Error('offline')
    return { ok: true, json: async () => ({ data: { whatsapp_number: '201234567890' } }) }
  })
  const contact = use()
  await settle()
  assert.equal(contact.whatsappUrl.value, '')
  use()
  await settle()
  assert.equal(contact.whatsappUrl.value, 'https://wa.me/201234567890')
})

for (const number of ['+20 123 456 7890', '00201234567890', '+٢٠ ١٢٣ ٤٥٦ ٧٨٩٠']) {
  test('formatted dashboard numbers produce a valid WhatsApp link: ' + number, async () => {
    const contact = setup(async () => ({ ok: true, json: async () => ({ data: { whatsapp_number: number } }) }))()
    await settle()
    assert.equal(contact.whatsappUrl.value, 'https://wa.me/201234567890')
  })
}

test('transient contact API failure retries without another component mounting', async () => {
  let calls = 0
  const contact = setup(async () => {
    if (++calls === 1) return { ok: false, status: 503 }
    return { ok: true, json: async () => ({ data: { whatsapp_number: '201234567890' } }) }
  })()
  await settle()
  assert.equal(calls, 2)
  assert.equal(contact.whatsappUrl.value, 'https://wa.me/201234567890')
})
