<template>
  <footer id="contact" class="site-footer sami-unified-footer">
    <div class="footer-content container">
      <div class="footer-contact">
        <h3>تواصل معنا</h3>
        <form class="phone-form" @submit.prevent>
          <span>+966</span><input type="tel" placeholder="رقم الجوال" />
          <button type="submit">رقم</button>
        </form>
        <a class="whatsapp" href="https://wa.me/966566101963">تواصل واتساب <b>◉</b></a>
        <div class="footer-socials">
          <a href="https://www.instagram.com/samicare.sa/" aria-label="Instagram">◎</a>
          <a href="https://www.linkedin.com/" aria-label="LinkedIn">in</a>
          <a href="https://x.com/samicare_sa" aria-label="X">♥</a>
          <a href="https://www.facebook.com/samicare.sa" aria-label="Facebook">f</a>
        </div>
      </div>
      <div class="footer-about">
        <h3>عن سامي</h3>
        <RouterLink to="/">الرئيسية</RouterLink><RouterLink to="/services">خدماتنا</RouterLink>
        <RouterLink to="/store">المنتجات</RouterLink><RouterLink to="/packages-gifts">الباقات</RouterLink>
        <a href="/#blog">المدونة</a><a href="/#about">عن سامي</a>
      </div>
      <div class="footer-brand">
        <RouterLink to="/"><img src="/logo.png" alt="عناية سامي" /></RouterLink>
      </div>
      <div class="footer-help">
        <h3>مساعدة</h3>
        <RouterLink to="/contact">تواصل معنا</RouterLink><a href="#">الشكاوى والاقتراحات</a>
        <a href="#">سياسة الخصوصية</a><a href="#">الشروط والأحكام</a>
      </div>
      <div class="footer-branches">
        <h3>عناوين الفروع</h3>
        <b>الفرع الأول</b><p>جدة - الحمدانية، شارع خالد<br />0566101963</p>
        <b>الفرع الثاني</b><p>جدة - الروابي، شارع باريس<br />0530064642</p>
      </div>
    </div>
    <div class="footer-payments" aria-label="وسائل الدفع">
      <span class="visa">VISA</span><span class="master">●●</span><span class="mada">مدى mada</span>
      <span class="tabby">tabby</span><span class="tamara">tamara</span><span class="apple"> Pay</span>
    </div>
    <p class="copyright">© {{ new Date().getFullYear() }} عناية سامي. جميع الحقوق محفوظة.</p>
  </footer>
</template>
