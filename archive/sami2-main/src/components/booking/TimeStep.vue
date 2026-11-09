<script setup>
/*
  اختيار الوقت — مُرحَّل حرفيًا من view4() في src/legacy/booking.html
  يشمل: شريط ملخص الجلسة · تقويم 7 أيام · تصفية الفترة · المواعيد · خطة تنفيذ الجلسة
*/
import { computed } from 'vue'
import { BRANCHES, AR_DAYS, AR_MONTHS } from '@/data/booking'
import { useBooking, bestEmp, fmtTime, fmtDur } from '@/composables/useBooking'

const { state, selCats, selSvcs, totalDur, slotsForDay, buildSession } = useBooking()

const TODAY = new Date(2026, 6, 6)

const br = computed(() => BRANCHES.find(b => b.id === state.branch))

const nEmp = computed(() => {
  const emps = state.mode === 'manual'
    ? selCats.value.map(c => (state.emp[c.id] && state.emp[c.id] !== 'any' ? state.emp[c.id] : bestEmp(c.id).n))
    : selCats.value.map(c => bestEmp(c.id).n)
  return [...new Set(emps)].length
})

/* التقويم — شريط 7 أيام */
const days = computed(() =>
  Array.from({ length: 7 }, (_, i) => {
    const d = new Date(state.calStart)
    d.setDate(d.getDate() + i)
    return d
  })
)

const atToday = computed(() => state.calStart.getTime() <= TODAY.getTime())

const rangeLabel = computed(() => {
  const d = days.value
  return d[0].getMonth() === d[6].getMonth()
    ? `${d[0].getDate()} – ${d[6].getDate()} ${AR_MONTHS[d[0].getMonth()]}`
    : `${d[0].getDate()} ${AR_MONTHS[d[0].getMonth()]} – ${d[6].getDate()} ${AR_MONTHS[d[6].getMonth()]}`
})

const isPast = d => d < TODAY
const isSel = d => state.date && d.getTime() === state.date.getTime()

function shiftCal(n) {
  const d = new Date(state.calStart)
  d.setDate(d.getDate() + n)
  state.calStart = d
}

function pickDate(d) {
  if (isPast(d)) return
  state.date = d
  state.time = null
}

/* المواعيد بعد تصفية الفترة */
const slots = computed(() => {
  if (!state.date) return []
  return slotsForDay(state.date).filter(s => {
    const h = s.t / 60
    return state.period === 'all'
      || (state.period === 'am' && h < 12)
      || (state.period === 'pm' && h >= 12 && h < 17)
      || (state.period === 'eve' && h >= 17)
  })
})

const slotTag = i => (i === 2 ? 'الأكثر طلبًا' : (i === slots.value.length - 1 ? 'آخر موعد' : ''))

const session = computed(() => (state.time != null ? buildSession(state.time) : null))

const dateTitle = computed(() =>
  state.date
    ? `${AR_DAYS[state.date.getDay()]} ${state.date.getDate()} ${AR_MONTHS[state.date.getMonth()]} ${state.date.getFullYear()}`
    : 'اختر تاريخًا من التقويم'
)

const PERIODS = [['all', 'كل اليوم', '🗓️'], ['am', 'صباحًا', '☀️'], ['pm', 'مساءً', '🌇']]
</script>

