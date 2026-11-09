const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const root = path.join(__dirname, '..')
function worker(fetchImpl = async () => new Response('live')) {
  const handlers = {}, removed = [], added = [], data = new Map()
  const cache = { addAll: async requests => added.push(...requests), match: async () => new Response('offline') }
  const context = {
    URL, Response, Request,
    fetch: fetchImpl,
    caches: { open: async () => cache, keys: async () => ['other-app', 'sami-pwa-old', 'sami-pwa-test'], delete: async key => removed.push(key) },
    self: { location: { origin: 'https://sami.example' }, clients: { claim: async () => data.set('claimed', true) }, addEventListener: (name, fn) => handlers[name] = fn }
  }
  // Resolve root-relative precache requests as browsers do.
  context.Request = class extends Request { constructor(url, options) { super(new URL(url, context.self.location.origin), options) } }
  vm.runInNewContext(fs.readFileSync(path.join(root, 'public/sw.js'), 'utf8').replaceAll('__PWA_BUILD__', 'test'), context)
  return { handlers, removed, added, data }
}
function event(url, options = {}) {
  let response
  return {
    request: { url, method: options.method || 'GET', mode: options.mode || 'navigate', headers: new Headers(options.headers) },
    respondWith: promise => { response = Promise.resolve(promise) },
    get response() { return response }
  }
}
test('worker passes APIs, authorization, payment callbacks, external URLs and writes through', () => {
  const { handlers } = worker()
  const cases = [
    ['/api/bookings', {}], ['/payment/callback', {}], ['/app/dashboard', {}],
    ['/booking', { method: 'POST' }], ['/booking', { headers: { authorization: 'Bearer private' } }],
    ['https://payment.example/checkout', {}], ['/api/profile', { mode: 'cors' }]
  ]
  for (const [url, options] of cases) {
    const request = event(new URL(url, 'https://sami.example').href, options)
    handlers.fetch(request)
    assert.equal(request.response, undefined, url)
  }
})
test('offline frontend navigations get an honest fallback; HTTP errors stay unchanged', async () => {
  const offline = worker(async () => { throw new TypeError('offline') })
  for (const url of ['/', '/booking?payment_status=pending', '/mobile/index.html?view=booking', '/services/3', '/gift-recipient?ref=private']) {
    const request = event('https://sami.example' + url)
    offline.handlers.fetch(request)
    assert.equal(await (await request.response).text(), 'offline')
  }
  const online = worker(async () => new Response('server error', { status: 503 }))
  const request = event('https://sami.example/booking')
  online.handlers.fetch(request)
  assert.equal((await request.response).status, 503)
})
test('precache contains only public offline assets and cleanup preserves unrelated caches', async () => {
  const instance = worker()
  let pending
  instance.handlers.install({ waitUntil: promise => pending = promise }); await pending
  assert.equal(instance.added.length, 5)
  assert.ok(instance.added.every(request => new URL(request.url).pathname.startsWith('/pwa/')))
  instance.handlers.activate({ waitUntil: promise => pending = promise }); await pending
  assert.deepEqual(instance.removed, ['sami-pwa-old'])
  assert.equal(instance.data.get('claimed'), true)
  assert.doesNotMatch(fs.readFileSync(path.join(root, 'public/sw.js'), 'utf8'), /skipWaiting\s*\(/)
})
test('manifest icons have their declared PNG dimensions and all HTML entries link the same app', () => {
  const manifest = JSON.parse(fs.readFileSync(path.join(root, 'public/manifest.webmanifest'), 'utf8'))
  assert.equal(manifest.display, 'standalone')
  assert.equal(manifest.id, 'com.sami.app')
  assert.equal(manifest.scope, '/')
  for (const icon of manifest.icons) {
    const png = fs.readFileSync(path.join(root, 'public', icon.src))
    assert.equal(`${png.readUInt32BE(16)}x${png.readUInt32BE(20)}`, icon.sizes)
  }
  assert.ok(manifest.icons.some(icon => icon.purpose === 'maskable'))
  for (const file of ['index.html', 'public/mobile/index.html', 'public/mobile/index.dynamic.html']) {
    const html = fs.readFileSync(path.join(root, file), 'utf8')
    assert.equal((html.match(/rel="manifest"/g) || []).length, 1)
    assert.match(html, /href="\/manifest.webmanifest"/)
    assert.match(html, /src="\/pwa.js"/)
  }
})