const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
class Element {
  constructor(tag, head) {this.tag=tag;this.head=head;this.attrs={};}
  setAttribute(k,v){this.attrs[k]=v;this[k]=v;}
  remove(){this.head.children=this.head.children.filter(n=>n!==this);}
}
function documentStub() {
  const head={children:[],append(n){n.parentNode=this;this.children.push(n);},querySelectorAll(selector){
    const [,tag,key,value]=selector.match(/^(\w+)(?:\[([^=]+)="([^"]+)"\])?$/);
    return this.children.filter(n=>n.tag===tag && (!key || (n.attrs[key]??n[key])===value));
  },querySelector(s){return this.querySelectorAll(s)[0]??null;}};
  return {head,baseURI:'https://sami-care.sa/mobile/index.html',createElement:tag=>new Element(tag,head)};
}
function seo(doc,fetcher) {
  const context={URL,AbortController,setTimeout,clearTimeout};
  vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname,'../public/site-seo.js'),'utf8'),context);
  return context.SamiSeo.create({endpoint:'https://dashboard.sami-care.sa/api/seo',doc,fetcher});
}
const success=async()=>({ok:true,json:async()=>({status:true,data:{title:'Saved title',description:'Saved description'}})});
test('canonical strips aliases and tracking; sharing metadata follows saved SEO',async()=>{
 const doc=documentStub(),updater=seo(doc,success);
 await updater.update('/services.html?ref=private#section','ar');
 assert.equal(doc.head.querySelector('link[rel="canonical"]').href,'https://sami-care.sa/services');
 assert.equal(doc.head.querySelector('meta[property="og:url"]').content,'https://sami-care.sa/services');
 assert.equal(doc.head.querySelector('meta[property="og:title"]').content,'Saved title');
 assert.equal(doc.head.querySelector('meta[name="robots"]').content,'index,follow');
 await updater.update('/store','en');
 assert.equal(doc.head.querySelectorAll('link[rel="canonical"]').length,1);
 assert.equal(doc.head.querySelectorAll('meta[property="og:title"]').length,1);
 assert.equal(doc.head.querySelector('meta[property="og:locale"]').content,'en_US');
});
test('private routes and missing dynamic pages remain noindex',async()=>{
 const doc=documentStub(),updater=seo(doc,success);
 for(const path of ['/account','/booking','/booking-receipt','/gift-recipient']){
  await updater.update(path+'?token=secret','ar');
  assert.equal(doc.head.querySelector('meta[name="robots"]').content,'noindex,follow');
  assert.ok(!doc.head.querySelector('link[rel="canonical"]').href.includes('secret'));
 }
 const missing=seo(doc,async()=>({ok:false,status:404}));
 await missing.update('/blog/missing','ar');
 assert.equal(doc.head.querySelector('meta[name="robots"]').content,'noindex,follow');
 await updater.update('/unknown','ar');
 assert.equal(doc.head.querySelector('meta[name="robots"]').content,'noindex,follow');
 await updater.update('/services','ar');
 assert.equal(doc.head.querySelector('meta[name="robots"]').content,'index,follow');
});
