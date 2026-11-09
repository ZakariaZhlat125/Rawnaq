(function(){
  const footerHTML=`<footer id="contact" class="site-footer sami-unified-footer">
    <div class="footer-content container">
      <div class="footer-contact"><h3>تواصل معنا</h3>
        <form class="phone-form" onsubmit="return false"><span>+966</span><input type="tel" placeholder="رقم الجوال"><button type="submit">رقم</button></form>
        <a class="whatsapp" href="https://wa.me/966566101963">تواصل واتساب <b>◉</b></a>
        <div class="footer-socials"><a href="https://www.instagram.com/samicare.sa/" aria-label="Instagram">◎</a><a href="https://www.linkedin.com/" aria-label="LinkedIn">in</a><a href="https://x.com/samicare_sa" aria-label="X">♥</a><a href="https://www.facebook.com/samicare.sa" aria-label="Facebook">f</a></div>
      </div>
      <div class="footer-about"><h3>عن سامي</h3><a href="/">الرئيسية</a><a href="/services">خدماتنا</a><a href="/store">المنتجات</a><a href="/packages-gifts">الباقات</a><a href="/#blog">المدونة</a><a href="/#about">عن سامي</a></div>
      <div class="footer-brand"><a href="/"><img src="/logo.png" alt="عناية سامي"></a></div>
      <div class="footer-help"><h3>مساعدة</h3><a href="/contact">تواصل معنا</a><a href="#">الشكاوى والاقتراحات</a><a href="#">سياسة الخصوصية</a><a href="#">الشروط والأحكام</a></div>
      <div class="footer-branches"><h3>عناوين الفروع</h3><b>الفرع الأول</b><p>جدة - الحمدانية، شارع خالد<br>0566101963</p><b>الفرع الثاني</b><p>جدة - الروابي، شارع باريس<br>0530064642</p></div>
    </div>
    <div class="footer-payments" aria-label="وسائل الدفع"><span class="visa">VISA</span><span class="master">●●</span><span class="mada">مدى mada</span><span class="tabby">tabby</span><span class="tamara">tamara</span><span class="apple"> Pay</span></div>
    <p class="copyright">© 2026 عناية سامي. جميع الحقوق محفوظة.</p>
  </footer>`;
  function unifyFooter(){
    const footer=document.querySelector('footer:not(.sami-unified-footer)');
    if(footer)footer.outerHTML=footerHTML;
  }
  const motionSelector=[
    'main > *','.g-head','.gtype','.branch-card','.branch','.cat','.sub','.gpkg','.pkg',
    '.product-card','.service-card','.card','.panel','.section-title','footer > *'
  ].join(',');
  let revealObserver;
  function registerMotion(root){
    if(!revealObserver)return;
    const items=[];
    if(root.nodeType===1&&root.matches(motionSelector))items.push(root);
    root.querySelectorAll&&root.querySelectorAll(motionSelector).forEach(item=>items.push(item));
    items.forEach((item,index)=>{
      if(item.dataset.desktopMotion)return;
      item.dataset.desktopMotion='pending';
      item.style.setProperty('--motion-delay',Math.min(index%8,7)*55+'ms');
      revealObserver.observe(item);
    });
  }
  function init(){
    unifyFooter();
    if(matchMedia('(max-width:640px)').matches||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
    document.documentElement.classList.add('desktop-motion-ready');
    revealObserver=new IntersectionObserver(entries=>entries.forEach(entry=>{
      if(!entry.isIntersecting)return;
      entry.target.dataset.desktopMotion='visible';
      revealObserver.unobserve(entry.target);
    }),{threshold:.08,rootMargin:'0px 0px -5% 0px'});
    registerMotion(document);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});
  else init();
  new MutationObserver(unifyFooter).observe(document.documentElement,{childList:true,subtree:true});
  new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(registerMotion)))
    .observe(document.documentElement,{childList:true,subtree:true});
})();
