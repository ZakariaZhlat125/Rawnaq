const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const { spawnSync } = require('node:child_process');
const path = require('node:path');
const root = path.resolve(__dirname, '../../..');
test('identical staff availability requests share one in-flight request', async () => {
 const source = fs.readFileSync(path.join(root, 'apps/web/src/components/booking/TimeStep.vue'), 'utf8');
 const start = source.indexOf('const slotRequests =');
 const end = source.indexOf('const days =', start);
 let calls = 0, resolve;
 const pending = new Promise(r => resolve = r);
 const context = vm.createContext({ fetchAvailableTimes: () => { calls++; return pending; } });
 vm.runInContext(source.slice(start, end), context);
 const first = vm.runInContext("requestSlots('2026-11-01', {id: 9}, 30)", context);
 const second = vm.runInContext("requestSlots('2026-11-01', {id: 9}, 30)", context);
 assert.equal(calls, 1); assert.equal(first, second);
 resolve(['10:00']); await first;
 vm.runInContext("requestSlots('2026-11-01', {id: 9}, 30)", context);
 assert.equal(calls, 2);
});
test('server free-run filtering excludes gaps and supports midnight', () => {
 const source = fs.readFileSync(path.join(root, 'backend/app/Http/Controllers/HomeBookingController.php'), 'utf8');
 const start = source.indexOf('    function filterAvailableTimes(');
 const end = source.indexOf('    /*', start);
 const method = source.slice(start, end);
 const php = '<?php ' + method + '\n' + '$cases = [["09:00","09:01","09:02","09:04","09:05"],["23:58","23:59","00:00","00:01"]]; foreach ($cases as $times) { echo json_encode(filterAvailableTimes($times, 3)), "\\n"; }';
 const result = spawnSync('php', [], { input: php, encoding: 'utf8' });
 assert.equal(result.status, 0, result.stderr);
 assert.deepEqual(result.stdout.trim().split('\n').map(JSON.parse), [['09:00'], ['23:58', '23:59']]);
});

test('SMS templates resolve actual booking variables before consolidation', () => {
 const source = fs.readFileSync(path.join(root, 'backend/app/Services/Payment/PaymentSuccessSmsService.php'), 'utf8');
 const start = source.indexOf('    private function renderMessage(');
 const end = source.indexOf('    private function loadBookings(', start);
 const method = source.slice(start, end).replace('private function', 'function');
 const php = '<?php function setting($key) { return null; } ' + method + ' echo renderMessage("taqnyat_payment_service_booking", ["service_names" => "مساج", "booking_dates" => "الأحد، 1 نوفمبر 2026", "booking_times" => "3:30 م", "invoice_code" => "INV-12", "branch_names" => "الرياض", "total_amount" => "150"]);';
 const result = spawnSync('php', [], { input: php, encoding: 'utf8' });
 assert.equal(result.status, 0, result.stderr);
 assert.ok(result.stdout.includes('مساج'));
 assert.ok(result.stdout.includes('الأحد، 1 نوفمبر 2026'));
 assert.ok(result.stdout.includes('3:30 م'));
 assert.equal(result.stdout.includes('[['), false);
});
