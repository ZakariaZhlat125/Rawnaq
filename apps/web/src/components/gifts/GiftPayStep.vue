<script setup>
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'
import CheckoutPaymentOptions from '@/components/common/CheckoutPaymentOptions.vue'
const { state, canNext, priceParts } = useGifts()
const emit = defineEmits(['nav', 'pay'])
const I = { check:'<path d="M20 6L9 17l-5-5"/>', prev:'<path d="M5 12h14M13 6l6 6-6 6"/>', lock:'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>' }
</script>
<template>
  <div class="g-head"><h1>الدفع</h1><p>اختر طريقة الدفع المناسبة وأكمل إهداءك</p></div>
  <div class="card" style="padding:22px">
    <CheckoutPaymentOptions :state="state" :total="priceParts.total" />
    <span class="terms-chk" :class="{ on: state.terms }" id="termsChk" @click="state.terms = !state.terms"><i><SIcon :inner="I.check" :size="11" /></i>
      أوافق على <RouterLink to="/terms" @click.stop>الشروط والأحكام</RouterLink> و<RouterLink to="/privacy-policy" @click.stop>سياسة الخصوصية</RouterLink></span>
    <div class="inline-actions">
      <button class="btn btn-prev" data-nav="back" @click="emit('nav', -1)"><SIcon :inner="I.prev" :size="15" /> الرجوع</button>
      <button class="btn btn-gold" id="doPay" :disabled="!canNext" @click="emit('pay')"><SIcon :inner="I.lock" :size="15" /> إتمام الإهداء</button>
    </div>
    <div class="after-pay">سيتم إرسال الهدية مباشرة بعد إتمام الدفع</div>
  </div>
</template>
