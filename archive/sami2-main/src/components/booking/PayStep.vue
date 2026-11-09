<script setup>
/*
  الدفع — مُرحَّل حرفيًا من view6() في src/legacy/booking.html
  يشمل: طرق الدفع · نقاط الولاء والمحفظة · كود الخصم · شارات الثقة
*/
import { computed } from 'vue'
import { PAY_METHODS } from '@/data/booking'
import { useBooking, rs } from '@/composables/useBooking'

const POINTS = 2450
const WALLET = 320
const COUPON_VAL = 25

const { state } = useBooking()

const cardOpen = computed(() => state.pay === 'card')

const NUM = 'width:24px;height:24px;border-radius:50%;background:var(--ink);color:var(--gold-bright);display:inline-grid;place-items:center;font-size:12px;font-family:var(--font-b)'

const TRUST = [
  ['ضمان الجودة', 'نضمن لك أفضل تجربة', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'],
  ['دفع آمن', 'تقنيات تشفير متقدمة', '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
  ['حماية البيانات', 'بياناتك محمية بالكامل', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
  ['دعم فوري', 'فريق دعم جاهز لخدمتك', '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>'],
]

/* تطبيق كود الخصم — نفس منطق الأصل */
function applyCoupon() {
  const code = state.couponCode.trim()
  state.coupon = code ? COUPON_VAL : 0
}
</script>

<template>
  <div class="panel-head"><h1>الدفع</h1><p>اختر طريقة الدفع المناسبة وأكمل حجزك</p></div>

  <div class="card detail-card">
    <h4><span :style="NUM">1</span> اختر طريقة الدفع</h4>
    <div class="pay-methods">
      <div v-for="m in PAY_METHODS" :key="m.id" class="pm" :class="{ sel: state.pay === m.id }" :data-pay="m.id" @click="state.pay = m.id">
        <span class="pmi">{{ m.logo }}</span>
        <span><b>{{ m.n }}</b><small>{{ m.d }}</small></span>
        <span class="rad"><i></i></span>
      </div>
    </div>
    <div class="card-form" :class="{ open: cardOpen }">
      <div class="cf">
        <input placeholder="رقم البطاقة" dir="ltr" inputmode="numeric" />
        <input placeholder="MM/YY" dir="ltr" inputmode="numeric" />
        <input placeholder="CVV" dir="ltr" inputmode="numeric" />
      </div>
    </div>
    <div class="secure-line">🔒 جميع عمليات الدفع آمنة ومشفرة</div>
  </div>

  <div class="card detail-card">
    <h4><span :style="NUM">2</span> استخدام العروض والمكافآت</h4>
    <div class="rewards">
      <div class="rw">
        <div class="rt"><span class="ri"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7"/></svg></span>
          <span><b>استخدام نقاط الولاء</b><small>لديك {{ rs(POINTS) }} نقطة</small></span>
          <span class="switch" :class="{ on: state.usePoints }" id="swPoints" @click="state.usePoints = !state.usePoints"></span></div>
        <input value="500 نقطة" readonly /><span class="hint">تساوي خصم 50 ر.س</span>
      </div>
      <div class="rw">
        <div class="rt"><span class="ri"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 000 4h4v-4z"/></svg></span>
          <span><b>استخدام من المحفظة</b><small>الرصيد الحالي: {{ rs(WALLET) }} ر.س</small></span>
          <span class="switch" :class="{ on: state.useWallet }" id="swWallet" @click="state.useWallet = !state.useWallet"></span></div>
        <input value="120 ر.س" readonly /><span class="hint">المبلغ المتاح حتى {{ rs(WALLET) }} ر.س</span>
      </div>
    </div>
    <div class="coupon">
      <span class="ci">%</span>
      <input id="cpnInput" placeholder="أدخل كود الخصم" v-model="state.couponCode" />
      <button id="cpnBtn" @click="applyCoupon">تطبيق</button>
    </div>
    <div class="coupon-msg" id="cpnMsg">{{ state.coupon ? '✓ تم تطبيق كود الخصم — خصم ' + COUPON_VAL + ' ر.س' : '' }}</div>
  </div>

  <div class="trust-row">
    <div v-for="(t, i) in TRUST" :key="i" class="trust"><span class="ti"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" v-html="t[2]"></svg></span><b>{{ t[0] }}</b><small>{{ t[1] }}</small></div>
  </div>
</template>
