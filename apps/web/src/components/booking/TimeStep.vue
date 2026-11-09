<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useBooking, fmtTimeStr, fmtDur, rs } from '@/composables/useBooking'
import { fetchAvailableTimes, fetchLoyaltyPointValue } from '@/services/bookingApi'
import { categoryAccent, categoryIconKey, categoryIconPath } from '@/utils/giftIcons'
import Skeleton from '@/components/common/SkeletonLoader.vue'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useLanguage } from '@/composables/useLanguage'
import { useQuietHours } from '@/composables/useQuietHours'
const { current: quietBranch } = useServiceLocation()
const { state: quietLanguage } = useLanguage()
const { bonus: quietPoints } = useQuietHours()
const quietBonus = time => !quietBranch.value?.home && state.date
  ? quietPoints(quietBranch.value?.id, toDateKey(state.date), time) : 0

const AR_DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
const AR_MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']

const { state, selSvcs, totalDur, priceParts, setEmployee, setTime } = useBooking()
const pointsPer100 = ref(5)
const earnedLoyaltyPoints = computed(() => Math.floor(priceParts.value.total / 100) * pointsPer100.value)
const quietTotal = computed(() => selSvcs.value.reduce((sum, service) => sum + quietBonus(state.time[service.id]), 0))

const TODAY = new Date()
TODAY.setHours(0, 0, 0, 0)

const calStart = ref(new Date(TODAY))
const period = ref('all')
const slotsByService = reactive({})
const staffBySlot = reactive({})
const loadingByService = reactive({})
const slotRequests = new Map()
const loadVersions = new Map()
function requestSlots(date, candidate, durationMin) {
  const key = [date, candidate.id, durationMin].join(':')
  if (!slotRequests.has(key)) slotRequests.set(key, fetchAvailableTimes({ date, staffId: candidate.id, durationMin }).finally(() => slotRequests.delete(key)))
  return slotRequests.get(key)
}

const days = computed(() =>
  Array.from({ length: 7 }, (_, i) => {
    const d = new Date(calStart.value)
    d.setDate(d.getDate() + i)
    return d
  })
)

const atToday = computed(() => calStart.value.getTime() <= TODAY.getTime())

const rangeLabel = computed(() => {
  const d = days.value
  return d[0].getMonth() === d[6].getMonth()
    ? `${d[0].getDate()} - ${d[6].getDate()} ${AR_MONTHS[d[0].getMonth()]}`
    : `${d[0].getDate()} ${AR_MONTHS[d[0].getMonth()]} - ${d[6].getDate()} ${AR_MONTHS[d[6].getMonth()]}`
})

const isPast = d => d < TODAY
const isSel = d => state.date && d.getTime() === state.date.getTime()

const serviceGroups = computed(() => {
  const groups = new Map()
  selSvcs.value.forEach(service => {
    const id = String(service.categoryId ?? service.categoryName ?? service.id)
    if (!groups.has(id)) {
      groups.set(id, {
        id,
        name: service.categoryName || service.name,
        icon: service.icon || categoryIconKey(service.categoryId),
        services: [],
      })
    }
    groups.get(id).services.push(service)
  })
  return [...groups.values()]
})

const orderedServices = computed(() => selSvcs.value)
const firstService = computed(() => {
  const selected = orderedServices.value.filter(s => state.time[s.id]).sort((a,b) => timeToMinutes(state.time[a.id]) - timeToMinutes(state.time[b.id]))
  return selected[0] || [...orderedServices.value].sort((a,b) => (timeToMinutes(normalizeSlots(slotsByService[a.id])[0]) ?? Infinity) - (timeToMinutes(normalizeSlots(slotsByService[b.id])[0]) ?? Infinity))[0] || null
})
const firstGroup = computed(() => serviceGroups.value[0] || null)
const firstServiceTime = computed(() => firstService.value ? state.time[firstService.value.id] : '')

function shiftCal(n) {
  const d = new Date(calStart.value)
  d.setDate(d.getDate() + n)
  calStart.value = d
}

