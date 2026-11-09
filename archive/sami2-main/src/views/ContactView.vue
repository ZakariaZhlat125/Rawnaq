<script setup>
/*
  صفحة تواصل معنا
  مُرحَّلة من src/legacy/contact.html إلى مكوّن Vue.
  الماركب منقول حرفيًا لضمان تطابق التصميم — راجع docs/ARCHITECTURE.md
*/
import { ref } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import pageCss from '@/assets/styles/pages/contact.css?raw'

const root = ref(null)
const { current, openPicker } = useServiceLocation()

usePageStyles(pageCss, 'contact')
useInternalLinks(root)
</script>

<template>
  <div ref="root" class="">
<div class="shell">
  <div class="wrap">
    <div class="contact-grid">
      <!-- نموذج التواصل اليساري -->
      <div class="form-card">
        <div>
          <h2>تواصل معنا</h2>
          <div class="fld">
            <label>الاسم بالكامل *</label>
            <input type="text" id="cName" placeholder="أدخل اسمك بالكامل" />
          </div>
          <div class="fld">
            <label>رقم الهاتف *</label>
            <input type="text" id="cPhone" placeholder="05xxxxxxxx" />
          </div>
          <div class="fld">
            <label>البريد الإلكتروني</label>
            <input type="email" id="cMail" placeholder="name@example.com" />
          </div>
          <div class="fld">
            <label>الرسالة</label>
            <textarea id="cMsg" placeholder="اكتب استفسارك أو رسالتك هنا..."></textarea>
          </div>
        </div>
        <button class="btn btn-gold" id="sendBtn" style="margin-top: 15px;">إرسال الرسالة</button>
      </div>

      <!-- معلومات التواصل اليمينية -->
      <div class="info-card">
        <!-- مواعيد العمل -->
        <div class="info-section">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
            مواعيد العمل
          </h3>
          <div class="sched-detail">
            يومياً من <b>9:00 صباحاً</b> إلى <b>1:00 صباحاً</b> (بعد منتصف الليل)
          </div>
        </div>

        <!-- العناوين وفروعنا -->
        <div class="info-section">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
            العناوين
          </h3>
          <div class="addresses-grid">
            <!-- فرع قريش -->
            <div class="addr-box">
              <h4>فرع قريش</h4>
              <p>جدة — البوادي، شارع قريش</p>
              <a href="tel:+966646200455">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                </svg>
                966646200455+
              </a>
            </div>
            <!-- فرع البغدادية -->
            <div class="addr-box">
              <h4>فرع البغدادية</h4>
              <p>جدة — البغدادية الغربية، شارع حائل</p>
              <a href="tel:+966589615696">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                </svg>
                966589615696+
              </a>
            </div>
          </div>
        </div>

        <!-- خدمات منزلية -->
        <div class="info-section">
          <h3>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            خدمات منزلية
          </h3>
          <div class="home-services">
            <p>نوفر خدمة الحلاقة والعناية في منزلك لراحتك التامة (شعر، لحية، وماسكات طبيعية).</p>
            <a href="tel:+966585555555">📞 966585555555+</a>
          </div>
        </div>

        <!-- التواصل السريع عبر الواتساب -->
        <a href="https://wa.me/963959415545" class="whatsapp-btn" target="_blank" rel="noopener">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
          </svg>
          التواصل السريع عبر الواتساب
        </a>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
    <path d="M20 6L9 17l-5-5"/>
  </svg>
  <span>تم إرسال رسالتك بنجاح! شكراً لتواصلك معنا.</span>
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
