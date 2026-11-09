<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { paymentPolicy } from '@/utils/paymentPolicy'
import PaymentMethodList from '@/components/common/PaymentMethodList.vue'
import GiftCodeRedeem from '@/components/gifts/GiftCodeRedeem.vue'
import { useBooking, rs } from '@/composables/useBooking'
import { fetchCheckoutBalances } from '@/services/accountApi'
import { useAuth } from '@/composables/useAuth'
import { fetchLoyaltyPointValue, validateInvoiceCoupon } from '@/services/bookingApi'

const {
  state,
  priceParts,
  selSvcs,
  pointValue,
  couponDiscount,
  walletDiscount,
  loyaltyPointsUsed,
  loyaltyDiscount,
  payableTotal,
} = useBooking()

const couponLoading = ref(false)
let couponRequest = 0

const { token } = useAuth()
const balancesLoading = ref(false)
const balancesError = ref(false)
let balancesRequest = 0
async function loadBalances() {
  const request = ++balancesRequest
  state.walletBalance = null
  state.loyaltyPoints = null
  balancesError.value = false
  balancesLoading.value = !!token.value
  if (!token.value) return
  try {
    const balances = await fetchCheckoutBalances()
    if (request !== balancesRequest) return
    state.walletBalance = balances.wallet
    state.loyaltyPoints = balances.loyalty_points
  } catch {
    if (request === balancesRequest) balancesError.value = true
  } finally {
    if (request === balancesRequest) balancesLoading.value = false
  }
}
watch(token, loadBalances, { immediate: true })
onBeforeUnmount(() => { balancesRequest++ })

/* كود بطاقة هدية بقيمة: يضاف للمحفظة، فنحدّث الرصيد ونفعّل "استخدام المحفظة" مباشرة */
async function onGiftRedeemed(gift) {
  await loadBalances()
  if (gift?.type === 'value' && !state.rewards.useWallet) toggleWallet()
}

onMounted(async () => {

  try {
    const res = await fetchLoyaltyPointValue()
    state.pointValue = Number(res?.data?.point_value ?? 0.5) || 0.5
  } catch { /* القيمة الافتراضية للنقطة تكفي لو فشل التحميل */ }
})

const walletBalance = computed(() => Math.max(Number(state.walletBalance) || 0, 0))
const loyaltyBalance = computed(() => Math.max(parseInt(state.loyaltyPoints, 10) || 0, 0))
const walletMax = computed(() => Math.max(priceParts.value.total - couponDiscount.value, 0))
const walletApplied = computed(() => Math.min(walletDiscount.value, walletMax.value))
const loyaltyRemaining = computed(() => Math.max(priceParts.value.total - couponDiscount.value - walletApplied.value, 0))
const loyaltyMaxPoints = computed(() => Math.min(loyaltyBalance.value, Math.ceil(loyaltyRemaining.value / pointValue.value)))
const hasSubRewards = computed(() => walletApplied.value > 0 || loyaltyPointsUsed.value > 0)
const codRequiredDeposit = computed(() => Math.round((priceParts.value.total * 0.3 + Number.EPSILON) * 100) / 100)
const hasCodDeposit = computed(() => walletBalance.value >= codRequiredDeposit.value)

const PAY_METHODS = computed(() => paymentPolicy.methods({ total: priceParts.value.total, walletBalance: walletBalance.value, payable: payableTotal.value, hasRewards: hasSubRewards.value }))

watch(PAY_METHODS, methods => {
  if (state.pay && !methods.some(method => method.id === state.pay && method.enabled)) state.pay = null
}, { immediate: true })

watch([hasCodDeposit, hasSubRewards, payableTotal], () => {
  if (hasSubRewards.value && state.pay === 'cod') {
    state.pay = payableTotal.value > 0 ? 'card' : null
  } else if (!hasCodDeposit.value && state.pay === 'cod') {
    state.pay = null
  }
})

