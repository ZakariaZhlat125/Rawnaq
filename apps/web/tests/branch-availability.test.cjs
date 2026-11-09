const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/mobile/index.html','utf8');
test('inactive branches stay visible with booking disabled',()=>{
 const start=source.indexOf('function mapFullBranch(');
 const end=source.indexOf('function mapReview(',start);
 const c=vm.createContext({localizedField:(r,k)=>r[k]||''});
 vm.runInContext(source.slice(start,end),c);
 for(const status of [0,'0',false,1,'1',true]){
  c.raw={id:8,status};
  assert.equal(vm.runInContext("mapFullBranch(raw,'br8',null).bookable",c),Number(status)===1);
  assert.equal(vm.runInContext("mapFullBranch(raw,'br8',null).realId",c),8);
 }
});
