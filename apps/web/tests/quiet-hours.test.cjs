const { test } = require('node:test'), assert = require('node:assert/strict')
const fs = require('node:fs'), vm = require('node:vm')
const c = vm.createContext({})
vm.runInContext(fs.readFileSync(__dirname + '/../public/quiet-hours.js', 'utf8'), c)
const points = c.SamiQuietHours.points
const settings = [{ branch_id: 9, enabled: true, points: 25, hours: [[0, 12, 23], [1], [], [], [], [], []] }]
test('quiet hour badges follow the branch, weekday and half-open hour boundaries', () => {
  for (const [date, time, expected] of [
    ['2026-10-25', '00:00', 25], ['2026-10-25', '00:59', 25], ['2026-10-25', '01:00', 0],
    ['2026-10-25', '12:59', 25], ['2026-10-25', '13:00', 0], ['2026-10-25', '23:59', 25],
    ['2026-10-26', '00:00', 0], ['2026-10-26', '01:00', 25], ['2026-10-27', '12:30', 0],
    ['2026-02-30', '12:30', 0], ['2026-10-25', '24:00', 0],
  ]) assert.equal(points(settings, 9, date, time), expected, date + ' ' + time)
  assert.equal(points(settings, 10, '2026-10-25', '12:30'), 0)
  assert.equal(points(settings, null, '2026-10-25', '12:30'), 0)
  assert.equal(points([{ ...settings[0], enabled: false }], 9, '2026-10-25', '12:30'), 0)
})
