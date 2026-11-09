const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const vm=require('node:vm');
require('../public/booking-journey.js');
const services=[{id:'a',dur:30},{id:'b',dur:60}], slots={a:['16:00','18:00'],b:['14:00','17:00']};
const mobile=fs.readFileSync(__dirname+'/../public/mobile/index.html','utf8');
const desktop=fs.readFileSync(__dirname+'/../src/components/booking/TimeStep.vue','utf8');
for(const platform of ['desktop','mobile']){
 test(platform+' applies earliest schedule, replaces stale times and keeps preview read-only',()=>{
  const state={time:{stale:'09:00'},mode:'manual'};
  const c=vm.createContext({window:{SamiBookingJourney:globalThis.SamiBookingJourney},state,orderedServices:{value:services},
   normalizeSlots:s=>s||[],slotsByService:slots,setTime:(id,t)=>state.time[id]=t,
   S:{bookDate:new Date(),bookTime:{stale:'09:00'},bookMode:'manual'},toBackendDate:()=> '2026-10-25',
   bookOrderedServices:()=>services,bookSortedSlotsFor:s=>slots[s.id],});
  const source=platform==='desktop'?desktop:mobile;
  const name=platform==='desktop'?'buildSequentialSchedule':'bookBuildSequentialSchedule';
  const start=source.indexOf('function '+name+'(');
  const end=source.indexOf('\nfunction ',start+10);
  vm.runInContext(source.slice(start,end),c);
  const preview=vm.runInContext(name+"('14:00')",c);
  assert.equal(preview.complete,true);assert.equal(state.time.stale,'09:00');assert.equal(c.S.bookTime.stale,'09:00');
  vm.runInContext(name+"('14:00',{apply:true})",c);
  const actual=platform==='desktop'?state.time:c.S.bookTime;
  assert.deepEqual({...actual},{b:'14:00',a:'16:00'});
  vm.runInContext(name+"('17:00',{apply:true})",c);
  assert.deepEqual({...platform==='desktop'?state.time:c.S.bookTime},{b:'17:00',a:'18:00'});
 });
}
test('mobile initial slots include all services and actual first service follows scheduled start',()=>{
 const c=vm.createContext({S:{bookDate:new Date(),bookTime:{}},bSelSvcs:()=>services,bookSortedSlotsFor:s=>slots[s.id],toBackendDate:()=>'',bookTimeToMin:t=>t?+t.slice(0,2)*60 + +t.slice(3):null});
 const start=mobile.indexOf('function bookOrderedServices()');const end=mobile.indexOf('function bookClearTimesFrom(',start);
 vm.runInContext(mobile.slice(start,end),c);
 assert.deepEqual(Array.from(vm.runInContext("bookInitialSlots('2026-10-25')",c)),['14:00','16:00','17:00','18:00']);
 assert.equal(vm.runInContext('bookFirstService().id',c),'b');
 c.S.bookTime={a:'16:00',b:'17:00'};assert.equal(vm.runInContext('bookFirstService().id',c),'a');
});
