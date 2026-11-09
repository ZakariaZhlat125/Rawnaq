<script setup>
import { reactive, computed, watch, onMounted } from 'vue'
import { useBooking } from '@/composables/useBooking'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { fetchStaff } from '@/services/bookingApi'
import { categoryAccent, categoryIconKey, categoryIconPath } from '@/utils/giftIcons'
import Skeleton from '@/components/common/SkeletonLoader.vue'

const { state, selSvcs, setEmployee } = useBooking()
const { current } = useServiceLocation()

const staffByService = reactive({})   // serviceId -> array of {id, name}
const loadingByService = reactive({})

const AUTO_LIST = [
  'توفير الوقت',
  'أفضل تقييم متاح',
  'تنفيذ خدمات القسم بنفس الموظف',
  'منع أي تعارض في المواعيد',
]
const MANUAL_LIST = [
  'اختر موظفًا واحدًا لكل قسم',
  'الموظف المختار ينفذ كل خدمات القسم',
  'تحكم كامل في فريق جلستك',
]

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

function employeeName(u) {
  return [u.first_name, u.last_name].filter(Boolean).join(' ') || u.username || 'موظف'
}

function groupStyle(group) {
  return { '--acc': categoryAccent(group.icon) }
}

function groupServicesLabel(group) {
  return `${group.services.length} ${group.services.length === 1 ? 'خدمة' : 'خدمات'}`
}

function groupServiceNames(group) {
  return group.services.map(s => s.name).join('، ')
}

function groupStaff(group) {
  const lists = group.services.map(s => staffByService[s.id])
  if (!lists.length || lists.some(list => !Array.isArray(list))) return []

  const [first, ...rest] = lists
  const shared = first.filter(employee =>
    rest.every(list => list.some(item => String(item.id) === String(employee.id)))
  )
  return [...new Map(shared.map(employee => [String(employee.id), employee])).values()]
}

function isGroupLoading(group) {
  return group.services.some(s => loadingByService[s.id] || !Array.isArray(staffByService[s.id]))
}

function groupSelectedEmployee(group) {
  const selected = group.services.map(s => state.emp[s.id]).filter(Boolean)
  if (selected.length !== group.services.length) return null

  const first = selected[0]
  return selected.every(employee => String(employee.id) === String(first.id)) ? first : null
}

function isGroupEmployeeSelected(group, employee) {
  return String(groupSelectedEmployee(group)?.id ?? '') === String(employee.id ?? '')
}

function firstGroupServiceIndex(group) {
  const indexes = group.services
    .map(service => selSvcs.value.findIndex(s => String(s.id) === String(service.id)))
    .filter(index => index >= 0)
  return indexes.length ? Math.min(...indexes) : -1
}

function clearTimesFromGroup(group) {
  const index = firstGroupServiceIndex(group)
  if (index === -1) return
  selSvcs.value.slice(index).forEach(s => { delete state.time[s.id] })
}

function applyEmployeeToGroup(group, employee, { clearTimes = true } = {}) {
  group.services.forEach(service => setEmployee(service.id, employee))
  if (clearTimes) clearTimesFromGroup(group)
}

function assignAutoStaff() {
  if (state.mode !== 'auto') return

  serviceGroups.value.forEach(group => {
    const list = groupStaff(group)
    if (!list.length) return

    group.services.forEach(service => {
      state.staffOptions[service.id] = staffByService[service.id] || []
      setEmployee(service.id, { id: null, name: 'اختيار تلقائي' })
    })
  })
}

function chooseMode(mode) {
  const modeChanged = state.mode !== mode
  state.mode = mode
  if (modeChanged) {
    state.time = {}
    state.emp = {}
  }
  assignAutoStaff()
}

function selectGroupEmployee(group, employee) {
  state.mode = 'manual'
  applyEmployeeToGroup(group, employee)
}

async function loadStaff(service) {
  if (staffByService[service.id]) return
  loadingByService[service.id] = true
  try {
    const branchId = current.value?.home ? 0 : current.value?.id
    const rows = await fetchStaff({ branchId, serviceId: service.id })
    const mapped = (Array.isArray(rows) ? rows : []).map(u => ({ id: u.id, name: employeeName(u) }))
    staffByService[service.id] = [...new Map(mapped.map(employee => [String(employee.id), employee])).values()]
    state.staffOptions[service.id] = staffByService[service.id]
    assignAutoStaff()
  } catch {
    staffByService[service.id] = []
  } finally {
    loadingByService[service.id] = false
  }
}

