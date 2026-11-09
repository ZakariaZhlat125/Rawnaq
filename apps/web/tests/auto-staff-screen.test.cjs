const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/mobile/index.html','utf8');
function setup(mode,staff){
 const services=[{id:1}],group={id:'g',cat:'g',name:'Service',services};
 const c=vm.createContext({S:{bookMode:mode,bookStep:3,bookEmp:{}},bServiceGroups:()=>[group],bSelSvcs:()=>services,bGroupStaff:()=>staff,bGroupLoading:()=>false,bGroupSelectedEmployee:()=>null,bIsGroupEmployeeSelected:()=>false,escHtml:String,initials:()=>'',bookCatIcon:()=>'',svg:()=>'',bGroupServicesLabel:()=>'',bGroupServiceNames:()=>'',wizAutoEmployeeStatusHTML:()=>'<p>Staff status</p>'});
 for(const [start,end] of [['function autoAssignEmployees(){','function ensureBookingEmployees(){'],['function wizEmployeeHTML(){','function wizDateTimeHTML(){'],['function canWizNext(){','function ']]){
  const a=source.indexOf(start),b=source.indexOf(end,a+start.length);vm.runInContext(source.slice(a,b),c);
 }
 return c;
}
for(const mode of ['auto','manual'])test(mode+' employee screen renders when staff has loaded',()=>{
 const c=setup(mode,[{id:9,name:'Employee'}]);
 assert.ok(vm.runInContext('wizEmployeeHTML()',c).includes('employee-modes'));
 if(mode==='auto'){vm.runInContext('autoAssignEmployees()',c);assert.equal(vm.runInContext('canWizNext()',c),true);}
});
test('automatic selection cannot continue without available staff',()=>{
 const c=setup('auto',[]);assert.doesNotThrow(()=>vm.runInContext('wizEmployeeHTML();autoAssignEmployees()',c));assert.equal(vm.runInContext('canWizNext()',c),false);
});
