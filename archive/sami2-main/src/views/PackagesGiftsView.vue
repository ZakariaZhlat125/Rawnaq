<script setup>
/*
  صفحة الباقات — مُرحَّلة بالكامل من src/legacy/packages-gifts.html إلى مكوّنات Vue.

  ثلاثة تدفقات:
    packages → PackagesCatalog
    book     → BookStepper + 4 خطوات + BookSuccess + BookSummary
    gift     → GiftStepper + 4 خطوات + GiftSuccess + GiftSummary
*/
import { ref, computed } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { usePackages, rs } from '@/composables/usePackages'
import pageCss from '@/assets/styles/pages/packages-gifts.css?raw'

import SIcon from '@/components/common/SIcon.vue'
import PackagesCatalog from '@/components/packages/PackagesCatalog.vue'
import BookStepper from '@/components/packages/BookStepper.vue'
import BookTimeStep from '@/components/packages/BookTimeStep.vue'
import BookConfirmStep from '@/components/packages/BookConfirmStep.vue'
import BookPayStep from '@/components/packages/BookPayStep.vue'
import BookSuccess from '@/components/packages/BookSuccess.vue'
import BookSummary from '@/components/packages/BookSummary.vue'
import GiftStepper from '@/components/packages/GiftStepper.vue'
import GiftTypeStep from '@/components/packages/GiftTypeStep.vue'
import GiftPickStep from '@/components/packages/GiftPickStep.vue'
import GiftCustomizeStep from '@/components/packages/GiftCustomizeStep.vue'
import GiftPayStep from '@/components/packages/GiftPayStep.vue'
import GiftSuccess from '@/components/packages/GiftSuccess.vue'
import GiftSummary from '@/components/packages/GiftSummary.vue'

const root = ref(null)
const { current, openPicker, requireLocation } = useServiceLocation()
const {
  state, pkgOf,
  gCanNext, gNextLabel, bkCanNext, bkNextLabel,
  startGift, startBook, backToPackages,
} = usePackages()

usePageStyles(pageCss, 'packages-gifts')
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

const ARROW = '<path d="M19 12H5M11 18l-6-6 6-6"/>'

/* ===== شريط التنقل السفلي — نفس منطق renderFoot() ===== */
const isBook = computed(() => state.page === 'book')

const footOn = computed(() => {
  if (isBook.value) return !(state.bk.done || state.bk.step >= 2)
  return state.page === 'gift' && !state.done && state.gstep !== 4
})

const backVisible = computed(() => (isBook.value ? true : state.gstep !== 0))

const footPkg = computed(() => {
  const id = isBook.value ? state.bk.pkg : state.gpkg
  return id ? pkgOf(id) : null
})

const canNext = computed(() => (isBook.value ? bkCanNext.value : gCanNext.value))
const nextLabel = computed(() => (isBook.value ? bkNextLabel.value : gNextLabel.value))

function goBack() {
  if (isBook.value) {
    if (state.bk.step === 0) { backToPackages(); return }
    state.bk.step--
  } else {
    if (state.gstep === 0) { backToPackages(); return }
    state.gstep--
  }
  scrollTo({ top: 0, behavior: 'smooth' })
}

function goNext() {
  if (!canNext.value) return
  if (isBook.value) {
    state.bk.step++
  } else {
    if (state.gstep === 3) { doGiftPay(); return }
    state.gstep++
  }
  scrollTo({ top: 0, behavior: 'smooth' })
}

/* ===== إتمام الدفع — نفس تأخير الأصل ===== */
function doGiftPay() {
  payLoading.value = true
  setTimeout(() => {
    payLoading.value = false
    state.done = true
    state.gstep = 4
    state.ref = '#GIFT-2026-' + String(Math.floor(10000 + Math.random() * 89999))
    scrollTo({ top: 0, behavior: 'smooth' })
  }, 2100)
}

function doBookPay() {
  payLoading.value = true
  setTimeout(() => {
    payLoading.value = false
    state.bk.done = true
    state.bk.ref = '#BK-2026-' + String(Math.floor(10000 + Math.random() * 89999))
    scrollTo({ top: 0, behavior: 'smooth' })
  }, 2100)
}

