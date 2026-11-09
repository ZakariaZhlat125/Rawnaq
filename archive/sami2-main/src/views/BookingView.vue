<script setup>
/*
  صفحة الحجز — مُرحَّلة بالكامل من src/legacy/booking.html إلى مكوّنات Vue.

  خمس خطوات + شاشة نجاح:
    ServicesStep → EmployeeStep → TimeStep → ConfirmStep → PayStep → BookingSuccess
  الملخص الجانبي يظهر عند اختيار خدمة واحدة على الأقل.
*/
import { ref, computed, watch } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useBooking, rs } from '@/composables/useBooking'
import pageCss from '@/assets/styles/pages/booking.css?raw'

import BookingStepper from '@/components/booking/BookingStepper.vue'
import ServicesStep from '@/components/booking/ServicesStep.vue'
import EmployeeStep from '@/components/booking/EmployeeStep.vue'
import TimeStep from '@/components/booking/TimeStep.vue'
import ConfirmStep from '@/components/booking/ConfirmStep.vue'
import PayStep from '@/components/booking/PayStep.vue'
import BookingSuccess from '@/components/booking/BookingSuccess.vue'
import BookingSummary from '@/components/booking/BookingSummary.vue'

const root = ref(null)
const { current, openPicker } = useServiceLocation()
const { state, selCats, priceParts, canProceed, nextLabel } = useBooking()

usePageStyles(pageCss, 'booking')
useInternalLinks(root)

const payLoading = ref(false)
const toastMsg = ref('')
const toastOn = ref(false)

function toast(msg) {
  toastMsg.value = msg
  toastOn.value = true
  clearTimeout(toast._h)
  toast._h = setTimeout(() => { toastOn.value = false }, 2600)
}

const cartCount = computed(() => state.services.length + state.upsell.length)
const hasSvc = computed(() => state.services.length > 0)

/* الملخص الجانبي يبدأ من اختيار الموظف ويستمر حتى الدفع، بما فيها اختيار الوقت */
const showBookingSummary = computed(() => !state.done && hasSvc.value && state.step >= 1)
const stageCols = computed(() => (showBookingSummary.value ? 'minmax(0,1fr) minmax(285px,315px)' : '1fr'))

/* تنظيف اختيارات الموظفين للأقسام التي لم تعد مختارة */
watch(() => state.services.slice(), () => {
  const ids = selCats.value.map(c => c.id)
  Object.keys(state.emp).forEach(k => { if (!ids.includes(k)) delete state.emp[k] })
})

function goBack() {
  if (state.step > 0) {
    state.step--
    scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function goNext() {
  if (!canProceed.value) return
  if (state.step === 4) { doPay(); return }
  state.step++
  scrollTo({ top: 0, behavior: 'smooth' })
}

/* إتمام الدفع — نفس تأخير الأصل */
function doPay() {
  payLoading.value = true
  setTimeout(() => {
    payLoading.value = false
    state.done = true
    state.bookRef = '#SAMI-2026-' + String(Math.floor(10000 + Math.random() * 89999))
    scrollTo({ top: 0, behavior: 'smooth' })
  }, 2100)
}

function goHome() { location.href = '/' }
</script>

<template>
  <div ref="root">
    <div class="shell">
    <BookingStepper />

    <div class="wrap">
      <div class="stage" id="stage" :style="`grid-template-columns:${stageCols}`">
        <main class="panel" id="panel">
          <BookingSuccess v-if="state.done" @home="goHome" @calendar="toast('تمت إضافة الموعد إلى التقويم')" @share="toast('تم نسخ رابط الحجز للمشاركة')" />
          <ServicesStep v-else-if="state.step === 0" />
          <EmployeeStep v-else-if="state.step === 1" />
          <TimeStep v-else-if="state.step === 2" />
          <ConfirmStep v-else-if="state.step === 3" />
          <PayStep v-else />
        </main>
        <aside class="summary booking-summary-compact" :class="{ show: showBookingSummary, 'booking-summary-time': state.step === 2 }" v-show="showBookingSummary"><BookingSummary /></aside>
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

    <div class="footbar" id="footbar" v-show="!state.done">
      <div class="wrap in">
        <button class="btn btn-back" id="btnBack" :style="{ visibility: state.step === 0 ? 'hidden' : 'visible' }" @click="goBack">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          السابق
        </button>
        <div class="fb-mid" id="fbMid">
          <span>🔒 بياناتك محمية وآمنة</span>
          <span v-if="hasSvc" class="tot">المبلغ الإجمالي <b>{{ rs(priceParts.total) }} ر.س</b></span>
        </div>
        <button class="btn" :class="state.step === 4 ? 'btn-pay' : 'btn-gold'" id="btnNext" :disabled="!canProceed" @click="goNext">{{ nextLabel }} <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg></button>
      </div>
    </div>

    <div class="pay-loading" :class="{ on: payLoading }" id="payLoading">
      <div class="pl-box">
        <div class="pl-ring"></div>
        <b>جارٍ معالجة الدفع بأمان…</b>
        <small>لا تغلق الصفحة، سيتم تأكيد حجزك خلال لحظات</small>
      </div>
    </div>

    <div class="toast" :class="{ on: toastOn }" id="toast">{{ toastMsg }}</div>
  </div>
</template>
