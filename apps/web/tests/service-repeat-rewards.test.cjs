const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/mobile/index.html','utf8');
function setup(){
 const c=vm.createContext({getAuth:()=>({api_token:'test'}),getLang:()=> 'ar',render:()=>{},document:{addEventListener(){}},bSelSvcs:()=>[{id:7,name:'Service',price:120}],escHtml:String,S:{}});
 vm.runInContext(source.slice(source.indexOf('let _repeatData='),source.indexOf('let _accountData=')),c);return c;
}
test('mobile reward selection applies only to the owned, usable matching service',()=>{
 const c=setup();
 vm.runInContext("_repeatData={progress:[],rewards:[{id:3,service_id:7,usable:true}]};_repeatSelected={7:3}",c);
 assert.equal(vm.runInContext('repeatServicePrice({id:7,price:120})',c),0);
 assert.equal(vm.runInContext('repeatServicePrice({id:8,price:80})',c),80);
 vm.runInContext('_repeatData.rewards[0].usable=false',c);
 assert.equal(vm.runInContext('repeatServicePrice({id:7,price:120})',c),120);
});
test('mobile confirmation renders free-service choice and customer progress',async()=>{
 const c=setup();c.authGet=async()=>({data:{progress:[{service_id:7,name:'Service',completed:2,required_visits:5}],rewards:[{id:3,service_id:7,name:'Service',usable:true,status:'available'}]}});
 await vm.runInContext('loadRepeatRewards()',c);
 const html=vm.runInContext('repeatRewardsHTML(true)',c);
 assert.match(html,/data-repeat-service="7"/);assert.match(html,/2\/5/);
 assert.doesNotMatch(vm.runInContext('repeatRewardsHTML(false)',c),/data-repeat-service/);
});
test('changing customer clears selection and ignores a late response from previous customer',async()=>{
 const c=setup();let resolveOld;c.authGet=()=>new Promise(resolve=>{resolveOld=resolve});
 const old=vm.runInContext('loadRepeatRewards()',c);
 c.getAuth=()=>({api_token:'new'});c.authGet=async()=>({data:{progress:[],rewards:[]}});
 await vm.runInContext('loadRepeatRewards()',c);
 resolveOld({data:{progress:[],rewards:[{id:99,usable:true}]}});await old;
 assert.equal(vm.runInContext('_repeatData.rewards.length',c),0);
 c.getAuth=()=>null;assert.equal(vm.runInContext('repeatRewardsHTML(true)',c),'');
});
test('desktop booking prices only matching usable rewards and keeps other services payable',()=>{
 const rewards=fs.readFileSync(__dirname+'/../src/composables/useServiceRepeatRewards.js','utf8');
 const booking=fs.readFileSync(__dirname+'/../src/composables/useBooking.js','utf8');
 const c=vm.createContext({serviceRepeat:{rewards:[{id:3,service_id:7,usable:true}]},state:{services:[{id:7,price:120},{id:8,price:80}],repeatRewardIds:{7:3}},current:{value:{id:1}},computed:fn=>({get value(){return fn()}}),servicePrice:s=>s.price});
 vm.runInContext(rewards.slice(rewards.indexOf('export function selectedServiceReward')).replace('export function','function'),c);
 const start=booking.indexOf('  const selSvcs = computed('),end=booking.indexOf('  const subTotal =',start);
 vm.runInContext(booking.slice(start,end),c);
 assert.equal(vm.runInContext('selSvcs.value[0].price',c),0);assert.equal(vm.runInContext('selSvcs.value[0].repeatRewardId',c),3);
 assert.equal(vm.runInContext('selSvcs.value[1].price',c),80);
 c.serviceRepeat.rewards[0].usable=false;assert.equal(vm.runInContext('selSvcs.value[0].price',c),120);
});
