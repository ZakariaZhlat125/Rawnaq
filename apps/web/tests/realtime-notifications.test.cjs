const {test}=require('node:test');const assert=require('node:assert/strict');const fs=require('node:fs');const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/realtime-notifications.js','utf8').replaceAll('export ','').replaceAll('import.meta.url',JSON.stringify('https://example.test/realtime-notifications.js'));
function setup(enabled=true){
 const callbacks={},events=[],timers=[],documents=[];let options,destroyed=false,authRequest;
 class Pusher {constructor(key,opt){options=opt;this.connection={bind(){},unbind_all(){}}}subscribe(name){assert.equal(name,'private-notifications.user.7');return{bind:(event,fn)=>callbacks[event]=fn,unbind_all(){}}}disconnect(){destroyed=true}}
 const c=vm.createContext({URL,Promise,Date,Set,WeakSet,console,window:{Pusher,addEventListener(){}},document:{hidden:false,addEventListener:(...a)=>documents.push(a),querySelectorAll:()=>[]},localStorage:{getItem:()=>null,setItem(){}},setInterval:fn=>{timers.push(fn);return timers.length},clearInterval(){},setTimeout:()=>1,clearTimeout(){},fetch:async(url,opt)=>{if(url.endsWith('/auth')){authRequest=opt;return{ok:true,json:async()=>({auth:'signed'})}}return{ok:true,json:async()=>({enabled,key:'key',cluster:'eu',channel:'private-notifications.user.7',auth_url:'https://example.test/auth'})}}});
 vm.runInContext(source,c);c.onEvent=id=>events.push(id);vm.runInContext("const live=mountRealtime({configUrl:'/config',headers:()=>({Authorization:'Bearer test'}),onEvent})",c);
 return{c,events,callbacks,timers,documents,get options(){return options},get destroyed(){return destroyed},get authRequest(){return authRequest}};
}
const flush=()=>new Promise(resolve=>setImmediate(resolve));
test('private Pusher events are deduplicated and stop after destroy',async()=>{
 const h=setup();await flush();h.callbacks['pusher:subscription_succeeded']();assert.deepEqual(h.events,[null]);
 h.callbacks['notification.created']({id:'one'});h.callbacks['notification.created']({id:'one'});assert.deepEqual(h.events,[null,'one']);
 vm.runInContext('live.destroy()',h.c);h.callbacks['notification.created']({id:'two'});assert.deepEqual(h.events,[null,'one']);assert.equal(h.destroyed,true);
});
test('authorization passes the current bearer token to the backend, with no secrets in client config',async()=>{
 const h=setup();await flush();let result;await h.options.channelAuthorization.customHandler({socketId:'1.2',channelName:'private-notifications.user.7'},(e,data)=>{assert.equal(e,null);result=data});
 assert.equal(result.auth,'signed');assert.equal(h.authRequest.headers.Authorization,'Bearer test');assert.equal(JSON.parse(h.authRequest.body).channel_name,'private-notifications.user.7');
});
test('missing Pusher credentials use fallback polling without connecting',async()=>{
 const h=setup(false);await flush();assert.equal(h.options,undefined);h.timers[0]();assert.deepEqual(h.events,[null]);
});
test('sound is unlocked by user interaction and never queues old sounds',()=>{
 const h=setup(false);let notes=0;h.c.window.AudioContext=class{constructor(){this.state='running';this.currentTime=0;this.destination={}}createOscillator(){return{frequency:{},connect(){},start(){notes++},stop(){}}}createGain(){return{gain:{setValueAtTime(){},linearRampToValueAtTime(){},exponentialRampToValueAtTime(){}},connect(){}}}};
 vm.runInContext("notificationSound.play('before')",h.c);assert.equal(notes,0);
 h.documents.find(a=>a[0]==='pointerdown')[1]();vm.runInContext("notificationSound.play('after');notificationSound.play('after')",h.c);assert.equal(notes,3);
});