/* ===== بدء الرحلات: مكان تنفيذ الخدمة أولًا ===== */
/* نافذة المكان تُحدِّث samiSiteBranch، فنزامنها مع حالة الصفحة قبل بدء الرحلة */
function syncBranch() {
  if (current.value) state.siteBranch = current.value.id
}
function onBook(id) { requireLocation(() => { syncBranch(); startBook(id) }) }
function onGift(id) { requireLocation(() => { syncBranch(); startGift('pkg', id) }) }
function onGiftNow() { requireLocation(() => { syncBranch(); startGift(null, null) }) }

function newGift() {
  state.done = false
  state.gstep = 0
  state.gtype = null
  state.gpkg = null
  state.pay = null
  state.ref = null
  scrollTo({ top: 0, behavior: 'smooth' })
}

function goHome() { location.href = '/' }
</script>

<template>
  <div ref="root">
    <div class="shell">
      <div class="wrap" id="pkgApp">

        <!-- كتالوج الباقات -->
        <PackagesCatalog v-if="state.page === 'packages'"
          @book="onBook" @gift="onGift" @gift-now="onGiftNow" @pick-branch="openPicker" />

        <!-- رحلة حجز الباقة -->
        <template v-else-if="state.page === 'book'">
          <BookStepper />
          <div v-if="state.bk.done" class="view on"><BookSuccess @home="goHome" @calendar="toast('تمت إضافة الموعد إلى التقويم')" @share="toast('تم نسخ رابط الحجز للمشاركة')" /></div>
          <div v-else class="g-stage">
            <main class="view on">
              <BookTimeStep v-if="state.bk.step === 0" />
              <BookConfirmStep v-else-if="state.bk.step === 1" />
              <BookPayStep v-else @pay="doBookPay" />
            </main>
            <aside id="gsumWrap"><BookSummary /></aside>
          </div>
        </template>

        <!-- رحلة إهداء الباقة -->
        <template v-else>
          <GiftStepper />
          <div v-if="state.done" class="view on">
            <GiftSuccess
              @recipient="location.href = '/gift-recipient?ref=' + encodeURIComponent(state.ref || '')"
              @new-gift="newGift"
              @copy-self="toast('تم إرسال نسخة من الهدية إلى بريدك')"
              @share="toast('تم نسخ رابط الهدية للمشاركة')"
              @home="goHome" />
          </div>
          <div v-else class="g-stage">
            <main class="view on">
              <GiftTypeStep v-if="state.gstep === 0" />
              <GiftPickStep v-else-if="state.gstep === 1" />
              <GiftCustomizeStep v-else-if="state.gstep === 2" />
              <GiftPayStep v-else />
            </main>
            <aside id="gsumWrap"><GiftSummary /></aside>
          </div>
        </template>

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

    <div class="footbar" :class="{ on: footOn }" id="footbar">
      <div class="wrap in">
        <button class="btn btn-back" id="btnBack" :style="{ visibility: backVisible ? 'visible' : 'hidden' }" @click="goBack">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          السابق
        </button>
        <div class="fb-mid" id="fbMid">
          <template v-if="footPkg">الإجمالي <b>{{ rs(footPkg.price) }} ر.س</b> — 🔒 بياناتك محمية وآمنة</template>
          <template v-else>🔒 بياناتك محمية وآمنة</template>
        </div>
        <button class="btn btn-gold" id="btnNext" :disabled="!canNext" @click="goNext">{{ nextLabel }} <SIcon :inner="ARROW" :size="15" /></button>
      </div>
    </div>

    <div class="pay-loading" :class="{ on: payLoading }" id="payLoading">
      <div class="pl-box"><div class="pl-ring"></div><b>جارٍ إتمام الإهداء بأمان…</b><small>سيتم إرسال الهدية مباشرة بعد إتمام الدفع</small></div>
    </div>

    <div class="toast" :class="{ on: toastOn }" id="toast">{{ toastMsg }}</div>
  </div>
</template>
