const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const http = require('node:http')
let chromium
try { ({ chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright')) } catch (error) { if (error.code !== 'MODULE_NOT_FOUND') throw error }
const root = path.resolve(__dirname, '../dist')
const types = { '.html': 'text/html', '.js': 'application/javascript', '.css': 'text/css', '.png': 'image/png', '.webmanifest': 'application/manifest+json' }
const options = { skip: !chromium && 'Playwright is required', timeout: 60000 }
async function serve() {
  let apiCount = 0
  const server = http.createServer((request, response) => {
    const pathname = new URL(request.url, 'http://localhost').pathname
    if (pathname.startsWith('/api/')) { response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({ status: true, data: [], count: ++apiCount })); return }
    const file = path.resolve(root, '.' + decodeURIComponent(pathname))
    if (!file.startsWith(root + path.sep) && file !== root) { response.writeHead(403); response.end(); return }
    let target = file
    if (!fs.existsSync(target) || fs.statSync(target).isDirectory()) {
      if (path.extname(pathname)) { response.writeHead(404); response.end(); return }
      target = path.join(root, 'index.html')
    }
    response.setHeader('Content-Type', types[path.extname(target)] || 'application/octet-stream')
    response.setHeader('Cache-Control', 'no-cache')
    response.end(fs.readFileSync(target))
  })
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve))
  return { base: `http://127.0.0.1:${server.address().port}`, close: () => new Promise(resolve => { server.close(resolve); server.closeAllConnections() }) }
}
test('production PWA registers, passes browser installability checks and recovers from offline', options, async () => {
  const server = await serve(), browser = await chromium.launch({ headless: true })
  try {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 } })
    await context.route('https://**/*', route => route.abort())
    await context.addInitScript(() => localStorage.setItem('samiSiteLang', 'en'))
    const page = await context.newPage()
    await page.goto(server.base)
    await page.evaluate(() => navigator.serviceWorker.ready)
    await page.waitForFunction(() => navigator.serviceWorker.controller)
    assert.equal(await page.locator('link[rel=manifest]').getAttribute('href'), '/manifest.webmanifest')
    const client = await context.newCDPSession(page)
    const { installabilityErrors } = await client.send('Page.getInstallabilityErrors')
    assert.deepEqual(installabilityErrors, [])
    const first = await page.evaluate(() => fetch('/api/private').then(response => response.json()))
    const second = await page.evaluate(() => fetch('/api/private').then(response => response.json()))
    assert.ok(second.count > first.count)
    const urls = await page.evaluate(async () => {
      const all = []
      for (const key of await caches.keys()) for (const request of await (await caches.open(key)).keys()) all.push(new URL(request.url).pathname)
      return all
    })
    assert.equal(urls.length, 5)
    assert.ok(urls.every(url => url.startsWith('/pwa/')))
    await context.setOffline(true)
    await page.goto(server.base + '/booking?payment_status=pending')
    assert.equal(await page.locator('h1').textContent(), 'You are offline')
    assert.equal(await page.locator('html').getAttribute('dir'), 'ltr')
    await context.setOffline(false)
    await page.locator('#retry').click()
    await page.locator('.global-mobile-frame').waitFor()
    await context.setOffline(true)
    await page.goto(server.base + '/mobile/index.html?view=store')
    assert.equal(await page.locator('h1').textContent(), 'You are offline')
  } finally { await browser.close(); await server.close() }
})
test('install action uses the native event and iPhone guidance is localized and dismissible', options, async () => {
  const server = await serve(), browser = await chromium.launch({ headless: true })
  try {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 } })
    await context.route('https://**/*', route => route.abort())
    const page = await context.newPage()
    await page.goto(server.base)
    await page.evaluate(() => {
      const event = new Event('beforeinstallprompt', { cancelable: true })
      event.prompt = async () => { window.promptCalled = true }
      event.userChoice = Promise.resolve({ outcome: 'accepted' })
      window.dispatchEvent(event)
    })
    await page.locator('#sami-pwa-install .pwa-install-action').click()
    assert.equal(await page.evaluate(() => window.promptCalled), true)
    assert.equal(await page.locator('#sami-pwa-install').count(), 0)
    const iphone = await browser.newContext({ viewport: { width: 390, height: 844 }, userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1' })
    await iphone.route('https://**/*', route => route.abort())
    const iosPage = await iphone.newPage()
    await iosPage.goto(server.base)
    await iosPage.locator('#sami-pwa-install .pwa-install-action').click()
    assert.match(await iosPage.locator('#sami-pwa-help').innerText(), /Safari/)
    await iosPage.evaluate(() => { document.documentElement.lang = 'en' })
    await iosPage.waitForFunction(() => document.querySelector('#sami-pwa-help').textContent.includes('Add to Home Screen'))
    await iosPage.locator('.pwa-install-close').click()
    assert.equal(await iosPage.locator('#sami-pwa-install').count(), 0)
  } finally { await browser.close(); await server.close() }
})
test('installed app retains the mobile layout in landscape and hides the install banner', options, async () => {
  const server = await serve(), browser = await chromium.launch({ headless: true })
  try {
    const context = await browser.newContext({ viewport: { width: 844, height: 390 } })
    await context.route('https://**/*', route => route.abort())
    await context.addInitScript(() => Object.defineProperty(navigator, 'standalone', { value: true }))
    const page = await context.newPage()
    await page.goto(server.base)
    await page.locator('.global-mobile-frame').waitFor()
    assert.equal(await page.locator('#sami-pwa-install').count(), 0)
    assert.equal(await page.frameLocator('.global-mobile-frame').locator('#sami-pwa-install').count(), 0)
  } finally { await browser.close(); await server.close() }
})