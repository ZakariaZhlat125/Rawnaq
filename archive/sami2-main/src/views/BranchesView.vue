<script setup>
/*
  صفحة الفروع
  مُرحَّلة من src/legacy/branches.html إلى مكوّن Vue.
  الماركب منقول حرفيًا لضمان تطابق التصميم — راجع docs/ARCHITECTURE.md
*/
import { ref } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import pageCss from '@/assets/styles/pages/branches.css?raw'

const root = ref(null)
const { current, openPicker } = useServiceLocation()

usePageStyles(pageCss, 'branches')
useInternalLinks(root)
</script>

<template>
  <div ref="root" class="">
<div class="shell">
  <div class="wrap">
    <div class="page-head">
      <h1>فروعنا الفاخرة</h1>
      <p>اختر الفرع الأقرب إليك لبدء حجز خدماتك. فروعنا مجهزة بالكامل لتقدم لك أرقى تجارب العناية والاسترخاء.</p>
    </div>

    <div class="branches-grid">
      <div class="branch-card">
        <div class="branch-img"><img src="https://sami-care.sa//storage/uploads/389/4fKSZ0AIoLjJT4JuFKO81qWrxGQ6ymQ6zLbi1L3b.jpg" alt="فرع قريش" /></div>
        <div class="branch-info">
          <h3>فرع قريش</h3>
          <div class="branch-meta">
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>جدة، البوادي — شارع قريش</span>
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>12:00 ص – 10:00 م</span>
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.9 6.2 6.6.8-4.9 4.6 1.3 6.6L12 17l-5.9 3.2 1.3-6.6L2.5 9l6.6-.8z"/></svg>تقييم 4.7</span>
          </div>
        </div>
        <a href="/booking?branch=qr" class="btn btn-gold" style="width: 100%">احجز في هذا الفرع</a>
      </div>

      <div class="branch-card">
        <div class="branch-img"><img src="https://sami-care.sa//storage/uploads/391/sSX2TVdlk84EEewXCDhT0w24X5WAJnPOA0GE6VHD.jpg" alt="فرع البغدادية" /></div>
        <div class="branch-info">
          <h3>فرع البغدادية</h3>
          <div class="branch-meta">
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>جدة، البغدادية الغربية — شارع حائل</span>
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>12:00 ص – 10:00 م</span>
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.9 6.2 6.6.8-4.9 4.6 1.3 6.6L12 17l-5.9 3.2 1.3-6.6L2.5 9l6.6-.8z"/></svg>تقييم 4.8</span>
          </div>
        </div>
        <a href="/booking?branch=bg" class="btn btn-gold" style="width: 100%">احجز في هذا الفرع</a>
      </div>

      <div class="branch-card">
        <div class="branch-img"><img src="https://sami-care.sa//storage/uploads/407/7gF352Ispha9FbN9jPxmjnpOQDg5vFY41n5ZTdqK.png" alt="خدمة منزلية" /></div>
        <div class="branch-info">
          <h3>خدمة منزلية</h3>
          <div class="branch-meta">
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21V12h6v9"/></svg>حلاقة وعناية ومساجات طبيعية — في منزلك</span>
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>خدمة VIP</span>
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.9 6.2 6.6.8-4.9 4.6 1.3 6.6L12 17l-5.9 3.2 1.3-6.6L2.5 9l6.6-.8z"/></svg>تقييم 4.9</span>
          </div>
        </div>
        <a href="/booking?branch=hm" class="btn btn-gold" style="width: 100%">احجز الخدمة المنزلية</a>
      </div>
    </div>
  </div>
</div>

<footer>
  <div class="wrap">
    <div class="f-grid">
      <div class="f-brand">
        <a class="logo" href="/"><span class="mark"><img src="/logo.png" alt="عناية سامي" style="width:29px;height:29px;object-fit:contain" /></span><span class="name"><b>عناية سامي</b><span>SAMI CARE</span></span></a>
        <p>مركز متخصص في العناية الرجالية المتكاملة بجدة، حيث تلتقي الفخامة بالاحترافية في كل تفصيلة.</p>
        <div class="socials">
          <a href="https://x.com/samicare_sa" aria-label="X"><svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.3 22h-6.3l-4.9-6.4L6.5 22H3.4l7.3-8.3L1 2h6.5l4.4 5.8L18.9 2zm-1.1 18h1.7L7.1 3.9H5.3L17.8 20z"/></svg></a>
          <a href="https://www.instagram.com/samicare.sa/" aria-label="انستقرام"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
          <a href="https://www.facebook.com/samicare.sa" aria-label="فيسبوك"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
        </div>
      </div>
      <div>
        <h4>روابط مهمة</h4>
        <ul class="f-links">
          <li><a href="/">الرئيسية</a></li>
          <li><a href="/booking">حجز موعد</a></li>
          <li><a href="/#services">خدماتنا</a></li>
          <li><a href="/packages-gifts">الباقات</a></li>
          <li><a href="/gifts">الهدايا</a></li>
        </ul>
      </div>
      <div>
        <h4>استكشف</h4>
        <ul class="f-links">
          <li><a href="/store">المتجر</a></li>
          <li><a href="/branches">فروعنا</a></li>
          <li><a href="/contact">تواصل معنا</a></li>
          <li></li>
          <li><a href="https://sami-care.sa/TermsAndConditions">الشروط والأحكام</a></li>
        </ul>
      </div>
      <div>
        <h4>عناوين الفروع</h4>
        <div class="f-branch"><b>قريش</b><small>جدة البوادي شارع قريش</small><a href="tel:+966550046462">+966 55 004 6462</a></div>
        <div class="f-branch"><b>البغدادية</b><small>جدة البغدادية الغربية شارع حائل</small><a href="tel:+966569610958">+966 56 961 0958</a></div>
        <div class="f-branch"><b>خدمات منزلية</b><small>حلاقة شعر ولحية وماسكات طبيعية</small></div>
      </div>
    </div>
    <div class="f-bottom">
      <small>© 2026 عناية سامي — جميع الحقوق محفوظة</small>
      <div class="pay" aria-label="بوابات الدفع"><span title="Visa">VISA</span><span title="Mastercard">Mastercard</span><span title="مدى">mada</span><span title="Tabby">tabby</span><span title="Apple Pay">Pay</span></div>
    </div>
  </div>
</footer>
  </div>
</template>