<template>
  <div class="panel-head"><h1>اختر الوقت المناسب لك</h1><p>سيتم عرض الأوقات المتاحة بناءً على توفر الموظفين ومدة الخدمات — كل وقت معروض يمكن تنفيذ جلستك فيه بالكامل</p></div>

  <div class="card branch-strip">
    <div class="bi"><b>📍 {{ br ? br.name : '' }}</b><small>ملخص جلستك قبل اختيار الوقت</small></div>
    <div class="cells">
      <div class="cell"><b>{{ selSvcs.length }}</b>الخدمات</div>
      <div class="cell"><b>{{ nEmp }}</b>الموظفون</div>
      <div class="cell"><b>{{ fmtDur(totalDur) }}</b>المدة الإجمالية</div>
    </div>
  </div>

  <div v-if="state.mode === 'auto'" class="notice"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex:none"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg> الأوقات الظاهرة أوقات جلسة كاملة قابلة للتنفيذ — وسيتم تنفيذ بعض الخدمات بالتوازي لتقليل وقت الانتظار</div>

  <div class="time-grid">
    <div class="card cal">
      <div class="cal-head">
        <button class="cal-nav" data-nav="-7" :disabled="atToday" @click="shiftCal(-7)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg></button>
        <b>{{ rangeLabel }}</b>
        <button class="cal-nav" data-nav="7" @click="shiftCal(7)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 6l-6 6 6 6"/></svg></button>
      </div>
      <div class="cal-cells">
        <button v-for="(dt, i) in days" :key="i" :data-i="i" :disabled="isPast(dt)"
                :class="{ sel: isSel(dt), avail: !isPast(dt) }" @click="pickDate(dt)">
          <span class="dow">{{ AR_DAYS[dt.getDay()].slice(0, 3) }}</span><span class="dnum">{{ dt.getDate() }}</span>
        </button>
      </div>
      <div class="cal-legend"><span><i style="background:var(--green)"></i> تتوفر مواعيد</span><span><i style="background:#D8D0BC"></i> غير متاح</span></div>
    </div>

    <div class="card times-panel">
      <div class="date-line">📅 {{ dateTitle }}</div>
      <template v-if="state.date">
        <div class="periods">
          <button v-for="p in PERIODS" :key="p[0]" class="period" :class="{ sel: state.period === p[0] }" :data-p="p[0]" @click="state.period = p[0]">{{ p[2] }} {{ p[1] }}</button>
        </div>
        <div style="font-size:13px;color:var(--mute);margin-bottom:10px">الأوقات المتاحة</div>
        <div class="slots">
          <template v-if="slots.length">
            <button v-for="(s, i) in slots" :key="s.t" class="slot" :class="{ sel: state.time === s.t }" :data-t="s.t"
                    :disabled="s.busy" :style="`animation-delay:${Math.min(i * 0.03, 0.4)}s`" @click="state.time = s.t"
            ><span v-if="slotTag(i) && !s.busy" class="tag">{{ slotTag(i) }}</span><template v-else>&#32;</template>{{ fmtTime(s.t) }}</button>
          </template>
          <div v-else class="empty-hint" style="grid-column:1/-1">لا توجد أوقات متاحة في هذه الفترة</div>
        </div>
        <div v-if="state.time == null" class="best-slot">⚡ أفضل موعد متاح اليوم: <b>05:30 م</b><button data-quick="1050" @click="state.time = 1050">حجز سريع</button></div>

        <div v-if="session" class="session-plan">
          <h4><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--gold-deep)" stroke-width="1.8"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg> خطة تنفيذ الجلسة</h4>
          <div class="plan-track">
            <template v-for="(it, i) in session.items" :key="i">
              <div v-if="it.isBreak" class="coffee-break"><span class="cup">☕</span><span>{{ fmtTime(it.start) }} – {{ fmtTime(it.end) }}</span></div>
              <div v-else class="plan-item" :style="`border-color:${it.cat.hex}44;background:${it.cat.hex}0D`">
                <b :style="`color:${it.cat.hex}`">{{ it.cat.emoji }} {{ it.cat.name }}</b>
                <small>مع {{ it.emp }}</small>
                <span class="t" :style="`color:${it.cat.hex}`">{{ fmtTime(it.start) }} – {{ fmtTime(it.end) }}</span>
                <small>{{ it.count }} {{ it.count > 2 ? 'خدمات' : 'خدمة' }} · {{ it.dur }} دقيقة</small>
              </div>
            </template>
          </div>
          <div class="plan-note">✓ هذا الترتيب يضمن راحتك وتنسيق الخدمات — نهاية الجلسة المتوقعة {{ fmtTime(session.end) }}</div>
        </div>
      </template>
      <div v-else class="empty-hint" style="margin-top:10px"><b>ابدأ باختيار التاريخ</b>اختر يومًا من التقويم لعرض الأوقات المتاحة لجلستك</div>
    </div>
  </div>
</template>
