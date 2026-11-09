<script setup>
/*
  إتمام الطلب — مُرحَّل حرفيًا من renderCheckout() في src/legacy/store.html

  الأنماط السطرية (inline styles) مُبقاة كما هي عمدًا:
  نقلها إلى ملف الأنماط قد يغيّر أولوية التطبيق (specificity) ويكسر الشكل.
*/
import { BRANCHES, CK_PAYS } from '@/data/store'
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

/* أنماط بطاقة الفرع — منقولة حرفيًا من القالب الأصلي */
const brLabelStyle = id =>
  `display:flex;gap:10px;align-items:center;font-size:13px;color:var(--ink);cursor:pointer;padding:10px 12px;border:1.5px solid ${c.branch === id ? 'var(--gold)' : 'var(--line)'};border-radius:11px;background:${c.branch === id ? '#FFFBF0' : '#fff'}`
const brDotStyle = id =>
  `width:16px;height:16px;border-radius:50%;border:1.5px solid ${c.branch === id ? 'var(--gold)' : 'var(--line)'};display:grid;place-items:center`
const brInnerStyle = id =>
  `width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,var(--gold-bright),var(--gold));transform:scale(${c.branch === id ? 1 : 0});transition:.2s`

function submit() {
  if (!ckCan.value) return
  emit('placed', () => placeOrder(BRANCHES, CK_PAYS))
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
        <h4 style="font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:14px">1️⃣ طريقة الاستلام</h4>
        <div class="bk-pays" style="display:grid;gap:10px">
          <div class="acc-pm" :class="{ sel: c.method === 'pickup' }" data-mth="pickup">
            <div class="head" @click="c.method = 'pickup'"><span class="lg">🏬</span>
              <span class="tt"><b>استلام من الفرع</b><small>جاهزة للاستلام من أقرب فرع — مجانًا</small></span>
              <span class="rad"><i></i></span></div>
            <div class="body" :class="{ has: c.method === 'pickup' }"><div class="in" style="display:grid;gap:9px">
              <label v-for="b in BRANCHES" :key="b.id" :style="brLabelStyle(b.id)" :data-br="b.id" @click="c.branch = b.id">
                <span :style="brDotStyle(b.id)"><i :style="brInnerStyle(b.id)"></i></span>
                <span><b style="display:block;font-size:13px">{{ b.n }}</b><small style="color:var(--mute);font-size:11px">{{ b.a }}</small></span></label>
            </div></div>
          </div>
          <div class="acc-pm" :class="{ sel: c.method === 'delivery' }" data-mth="delivery">
            <div class="head" @click="c.method = 'delivery'"><span class="lg">🚚</span>
              <span class="tt"><b>توصيل للمنزل</b><small>توصيل سريع داخل جدة — 25 ر.س</small></span>
              <span class="rad"><i></i></span></div>
            <div class="body" :class="{ has: c.method === 'delivery' }"><div class="in">
              <div class="fld" style="margin:0"><label>عنوان التوصيل</label>
                <input id="ckAddr" placeholder="الحي، الشارع، رقم المبنى..." v-model="c.addr" /></div>
            </div></div>
          </div>
        </div>
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
        <div v-for="m in CK_PAYS" :key="m.id" class="acc-pm" :class="{ sel: c.pay === m.id }" :data-ckpay="m.id">
          <div class="head" @click="c.pay = m.id"><span class="lg">{{ m.logo }}</span>
            <span class="tt"><b>{{ m.n }}</b><small>{{ m.d }}</small></span>
            <span class="rad"><i></i></span></div>
          <div v-if="m.form" class="body" :class="{ has: c.pay === m.id }"><div class="in">
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
        <span class="terms-chk" :class="{ on: c.terms }" id="ckTerms" @click="c.terms = !c.terms"><i><SIcon :inner="I.check" :size="11"/></i>
          أوافق على <a href="https://sami-care.sa/TermsAndConditions">الشروط والأحكام وسياسة الاسترجاع</a></span>
        <div class="inline-actions">
          <button class="btn btn-prev" id="backStore2" @click="emit('back')"><SIcon :inner="I.arrowR" :size="15"/> الرجوع للمتجر</button>
          <button class="btn btn-gold" id="placeOrder" :disabled="!ckCan" @click="submit"><SIcon :inner="I.lock" :size="15"/> إتمام الطلب — {{ rs(ckParts.total) }} ر.س</button>
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
        <div class="gs-row" style="border:none;padding:4px 0"><span class="k">{{ c.method === 'delivery' ? 'رسوم التوصيل' : 'الاستلام من الفرع' }}</span><span class="v" :style="ckParts.ship ? '' : 'color:var(--green)'">{{ ckParts.ship ? '+' + rs(ckParts.ship) + ' ر.س' : 'مجانًا' }}</span></div>
      </div>
      <div class="gs-total"><span class="k">الإجمالي</span><span class="v">{{ rs(ckParts.total) }} <small style="font-size:13px">ر.س</small></span></div>
      <div class="gs-note"><SIcon :inner="I.lock" :size="14"/> الدفع آمن ومشفّر — بياناتك محمية بالكامل</div>
    </div></div></aside>
  </div>
</template>
