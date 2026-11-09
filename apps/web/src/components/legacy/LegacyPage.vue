<script setup>
import { nextTick,onBeforeUnmount,onMounted,ref } from 'vue'
import { useRouter } from 'vue-router'
import { assetPath } from '@/utils/assetPath'
const props=defineProps({source:{type:String,required:true},title:String})
const root=ref(null); const router=useRouter(); const styles=[]; const cleanups=[]
let previousBodyClass='';let previousBodyStyle=''
const page=parse(props.source)
function parse(source){
  const doc=new DOMParser().parseFromString(source,'text/html')
  rewriteAssetAttributes(doc)
  return {html:doc.body.innerHTML.replace(/<script[\s\S]*?<\/script>/gi,''),bodyClass:doc.body.className,bodyStyle:doc.body.getAttribute('style')||'',css:[...doc.querySelectorAll('style')].map(x=>rewriteCssAssetUrls(x.textContent)).join('\n'),scripts:[...doc.querySelectorAll('script:not([src])')].map(x=>rewriteCssAssetUrls(x.textContent))}
}
function isLocalAsset(value){
  return /^\/(?:images|mobile|assets)\//.test(value) || value === '/logo.png'
}
function rewriteAssetAttributes(doc){
  doc.querySelectorAll('[src]').forEach(el=>{const value=el.getAttribute('src'); if(value&&isLocalAsset(value))el.setAttribute('src',assetPath(value))})
  doc.querySelectorAll('[srcset]').forEach(el=>{
    const value=el.getAttribute('srcset')
    if(!value)return
    el.setAttribute('srcset',value.split(',').map(part=>{
      const [url,...rest]=part.trim().split(/\s+/)
      return [isLocalAsset(url)?assetPath(url):url,...rest].join(' ')
    }).join(', '))
  })
}
function rewriteCssAssetUrls(css){
  return css.replace(/url\((['"]?)(\/(?:images|mobile|assets)\/[^'")]+|\/logo\.png)\1\)/g,(_,quote,path)=>`url(${quote}${assetPath(path)}${quote})`)
}
function routeClicks(event){
  const a=event.target.closest('a[href]'); if(!a)return
  const href=a.getAttribute('href'); if(!href||href.startsWith('#')||href.startsWith('http')||href.startsWith('mailto:')||href.startsWith('tel:'))return
  const url=new URL(href,window.location.origin); if(url.origin!==window.location.origin)return
  const map={'/':'/','/index.html':'/','/booking':'/booking','/booking.html':'/booking','/store':'/store','/store.html':'/store','/gifts':'/gifts','/gifts.html':'/gifts','/packages-gifts':'/packages-gifts','/packages-gifts.html':'/packages-gifts','/branches':'/branches','/branches.html':'/branches','/contact':'/contact','/contact.html':'/contact','/gift-recipient':'/gift-recipient','/gift-recipient.html':'/gift-recipient'}
  if(map[url.pathname]){event.preventDefault();router.push(map[url.pathname]+url.search+url.hash)}
}
onMounted(async()=>{
  document.title=props.title||'رونق'; document.documentElement.dir='rtl';
  previousBodyClass=document.body.className;previousBodyStyle=document.body.getAttribute('style')||''
  document.body.className=page.bodyClass;page.bodyStyle?document.body.setAttribute('style',page.bodyStyle):document.body.removeAttribute('style')
  const style=document.createElement('style');style.dataset.legacyPage='true';style.textContent=page.css;document.head.appendChild(style);styles.push(style)
  await nextTick();root.value?.addEventListener('click',routeClicks,true)
  const nativeAdd=window.addEventListener.bind(window);const nativeDocAdd=document.addEventListener.bind(document)
  window.addEventListener=(type,fn,opt)=>{cleanups.push(()=>window.removeEventListener(type,fn,opt));return nativeAdd(type,fn,opt)}
  document.addEventListener=(type,fn,opt)=>{cleanups.push(()=>document.removeEventListener(type,fn,opt));return nativeDocAdd(type,fn,opt)}
  try{for(const code of page.scripts)(0,eval)(code)}finally{window.addEventListener=nativeAdd;document.addEventListener=nativeDocAdd}
})
onBeforeUnmount(()=>{root.value?.removeEventListener('click',routeClicks,true);cleanups.forEach(fn=>fn());styles.forEach(x=>x.remove());document.body.className=previousBodyClass;previousBodyStyle?document.body.setAttribute('style',previousBodyStyle):document.body.removeAttribute('style')})
</script>
<template><div ref="root" class="legacy-page" v-html="page.html"></div></template>
