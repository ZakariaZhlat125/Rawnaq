<script setup>
/*
  إتمام الطلب — مُرحَّل حرفيًا من renderCheckout() في src/legacy/store.html

  الأنماط السطرية (inline styles) مُبقاة كما هي عمدًا:
  نقلها إلى ملف الأنماط قد يغيّر أولوية التطبيق (specificity) ويكسر الشكل.
*/
import { ref } from 'vue'
import { CK_PAYS } from '@/data/store'
import { rs, shapeParts } from '@/utils/storeHelpers'
import { useStore } from '@/composables/useStore'
import SIcon from '@/components/common/SIcon.vue'

const emit = defineEmits(['back', 'placed'])
const { state, cartItems, ckParts, ckCan, placeOrder } = useStore()
const c = state.ck

const I = {
  arrowR: '<path d="M5 12h14M13 6l6 6-6 6"/>',
  lock:   '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
  check:  '<path d="M20 6L9 17l-5-5"/>',
}

const thumb = shape => shapeParts(shape, 34)

/* Product checkout supports cash on delivery and wallet payments. */
const ENABLED_PAYS = ['cod', 'wallet']

const error = ref('')

function selectMethod(m) {
  if (!ENABLED_PAYS.includes(m.id)) return
  c.pay = m.id
}

async function submit() {
  if (!ckCan.value) return
  error.value = ''
  try {
    await placeOrder()
    emit('placed')
  } catch (e) {
    error.value = e.message || 'تعذّر إتمام الطلب، حاول مرة أخرى'
  }
}
</script>

<template>
  <div class="g-stepper" style="padding-bottom:0">
    <div style="display:flex;align-items:center;gap:12px;justify-content:center">
      <button class="btn btn-prev" id="backStore" style="padding:10px 20px;font-size:12.5px" @click="emit('back')"><SIcon :inner="I.arrowR" :size="14"/> العودة للمتجر</button>
    </div>
  </div>
  <div class="g-head" style="margin-top:14px"><h1>إتمام الطلب 🛍️</h1><p>خطوة أخيرة وتصلك منتجاتك المختارة بعناية</p></div>
  <div class="g-stage" style="padding-top:6px">
    <main class="view on">

      <div class="card" style="padding:22px;margin-bottom:14px">
        <h4 style="font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:14px">1️⃣ عنوان التوصيل</h4>
        <div class="fld" style="margin:0"><label>العنوان</label>
          <input id="ckAddr" placeholder="الحي، الشارع، رقم المبنى..." v-model="c.addr" /></div>
      </div>

      <div class="card" style="padding:22px;margin-bottom:14px">
        <h4 style="font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:14px">2️⃣ بيانات العميل</h4>
        <div class="two">
          <div class="fld" style="margin:0"><label>الاسم الكامل</label><input id="ckName" v-model="c.name" /></div>
          <div class="fld" style="margin:0"><label>رقم الجوال</label><input id="ckPhone" dir="ltr" v-model="c.phone" /></div>
        </div>
      </div>

      <div class="card" style="padding:22px">
        <h4 style="font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:14px">3️⃣ طريقة الدفع</h4>
        <div v-for="m in CK_PAYS" :key="m.id" class="acc-pm" :class="{ sel: c.pay === m.id, disabled: !ENABLED_PAYS.includes(m.id) }" :data-ckpay="m.id">
          <button type="button" class="head" :disabled="!ENABLED_PAYS.includes(m.id)" @click="selectMethod(m)"><span class="lg">{{ m.logo }}</span>
            <span class="tt"><b>{{ m.n }}</b><small>{{ ENABLED_PAYS.includes(m.id) ? m.d : 'قريبًا' }}</small></span>
            <span class="rad"><i></i></span></button>
        </div>
        <p v-if="error" style="color:#b42318;font-size:12.5px;margin:12px 0 0">{{ error }}</p>
        <span class="terms-chk" :class="{ on: c.terms }" id="ckTerms" @click="c.terms = !c.terms"><i><SIcon :inner="I.check" :size="11"/></i>
          أوافق على <RouterLink to="/terms" @click.stop>الشروط والأحكام وسياسة الاسترجاع</RouterLink></span>
        <div class="inline-actions">
          <button class="btn btn-prev" id="backStore2" @click="emit('back')"><SIcon :inner="I.arrowR" :size="15"/> الرجوع للمتجر</button>
          <button class="btn btn-gold" id="placeOrder" :disabled="!ckCan || state.placing" @click="submit"><SIcon :inner="I.lock" :size="15"/> {{ state.placing ? 'جارٍ إتمام الطلب...' : `إتمام الطلب — ${rs(ckParts.total)} ر.س` }}</button>
        </div>
        <div class="after-pay" style="font-size:11.5px;color:var(--mute);text-align:center;margin-top:12px">🔒 جميع عمليات الدفع آمنة ومشفرة</div>
      </div>

    </main>
    <aside><div class="gsum"><div class="card" style="padding:20px">
      <h3 style="font-family:var(--font-d);font-size:17px;color:var(--ink);margin-bottom:14px;display:flex;gap:10px;align-items:center">ملخص الطلب<span style="flex:1;height:1px;background:linear-gradient(-90deg,var(--gold),transparent)"></span></h3>
      <div v-for="it in cartItems" :key="it.id" class="sum-item"><span class="thumb"><svg class="prod" width="34" height="34" :viewBox="thumb(it.shape).viewBox" v-html="thumb(it.shape).inner"></svg></span>
        <span><b>{{ it.n }}</b><small>الكمية: {{ it.qty }}</small></span>
        <span class="p">{{ rs(it.pr * it.qty) }} ر.س</span></div>
      <div style="margin-top:12px;padding-top:12px;border-top:1px dashed var(--line)">
        <div class="gs-row" style="border:none;padding:4px 0"><span class="k">المجموع الفرعي</span><span class="v">{{ rs(ckParts.sub) }} ر.س</span></div>
        <div class="gs-row" style="border:none;padding:4px 0"><span class="k">ضريبة القيمة المضافة (15%)</span><span class="v">+{{ rs(ckParts.vat) }} ر.س</span></div>
        <div class="gs-row" style="border:none;padding:4px 0"><span class="k">رسوم التوصيل</span><span class="v" :style="ckParts.ship ? '' : 'color:var(--green)'">{{ ckParts.ship ? '+' + rs(ckParts.ship) + ' ر.س' : 'مجانًا' }}</span></div>
      </div>
      <div class="gs-total"><span class="k">الإجمالي</span><span class="v">{{ rs(ckParts.total) }} <small style="font-size:13px">ر.س</small></span></div>
      <div class="gs-note"><SIcon :inner="I.lock" :size="14"/> الدفع آمن ومشفّر — بياناتك محمية بالكامل</div>
    </div></div></aside>
  </div>
</template>

<style scoped>
.acc-pm .head{width:100%;border:0;background:none;text-align:start;font:inherit;color:inherit}.acc-pm .head:disabled{cursor:not-allowed}

.acc-pm.disabled { opacity: .45; }
.acc-pm.disabled .head { cursor: not-allowed; }
</style>
