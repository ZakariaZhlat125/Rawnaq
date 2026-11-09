;(function(root){
 const icons={shield:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',leaf:'<path d="M2 21c0-9 4-13 13-13M11 20A7 7 0 019.8 6.1C15.5 5 20 9.5 20 15a7 7 0 01-9 5z"/>',user:'<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"/>',heart:'<path d="M20 4c-4-3-8 2-8 2S8 1 4 4s-2 7 8 16C22 11 24 7 20 4z"/>',clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',sparkle:'<path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"/>'};
 const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const image=value=>typeof value==='string'&&/^(https?:\/\/|\/(?!\/))[^\s<>"']+$/.test(value)?value:'';
 function resolve(category){
  if(category.page_content&&typeof category.page_content==='object')return category.page_content;
  const text=(category.slug+' '+JSON.stringify(category.name||'')+' '+(category.key||'')).toLowerCase();
  const groups={bath:['bath','hammam','moroccan','حمام'],pedi:['pedi','foot','بديكير'],skin:['skin','facial','بشرة'],mass:['mass','مساج'],hair:['hair','shav','حلاق']};
  for(const [key,words] of Object.entries(groups))if(words.some(w=>text.includes(w)))return root.SamiServicePageDefaults[key];
  return {...root.SamiServicePageDefaults.hair,benefits:[],faq:[],benefits_image:'',banner_image:''};
 }
 function render(category,lang,hero=''){
  const data=resolve(category),en=lang==='en';
  const field=value=>root.SamiDataI18n.field(value,lang);
  const name=field(category.name),text=key=>esc(field(data[key]));
  const icon=key=>'<svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">'+(icons[key]||icons.shield)+'</svg>';
  const heading=(key,fallback)=>text(key)||esc(fallback);
  const list=key=>(Array.isArray(data[key])?data[key]:[]).filter(row=>field(row.title)&&field(row.text));
  const card=row=>'<article class="sc-card"><span class="sc-icon">'+icon(row.icon)+'</span><div><h3>'+esc(field(row.title))+'</h3><p>'+esc(field(row.text))+'</p></div></article>';
  let html='';
  if(data.show_why!==false&&list('why').length)html+='<section class="sc-section sc-why"><h2>'+heading('why_title',en?'Why choose '+name+'?':'لماذا تختار '+name+' لدينا؟')+'</h2><p class="sc-intro">'+text('why_intro')+'</p><div class="sc-grid">'+list('why').map(card).join('')+'</div></section>';
  const benefitsImage=image(data.benefits_image)||image(hero);
  if(data.show_benefits!==false&&list('benefits').length)html+='<section class="sc-section sc-benefits">'+(benefitsImage?'<img class="sc-benefits-image" src="'+esc(benefitsImage)+'" alt="'+esc(name)+'" loading="lazy">':'')+'<div class="sc-benefits-panel"><h2>'+heading('benefits_title',en?'Benefits of '+name:'فوائد '+name)+'</h2><p class="sc-intro">'+text('benefits_intro')+'</p><div class="sc-grid">'+list('benefits').map(card).join('')+'</div></div></section>';
  const faq=(Array.isArray(data.faq)?data.faq:[]).filter(row=>field(row.q)&&field(row.a));
  if(data.show_faq!==false&&faq.length)html+='<section class="sc-section sc-faq"><h2>'+heading('faq_title',en?'Frequently asked questions':'الأسئلة الشائعة')+'</h2>'+faq.map(row=>'<details><summary>'+esc(field(row.q))+'</summary><p>'+esc(field(row.a))+'</p></details>').join('')+'</section>';
  const bannerImage=image(data.banner_image)||image(hero);
  if(data.show_banner!==false)html+='<section class="sc-section sc-banner">'+(bannerImage?'<img src="'+esc(bannerImage)+'" alt="" loading="lazy">':'')+'<div><h2>'+heading('banner_title',en?'Ready to experience '+name+'?':'جاهز لتجربة '+name+'؟')+'</h2><p>'+text('banner_text')+'</p><button type="button" data-category-book="'+esc(category.id||category.apiId||'')+'">'+(en?'Book now':'احجز الآن')+'</button></div></section>';
  return '<div class="service-page-content" dir="'+(en?'ltr':'rtl')+'">'+html+'</div>';
 }
 root.SamiServiceContent={resolve,render};
})(globalThis);
