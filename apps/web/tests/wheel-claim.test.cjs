const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs'), vm = require('node:vm')
const source = fs.readFileSync(__dirname + '/../public/mobile/index.html', 'utf8')
test('guest spins directly without a registration form; unavailable wheel stays disabled', () => {
  let click, spins = 0
  const state = { spinning: false, wheelEnabled: true, wheelCanSpin: true }
  const c = vm.createContext({ S: state, $: () => ({ addEventListener: (_, fn) => { click = fn } }), doSpin: () => { spins++ } })
  const start = source.indexOf("  const sp=$('#spinBtn');", source.indexOf('function bind(){'))
  const end = source.indexOf(" if(S.tab==='home'&&S.svPage)", start)
  vm.runInContext(source.slice(start, end).replace(/\n }\s*$/, ''), c)
  click()
  assert.equal(spins, 1)
  state.wheelCanSpin = false
  click()
  assert.equal(spins, 1)
})
function openResult(loginRequired) {
  const listeners = {}, body = { innerHTML: '' }, events = []
  const ui = { body, ov: { classList: { add() {} } }, sheet: { classList: { add() {} } } }
  const c = vm.createContext({
    ensureBottomSheet: () => ui, document: { body: { classList: { add() {} } } },
    S: {}, getLang: () => 'ar', escHtml: s => String(s),
    $: selector => ({ addEventListener: (_, fn) => { listeners[selector] = fn } }),
    closeBottomSheet: () => events.push('close'), go: tab => events.push(tab),
    navigateParent: path => events.push(path),
  })
  const start = source.indexOf('function openWheelResultSheet(')
  vm.runInContext(source.slice(start, source.indexOf('let nearbyPosition', start)), c)
  c.openWheelResultSheet('Reward 50', true, loginRequired)
  return { body, events, listeners }
}
test('guest sees the prize and can open login from the result', () => {
  const { body, events, listeners } = openResult(true)
  assert.match(body.innerHTML, /Reward 50/)
  assert.match(body.innerHTML, /سجّل دخولك/)
  assert.match(body.innerHTML, /id="wheelResultLogin"/)
  listeners['#wheelResultLogin']()
  assert.deepEqual(events, ['close', 'account', '/account'])
})
test('signed in winner sees the prize without a login prompt', () => {
  const { body } = openResult(false)
  assert.match(body.innerHTML, /Reward 50/)
  assert.doesNotMatch(body.innerHTML, /id="wheelResultLogin"/)
})
