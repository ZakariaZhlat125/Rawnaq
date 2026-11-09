<script setup>
import { computed } from 'vue'
import { usePackages, fmtTime, fmtDate, rs } from '@/composables/usePackages'
import CheckoutPaymentOptions from '@/components/common/CheckoutPaymentOptions.vue'
import { paymentPolicy } from '@/utils/paymentPolicy'
const { state, pkgOf, bkDays } = usePackages()
const emit = defineEmits(['pay'])
const B = state.bk
const p = computed(() => pkgOf(B.pkg))
const d = computed(() => bkDays()[B.dayIdx])
const total = computed(() => { const subtotal = Math.max(Number(p.value?.price) || 0, 0); return subtotal + Math.round(subtotal * 0.15) })
const payable = computed(() => paymentPolicy.rewards(B, total.value).payable)
const canPay = computed(() => paymentPolicy.canPay(B, total.value))
</script>
<template>
  <div class="g-head"><h1>اختر طريقة الدفع</h1><p>ادفع بأمان وسهولة</p></div>
  <div class="card pay-summary-strip"><b>{{ p.name }}</b><small>{{ p.branchName }} — {{ fmtDate(d) }} • {{ fmtTime(B.time) }}</small><span class="amt">{{ rs(total) }} ر.س</span></div>
  <div class="card" style="padding:20px">
    <CheckoutPaymentOptions :state="B" :total="total" />
    <button class="btn-paynow" :disabled="!canPay" @click="emit('pay')">ادفع الآن — {{ rs(payable) }} ر.س</button>
  </div>
</template>
