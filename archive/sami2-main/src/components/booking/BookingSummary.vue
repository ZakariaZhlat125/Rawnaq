<script setup>
/*
  ملخص الحجز (العمود الجانبي) — مُرحَّل حرفيًا من renderSummary()
  في src/legacy/booking.html

  يظهر فقط عند اختيار خدمة واحدة على الأقل، مع انتقال ظهور سلس
  (كان يُدار بـ classList.add('show') في الأصل).
*/
import { computed, ref, watch, nextTick } from 'vue'
import { BRANCHES, UPSELL, AR_DAYS, AR_MONTHS } from '@/data/booking'
import { useBooking, catOf, fmtTime, fmtDur, rs } from '@/composables/useBooking'

const { state, selSvcs, totalDur, priceParts, toggleSvc, toggleUpsell } = useBooking()

const show = ref(false)
const hasSvc = computed(() => state.services.length > 0)

watch(hasSvc, v => {
  if (v) nextTick(() => requestAnimationFrame(() => { show.value = true }))
  else show.value = false
}, { immediate: true })

const br = computed(() => BRANCHES.find(b => b.id === state.branch))
const p = computed(() => priceParts.value)
const upsellItems = computed(() => state.upsell.map(id => UPSELL.find(x => x.id === id)))

const dateLabel = computed(() =>
  state.date
    ? `${AR_DAYS[state.date.getDay()]} ${state.date.getDate()} ${AR_MONTHS[state.date.getMonth()]} ${state.date.getFullYear()}`
    : ''
)
</script>

<template>
  <div id="summary" :class="{ show }">
    <div v-if="hasSvc" class="card">
      <h3><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--gold-deep)" stroke-width="1.8"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg> ملخص الحجز</h3>

      <div v-if="br" class="br-img"><img :src="br.img" :alt="br.name" /></div>

      <div v-if="br" class="sum-row"><span class="k">📍 الفرع</span><span class="v">{{ br.name }}</span></div>
      <div v-if="state.date" class="sum-row"><span class="k">📅 التاريخ</span><span class="v">{{ dateLabel }}</span></div>
      <div v-if="state.time != null" class="sum-row"><span class="k">🕐 الوقت</span><span class="v">{{ fmtTime(state.time) }}</span></div>
      <div class="sum-row"><span class="k">⏱️ المدة الإجمالية</span><span class="v">{{ fmtDur(totalDur) }}</span></div>
      <div class="sum-row"><span class="k">✂️ عدد الخدمات</span><span class="v">{{ selSvcs.length }} {{ selSvcs.length > 2 ? 'خدمات' : 'خدمة' }}</span></div>
      <div v-if="state.mode" class="sum-row"><span class="k">👤 الموظفون</span><span class="v">{{ state.mode === 'auto' ? 'اختيار تلقائي ذكي' : 'اختيار يدوي' }}</span></div>

      <div class="sum-svcs">
        <div v-for="s in selSvcs" :key="s.id" class="sum-svc"><i :style="`background:${catOf(s.cat).hex}`"></i>
          <span class="nm">{{ s.name }}</span><span class="pr">{{ rs(s.price) }} ر.س</span>
          <button class="rm" :data-rm="s.id" aria-label="حذف" @click="toggleSvc(s.id)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
        </div>
        <div v-for="u in upsellItems" :key="u.id" class="sum-svc"><i style="background:var(--gold)"></i><span class="nm">{{ u.name }} <small style="color:var(--mute)">(منتج)</small></span><span class="pr">{{ rs(u.price) }} ر.س</span>
          <button class="rm" :data-rmu="u.id" @click="toggleUpsell(u.id)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>
      </div>

      <div class="sum-price">
        <div class="sp-row"><span>المجموع الفرعي</span><span>{{ rs(p.sub) }} ر.س</span></div>
        <div v-if="p.pts" class="sp-row disc"><span>خصم النقاط</span><span>-{{ rs(p.pts) }} ر.س</span></div>
        <div v-if="p.wal" class="sp-row disc"><span>استخدام المحفظة</span><span>-{{ rs(p.wal) }} ر.س</span></div>
        <div v-if="p.cpn" class="sp-row disc"><span>كود الخصم ({{ state.couponCode }})</span><span>-{{ rs(p.cpn) }} ر.س</span></div>
        <div class="sp-row"><span>ضريبة القيمة المضافة (15%)</span><span>+{{ rs(p.vat) }} ر.س</span></div>
        <div class="sp-total"><span class="k">المبلغ الإجمالي</span><span class="v" id="sumTotal">{{ rs(p.total) }} <small>ر.س</small></span></div>
      </div>

      <div class="sum-note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="flex:none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> بياناتك محمية — يمكنك تعديل أو إلغاء الحجز قبل 6 ساعات من الموعد</div>
    </div>
  </div>
</template>
