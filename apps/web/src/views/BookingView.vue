<script setup>
/*
  صفحة الحجز — مُرحَّلة بالكامل من src/legacy/booking.html إلى مكوّنات Vue.

  خمس خطوات + شاشة نجاح:
    ServicesStep → EmployeeStep → TimeStep → ConfirmStep → PayStep → BookingSuccess
  الملخص الجانبي يظهر عند اختيار خدمة واحدة على الأقل.
*/
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useAuth } from '@/composables/useAuth'
import { useBooking, rs, fmtDate, fmtDur, fmtTimeStr } from '@/composables/useBooking'
import { useStore } from '@/composables/useStore'
import { productCartPayload } from '@/utils/bookingProducts'
import { savePendingReceipt } from '@/utils/bookingReceipt'
import { createBooking, initPayment, syncBookingProducts } from '@/services/bookingApi'
import pageCss from '@/assets/styles/pages/booking.css?raw'

import BookingStepper from '@/components/booking/BookingStepper.vue'
import ServicesStep from '@/components/booking/ServicesStep.vue'
import EmployeeStep from '@/components/booking/EmployeeStep.vue'
import TimeStep from '@/components/booking/TimeStep.vue'
import ConfirmStep from '@/components/booking/ConfirmStep.vue'
import PayStep from '@/components/booking/PayStep.vue'
import BookingSuccess from '@/components/booking/BookingSuccess.vue'
import BookingSummary from '@/components/booking/BookingSummary.vue'

const { state: storeState, clearCart, cartItems } = useStore()
const root = ref(null)
const route = useRoute()
const { current, locations, loadServiceLocations, setLocation } = useServiceLocation()
const { requireAuth, user } = useAuth()

loadServiceLocations()

// When booking is opened from a branch card, prefer that branch over the
// previously saved location. hm is the public URL alias for home service.
watch(
  [() => route.query.branch, locations],
  ([requestedBranch, availableLocations]) => {
    const branchParam = Array.isArray(requestedBranch) ? requestedBranch[0] : requestedBranch
    if (!branchParam || !availableLocations.length) return

    const requestedLocation = branchParam === 'hm' || branchParam === 'home-service'
      ? availableLocations.find(location => location.home)
      : availableLocations.find(location => String(location.id) === String(branchParam))

    if (requestedLocation) setLocation(requestedLocation.id)
  },
  { immediate: true },
)
const { state, selSvcs, totalDur, priceParts, canProceed, nextLabel, reset, payableTotal, walletDiscount, loyaltyPointsUsed } = useBooking()

usePageStyles(pageCss, 'booking')
useInternalLinks(root)

const payLoading = ref(false)
const toastMsg = ref('')
const toastOn = ref(false)
const AMT = 'color:var(--gold-deep);font-family:var(--font-d);font-size:17px'

function toast(msg) {
  toastMsg.value = msg
  toastOn.value = true
  clearTimeout(toast._h)
  toast._h = setTimeout(() => { toastOn.value = false }, 2600)
}

watch(
  () => route.query.payment,
  val => {
    if (!val) return
    if (val === 'failed') {
      toast('فشلت عملية الدفع الإلكتروني، يرجى إعادة المحاولة.')
    } else if (val === 'cancelled' || val === 'cancel') {
      toast('تم إلغاء عملية الدفع.')
    }
  },
  { immediate: true },
)

const hasSvc = computed(() => state.services.length > 0)
const footerTotal = computed(() => state.step === 4 ? payableTotal.value : priceParts.value.total)
function decodeReceipt(code) {
  if (!code) return null
  try {
    const normalized = String(code).replace(/-/g, '+').replace(/_/g, '/')
    const padded = normalized.padEnd(normalized.length + ((4 - normalized.length % 4) % 4), '=')
    const data = JSON.parse(decodeURIComponent(escape(atob(padded))))
    return data && typeof data === 'object' ? data : null
  } catch {
    return null
  }
}
const receipt = computed(() => decodeReceipt(route.query.receipt))
const isReceiptMode = computed(() => !!receipt.value)
const receiptServices = computed(() => Array.isArray(receipt.value?.s) ? receipt.value.s : [])

