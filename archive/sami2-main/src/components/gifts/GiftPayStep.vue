<script setup>
/*
  الدفع — مُرحَّل حرفيًا من view4() في src/legacy/gifts.html
  يشمل: وسائل الدفع الأساسية · الخيارات الإضافية القابلة للطي · كود الخصم · الشروط
*/
import { computed } from 'vue'
import { PAY_MAIN, PAY_FLEX, PAY_BAL } from '@/data/gifts'
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'
import PayMethod from './PayMethod.vue'

const COUPON_VAL = 25

const { state, canNext } = useGifts()
const emit = defineEmits(['nav', 'pay'])

const collapsed = computed(() => state.pay === 'apple' && state.extraCollapsed)

const I = {
  card:  '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  wallet:'<path d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 000 4h4v-4z"/>',
  chevD: '<path d="M6 9l6 6 6-6"/>',
  check: '<path d="M20 6L9 17l-5-5"/>',
  prev:  '<path d="M5 12h14M13 6l6 6-6 6"/>',
  lock:  '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
}

/* تطبيق كود الخصم — نفس منطق الأصل */
function applyCoupon() {
  const code = state.couponIn.trim()
  if (!code) return
  state.couponCode = code
  state.coupon = COUPON_VAL
}
</script>

<template>
  <div class="g-head"><h1>الدفع</h1><p>اختر طريقة الدفع المناسبة وأكمل إهداءك</p></div>
  <div class="card" style="padding:22px">
    <div class="pay-group">
      <b><span class="gi"><SIcon :inner="I.card" :size="14" /></span> وسائل الدفع الأساسية</b>
      <PayMethod v-for="m in PAY_MAIN" :key="m.id" :m="m" :has-form="!!m.form" />
    </div>
    <div class="collapse-hint" :class="{ on: collapsed }" id="expandPays" @click="state.extraCollapsed = false"><SIcon :inner="I.chevD" :size="14" /> عرض خيارات الدفع الإضافية (الدفع المرن، المحفظة والولاء)</div>
    <div class="pay-collapse" :class="{ hid: collapsed }" id="extraPays">
      <div class="pay-group">
        <b><span class="gi"><SIcon :inner="I.clock" :size="14" /></span> الدفع المرن</b>
        <PayMethod v-for="m in PAY_FLEX" :key="m.id" :m="m" />
      </div>
      <div class="pay-group" style="margin-bottom:4px">
        <b><span class="gi"><SIcon :inner="I.wallet" :size="14" /></span> المحفظة والولاء</b>
        <PayMethod v-for="m in PAY_BAL" :key="m.id" :m="m" />
      </div>
    </div>
    <div class="coupon" style="border:none;padding:16px 0 0;margin-top:6px;border-top:1px dashed var(--line);border-radius:0">
      <span class="ci">%</span>
      <input id="cpnInput" placeholder="أدخل كود الخصم" v-model="state.couponIn" />
      <button id="cpnBtn" @click="applyCoupon">تطبيق</button>
    </div>
    <div class="coupon-msg" :class="{ ok: state.couponCode }" id="cpnMsg">{{ state.couponCode ? '✓ تم تطبيق كود الخصم بنجاح — خصم ' + COUPON_VAL + ' ر.س' : '' }}</div>
    <span class="terms-chk" :class="{ on: state.terms }" id="termsChk" @click="state.terms = !state.terms"><i><SIcon :inner="I.check" :size="11" /></i>
      أوافق على <a href="https://sami-care.sa/TermsAndConditions">الشروط والأحكام وسياسة الخصوصية</a></span>
    <div class="inline-actions">
      <button class="btn btn-prev" data-nav="back" @click="emit('nav', -1)"><SIcon :inner="I.prev" :size="15" /> الرجوع</button>
      <button class="btn btn-gold" id="doPay" :disabled="!canNext" @click="emit('pay')"><SIcon :inner="I.lock" :size="15" /> إتمام الإهداء</button>
    </div>
    <div class="after-pay">سيتم إرسال الهدية مباشرة بعد إتمام الدفع</div>
  </div>
</template>