function toDateKey(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function timeToMinutes(time) {
  if (!time || typeof time !== 'string') return null
  const [h, m] = time.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return null
  return h * 60 + m
}

function minutesToTime(minutes) {
  if (!Number.isFinite(minutes)) return ''
  const dayMinutes = 24 * 60
  const normalized = ((Math.round(minutes) % dayMinutes) + dayMinutes) % dayMinutes
  return `${String(Math.floor(normalized / 60)).padStart(2, '0')}:${String(normalized % 60).padStart(2, '0')}`
}

function normalizeSlots(slots) {
  return [...new Set((Array.isArray(slots) ? slots : []).filter(t => timeToMinutes(t) !== null))]
    .sort((a, b) => timeToMinutes(a) - timeToMinutes(b))
}

function clearTimesFrom(index) {
  orderedServices.value.slice(index).forEach(service => { delete state.time[service.id] })
}

function buildSequentialSchedule(startTime, { apply = false } = {}) {
  const result = window.SamiBookingJourney.schedule(orderedServices.value, service => normalizeSlots(slotsByService[service.id]), startTime)
  if (apply) {
    state.time = {}
    result.rows.forEach(row => {
      setTime(row.service.id, row.start)
      if (state.mode === 'auto' && staffBySlot[row.service.id]?.[row.start]) setEmployee(row.service.id, staffBySlot[row.service.id][row.start])
    })
  }
  return result
}

function syncSequentialTimes() {
  if (!firstServiceTime.value || !allScheduleSlotsLoaded.value) return
  buildSequentialSchedule(firstServiceTime.value, { apply: true })
}

function chooseInitialTime(time) {
  buildSequentialSchedule(time, { apply: true })
}

function groupStyle(group) {
  return { '--acc': categoryAccent(group.icon) }
}

function groupDuration(group) {
  return group.services.reduce((total, service) => total + (Number(service.dur) || 0), 0)
}

function groupEmployee(group) {
  const selected = group.services.map(service => state.emp[service.id]).filter(Boolean)
  if (!selected.length) return null
  const first = selected[0]
  return selected.every(employee => String(employee.id) === String(first.id)) ? first : first
}

function groupServicesLabel(group) {
  return `${group.services.length} ${group.services.length === 1 ? 'خدمة' : 'خدمات'}`
}

function groupServiceNames(group) {
  return group.services.map(service => service.name).join('، ')
}

function pickDate(d) {
  if (isPast(d)) return
  state.date = d
  state.time = {}
  Object.keys(slotsByService).forEach(key => { delete slotsByService[key] })
}

async function loadSlots(service) {
  const emp = state.emp[service.id]
  if (!state.date || !emp) {
    delete slotsByService[service.id]
    return
  }

  const dateKey = toDateKey(state.date)
  const version = (loadVersions.get(service.id) || 0) + 1
  loadVersions.set(service.id, version)
  loadingByService[service.id] = true
  try {
    const candidates = state.mode === 'auto'
      ? (state.staffOptions[service.id] || [])
      : [emp]
    const results = await Promise.all(candidates.filter(candidate => candidate?.id != null).map(async candidate => ({
      candidate,
      times: await requestSlots(dateKey, candidate, service.dur).catch(() => []),
    })))
    const slotMap = {}
    results.forEach(({ candidate, times }) => {
      normalizeSlots(times).forEach(time => { if (!slotMap[time]) slotMap[time] = candidate })
    })
    const times = Object.keys(slotMap)
    if (loadVersions.get(service.id) === version && state.date && toDateKey(state.date) === dateKey && (state.mode === 'auto' || state.emp[service.id]?.id === emp.id)) {
      slotsByService[service.id] = normalizeSlots(times)
      staffBySlot[service.id] = slotMap
      syncSequentialTimes()
    }
  } catch {
    if (loadVersions.get(service.id) !== version) return
    slotsByService[service.id] = []
    syncSequentialTimes()
  } finally {
    if (loadVersions.get(service.id) === version) {
      loadingByService[service.id] = false
      syncSequentialTimes()
    }
  }
}

watch(() => state.date, () => {
  period.value = 'all'
  selSvcs.value.forEach(loadSlots)
})
watch(() => selSvcs.value.map(s => `${s.id}:${s.dur}:${state.mode}:${state.mode === 'auto' ? (state.staffOptions[s.id] || []).map(e => e.id).join(',') : state.emp[s.id]?.id}`).join('|'), () => selSvcs.value.forEach(loadSlots))
onMounted(async () => {
  selSvcs.value.forEach(loadSlots)
  try {
    const response = await fetchLoyaltyPointValue()
    pointsPer100.value = Math.max(Number(response?.data?.points_per_100) || 5, 1)
  } catch { /* keep the same backend fallback used while awarding points */ }
})

const dateTitle = computed(() => (
  state.date ? `${AR_DAYS[state.date.getDay()]} ${state.date.getDate()} ${AR_MONTHS[state.date.getMonth()]} ${state.date.getFullYear()}` : 'اختر تاريخًا من التقويم'
))

const firstServiceLoading = computed(() => orderedServices.value.some(service => loadingByService[service.id] || !Array.isArray(slotsByService[service.id])))
const initialSlots = computed(() => normalizeSlots(orderedServices.value.flatMap(service => slotsByService[service.id] || [])))
const visibleInitialSlots = computed(() => initialSlots.value.filter(time => {
  const minutes = timeToMinutes(time)
  if (period.value === 'am') return minutes < 12 * 60
  if (period.value === 'pm') return minutes >= 12 * 60
  return true
}))
const allScheduleSlotsLoaded = computed(() =>
  !!state.date && orderedServices.value.every(service => Array.isArray(slotsByService[service.id]) && !loadingByService[service.id])
)
const schedulingLoading = computed(() =>
  !!firstServiceTime.value && orderedServices.value.some(service => loadingByService[service.id] || !Array.isArray(slotsByService[service.id]))
)
const unscheduledService = computed(() =>
  firstServiceTime.value ? orderedServices.value.find(service => !state.time[service.id]) : null
)

const sessionPlan = computed(() => {
  const rows = []
  let previousEnd = null

  window.SamiBookingJourney.groupsByTime(serviceGroups.value, state.time, selSvcs.value).forEach(group => {
    const starts = group.services.map(service => timeToMinutes(state.time[service.id]))
    const complete = starts.every(start => start !== null)
    const firstStart = starts[0]

    if (!complete) {
      rows.push({
        type: 'group',
        key: `group-${group.key}`,
        group,
        name: group.name,
        icon: group.icon,
        style: groupStyle(group),
        duration: groupDuration(group),
        services: group.services,
        employee: groupEmployee(group),
        pending: true,
      })
      return
    }

    if (previousEnd !== null && firstStart > previousEnd) {
      rows.push({
        type: 'gap',
        key: `gap-${rows.length}`,
        start: minutesToTime(previousEnd),
        end: minutesToTime(firstStart),
        duration: firstStart - previousEnd,
      })
    }

    const lastService = group.services[group.services.length - 1]
    const lastStart = timeToMinutes(state.time[lastService.id])
    const end = lastStart + (Number(lastService.dur) || 0)

    rows.push({
      type: 'group',
      key: `group-${group.key}`,
      group,
      name: group.name,
      icon: group.icon,
      style: groupStyle(group),
      start: minutesToTime(firstStart),
      end: minutesToTime(end),
      duration: groupDuration(group),
      services: group.services,
      employee: groupEmployee(group),
      pending: false,
    })
    previousEnd = end
  })

  return rows
})

const sessionEndTime = computed(() => {
  const groups = sessionPlan.value.filter(item => item.type === 'group' && !item.pending)
  const lastGroup = groups[groups.length - 1]
  return lastGroup?.end || ''
})
</script>

<template>
  <div class="panel-head">
    <h1>اختر وقت بداية الجلسة</h1>
    <p>اختر أول موعد فقط، وسيتم ترتيب باقي الأقسام تلقائيًا حسب مدة كل قسم والأوقات المتاحة.</p>
  </div>

  <div class="card branch-strip">
    <div class="bi"><b>ملخص جلستك</b><small>قبل اختيار الوقت</small></div>
    <div class="cells">
      <div class="cell"><b>{{ selSvcs.length }}</b>الخدمات</div>
      <div class="cell"><b>{{ fmtDur(totalDur) }}</b>المدة الإجمالية</div>
    </div>
  </div>

  <div class="loyalty-preview" aria-live="polite">
    <h3><span aria-hidden="true">✨</span> مكافأة نقاط الولاء لهذه الجلسة</h3>
    <p>ستحصل على <b>{{ rs(earnedLoyaltyPoints) }} نقطة ولاء</b> عند إتمام هذا الحجز.</p>
    <p v-if="quietTotal" data-no-i18n style="color:#26714d">{{ quietLanguage.lang === 'en' ? `Plus ${quietTotal} quiet hours points when the eligible bookings are completed.` : `بالإضافة إلى ${quietTotal} نقطة أوقات هدوء عند اكتمال الحجوزات المؤهلة.` }}</p>
    <strong>يمنحك نظام الولاء {{ rs(pointsPer100) }} نقطة مقابل كل 100 ر.س مدفوعة.</strong>
  </div>

  <div class="card cal">
    <div class="cal-head">
      <b>{{ rangeLabel }}</b>
    </div>
    <div class="cal-cells">
      <button v-for="(dt, i) in days" :key="i" :disabled="isPast(dt)"
              :class="{ sel: isSel(dt), avail: !isPast(dt) }" @click="pickDate(dt)">
        <span class="dow">{{ AR_DAYS[dt.getDay()].slice(0, 3) }}</span><span class="dnum">{{ dt.getDate() }}</span>
      </button>
    </div>
    <div class="cal-legend"><span><i style="background:var(--green)"></i> تتوفر مواعيد</span><span><i style="background:#D8D0BC"></i> غير متاح</span></div>
  </div>

  <div v-if="!state.date" class="empty-hint" style="margin-top:10px"><b>ابدأ باختيار التاريخ</b>اختر يومًا من التقويم لعرض الأوقات المتاحة</div>

  <template v-else>
    <div class="date-line">📅 {{ dateTitle }}</div>

    <div class="card detail-card time-start-card" style="margin-top:14px">
      <h4>وقت بداية الجلسة <small style="font-weight:400;color:var(--mute)">سننسق باقي الأقسام بعده</small></h4>

      <!-- بطاقة الخدمة المختارة مخفية مؤقتًا من جزء وقت بداية الجلسة. -->
      <div v-if="false && firstGroup" class="time-start-service" :style="groupStyle(firstGroup)">
        <span class="cat-ico"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(firstGroup.icon)"></svg></span>
        <span class="time-start-copy">
          <b>{{ firstGroup.name }}</b>
          <small>{{ groupServicesLabel(firstGroup) }} - {{ groupServiceNames(firstGroup) }}</small>
        </span>
      </div>

      <div class="periods booking-periods">
        <button class="period" :class="{ sel: period === 'all' }" @click="period = 'all'">🗓️ كل اليوم</button>
        <button class="period" :class="{ sel: period === 'am' }" @click="period = 'am'">☀️ صباحًا</button>
        <button class="period" :class="{ sel: period === 'pm' }" @click="period = 'pm'">🌇 مساءً</button>
      </div>
      <div class="available-times-title">الأوقات المتاحة</div>

      <div v-if="firstServiceLoading || (firstService && !Array.isArray(slotsByService[firstService.id]))" class="empty-hint">
        <Skeleton height="44px" border-radius="8px" />
      </div>
      <div v-else-if="!initialSlots.length" class="empty-hint">لا توجد أوقات متاحة لهذا اليوم</div>
      <div v-else-if="!visibleInitialSlots.length" class="empty-hint">لا توجد أوقات في هذه الفترة</div>
      <div v-else class="slots initial-slots">
        <button v-for="t in visibleInitialSlots" :key="t" class="slot" :class="{ sel: firstServiceTime === t }" @click="chooseInitialTime(t)">
          {{ fmtTimeStr(t) }}
          <small v-if="quietBonus(t)" data-no-i18n style="display:block;color:#26714d;font-size:11px;white-space:normal">{{ quietLanguage.lang === 'en' ? `Quiet hours: +${quietBonus(t)} points on completion` : `وقت هدوء: +${quietBonus(t)} نقطة عند إكمال الحجز` }}</small>
        </button>
      </div>

    </div>

    <div v-if="firstServiceTime" class="session-plan">
      <h4>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
        خطة تنفيذ الجلسة
      </h4>
      <div class="plan-track">
        <template v-for="item in sessionPlan" :key="item.key">
          <div v-if="item.type === 'gap'" class="plan-gap">
            <span>فاصل انتظار</span>
            <b>{{ fmtTimeStr(item.start) }} - {{ fmtTimeStr(item.end) }}</b>
            <small>{{ fmtDur(item.duration) }}</small>
          </div>
          <div v-else class="plan-item plan-item--group" :class="{ pending: item.pending }" :style="item.style">
            <span class="plan-icon"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(item.icon)"></svg></span>
            <b>{{ item.name }}</b>
            <small>مع {{ item.employee?.name || '-' }}</small>
            <span class="t">{{ item.pending ? 'بانتظار وقت متاح' : `${fmtTimeStr(item.start)} - ${fmtTimeStr(item.end)}` }}</span>
            <small>{{ groupServicesLabel(item.group) }} · {{ fmtDur(item.duration) }}</small>
          </div>
        </template>
      </div>

      <div v-if="schedulingLoading" class="plan-note plan-note--pending">جاري تنسيق باقي الأقسام حسب الأوقات المتاحة.</div>
      <div v-else-if="unscheduledService" class="plan-note plan-note--warn">لم نجد وقتًا متاحًا لـ {{ unscheduledService.name }} بعد هذا الموعد. اختر بداية أبكر أو يومًا آخر.</div>
      <div v-else class="plan-note">تم تنسيق الجلسة تلقائيًا، والنهاية المتوقعة {{ fmtTimeStr(sessionEndTime) }}.</div>
    </div>
  </template>
</template>
