const {test}=require('node:test');
const assert=require('node:assert/strict');
require('../public/booking-journey.js');
const sort=globalThis.SamiBookingJourney.groupsByTime;
const a={id:'a'},b={id:'b'},c={id:'c'},d={id:'d'};
const groups=[{id:'one',services:[a,c]},{id:'two',services:[b,d]}];
const flatten=rows=>rows.flatMap(g=>g.services.map(s=>s.id));
test('journey orders services chronologically across categories without changing selection',()=>{
 const times={a:'16:00',b:'14:00',c:'18:00',d:'17:00'};
 const rows=sort(groups,times,[a,b,c,d]);
 assert.deepEqual(flatten(rows),['b','a','d','c']);
 assert.deepEqual(groups[0].services,[a,c]);
 assert.deepEqual(times,{a:'16:00',b:'14:00',c:'18:00',d:'17:00'});
 assert.equal(new Set(rows.map(g=>g.key)).size,rows.length);
});
test('equal times use selection order even across grouped categories; missing times are last',()=>{
 assert.deepEqual(flatten(sort(groups,{a:'14:00',b:'14:00',c:'14:00'},[b,a,c,d])),['b','a','c','d']);
});
test('updated times immediately change the journey order',()=>{
 assert.deepEqual(flatten(sort(groups,{a:'12:00',b:'09:00',c:'00:00',d:'23:59'},[a,b,c,d])),['c','b','a','d']);
});
test('mobile journey renderer uses chronological groups and preserves equal-time selection order',()=>{
 const source= require('node:fs').readFileSync(__dirname+'/../public/mobile/index.html','utf8');
 const vm=require('node:vm');
 const context=vm.createContext({window:{SamiBookingJourney:globalThis.SamiBookingJourney},S:{bookTime:{a:'16:00',b:'14:00',c:'16:00'}},
 bServiceGroups:()=>[{id:'one',services:[a,c]},{id:'two',services:[b]}],bSelSvcs:()=>[a,b,c],
 bookTimeToMin:t=>t?Number(t.split(':')[0])*60+Number(t.split(':')[1]):null,
 bookMinToTime:m=>`${Math.floor(m/60)}:${String(m%60).padStart(2,'0')}`,
 bookGroupDuration:g=>g.services.reduce((sum,s)=>sum+(s.dur||0),0)});
 vm.runInContext(source.slice(source.indexOf('function bookGroupPlan(){'),source.indexOf('function bookSessionEndTime(){')),context);
 assert.deepEqual(Array.from(vm.runInContext("bookGroupPlan().filter(r=>r.type==='group').flatMap(r=>r.group.services.map(s=>s.id))",context)),['b','a','c']);
});
