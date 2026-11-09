<script setup>
/*
  صفحة الإهداء — مُرحَّلة بالكامل من src/legacy/gifts.html إلى مكوّنات Vue.

  البنية:
    (شريط الخطوات اتشال بناءً على الطلب)
    GiftTypeStep      → view0()
    GiftPickStep      → view2()
    GiftCustomizeStep → view3()
    GiftPayStep       → view4()
    GiftSuccess       → view5()
    GiftSummary       → summary()

  الحالة في composables/useGifts.js، والأنماط تُحقن عبر usePageStyles.
*/
import { ref, computed } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useGifts } from '@/composables/useGifts'
import pageCss from '@/assets/styles/pages/gifts.css?raw'

import SIcon from '@/components/common/SIcon.vue'
import GiftTypeStep from '@/components/gifts/GiftTypeStep.vue'
import GiftPickStep from '@/components/gifts/GiftPickStep.vue'
import GiftCustomizeStep from '@/components/gifts/GiftCustomizeStep.vue'
import GiftPayStep from '@/components/gifts/GiftPayStep.vue'
import GiftSuccess from '@/components/gifts/GiftSuccess.vue'
import GiftSummary from '@/components/gifts/GiftSummary.vue'
import LocationNotice from '@/components/common/LocationNotice.vue'
import GiftStepper from '@/components/gifts/GiftStepper.vue'

const root = ref(null)
const { current, openPicker } = useServiceLocation()
const { state, go, reset } = useGifts()

usePageStyles(pageCss, 'gifts')
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

/* الشاشة الأولى: الهيرو + أقسام التعريف تظهر قبل اختيار النوع */
const isIntro = computed(() => !state.done && state.step === 0 && !state.gtype)

/* اختيار النوع ينتقل فورًا؛ مكان التنفيذ يظهر كملاحظة غير معطِّلة */
function pickType(gt) {
  const continueToPicks = () => {
    state.gtype = gt
    state.pkg = null
    state.svcs = []
    state.activeCat = null
    state.branch = current.value?.id || state.branch
    state.step = 1
    scrollTo({ top: 0, behavior: 'smooth' })
  }
  openPicker(continueToPicks)
}

/* إتمام الدفع — نفس تأخير الأصل (2100ms) */
function doPay() {
  payLoading.value = true
  setTimeout(() => {
    payLoading.value = false
    state.done = true
    state.step = 4
    state.ref = '#SAMI-2026-' + String(Math.floor(10000 + Math.random() * 89999))
    scrollTo({ top: 0, behavior: 'smooth' })
  }, 2100)
}

function recipientURL() {
  return new URL('gift-recipient/', location.href).href + '?ref=' + encodeURIComponent(state.ref || '')
}

const I = {
  gift: '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
  pin:  '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  head: '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>',
}

const WHY = [
  ['هدية رقمية فورية', 'تصلك فورًا عبر البريد الإلكتروني أو الواتساب.', '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>'],
  ['تجربة فاخرة', 'أجواء راقية وخدمات تليق بمن تحب.', '<path d="M12 3l1.9 5.2L19 10l-5.1 1.8L12 17l-1.9-5.2L5 10l5.1-1.8z"/><path d="M18 3v3M19.5 4.5h-3"/>'],
  ['صالحة لمدة 6 أشهر', 'يستخدمها المستلم في الوقت الذي يناسبه.', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
  ['آمنة وموثوقة', 'هدية إلكترونية آمنة تصلك فورًا.', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
]
</script>

<template>
  <div ref="root">
    <div class="shell">
      <div class="wrap" id="giftsApp">

        <!-- الهيرو: الشاشة الأولى فقط -->
        <section v-if="!state.done && state.step === 0" class="gifts-hero">
          <div class="gifts-hero__copy">
            <span class="gifts-hero__ic"><SIcon :inner="I.gift" :size="30" /></span>
            <h1>فاجئ من تحب</h1>
            <p>أهدِ خدمة أو باقة من عناية سامي، وقدِّم تجربة استرخاء متكاملة تجمع بين العناية الفاخرة وأدق التفاصيل. لتكون هدية تترك انطباعًا لا يُنسى.</p>
          </div>
          <div class="gifts-hero__img"><img src="/images/gifts/hero.jpg" alt="هدية عناية سامي" /></div>
        </section>


        <!-- الشاشة الأولى: عرض بعرض كامل + أقسام التعريف -->
        <template v-if="isIntro">
          <div class="view on gift-type-full"><GiftTypeStep @pick="pickType" /></div>

          <div class="container why-gift-row">
            <div class="section-title-sm"><i></i><h2>لماذا تهدي من عناية سامي؟</h2><i></i></div>
            <div class="wg-row">
              <div v-for="(w, i) in WHY" :key="i" class="wg-col"><span class="wi"><SIcon :inner="w[2]" :size="26" /></span><b>{{ w[0] }}</b><small>{{ w[1] }}</small></div>
            </div>
          </div>

          <div class="container">
            <div class="help-bar">
              <div class="help-bar__txt"><b>تحتاج مساعدة؟</b><small>فريقنا جاهز لمساعدتك في اختيار الهدية المناسبة.</small></div>
              <a href="https://wa.me/963959415545" class="btn btn-line">تواصل معنا</a>
              <span class="hi"><SIcon :inner="I.head" :size="22" /></span>
            </div>
          </div>
        </template>

        <!-- شريط خطوات مضغوط لمساري إهداء الخدمة والباقة -->
        <div v-if="state.gtype && (state.step > 0 || state.done)" class="gift-stepper-compact">
          <GiftStepper />
        </div>

        <!-- باقي الخطوات: عمودان مع الملخص -->
        <div v-if="!isIntro" class="g-stage" :class="{ 'g-stage--pick': !state.done && state.step === 1 }">
          <main class="view on">
            <div v-if="!state.done && state.step > 0" class="gift-location-note"><LocationNotice /></div>
            <GiftSuccess v-if="state.done"
              @recipient="location.href = recipientURL()"
              @track="toast('حالة الهدية: تم الإرسال — بانتظار فتح المستلم')"
              @new-gift="reset()"
              @share="toast('تم نسخ رابط الهدية للمشاركة')"
              @home="$router.push('/')"
              @copy-ref="toast('تم نسخ رقم الطلب')" />
            <GiftTypeStep v-else-if="state.step === 0" @pick="pickType" />
            <GiftPickStep v-else-if="state.step === 1" @nav="go" />
            <GiftCustomizeStep v-else-if="state.step === 2" @nav="go" />
            <GiftPayStep v-else-if="state.step === 3" @nav="go" @pay="doPay" />
          </main>
          <aside v-if="state.done || state.step >= 2" id="gsumWrap"><GiftSummary /></aside>
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

    <div class="pay-loading" :class="{ on: payLoading }" id="payLoading">
      <div class="pl-box"><div class="pl-ring"></div><b>جارٍ إتمام الإهداء بأمان…</b><small>سيتم إرسال الهدية مباشرة بعد إتمام الدفع</small></div>
    </div>

    <div class="toast" :class="{ on: toastOn }" id="toast"><span>{{ toastMsg }}</span></div>
  </div>
</template>
