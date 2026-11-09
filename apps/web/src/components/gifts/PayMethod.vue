<script setup>
/*
  بطاقة طريقة الدفع — مُرحَّلة حرفيًا من accPm(m, hasForm) في src/legacy/gifts.html
*/
import { useGifts } from '@/composables/useGifts'

const props = defineProps({
  m: { type: Object, required: true },
  hasForm: { type: Boolean, default: false },
})

const { state } = useGifts()

const ENABLED_PAYS = ['cash', 'wallet', 'urpay']

function select() {
  if (!ENABLED_PAYS.includes(props.m.id)) return
  state.pay = props.m.id
}
</script>

<template>
  <div class="acc-pm" :class="{ sel: state.pay === m.id, disabled: !ENABLED_PAYS.includes(m.id) }" :data-pay="m.id" @click="select">
    <div class="head">
      <span class="lg">{{ m.logo }}</span>
      <span class="tt"><b>{{ m.n }}</b><small>{{ ENABLED_PAYS.includes(m.id) ? m.d : 'قريبًا' }}</small></span>
      <span class="rad"><i></i></span>
    </div>
    <div v-if="hasForm" class="body has"><div class="in">
      <div class="two">
        <div class="fld" style="margin-bottom:10px"><label>رقم البطاقة</label><input dir="ltr" inputmode="numeric" placeholder="•••• •••• •••• ••••" /></div>
        <div class="fld" style="margin-bottom:10px"><label>الاسم على البطاقة</label><input placeholder="الاسم كما هو على البطاقة" /></div>
      </div>
      <div class="two">
        <div class="fld" style="margin:0"><label>تاريخ الانتهاء</label><input dir="ltr" placeholder="MM / YY" /></div>
        <div class="fld" style="margin:0"><label>رمز الأمان (CVV)</label><input dir="ltr" inputmode="numeric" placeholder="•••" /></div>
      </div>
    </div></div>
  </div>
</template>

<style scoped>
.acc-pm.disabled { opacity: .45; }
.acc-pm.disabled .head { cursor: not-allowed; }
</style>
