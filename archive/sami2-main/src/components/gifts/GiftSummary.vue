<script setup>
/*
  ملخص الهدية (العمود الجانبي) — مُرحَّل حرفيًا من summary() في src/legacy/gifts.html

  الأقسام الشرطية:
  • بطاقة مصغّرة أو صف المنتج حسب وجود اختيار
  • تفاصيل التخصيص  (step >= 2)
  • تفاصيل الدفع     (done)
  • تفاصيل السعر     (val > 0)
  • بطاقة المساعدة   (step <= 1 && !done)
*/
import { computed } from 'vue'
import { SENDS, PAY_MAIN, PAY_FLEX, PAY_BAL } from '@/data/gifts'
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'

const { state, priceParts, giftLabel, pkgOf, dsgOf, brOf } = useGifts()

const rs = n => n.toLocaleString('ar-EG-u-nu-latn')

const p = computed(() => priceParts.value)
const label = computed(() => giftLabel.value)
const br = computed(() => (state.branch ? brOf(state.branch) : null))
const d = computed(() => dsgOf(state.design))
const pkg = computed(() => (state.pkg ? pkgOf(state.pkg) : null))

const SVC_IMG = 'https://sami-care.sa//storage/uploads/404/IO26IC8Qd0LCbCeHNjYPUhOmSWeZi3FkEPYFpsuB.jpg'
const img = computed(() => (state.gtype === 'svc' ? SVC_IMG : (pkg.value ? pkg.value.img : null)))

const sendName = computed(() => {
  const m = SENDS.find(x => x.id === state.method)
  return m ? m.n : ''
})
const payName = computed(() => {
  const m = [...PAY_MAIN, ...PAY_FLEX, ...PAY_BAL].find(x => x.id === state.pay)
  return m ? m.n : ''
})

const emptyHint = computed(() =>
  !br.value
    ? 'حدد مكان تنفيذ الخدمة أولًا لعرض ' + (state.gtype === 'svc' ? 'الخدمات' : 'الباقات') + ' والأسعار'
    : 'اختر ' + (state.gtype === 'svc' ? 'الخدمة' : 'الباقة') + ' المناسبة لإهدائها لمن تحب'
)

const I = {
  lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
  head: '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>',
}

const MINI_CARD = 'background:linear-gradient(150deg,#1D1810,#0B0906);color:#E8BE6C;max-width:190px;margin:0 auto 12px;aspect-ratio:16/10;border-radius:14px;display:grid;place-items:center;box-shadow:0 16px 30px -16px rgba(20,15,5,.5);position:relative'
const ROW = 'border:none;padding:4px 0'
</script>

<template>
  <div class="gsum">
    <div class="card">
      <h3>ملخص الهدية</h3>

      <div v-if="label && img" class="pkline"><img :src="img" alt="" /><span><b>{{ label }}</b>
        <template v-if="state.gtype === 'pkg' && pkg"><small>🕐 {{ pkg.dur }} دقيقة</small><small>{{ pkg.desc }}</small></template>
        <template v-else><small>{{ state.svcs.length }} {{ state.svcs.length > 2 ? 'خدمات' : 'خدمة' }} مختارة</small></template>
        <span class="pr">{{ rs(p.val) }} ر.س</span></span></div>

      <div v-else style="text-align:center;margin-bottom:14px">
        <div class="mini-card" :style="MINI_CARD">
          <svg style="position:absolute;top:-5px;right:14px;width:46px;opacity:.9" viewBox="0 0 64 40" fill="none" stroke="currentColor" stroke-width="2"><path d="M32 30C18 30 10 24 12 15s16-5 20 8c4-13 18-17 20-8s-6 15-20 15z"/></svg>
          <span style="font-family:var(--font-d);font-size:14px;letter-spacing:.1em;text-align:center"><img src="/logo.png" style="height:24px;vertical-align:middle" alt="" /><br />SAMI<small style="display:block;font-size:8.5px;letter-spacing:.04em;opacity:.8;margin-top:2px">عناية سامي</small></span>
        </div>
        <b style="display:block;font-family:var(--font-d);color:var(--ink);font-size:14.5px">{{ state.gtype === 'svc' ? 'إهداء خدمة' : 'إهداء باقة' }}</b>
        <small style="display:block;color:var(--ink);font-size:12px;font-weight:700;margin-top:8px">{{ state.gtype === 'svc' ? 'لم يتم اختيار خدمة بعد' : 'لم يتم اختيار باقة بعد' }}</small>
        <small style="display:block;color:var(--mute);font-size:11.5px;margin-top:4px;line-height:1.7">{{ emptyHint }}</small>
      </div>

      <div v-if="br" class="gs-row"><span class="k">📍 الفرع المختار</span><span class="v">{{ br.name }}</span></div>

      <template v-if="state.step >= 2">
        <div class="gs-row"><span class="k">🎨 تصميم البطاقة</span><span class="v">{{ d.n }}</span></div>
        <div class="gs-row"><span class="k">💬 الرسالة</span><span class="v">{{ state.msg ? 'مخصصة' : '—' }}</span></div>
        <div class="gs-row"><span class="k">📤 طريقة الإرسال</span><span class="v">{{ sendName }}</span></div>
        <div class="gs-row"><span class="k">👤 المهدى إليه</span><span class="v">{{ state.name || '—' }}</span></div>
        <div class="gs-row"><span class="k">🕐 وقت الإرسال</span><span class="v"><span class="chip">{{ state.when === 'now' ? 'إرسال الآن' : 'مجدول' }}</span></span></div>
      </template>

      <template v-if="state.done">
        <div class="gs-row"><span class="k">💳 طريقة الدفع</span><span class="v">{{ payName }}</span></div>
        <div class="gs-row"><span class="k">✅ حالة الدفع</span><span class="v"><span class="chip">مكتمل</span></span></div>
      </template>

      <template v-if="p.val">
        <div class="sum-like" style="margin-top:10px;padding-top:12px;border-top:1px dashed var(--line)">
          <div class="gs-row" :style="ROW"><span class="k">قيمة الهدية</span><span class="v">{{ rs(p.val) }} ر.س</span></div>
          <div v-if="p.fee" class="gs-row" :style="ROW"><span class="k">رسوم الخدمة المنزلية</span><span class="v">+{{ rs(p.fee) }} ر.س</span></div>
          <div class="gs-row" :style="ROW"><span class="k">ضريبة القيمة المضافة (15%)</span><span class="v">+{{ rs(p.vat) }} ر.س</span></div>
          <div v-if="p.disc" class="gs-row" :style="ROW"><span class="k" style="color:var(--green)">خصم</span><span class="v" style="color:var(--green)">-{{ rs(p.disc) }} ر.س</span></div>
        </div>
        <div class="gs-total"><span class="k">الإجمالي</span><span class="v">{{ rs(p.total) }} <small style="font-size:13px">ر.س</small></span></div>
      </template>

      <div class="gs-note"><SIcon :inner="I.lock" :size="14" /> الدفع آمن ومشفّر — نستخدم أحدث تقنيات التشفير لحماية بياناتك</div>
    </div>

    <div v-if="state.step <= 1 && !state.done" class="card help-card"><span class="hi"><SIcon :inner="I.head" :size="18" /></span><b>تحتاج مساعدة؟</b><small>فريقنا جاهز لمساعدتك في الاختيار</small><a href="https://wa.me/963959415545">تواصل معنا</a></div>
  </div>
</template>
