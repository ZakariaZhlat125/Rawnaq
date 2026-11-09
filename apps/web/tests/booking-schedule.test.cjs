const {test}=require('node:test');const assert=require('node:assert/strict');
require('../public/booking-journey.js');const schedule=globalThis.SamiBookingJourney.schedule;
const services=[{id:'a',dur:30},{id:'b',dur:60},{id:'c',dur:15}];
const run=(slots,start='00:00',list=services)=>schedule(list,s=>slots[s.id]||[],start);
test('starts with the earliest available service rather than first selected',()=>{
 const result=run({a:['16:00'],b:['14:00']},'14:00',services.slice(0,2));
 assert.equal(result.complete,true);assert.deepEqual(result.rows.map(r=>r.service.id),['b','a']);
 assert.deepEqual(result.rows.map(r=>r.start),['14:00','16:00']);assert.equal(result.end,'16:30');
});
test('chooses next service after actual end, skipping overlapping slots',()=>{
 const result=run({a:['14:00','15:30'],b:['14:00'],c:['14:15','14:30']},'14:00');
 assert.deepEqual(result.rows.map(r=>r.service.id),['a','c']);assert.equal(result.complete,false);
 assert.equal(result.missing.id,'b');assert.equal(result.rows[1].start,'14:30');
});
test('ties preserve selection order and durations prevent overlaps',()=>{
 const result=run({a:['14:00','15:00'],b:['14:00','15:00']},'14:00',[services[1],services[0]]);
 assert.deepEqual(result.rows.map(r=>r.service.id),['b','a']);assert.deepEqual(result.rows.map(r=>r.start),['14:00','15:00']);
});
test('rechecks all remaining services at every step and honors chosen start',()=>{
 const result=run({a:['16:00'],b:['14:00','17:00'],c:['15:00','16:30']},'15:00');
 assert.equal(result.complete,true);assert.deepEqual(result.rows.map(r=>r.service.id),['c','a','b']);
 assert.equal(result.rows[1].shifted,true);
});
test('empty slots report missing service without inventing appointments',()=>{
 const result=run({a:['16:00'],c:['14:00']});assert.equal(result.complete,false);assert.equal(result.missing.id,'b');
 assert.deepEqual(result.rows.map(r=>r.service.id),['c','a']);
});
test('invalid slots and invalid starts are ignored safely',()=>{
 assert.equal(run({a:['invalid','25:00','14:90']}).rows.length,0);
 assert.equal(run({},'invalid').complete,false);
});
