<script setup>
/*
  دفع الإهداء — مُرحَّل حرفيًا من gView3() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { PAYS } from '@/data/packages'
import { usePackages } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state } = usePackages()

const cardOpen = computed(() => state.pay === 'card' || state.pay === 'mada')

const I = {
  check:  '<path d="M20 6L9 17l-5-5"/>',
  shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
}
const H4 = 'font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:16px'
</script>

<template>
  <div class="g-head"><h1>أكمل الدفع لإرسال هديتك <span class="lock">🔒</span></h1><p>بيانات دفع آمنة ومشفرة بالكامل</p></div>
  <div class="card" style="padding:22px;margin-bottom:16px">
    <h4 :style="H4">اختر طريقة الدفع</h4>
    <div class="pay-methods">
      <div v-for="m in PAYS" :key="m.id" class="pm" :class="{ sel: state.pay === m.id }" :data-pay="m.id" @click="state.pay = m.id">
        <span class="rad"><i></i></span>
        <span class="pmi">{{ m.logo }}</span><b>{{ m.n }}</b><small>{{ m.d }}</small>
      </div>
    </div>
    <div class="card-form" :class="{ open: cardOpen }">
      <div class="cf-box">
        <h5>بيانات البطاقة</h5>
        <div class="two">
          <div class="fld"><label>رقم البطاقة</label><input dir="ltr" inputmode="numeric" placeholder="•••• •••• •••• ••••" /></div>
          <div class="fld"><label>الاسم على البطاقة</label><input placeholder="الاسم كما هو على البطاقة" /></div>
        </div>
        <div class="two">
          <div class="fld" style="margin:0"><label>تاريخ الانتهاء</label><input dir="ltr" placeholder="MM / YY" /></div>
          <div class="fld" style="margin:0"><label>رمز الأمان (CVV)</label><input dir="ltr" inputmode="numeric" placeholder="•••" /></div>
        </div>
        <span class="save-card" :class="{ on: state.saveCard }" id="saveCard" @click="state.saveCard = !state.saveCard"><i><SIcon :inner="I.check" :size="11" /></i> حفظ البطاقة لاستخدامها لاحقًا</span>
      </div>
    </div>
    <div class="secure-strip"><SIcon :inner="I.shield" :size="15" /> جميع بيانات الدفع محمية بتقنية تشفير آمنة ومعتمدة</div>
    <div class="after-pay">سيتم إرسال الهدية مباشرة بعد إتمام الدفع</div>
  </div>
</template>
