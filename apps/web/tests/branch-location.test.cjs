const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')

function load(geolocation, secure = true) {
  const context = { window: { isSecureContext: secure }, navigator: { geolocation }, Intl }
  vm.runInNewContext(fs.readFileSync(require.resolve('../public/branch-location.js'), 'utf8'), context)
  return context.window.SamiBranchLocation
}
const geo = load()

test('haversine gives zero, a known one-degree distance and handles the date line', () => {
  assert.equal(geo.distance({ latitude: 21.5, longitude: 39.2 }, { latitude: 21.5, longitude: 39.2 }), 0)
  assert.ok(Math.abs(geo.distance({ latitude: 0, longitude: 0 }, { latitude: 0, longitude: 1 }) - 111.195) < .001)
  assert.ok(Math.abs(geo.distance({ latitude: 0, longitude: 179.5 }, { latitude: 0, longitude: -179.5 }) - 111.195) < .001)
})

test('missing or invalid coordinates are not treated as zero', () => {
  for (const latitude of [null, undefined, '', ' ', 'abc', false, 91, -91, Infinity]) {
    assert.equal(geo.coordinates({ latitude, longitude: 39 }), null)
  }
  assert.equal(geo.coordinates({ latitude: 21, longitude: 181 }), null)
  assert.equal(geo.coordinates({ latitude: '0', longitude: '0' }).latitude, 0)
  assert.equal(geo.coordinates({ address: { latitude: '21.5', longitude: '39.2' } }).latitude, 21.5)
})

test('sorting preserves IDs and input, excludes home service, and does not invent missing distances', () => {
  const branches = [
    { id: 'far', latitude: 0, longitude: 2 },
    { id: 'unknown', latitude: null, longitude: null },
    { id: 'home', home: true, latitude: 0, longitude: 0 },
    { id: 'near', latitude: 0, longitude: 1 },
  ]
  const ranked = geo.rank(branches, { latitude: 0, longitude: 0 })
  assert.equal(ranked.map(branch => branch.id).join(','), 'near,far,unknown,home')
  assert.equal(ranked[0].nearest, true)
  assert.equal(ranked[2].distanceKm, null)
  assert.equal(ranked[3].nearest, false)
  assert.equal(branches[0].id, 'far')
  assert.equal(branches[0].distanceKm, undefined)
  assert.equal(geo.rank(branches, null).map(branch => branch.id).join(','), 'far,unknown,home,near')
  assert.equal(geo.rank(branches, null).some(branch => branch.nearest), false)
})

test('distances use metres or kilometres and missing coordinates have an explicit label', () => {
  assert.match(geo.format(.35, 'en'), /350 m/)
  assert.match(geo.format(2.43, 'en'), /2.4 km/)
  assert.match(geo.format(0, 'en'), /0 m/)
  assert.equal(geo.format(null, 'en'), 'Distance unavailable')
})

test('location request passes timeout and returns valid coordinates', async () => {
  const helper = load({ getCurrentPosition(success, failure, options) {
    assert.equal(options.timeout, 12000)
    assert.equal(options.maximumAge, 60000)
    success({ coords: { latitude: 21.5, longitude: 39.2, accuracy: 25 } })
  } })
  const position = await helper.locate()
  assert.equal(position.latitude, 21.5)
  assert.equal(position.accuracy, 25)
})

test('denied, unavailable, timed out, unsupported and insecure requests remain recoverable', async () => {
  for (const code of [1, 2, 3]) {
    const helper = load({ getCurrentPosition(success, failure) { failure({ code }) } })
    await assert.rejects(helper.locate(), error => error.code === code)
    assert.match(helper.errorMessage({ code }, 'en'), /manually/)
  }
  await assert.rejects(load(undefined).locate(), error => error.code === 'unsupported')
  await assert.rejects(load(undefined, false).locate(), error => error.code === 'insecure')
})