function loadAll() {
  selSvcs.value.forEach(loadStaff)
}

onMounted(loadAll)
watch(() => selSvcs.value.map(s => s.id), loadAll)
watch(() => state.mode, assignAutoStaff)
</script>

<template>
  <div class="panel-head">
    <h1>اختر الموظف</h1>
    <p>اختر موظفًا واحدًا لكل قسم، وسيتم تطبيقه على كل الخدمات المختارة داخل هذا القسم</p>
  </div>

  <div class="mode-grid">
    <div class="mode dark" role="button" tabindex="0" :aria-pressed="state.mode === 'auto'" :class="{ sel: state.mode === 'auto' }" @click="chooseMode('auto')" @keydown.enter="chooseMode('auto')" @keydown.space.prevent="chooseMode('auto')">
      <span class="chk"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span class="mi"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m13 2-9 12h7l-1 8 10-13h-7l0-7Z"/></svg></span>
      <h3>اختيار تلقائي</h3><div class="sub-t">سنختار لك أفضل فريق متاح</div>
      <span class="flag">★ موصى به</span>
      <ul>
        <li v-for="(x, i) in AUTO_LIST" :key="i"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg> {{ x }}</li>
      </ul>
    </div>
    <div class="mode" role="button" tabindex="0" :aria-pressed="state.mode === 'manual'" :class="{ sel: state.mode === 'manual' }" @click="chooseMode('manual')" @keydown.enter="chooseMode('manual')" @keydown.space.prevent="chooseMode('manual')">
      <span class="chk"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span class="mi"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
      <h3>اختيار موظف محدد</h3><div class="sub-t">اختر موظفًا لكل قسم</div>
      <span class="flag">تحكم كامل</span>
      <ul>
        <li v-for="(x, i) in MANUAL_LIST" :key="i"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg> {{ x }}</li>
      </ul>
    </div>
  </div>

  <div v-if="state.mode === 'manual'" class="emp-section emp-section--groups">
    <div v-for="group in serviceGroups" :key="group.id" class="emp-row emp-row--group" :style="groupStyle(group)">
      <div class="emp-cat emp-cat--group">
        <span class="cat-ico"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(group.icon)"></svg></span>
        <span class="emp-cat__info">
          <b>{{ group.name }}</b>
          <small>{{ groupServicesLabel(group) }}</small>
          <span class="emp-cat__services">{{ groupServiceNames(group) }}</span>
        </span>
      </div>
      <div v-if="isGroupLoading(group)" class="empty-hint">
        <Skeleton height="44px" border-radius="8px" />
      </div>
      <div v-else-if="!groupStaff(group).length" class="empty-hint">لا يوجد موظف متاح يمكنه تنفيذ كل خدمات هذا القسم</div>
      <div v-else class="emp-list emp-list--groups">
        <button type="button" class="emp any" @click.stop="selectGroupEmployee(group, groupStaff(group)[0])">
          <span class="av" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <b>أي موظف</b>
          <span class="rate">الأنسب تلقائيًا</span>
        </button>
        <button v-for="e in groupStaff(group)" :key="e.id" type="button" class="emp" :class="{ sel: isGroupEmployeeSelected(group, e) }" @click.stop="selectGroupEmployee(group, e)">
          <span class="chk"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
          <span class="av" aria-hidden="true"></span>
          <b>{{ e.name }}</b>
        </button>
      </div>
    </div>
  </div>

  <div v-if="state.mode === 'auto'" class="j-note card" style="display:flex;gap:16px;align-items:center;padding:20px 24px;border-color:var(--line)">
    <span style="flex:none;width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--gold-bright),var(--gold));color:var(--ink)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg></span>
    <p style="font-size:13.5px;color:#5c5442;line-height:1.9"><b style="color:var(--ink)">تم اختيار موظف واحد مناسب لكل قسم تلقائيًا.</b> سيتم تنفيذ كل خدمات القسم بنفس الموظف، وتقدر تشوف الأوقات في الخطوة الجاية.</p>
  </div>
</template>
