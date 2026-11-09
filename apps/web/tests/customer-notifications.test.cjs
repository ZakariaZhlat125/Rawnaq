const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')

function harness(initialToken = 'customer-one') {
  const elements = new Map(), listeners = new Map(), intervals = [], requests = []
  const makeElement = () => ({
    hidden: true, children: [], textContent: '',
    append(...children) { this.children.push(...children) },
    replaceChildren() { this.children = [] },
    insertBefore(child) { this.children.push(child) },
    setAttribute() {}, focus() {}, remove() {},
    querySelector(selector) { if (!elements.has(selector)) elements.set(selector, makeElement()); return elements.get(selector) },
  })
  let token = initialToken, stream, liveOptions
  let snapshot = { notification_data: [], all_unread_count: 0, current_page: 1, last_page: 1 }
  const window = {
    addEventListener(name, fn) { listeners.set(name, fn) }, removeEventListener() {}, dispatchEvent() {},
  }
  const context = vm.createContext({
    testRealtime: { notificationSound: { muted: () => false, toggle() {}, play() {} }, mountRealtime(options) { liveOptions = options; return { destroy() {} } } },
    testBrowserPush: { mountBrowserPush: () => ({ sync() {}, destroy() {} }) },
    window, document: { hidden: false, createElement: makeElement, body: makeElement(), addEventListener() {}, removeEventListener() {} },
    localStorage: { getItem: () => token },
    DOMParser: class { parseFromString(text) { return { body: { textContent: text } } } },
    CustomEvent: class {}, AbortController, TextDecoder, TextEncoder,
    setTimeout: () => 1, clearTimeout() {}, setInterval(fn) { intervals.push(fn); return 1 }, clearInterval() {},
    fetch: async (url, options) => {
      requests.push({ url, options })
      if (url.endsWith('/stream')) {
        const body = new ReadableStream({ start(controller) { stream = controller } })
        options.signal.addEventListener('abort', () => { try { stream.close() } catch {} })
        return { ok: true, headers: { get: () => 'text/event-stream' }, body }
      }
      return { ok: true, json: async () => structuredClone(snapshot) }
    },
  })
  const source = fs.readFileSync(require.resolve('../public/customer-notifications.js'), 'utf8').replace(/import\('\/realtime-notifications[^']*'\)/g, 'Promise.resolve(testRealtime)').replace(/import\('\/browser-push[^']*'\)/g, 'Promise.resolve(testBrowserPush)')
  vm.runInContext(source, context)
  const counts = []
  const inbox = window.mountSamiNotifications({ apiBase: '/api', onCount: count => counts.push(count) })
  const settle = () => new Promise(resolve => setImmediate(resolve))
  return {
    inbox, requests, counts, elements, settle,
    setToken(value) { token = value; intervals[0]() },
    setSnapshot(value) { snapshot = value },
    emit(value) { snapshot = value; liveOptions.onEvent(value.notification_data[0]?.id || null) },
    chunk(value) { stream.enqueue(new TextEncoder().encode(value)) },
  }
}

const entry = (id, read = null) => ({ id, read_at: read, created_at: '2026-09-28T10:00:00Z', data: { subject: 'Wallet', data: { message: `Activity ${id}` } } })
const payload = entries => ({ notification_data: entries, all_unread_count: entries.filter(item => !item.read_at).length, current_page: 1, last_page: 1 })

test('guest makes no requests; login starts authenticated feed and logout clears private content', async () => {
  const h = harness(null)
  assert.equal(h.requests.length, 0)
  h.setToken('customer-one'); await h.settle()
  assert.equal(h.requests[0].options.headers.Authorization, 'Bearer customer-one')
  h.emit(payload([entry('a')])); await h.settle()
  assert.equal(h.elements.get('.sn-list').children.length, 1)
  h.setToken(null); await h.settle()
  assert.equal(h.elements.get('.sn-root').hidden, true)
  assert.equal(h.elements.get('.sn-list').children.length, 0)
  assert.equal(h.counts.at(-1), 0)
  h.inbox.destroy()
})

test('Pusher invalidation updates unread count and duplicate events do not add rows', async () => {
  const h = harness(); await h.settle()
  h.emit(payload([entry('a')])); await h.settle()
  assert.equal(h.counts.at(-1), 1)
  assert.equal(h.elements.get('.sn-toast').textContent, 'Activity a')
  h.emit(payload([entry('a')])); await h.settle()
  assert.equal(h.elements.get('.sn-list').children.length, 1)
  h.inbox.destroy()
})

test('opening inbox preserves unread state and reading uses explicit POST', async () => {
  const h = harness(); h.setSnapshot(payload([entry('a')])); await h.settle()
  h.inbox.open(); await h.settle()
  assert.equal(h.requests.filter(request => request.options.method === 'POST').length, 0)
  h.setSnapshot(payload([entry('a', '2026-09-28T10:01:00Z')]))
  await h.elements.get('.sn-list').children[0].onclick(); await h.settle()
  assert.ok(h.requests.some(request => request.url === '/api/notifications/a/read' && request.options.method === 'POST'))
  assert.equal(h.counts.at(-1), 0)
  h.inbox.destroy()
})
