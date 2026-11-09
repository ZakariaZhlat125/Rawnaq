<script setup>
/*
  ملخص الإهداء (العمود الجانبي) — مُرحَّل حرفيًا من gSummary()
  في src/legacy/packages-gifts.html

  للدالة الأصلية شكلان:
  • بطاقة "لماذا الإهداء من سامي؟" — في الخطوة 0 أو قبل اختيار باقة
  • ملخص التفاصيل — بعد ذلك
*/
import { computed } from 'vue'
import { DESIGNS, SEND_METHODS } from '@/data/packages'
import { usePackages, rs } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state, pkgOf } = usePackages()

const p = computed(() => (state.gpkg ? pkgOf(state.gpkg) : null))
const d = computed(() => DESIGNS.find(x => x.id === state.design))

/* نفس شرط الأصل للتبديل بين الشكلين */
const showIntro = computed(() => state.gstep === 0 || (!p.value && state.gstep < 2))

const sendName = computed(() => {
  const m = SEND_METHODS.find(x => x.id === state.method)
  return m ? m.n : ''
})

const I = {
  head: '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>',
  lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
}

const WHY = [
  ['هدية رقمية فورية', 'تصلك أو تصل للمستلم فور إتمام الدفع', '<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>'],
  ['صالحة لمدة 6 أشهر', 'من تاريخ الشراء', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
  ['المستلم يختار موعده', 'يختار الوقت والفرع المناسب له', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>'],
  ['آمنة وموثوقة', 'نضمن لك تجربة إهداء مميزة', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'],
]

const EMPTY = 'text-align:center;padding:20px;border:1.5px dashed var(--line);border-radius:13px;color:var(--mute);font-size:12.5px;margin-bottom:12px'
const EMPTY_B = 'display:block;font-family:var(--font-d);color:var(--ink);margin-bottom:4px'
</script>

<template>
  <!-- بطاقة التعريف -->
  <div v-if="showIntro" class="gsum">
    <div class="card">
      <h3>لماذا الإهداء من سامي؟</h3>
      <div class="why-gift">
        <div v-for="(w, i) in WHY" :key="i" class="wg"><span class="wi"><SIcon :inner="w[2]" :size="17" /></span><span><b>{{ w[0] }}</b><small>{{ w[1] }}</small></span></div>
      </div>
    </div>
    <div class="card help-card">
      <span class="hi"><SIcon :inner="I.head" :size="18" /></span>
      <b>تحتاج مساعدة؟</b><small>فريقنا جاهز لمساعدتك</small>
      <a href="https://wa.me/963959415545">تواصل معنا</a>
    </div>
  </div>

  <!-- ملخص التفاصيل -->
  <div v-else class="gsum">
    <div class="card">
      <h3>ملخص الإهداء</h3>
      <div v-if="p" class="pkline"><img :src="p.img" alt="" /><span><b>{{ p.name }}</b><small>🕐 {{ p.dur }} دقيقة</small><small>{{ p.desc }}</small><span class="pr">{{ rs(p.price) }} ر.س</span></span></div>
      <div v-else :style="EMPTY"><b :style="EMPTY_B">لم يتم اختيار باقة بعد</b>اختر الباقة المناسبة لإهدائها لمن تحب</div>
      <template v-if="state.gstep >= 2">
        <div class="gs-row"><span class="k">👤 المهدى إليه</span><span class="v">{{ state.name || '—' }}</span></div>
        <div class="gs-row"><span class="k">📱 رقم الجوال</span><span class="v" dir="ltr">{{ state.phone || '—' }}</span></div>
        <div class="gs-row"><span class="k">🎨 تصميم البطاقة</span><span class="v">{{ d.n }}</span></div>
        <div class="gs-row"><span class="k">💬 الرسالة</span><span class="v">{{ state.msg ? 'تمت إضافة رسالة مخصصة' : '—' }}</span></div>
        <div class="gs-row"><span class="k">📤 طريقة الإرسال</span><span class="v">{{ sendName }}</span></div>
        <div class="gs-row"><span class="k">🕐 وقت الإرسال</span><span class="v"><span class="chip">{{ state.when === 'now' ? 'فوري' : 'مجدول' }}</span></span></div>
      </template>
      <div v-if="p" class="gs-total"><span class="k">الإجمالي</span><span class="v">{{ rs(p.price) }} <small style="font-size:13px">ر.س</small></span></div>
      <div class="gs-note"><SIcon :inner="I.lock" :size="14" /> بإتمامك معلومات الهدية مشفرة وآمنة 100%</div>
    </div>
    <div v-if="state.gstep === 1" class="card help-card"><span class="hi"><SIcon :inner="I.head" :size="18" /></span><b>تحتاج مساعدة؟</b><small>فريقنا جاهز لمساعدتك في اختيار الهدية المثالية</small><a href="https://wa.me/963959415545">تواصل معنا</a></div>
  </div>
</template>
