const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const source = fs.readFileSync(__dirname + '/../public/public-api.js', 'utf8')
function setup(fetch, parent) {
  const c = vm.createContext({ fetch, parent, Headers, URL, AbortController, setTimeout, clearTimeout, location: { href: 'https://sami-care.sa/home' } })
  vm.runInContext(source, c)
  return c.SamiPublicApi
}
const tick = () => new Promise(resolve => setImmediate(resolve))

test('public requests are serialized, home has priority and language needs no custom header', async () => {
  const calls = [], releases = []
  const api = setup((url, options) => {
    calls.push({ url, options })
    return new Promise(resolve => releases.push(() => resolve(new Response('{"data":[]}'))))
  })
  const shop = api.fetch('/api/shop')
  const home = api.fetch('/api/Home/all', { headers: { 'frezka-localization': 'en' } })
  await tick()
  assert.equal(calls.length, 1)
  assert.match(calls[0].url, /\/Home\/all$/)
  assert.equal(calls[0].options.headers.get('Accept-Language'), 'en')
  assert.equal(calls[0].options.headers.has('frezka-localization'), false)
  releases.shift()()
  await home
  await tick()
  assert.equal(calls.length, 2)
  releases.shift()()
  await shop
})

test('inflight duplicate requests share the network and have independent response bodies', async () => {
  let calls = 0
  const api = setup(async () => { calls++; return new Response('{"data":[1]}') })
  const [a,b] = await Promise.all([api.fetch('/api/public-contact'), api.fetch('/api/public-contact')])
  assert.equal(calls, 1)
  assert.deepEqual(await a.json(), { data: [1] })
  assert.deepEqual(await b.json(), { data: [1] })
  await api.fetch('/api/public-contact')
  assert.equal(calls, 2, 'completed data is not cached indefinitely')
})

test('a failed or cancelled read releases the queue for the next request', async () => {
  const api = setup(async (url, options) => {
    if (options.signal.aborted) throw Error('aborted')
    if (url.endsWith('/fail')) throw Error('offline')
    return new Response('{}')
  })
  const controller = new AbortController()
  controller.abort()
  const settled = await Promise.allSettled([
    api.fetch('/api/fail'), api.fetch('/api/cancel', { signal: controller.signal }), api.fetch('/api/ok'),
  ])
  assert.deepEqual(settled.map(r=>r.status), ['rejected', 'rejected', 'fulfilled'])
})

test('the mobile iframe reuses the parent queue and never queues authenticated writes', () => {
  const parentApi = { fetch() {} }
  assert.equal(setup(() => {}, { SamiPublicApi: parentApi }), parentApi)
  const api = setup(() => {})
  assert.throws(() => api.fetch('/api/book', { method: 'POST' }), /unauthenticated GET/)
  assert.throws(() => api.fetch('/api/profile', { headers: { Authorization: 'Bearer test' } }), /unauthenticated GET/)
})
