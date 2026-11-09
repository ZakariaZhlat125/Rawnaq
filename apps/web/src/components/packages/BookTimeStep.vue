<script setup>
/*
  اختيار الوقت والتاريخ — شريط 12 يوم + مواعيد حقيقية من الباك إند.
  الموظف بيتحدد تلقائيًا (أول موظف متاح في فرع الباقة) بدون خطوة منفصلة،
  بنفس فكرة الاختيار التلقائي في حجز الخدمات.
*/
import { ref, computed, onMounted, watch } from 'vue'
import { AR_DAYS, AR_MONTHS } from '@/data/packages'
import { usePackages, fmtTime, fmtDate } from '@/composables/usePackages'
import { fetchStaff, fetchAvailableTimes } from '@/services/bookingApi'
import Skeleton from '@/components/common/SkeletonLoader.vue'
import { useQuietHours } from '@/composables/useQuietHours'
import { useLanguage } from '@/composables/useLanguage'
const { bonus: quietPoints } = useQuietHours()
const { state: quietLanguage } = useLanguage()
const quietBonus = time => quietPoints(B.branch, B.dayIdx == null ? '' : toDateKey(days[B.dayIdx]), time)

const { state, pkgOf, bkDays } = usePackages()

const B = state.bk
const days = bkDays()
const p = computed(() => pkgOf(B.pkg))

const loadingEmployee = ref(true)
const slots = ref([])
const loadingSlots = ref(false)

function toDateKey(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

onMounted(async () => {
  if (B.employee) { loadingEmployee.value = false; return }
  try {
    const rows = await fetchStaff({ branchId: B.branch, serviceId: 0 })
    const first = (Array.isArray(rows) ? rows : [])[0]
    if (first) {
      B.employee = { id: first.id, name: [first.first_name, first.last_name].filter(Boolean).join(' ') || 'موظف' }
    }
  } catch {
    B.employee = null
  } finally {
    loadingEmployee.value = false
  }
})

async function loadSlots() {
  if (B.dayIdx == null || !B.employee) { slots.value = []; return }
  loadingSlots.value = true
  try {
    const times = await fetchAvailableTimes({ date: toDateKey(days[B.dayIdx]), staffId: B.employee.id, durationMin: p.value?.dur })
    slots.value = Array.isArray(times) ? times : []
  } catch {
    slots.value = []
  } finally {
    loadingSlots.value = false
  }
}

watch(() => [B.dayIdx, B.employee?.id], loadSlots)

/* تصفية المواعيد حسب الفترة */
const filteredSlots = computed(() =>
  slots.value.filter(t => {
    const h = Number(t.split(':')[0])
    return B.period === 'all'
      || (B.period === 'am' && h < 12)
      || (B.period === 'pm' && h >= 12 && h < 17)
      || (B.period === 'eve' && h >= 17)
  })
)

const slotTag = i => (i === 2 ? 'الأكثر طلبًا' : (i === filteredSlots.value.length - 1 ? 'آخر موعد' : ''))

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

  <div v-if="loadingEmployee" class="card" style="padding:20px">
    <Skeleton height="72px" border-radius="12px" />
  </div>

  <div v-else-if="B.dayIdx != null" class="card" style="padding:20px">
    <h4 :style="H4B">🕐 {{ fmtDate(days[B.dayIdx]) }}</h4>
    <div class="periods">
      <button v-for="x in PERIODS" :key="x[0]" class="period" :class="{ sel: B.period === x[0] }" :data-bp="x[0]" @click="B.period = x[0]">{{ x[2] }} {{ x[1] }}</button>
    </div>
    <div v-if="loadingSlots" :style="EMPTY_STYLE"><Skeleton height="44px" border-radius="10px" /></div>
    <div v-else class="slots">
      <template v-if="filteredSlots.length">
        <button v-for="(t, i) in filteredSlots" :key="t" class="slot" :class="{ sel: B.time === t }" :data-bt="t"
                :style="`animation-delay:${Math.min(i * 0.03, 0.4)}s`" @click="B.time = t"
        ><span v-if="slotTag(i)" class="tag">{{ slotTag(i) }}</span><template v-else>&#32;</template>{{ fmtTime(t) }}<small v-if="quietBonus(t)" data-no-i18n style="display:block;color:#26714d;font-size:11px;white-space:normal">{{ quietLanguage.lang === 'en' ? `Quiet hours: +${quietBonus(t)} points on completion` : `وقت هدوء: +${quietBonus(t)} نقطة عند إكمال الحجز` }}</small></button>
      </template>
      <div v-else :style="EMPTY_STYLE">لا توجد أوقات متاحة في هذه الفترة</div>
    </div>
  </div>

  <div v-else class="card" style="padding:40px;text-align:center;color:var(--mute);font-size:13.5px"><b style="display:block;font-family:var(--font-d);font-size:16px;color:var(--ink);margin-bottom:6px">ابدأ باختيار اليوم</b>اختر يومًا من الشريط أعلاه لعرض الأوقات المتاحة</div>
</template>
