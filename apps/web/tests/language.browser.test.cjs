const { test } = require('node:test')
const assert = require('node:assert/strict')
const path = require('node:path')
// Browser tests are optional when Playwright is not installed locally.
let chromium
try { ({ chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright')) } catch (error) {
  if (error.code !== 'MODULE_NOT_FOUND') throw error
}
const root = path.join(__dirname, '..')

test('static translations handle toggles, reused nodes, attributes and late dialogs', { skip: !chromium && 'Playwright is required' }, async () => {
  const browser = await chromium.launch({ headless: true })
  try {
    const page = await browser.newPage()
    await page.setContent('<main><span id="label">تسجيل الخروج</span><input id="input" placeholder="اسمك"><textarea id="user">اسمك</textarea><div contenteditable="true">تسجيل الخروج</div><span translate="no">اسمك</span></main>')
    await page.addScriptTag({ path: path.join(root, 'public/site-dictionary.js') })
    await page.addScriptTag({ path: path.join(root, 'public/site-i18n.js') })
    await page.evaluate(() => {
      window.lang = 'en'
      window.translator = SamiI18n.createTranslator(document, () => window.lang)
    })
    assert.equal(await page.locator('#label').textContent(), 'Log out')
    assert.equal(await page.locator('#input').getAttribute('placeholder'), 'Your name')
    assert.equal(await page.locator('#user').inputValue(), 'اسمك')
    assert.equal(await page.locator('[contenteditable]').textContent(), 'تسجيل الخروج')
    assert.equal(await page.locator('[translate=no]').textContent(), 'اسمك')
    await page.evaluate(() => {
      document.querySelector('#label').firstChild.data = 'المحفظة'
      document.querySelector('#input').setAttribute('placeholder', 'اسم المستلم')
      const dialog = document.createElement('div')
      dialog.id = 'dialog'; dialog.textContent = 'اختر طريقة الدفع المناسبة'
      document.body.append(dialog)
    })
    await page.waitForFunction(() => document.querySelector('#label').textContent === 'Wallet')
    assert.equal(await page.locator('#input').getAttribute('placeholder'), 'Recipient’s name')
    assert.equal(await page.locator('#dialog').textContent(), 'Choose a payment method')
    await page.evaluate(() => {
      window.lang = 'ar'; translator.process(document.documentElement)
    })
    assert.equal(await page.locator('#label').textContent(), 'المحفظة')
    assert.equal(await page.locator('#input').getAttribute('placeholder'), 'اسم المستلم')
    await page.evaluate(() => {
      document.querySelector('#label').firstChild.data = 'اسم جديد خاص بالعميل'
      window.lang = 'en'; translator.process(document.documentElement)
    })
    assert.equal(await page.locator('#label').textContent(), 'اسم جديد خاص بالعميل')
    assert.equal(await page.evaluate(() => SamiI18n.translate('  اختر\nطريقة الدفع المناسبة  ', 'en')), '  Choose a payment method  ')
    assert.equal(await page.evaluate(() => SamiI18n.translate('120 ر.س', 'en')), '120 SAR')
    // Own DOM writes must settle, with no observer feedback loop.
    await page.waitForTimeout(50)
    assert.equal(await page.evaluate(() => 2 + 2), 4)
  } finally { await browser.close() }
})
test('desktop and embedded mobile share language across toggles, resize and reload', { skip: (!chromium || !process.env.LANGUAGE_TEST_URL) && 'Set LANGUAGE_TEST_URL to a running frontend and provide Playwright' }, async () => {
  const browser = await chromium.launch({ headless: true })
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } })
    const base = process.env.LANGUAGE_TEST_URL || 'http://127.0.0.1:5174'
    const errors = []
    page.on('pageerror', error => errors.push(error.message))
    await page.route('**/*', route => {
      const url = route.request().url()
      if (url.startsWith(base + '/') && !url.includes('/api/')) return route.continue()
      return route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ status: true, data: [] }) })
    })
    await page.goto(base)
    await page.locator('.lang-toggle').click()
    await page.waitForFunction(() => document.documentElement.lang === 'en')
    assert.equal(await page.locator('html').getAttribute('dir'), 'ltr')
    assert.match(await page.locator('body').innerText(), /Expert team/)
    await page.setViewportSize({ width: 390, height: 844 })
    const frame = page.frameLocator('.global-mobile-frame')
    await frame.locator('#langBtn').waitFor()
    assert.equal(await frame.locator('html').getAttribute('lang'), 'en')
    assert.match(await frame.locator('body').innerText(), /Summer offer/)
    await frame.locator('#langBtn').click()
    await page.waitForFunction(() => document.documentElement.lang === 'ar')
    assert.equal(await frame.locator('html').getAttribute('dir'), 'rtl')
    assert.match(await frame.locator('body').innerText(), /عرض الصيف/)
    await frame.locator('#langBtn').click()
    await page.waitForFunction(() => document.documentElement.lang === 'en')
    await page.reload()
    await frame.locator('#langBtn').waitFor()
    assert.equal(await frame.locator('html').getAttribute('lang'), 'en')
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.locator('.lang-toggle').waitFor()
    assert.match(await page.locator('body').innerText(), /Expert team/)
    await page.locator('.lang-toggle').click()
    await page.waitForFunction(() => document.documentElement.lang === 'ar')
    assert.match(await page.locator('body').innerText(), /فريق متخصص/)
    assert.deepEqual(errors, [])
  } finally { await browser.close() }
})