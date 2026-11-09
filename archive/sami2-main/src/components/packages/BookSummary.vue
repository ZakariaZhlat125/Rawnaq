<script setup>
/*
  ملخص الحجز (العمود الجانبي) — مُرحَّل حرفيًا من bkSummary() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { BRANCHES, BK_PAYS } from '@/data/packages'
import { usePackages, fmtTime, fmtDate, rs } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state, pkgOf, bkDays } = usePackages()

const B = state.bk
const p = computed(() => pkgOf(B.pkg))
const br = computed(() => (B.branch ? BRANCHES.find(b => b.id === B.branch) : null))
const d = computed(() => (B.dayIdx != null ? bkDays()[B.dayIdx] : null))
const payName = computed(() => {
  const m = BK_PAYS.find(x => x.id === B.pay)
  return m ? m.n : ''
})

const I = {
  shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  head:   '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>',
}
</script>

<template>
  <div class="gsum">
    <div class="card">
      <h3>ملخص الحجز</h3>
      <div class="pkline"><img :src="p.img" alt="" /><span><b>{{ p.name }}</b><small>🕐 {{ p.dur }} دقيقة</small><small>{{ p.desc }}</small><span class="pr">{{ rs(p.price) }} ر.س</span></span></div>
      <div v-if="br" class="gs-row"><span class="k">📍 الفرع</span><span class="v">{{ br.name }}</span></div>
      <div v-if="d" class="gs-row"><span class="k">📅 التاريخ</span><span class="v">{{ fmtDate(d) }}</span></div>
      <template v-if="B.time != null">
        <div class="gs-row"><span class="k">🕐 الوقت</span><span class="v">{{ fmtTime(B.time) }}</span></div>
        <div class="gs-row"><span class="k">⏱️ نهاية الجلسة</span><span class="v">{{ fmtTime(B.time + p.dur) }}</span></div>
      </template>
      <div v-if="B.pay" class="gs-row"><span class="k">💳 طريقة الدفع</span><span class="v">{{ payName }}</span></div>
      <div class="gs-total"><span class="k">الإجمالي</span><span class="v">{{ rs(p.price) }} <small style="font-size:13px">ر.س</small></span></div>
      <div class="gs-note"><SIcon :inner="I.shield" :size="14" /> حجز آمن — تعديل أو إلغاء مجاني قبل 6 ساعات</div>
    </div>
    <div class="card help-card"><span class="hi"><SIcon :inner="I.head" :size="18" /></span><b>دعم على مدار الساعة</b><small>نحن هنا لخدمتك</small><a href="https://wa.me/963959415545">تواصل معنا</a></div>
  </div>
</template>