/* يظهر الملخص بمجرد اختيار أول خدمة ويستمر حتى الدفع. */
const showBookingSummary = computed(() => !isReceiptMode.value && !state.done && hasSvc.value)
const stageCols = computed(() => (showBookingSummary.value ? 'minmax(0,1fr) minmax(285px,315px)' : '1fr'))

function goBack() {
  if (state.step > 0) {
    state.step--
    scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function goNext() {
  if (!canProceed.value) return
  if (state.step === 4) { requireAuth(doPay); return }
  state.step++
  scrollTo({ top: 0, behavior: 'smooth' })
}

function toDateKey(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

/* إنشاء الحجز فعليًا في الباك إند ثم الدفع */
async function doPay() {
  if (payLoading.value || !canProceed.value) return
  payLoading.value = true
  try {
    const accountName = [user.value?.first_name, user.value?.last_name].filter(Boolean).join(' ')
    state.cust.name = state.cust.name || accountName || user.value?.username || ''
    state.cust.phone = state.cust.phone || user.value?.mobile || ''
    state.cust.mail = state.cust.mail || user.value?.email || ''
    const branchId = current.value?.home ? 0 : Number(current.value?.id)

    if (state.pay === 'cod') {
      const requiredDeposit = Math.round(priceParts.value.total * 0.3)
      if ((Number(state.walletBalance) || 0) < requiredDeposit) {
        toast(`عذرًا، الدفع عند الوصول يتطلب توفر عربون 30% (${rs(requiredDeposit)} ر.س) في محفظتك`)
        return
      }
    }

    const services = selSvcs.value.map(s => ({
      subServices: [{
        id: s.id,
        ...(s.repeatRewardId ? { repeat_reward_id: s.repeatRewardId } : {}),
        date: toDateKey(state.date),
        time: state.time[s.id],
        duration: s.dur,
        staffId: state.emp[s.id]?.id,
      }],
    }))

    await syncBookingProducts(productCartPayload(storeState.cart))

    await createBooking({
      branch: branchId,
      ...(branchId === 0 ? { city_id: state.homeCityId, latitude: state.homeLatitude, longitude: state.homeLongitude } : {}),
      services,
      customerName: state.cust.name,
      mobileNo: state.cust.phone,
    })

    const rewards = state.rewards
    const walletAmount = Number(walletDiscount.value.toFixed(2))
    const loyaltyPoints = loyaltyPointsUsed.value
    const hasWallet = walletAmount > 0
    const hasLoyalty = loyaltyPoints > 0
    const couponCode = rewards.couponApplied || ''

    /* عند اختيار cod يمر كعربون محفظة مستقل، أما عند اختيار أي بوابة أخرى (مثل urpay أو card)
       فتُخصم المكافآت أولاً ويُحصّل المتبقي عبر البوابة المختارة نفسها */
    const gateway = payableTotal.value <= 0 ? 'card' : state.pay === 'cod'
      ? 'cod'
      : (state.pay || 'card')

    const payment = await initPayment(gateway, {
      wallet: hasWallet,
      walletAmount,
      loyalty: hasLoyalty,
      loyaltyPoints,
      couponCode,
    })
    if (payment.payment_url) {
      savePendingReceipt({
        b: current.value?.name || '', d: fmtDate(state.date), u: fmtDur(totalDur.value),
        e: [...new Set(selSvcs.value.map(s => state.emp[s.id]?.name).filter(Boolean))].join('، '),
        p: priceParts.value.total,
        s: selSvcs.value.map(s => [s.name, fmtTimeStr(state.time[s.id]), state.emp[s.id]?.name || '', s.price]),
      }, payment.attempt_id)
      window.location.href = payment.payment_url
      return
    }
    state.purchasedProducts = cartItems.value.map(item => ({ ...item }))
    state.bookRef = payment.invoice_id || null
    state.done = true
    clearCart()
    scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e) {
    toast(e.message || 'تعذّر إتمام الحجز، حاول مرة أخرى')
  } finally {
    payLoading.value = false
  }
}

function goHome() { reset(); location.href = '/' }
</script>

<template>
  <div ref="root">
    <div class="shell">
      <BookingStepper v-if="!isReceiptMode" />

      <div class="wrap">
        <div class="stage" id="stage" :class="{ 'stage-services-summary': state.step === 0 && showBookingSummary }" :style="`grid-template-columns:${stageCols}`">
          <main class="panel" id="panel">
            <div v-if="isReceiptMode" class="success-wrap receipt-wrap">
              <div class="suc-ic">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                  <path d="M20 6L9 17l-5-5" />
                </svg>
              </div>
              <h1>تفاصيل الحجز</h1>
              <p class="sub">تم فتح تفاصيل الحجز من رمز QR</p>
              <div class="suc-grid receipt-grid">
                <div class="card suc-details">
                  <h4>بيانات الحجز</h4>
                  <div class="sd-row"><span class="k">رقم الفاتورة</span><span class="v receipt-code">{{ receipt.r ||
                      '—' }}</span></div>
                  <div class="sd-row"><span class="k">الفرع</span><span class="v">{{ receipt.b || '—' }}</span></div>
                  <div class="sd-row"><span class="k">التاريخ</span><span class="v">{{ receipt.d || '—' }}</span></div>
                  <div class="sd-row"><span class="k">مدة الجلسة</span><span class="v">{{ receipt.u || '—' }}</span>
                  </div>
                  <div class="sd-row"><span class="k">الفريق</span><span class="v">{{ receipt.e || '—' }}</span></div>
                  <div class="sd-row"><span class="k">المبلغ</span><span class="v" :style="AMT">{{ rs(Number(receipt.p)
                      || 0) }} ر.س</span></div>
                </div>
                <div class="card suc-details receipt-services">
                  <h4>الخدمات</h4>
                  <div v-for="(svc, i) in receiptServices" :key="i" class="receipt-service">
                    <b>{{ svc[0] }}</b>
                    <small>{{ svc[1] || '—' }} · {{ svc[2] || receipt.e || '—' }}</small>
                    <span>{{ rs(Number(svc[3]) || 0) }} ر.س</span>
                  </div>
                </div>
              </div>
            </div>
            <BookingSuccess v-else-if="state.done" @home="goHome" @calendar="toast('تمت إضافة الموعد إلى التقويم')"
              @share="toast('تم نسخ رابط الحجز للمشاركة')" />
            <ServicesStep v-else-if="state.step === 0" />
            <EmployeeStep v-else-if="state.step === 1" />
            <TimeStep v-else-if="state.step === 2" />
            <ConfirmStep v-else-if="state.step === 3" />
            <PayStep v-else />
          </main>
          <aside class="summary booking-summary-compact"
            :class="{ show: showBookingSummary, 'booking-summary-time': state.step === 2 }" v-show="showBookingSummary">
            <BookingSummary />
          </aside>
        </div>
      </div>
    </div>

    <!-- فوتر صفحة الحجز مخفي مؤقتًا؛ غيّر الشرط إلى true لإعادته. -->
    <footer v-if="false">
      <div class="wrap">
        <div class="f-grid">
          <div class="f-brand">
            <RouterLink class="logo" to="/"><span class="mark"><img src="/logo.png" alt="رونق"
                  style="width:29px;height:29px;object-fit:contain" /></span><span class="name"><b>عناية
                  رونق</b><span>RAWNAQ</span></span></RouterLink>
            <p>مركز متخصص في العناية الرجالية المتكاملة بجدة، حيث تلتقي الفخامة بالاحترافية في كل تفصيلة.</p>
            <div class="socials">
              <a href="https://x.com/samicare_sa" aria-label="X"><svg width="17" height="17" viewBox="0 0 24 24"
                  fill="currentColor">
                  <path
                    d="M18.9 2H22l-6.8 7.8L23.3 22h-6.3l-4.9-6.4L6.5 22H3.4l7.3-8.3L1 2h6.5l4.4 5.8L18.9 2zm-1.1 18h1.7L7.1 3.9H5.3L17.8 20z" />
                </svg></a>
              <a href="https://www.instagram.com/samicare.sa/" aria-label="انستقرام"><svg width="17" height="17"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <rect x="2" y="2" width="20" height="20" rx="5" />
                  <circle cx="12" cy="12" r="4" />
                  <circle cx="17.5" cy="6.5" r="1" fill="currentColor" />
                </svg></a>
              <a href="https://www.facebook.com/samicare.sa" aria-label="فيسبوك"><svg width="17" height="17"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                </svg></a>
            </div>
          </div>
          <div>
            <h4>روابط مهمة</h4>
            <ul class="f-links">
              <li>
                <RouterLink to="/">الرئيسية</RouterLink>
              </li>
              <li>
                <RouterLink to="/booking">حجز موعد</RouterLink>
              </li>
              <li>
                <RouterLink to="/#services">خدماتنا</RouterLink>
              </li>
              <li>
                <RouterLink to="/packages-gifts">الباقات</RouterLink>
              </li>
              <li>
                <RouterLink to="/gifts">الهدايا</RouterLink>
              </li>
            </ul>
          </div>
          <div>
            <h4>استكشف</h4>
            <ul class="f-links">
              <li>
                <RouterLink to="/store">المتجر</RouterLink>
              </li>
              <li>
                <RouterLink to="/branches">فروعنا</RouterLink>
              </li>
              <li>
                <RouterLink to="/contact">تواصل معنا</RouterLink>
              </li>
              <li>
                <RouterLink to="/terms">الشروط والأحكام</RouterLink>
              </li>
              <li>
                <RouterLink to="/privacy-policy">سياسة الخصوصية</RouterLink>
              </li>
            </ul>
          </div>
          <div>
            <h4>عناوين الفروع</h4>
            <div v-for="branch in locations" :key="branch.id" class="f-branch">
              <b>{{ branch.name }}</b>
              <small>{{ branch.address }}</small>
              <a v-if="branch.contact_number" :href="`tel:${branch.contact_number}`">{{ branch.contact_number }}</a>
            </div>
          </div>
        </div>
        <div class="f-bottom">
          <small>© 2026 رونق — جميع الحقوق محفوظة</small>
          <div class="pay" aria-label="بوابات الدفع"><span title="Visa">VISA</span><span
              title="Mastercard">Mastercard</span><span title="مدى">mada</span><span title="Tabby">tabby</span><span
              title="Apple Pay">Pay</span></div>
        </div>
      </div>
    </footer>

    <div class="footbar" id="footbar" v-show="!state.done && !isReceiptMode">
      <div class="wrap in">
        <button class="btn btn-back" id="btnBack" :style="{ visibility: state.step === 0 ? 'hidden' : 'visible' }"
          @click="goBack">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14M13 6l6 6-6 6" />
          </svg>
          السابق
        </button>
        <div class="fb-mid" id="fbMid">
          <span>🔒 بياناتك محمية وآمنة</span>
          <span v-if="hasSvc" class="tot">المبلغ الإجمالي <b>{{ rs(Math.round(footerTotal)) }} ر.س</b></span>
        </div>
        <button class="btn" :class="state.step === 4 ? 'btn-pay' : 'btn-gold'" id="btnNext" :disabled="!canProceed"
          @click="goNext">{{ nextLabel }} <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.2">
            <path d="M19 12H5M11 18l-6-6 6-6" />
          </svg></button>
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
