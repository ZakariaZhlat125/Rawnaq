<script setup>
/*
  بطاقة طريقة الدفع — مُرحَّلة حرفيًا من accPm(m, hasForm) في src/legacy/gifts.html
*/
import { useGifts } from '@/composables/useGifts'

defineProps({
  m: { type: Object, required: true },
  hasForm: { type: Boolean, default: false },
})

const { state } = useGifts()
</script>

<template>
  <div class="acc-pm" :class="{ sel: state.pay === m.id }" :data-pay="m.id" @click="state.pay = m.id">
    <div class="head">
      <span class="lg">{{ m.logo }}</span>
      <span class="tt"><b>{{ m.n }}</b><small>{{ m.d }}</small></span>
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
