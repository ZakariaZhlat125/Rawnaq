<script setup>
import { computed, onMounted, watch } from 'vue'
import PaymentMethodList from './PaymentMethodList.vue'
import { fetchProfile } from '@/services/accountApi'
import { fetchLoyaltyPointValue } from '@/services/bookingApi'
import { paymentPolicy } from '@/utils/paymentPolicy'

const props = defineProps({ state: { type: Object, required: true }, total: { type: Number, required: true } })
const parts = computed(() => paymentPolicy.rewards(props.state, props.total))
const methods = computed(() => paymentPolicy.options(props.state, props.total))
const rs = value => Number(value || 0).toLocaleString('ar-SA', { maximumFractionDigits: 2 })
watch(methods, () => {
  if (props.state.pay && !methods.value.some(m => m.id === props.state.pay && m.enabled)) props.state.pay = null
}, { immediate: true })
onMounted(async () => {
  await Promise.allSettled([
    fetchProfile().then(res => {
      props.state.walletBalance = Number(res?.data?.balances?.wallet || 0)
      props.state.loyaltyPoints = Number(res?.data?.balances?.loyalty_points || 0)
    }),
    fetchLoyaltyPointValue().then(res => { props.state.pointValue = Number(res?.data?.point_value) || 0.5 }),
  ])
})
function toggleWallet(checked) {
  props.state.useWallet = checked
  props.state.walletAmount = checked ? Math.min(Number(props.state.walletBalance) || 0, props.total) : 0
}
function toggleLoyalty(checked) {
  props.state.useLoyalty = checked
  props.state.loyaltyPointsUsed = checked ? parts.value.maxPoints : 0
}
</script>

<template>
  <div class="checkout-payment-options">
    <h4>اختر طريقة الدفع</h4>
    <PaymentMethodList v-model="state.pay" :methods="methods" />
    <section class="checkout-rewards">
      <h4>استخدام المكافآت ونقاط الولاء</h4>
      <label><span>استخدام من المحفظة <small>الرصيد الحالي: {{ rs(state.walletBalance) }} ر.س</small></span><input type="checkbox" :checked="!!state.useWallet" @change="toggleWallet($event.target.checked)"></label>
      <input v-if="state.useWallet" type="number" aria-label="مبلغ المحفظة" min="0" step="0.01" :max="Math.min(Number(state.walletBalance) || 0, total)" :value="parts.wallet" @input="state.walletAmount = Math.min(Math.max(Number($event.target.value) || 0, 0), Number(state.walletBalance) || 0, total)">
      <label><span>استخدام نقاط الولاء <small>لديك {{ rs(state.loyaltyPoints) }} نقطة</small></span><input type="checkbox" :checked="!!state.useLoyalty" @change="toggleLoyalty($event.target.checked)"></label>
      <input v-if="state.useLoyalty" type="number" aria-label="نقاط الولاء المستخدمة" min="0" step="1" :max="parts.maxPoints" :value="parts.points" @input="state.loyaltyPointsUsed = Math.min(Math.max(parseInt($event.target.value, 10) || 0, 0), parts.maxPoints)">
      <div class="payment-total"><span>المتبقي للدفع شامل الضريبة</span><b>{{ rs(parts.payable) }} ر.س</b></div>
    </section>
  </div>
</template>

<style scoped>
.checkout-payment-options h4{margin:0 0 14px;font-size:16px}.payment-options{display:grid;gap:10px}.payment-option{display:flex;align-items:center;gap:12px;width:100%;padding:15px;border:1px solid #e4d7c7;border-radius:14px;background:#fff;color:#2a2519;text-align:start;font:inherit;cursor:pointer}.payment-option.selected{border-color:#1F9E8F;background:#fff8ea}.payment-option:disabled{opacity:.45;cursor:not-allowed}.payment-logo{min-width:54px;font-weight:800;text-align:center}.payment-copy{display:grid;gap:4px}.payment-copy small,.checkout-rewards small{font-size:12px;color:#7d745f}.payment-radio{margin-inline-start:auto;color:#1F9E8F}.checkout-rewards{margin-top:18px;padding:16px;background:#fffaf0;border:1px solid #e4d7c7;border-radius:14px}.checkout-rewards label{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0}.checkout-rewards label span{display:grid;gap:4px}.checkout-rewards input[type=number]{width:100%;padding:10px;border:1px solid #e4d7c7;border-radius:10px;font:inherit}.payment-total{display:flex;justify-content:space-between;gap:12px;margin-top:16px;padding-top:12px;border-top:1px dashed #1F9E8F}
</style>
