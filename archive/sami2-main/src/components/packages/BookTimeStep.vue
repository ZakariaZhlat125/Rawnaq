<script setup>
/*
  اختيار الوقت والتاريخ — مُرحَّل حرفيًا من bkView1() في src/legacy/packages-gifts.html
  يشمل: شريط الأيام (12 يومًا) · تصفية الفترة · شبكة المواعيد · وقت الانتهاء المتوقع
*/
import { computed } from 'vue'
import { AR_DAYS, AR_MONTHS } from '@/data/packages'
import { usePackages, fmtTime, fmtDate } from '@/composables/usePackages'

const { state, pkgOf, bkDays, bkSlots } = usePackages()

const B = state.bk
const days = bkDays()
const p = computed(() => pkgOf(B.pkg))

/* تصفية المواعيد حسب الفترة — نفس شروط الأصل */
const slots = computed(() => {
  if (B.dayIdx == null) return []
  return bkSlots(days[B.dayIdx]).filter(s => {
    const h = s.t / 60
    return B.period === 'all'
      || (B.period === 'am' && h < 12)
      || (B.period === 'pm' && h >= 12 && h < 17)
      || (B.period === 'eve' && h >= 17)
  })
})

/* وسم "الأكثر طلبًا" للثالث و"آخر موعد" للأخير */
const slotTag = i => (i === 2 ? 'الأكثر طلبًا' : (i === slots.value.length - 1 ? 'آخر موعد' : ''))

const endTime = computed(() => (B.time != null ? fmtTime(B.time + p.value.dur) : null))

const PERIODS = [['all', 'كل اليوم', '🗓️'], ['am', 'صباحًا', '☀️'], ['pm', 'مساءً', '🌇']]

const EMPTY_STYLE = 'grid-column:1/-1;text-align:center;color:var(--mute);padding:30px;border:1.5px dashed var(--line);border-radius:14px;font-size:13px'
const H4 = 'font-family:var(--font-d);font-size:15px;color:var(--ink);margin-bottom:12px'
const H4B = 'font-family:var(--font-d);font-size:15px;color:var(--ink);margin-bottom:4px'
</script>

<template>
  <div class="g-head"><h1>اختر الوقت والتاريخ</h1><p>اختر الوقت المناسب لك — مدة الباقة {{ p.dur }} دقيقة</p></div>

  <div class="card" style="padding:20px;margin-bottom:16px">
    <h4 :style="H4">📅 اختر اليوم</h4>
    <div class="daystrip">
      <div v-for="(d, i) in days" :key="i" class="day" :class="{ sel: B.dayIdx === i }" :data-bd="i" @click="B.dayIdx = i; B.time = null">
        <small>{{ AR_DAYS[d.getDay()] }}</small><b>{{ d.getDate() }}</b><small>{{ AR_MONTHS[d.getMonth()] }}</small>
      </div>
    </div>
  </div>

  <div v-if="B.dayIdx != null" class="card" style="padding:20px">
    <h4 :style="H4B">🕐 {{ fmtDate(days[B.dayIdx]) }}</h4>
    <div class="periods">
      <button v-for="x in PERIODS" :key="x[0]" class="period" :class="{ sel: B.period === x[0] }" :data-bp="x[0]" @click="B.period = x[0]">{{ x[2] }} {{ x[1] }}</button>
    </div>
    <div class="slots">
      <template v-if="slots.length">
        <button v-for="(s, i) in slots" :key="s.t" class="slot" :class="{ sel: B.time === s.t }" :data-bt="s.t"
                :disabled="s.busy" :style="`animation-delay:${Math.min(i * 0.03, 0.4)}s`" @click="B.time = s.t"
        ><span v-if="slotTag(i) && !s.busy" class="tag">{{ slotTag(i) }}</span><template v-else>&#32;</template>{{ fmtTime(s.t) }}</button>
      </template>
      <div v-else :style="EMPTY_STYLE">لا توجد أوقات متاحة في هذه الفترة</div>
    </div>
    <div v-if="B.time != null" class="end-line"><span>⏱️ مدة الجلسة: <b>{{ p.dur }} دقيقة</b></span><span>الوقت المتوقع للانتهاء: <b>{{ endTime }}</b></span></div>
  </div>

  <div v-else class="card" style="padding:40px;text-align:center;color:var(--mute);font-size:13.5px"><b style="display:block;font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:6px">ابدأ باختيار اليوم</b>اختر يومًا من الشريط أعلاه لعرض الأوقات المتاحة</div>
</template>