const TRUST = [
  ['ضمان الجودة', 'نضمن لك أفضل تجربة', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'],
  ['دفع آمن', 'تقنيات تشفير متقدمة', '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
  ['حماية البيانات', 'بياناتك محمية بالكامل', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
  ['دعم فوري', 'فريق دعم جاهز لخدمتك', '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>'],
]

const ICONS = {
  wallet: '<path d="M18 8V7a2 2 0 00-2-2H5a2 2 0 000 4h14a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2V7"/><path d="M16 13h.01"/>',
  gift: '<path d="M20 12v8H4v-8"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 110-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 100-5C13 2 12 7 12 7z"/>',
  coupon: '<path d="M4 7a2 2 0 012-2h12a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3a2 2 0 000-4z"/><path d="M9 9h.01M15 15h.01M16 8l-8 8"/>',
}

function selectMethod(m) {
  if (!m.enabled) return
  state.pay = m.id
}

function clampMoney(value, max) {
  const num = Number(value)
  if (!Number.isFinite(num)) return 0
  return Math.max(0, Math.min(num, max))
}

function toggleWallet() {
  if (state.walletBalance === null) return
  state.rewards.useWallet = !state.rewards.useWallet
  if (state.rewards.useWallet) {
    state.rewards.walletAmount = clampMoney(state.rewards.walletAmount || walletMax.value, Math.min(walletBalance.value, walletMax.value))
  } else {
    state.rewards.walletAmount = 0
  }
}

function setWalletAmount(value) {
  state.rewards.walletAmount = clampMoney(value, Math.min(walletBalance.value, walletMax.value))
  state.rewards.useWallet = state.rewards.walletAmount > 0
}

function toggleLoyalty() {
  if (state.loyaltyPoints === null) return
  state.rewards.useLoyalty = !state.rewards.useLoyalty
  if (state.rewards.useLoyalty) {
    state.rewards.loyaltyPoints = Math.min(
      state.rewards.loyaltyPoints || loyaltyMaxPoints.value,
      loyaltyMaxPoints.value,
    )
  } else {
    state.rewards.loyaltyPoints = 0
  }
}

function setLoyaltyPoints(value) {
  const points = Math.max(0, Math.min(parseInt(value, 10) || 0, loyaltyMaxPoints.value))
  state.rewards.loyaltyPoints = points
  state.rewards.useLoyalty = points > 0
}

async function applyCoupon() {
  const code = state.rewards.couponCode.trim()
  if (!code) {
    state.rewards.couponStatus = 'bad'
    state.rewards.couponMessage = 'أدخل كود الخصم أولًا'
    return
  }

  couponLoading.value = true
  const request = ++couponRequest
  try {
    const result = await validateInvoiceCoupon(code, selSvcs.value.map(service => service.id))
    if (request !== couponRequest || state.rewards.couponCode.trim() !== code) return
    if (!result?.valid) {
      state.rewards.couponApplied = ''
      state.rewards.couponDiscount = 0
      state.rewards.couponStatus = 'bad'
      state.rewards.couponMessage = result?.message || 'كود الخصم غير صالح'
      return
    }
    const discount = Math.max(0, Number(result.effective_discount) || 0)
    state.rewards.couponCode = code
    state.rewards.couponApplied = code
    state.rewards.couponDiscount = discount
    state.rewards.couponStatus = 'ok'
    state.rewards.couponMessage = `تم تطبيق خصم ${rs(Math.round(discount))} ر.س`
  } catch (e) {
    state.rewards.couponApplied = ''
    state.rewards.couponDiscount = 0
    state.rewards.couponStatus = 'bad'
    state.rewards.couponMessage = e.message || 'تعذّر التحقق من الكوبون'
  } finally {
    couponLoading.value = false
  }
}

function clearCoupon() {
  couponRequest++
  state.rewards.couponApplied = ''
  state.rewards.couponDiscount = 0
  state.rewards.couponStatus = ''
  state.rewards.couponMessage = ''
}

watch(() => selSvcs.value.map(s => `${s.id}:${s.price}`).join('|'), clearCoupon)

watch([walletBalance, walletMax], () => {
  if (state.rewards.useWallet) setWalletAmount(state.rewards.walletAmount)
})

watch(loyaltyMaxPoints, () => {
  if (state.rewards.useLoyalty) setLoyaltyPoints(state.rewards.loyaltyPoints)
})
</script>

<template>
  <div class="panel-head"><h1>الدفع</h1><p>اختر طريقة الدفع المناسبة وأكمل حجزك</p></div>

  <div class="card detail-card">
    <h4>اختر طريقة الدفع</h4>
    <PaymentMethodList v-model="state.pay" :methods="PAY_METHODS" />
    <div v-if="state.walletBalance !== null && !hasCodDeposit && !hasSubRewards" class="cod-deposit-alert">
      💡 <b>ملاحظة الدفع عند الوصول:</b> يتطلب توفر عربون بنسبة 30% ({{ rs(codRequiredDeposit) }} ر.س) في رصيد محفظتك لتأكيد الحجز. رصيدك الحالي: {{ rs(walletBalance) }} ر.س.
    </div>
    <div class="secure-line">🔒 جميع عمليات الدفع آمنة ومشفرة</div>
  </div>

  <div class="card detail-card payment-rewards-card">
    <h4><span class="step-dot">2</span> استخدام العروض و المكافآت</h4>
    <p v-if="balancesLoading" role="status">جارٍ تحميل رصيد المحفظة ونقاط الولاء...</p>
    <p v-else-if="balancesError" role="alert">تعذر تحميل الرصيد. <button type="button" @click="loadBalances">إعادة المحاولة</button></p>

    <div class="rewards">
      <div class="gift-code-box"><GiftCodeRedeem compact @redeemed="onGiftRedeemed" /></div>
      <div class="rw" :class="{ on: state.rewards.useWallet }">
        <div class="rt">
          <span class="ri"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="ICONS.wallet"></svg></span>
          <span>
            <b>استخدام من المحفظة</b>
            <small>الرصيد الحالي: {{ state.walletBalance === null ? '—' : rs(walletBalance) + ' ر.س' }}</small>
          </span>
          <span class="switch" :class="{ on: state.rewards.useWallet }" @click="toggleWallet"></span>
        </div>
        <input
          type="number"
          min="0"
          :max="Math.min(walletBalance, walletMax)"
          step="1"
          :value="state.rewards.walletAmount"
          :disabled="!state.rewards.useWallet"
          @input="setWalletAmount($event.target.value)"
        />
        <span v-if="state.walletBalance !== null" class="hint">المبلغ المتاح حتى {{ rs(Math.round(Math.min(walletBalance, walletMax))) }} ر.س</span>
      </div>

      <div class="rw" :class="{ on: state.rewards.useLoyalty }">
        <div class="rt">
          <span class="ri"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="ICONS.gift"></svg></span>
          <span>
            <b>استخدام نقاط الولاء</b>
            <small>لديك {{ state.loyaltyPoints === null ? '—' : rs(loyaltyBalance) }} نقطة</small>
          </span>
          <span class="switch" :class="{ on: state.rewards.useLoyalty }" @click="toggleLoyalty"></span>
        </div>
        <input
          type="number"
          min="0"
          :max="loyaltyMaxPoints"
          step="1"
          :value="state.rewards.loyaltyPoints"
          :disabled="!state.rewards.useLoyalty"
          @input="setLoyaltyPoints($event.target.value)"
        />
        <span class="hint">تساوي خصم {{ rs(Math.round(loyaltyPointsUsed * pointValue)) }} ر.س</span>
      </div>
    </div>

    <div class="coupon">
      <span class="ci"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="ICONS.coupon"></svg></span>
      <input
        v-model="state.rewards.couponCode"
        placeholder="أدخل كود الخصم"
        @input="state.rewards.couponApplied && state.rewards.couponCode !== state.rewards.couponApplied ? clearCoupon() : null"
        @keydown.enter.prevent="applyCoupon"
      />
      <button v-if="state.rewards.couponApplied" type="button" class="coupon-clear" @click="clearCoupon">إلغاء</button>
      <button type="button" @click="applyCoupon" :disabled="couponLoading">{{ couponLoading ? '...' : 'تطبيق' }}</button>
    </div>
    <p v-if="state.rewards.couponMessage" class="coupon-msg" :class="state.rewards.couponStatus">{{ state.rewards.couponMessage }}</p>

    <div class="reward-total">
      <span>المبلغ بعد المكافآت</span>
      <b>{{ rs(Math.round(payableTotal)) }} ر.س</b>
      <small v-if="couponDiscount || walletApplied || loyaltyDiscount">
        تم خصم {{ rs(Math.round(couponDiscount + walletApplied + loyaltyDiscount)) }} ر.س
      </small>
    </div>
  </div>

  <div class="trust-row">
    <div v-for="(t, i) in TRUST" :key="i" class="trust"><span class="ti"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" v-html="t[2]"></svg></span><b>{{ t[0] }}</b><small>{{ t[1] }}</small></div>
  </div>
</template>

<style scoped>
.pm.disabled { opacity: .45; cursor: not-allowed; }
.cod-deposit-alert {
  margin-top: 14px;
  padding: 11px 15px;
  background: #fff9ed;
  border: 1px solid #f6deb4;
  border-radius: 10px;
  font-size: 12.5px;
  color: #8c5b16;
  line-height: 1.6;
}
.gift-code-box { margin-bottom: 10px; }
</style>
