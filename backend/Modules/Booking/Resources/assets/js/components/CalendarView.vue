<template>
  <div class="pagination-controls">
    <div class="calendar-date-picker">
      <label class="calendar-date-picker__label" for="calendarDatePicker">تاريخ التقويم</label>
      <div class="calendar-date-picker__control">
        <i class="fa-regular fa-calendar-days"></i>
        <FlatPickr id="calendarDatePicker" v-model="selectedCalendarDate" class="form-control calendar-date-picker__input" :config="calendarDatePickerConfig" @on-change="handleCalendarDateChange" />
      </div>
    </div>
    <div class="calendar-view-switcher" role="group" aria-label="خيارات عرض التقويم">
      <button v-for="viewOption in calendarViewOptions" :key="viewOption.key" type="button" class="calendar-view-switcher__button" :class="{ active: selectedCalendarView === viewOption.key }" @click="changeCalendarView(viewOption.key)">
        <i :class="viewOption.icon"></i>
        <span>{{ viewOption.label }}</span>
      </button>
    </div>
    <nav :aria-label="$t('Pagination')">
      <ul class="pagination justify-content-end">
        <div v-if="!canReorder" class="dropdown ms-2">
          <!-- إضافة مسافة بسيطة من زر الريفرش -->
          <button class="btn btn-secondary dropdown-toggle" type="button" id="employeeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            {{ selectedEmployeeName || 'All Employees' }}
          </button>
          <ul class="dropdown-menu" aria-labelledby="employeeDropdown">
            <li @click="filterByEmployee(null)">
              <a class="dropdown-item" href="#">{{ $t("All Employees") }}</a>
            </li>
            <li v-for="employee in calendarEmployeeOptions" :key="employee.id" @click="filterByEmployee(employee)">
              <a class="dropdown-item" href="#">{{ employee.title }}</a>
            </li>
          </ul>
        </div>

        <li class="px-2">
          <button id="refresh" class="btn bg-primary rounded" data-bs-toggle="tooltip" title="refresh" @click="refreshPage">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M21.4799 12.2424C21.7557 12.2326 21.9886 12.4482 21.9852 12.7241C21.9595 14.8075 21.2975 16.8392 20.0799 18.5506C18.7652 20.3986 16.8748 21.7718 14.6964 22.4612C12.518 23.1505 10.1711 23.1183 8.01299 22.3694C5.85488 21.6205 4.00382 20.196 2.74167 18.3126C1.47952 16.4293 0.875433 14.1905 1.02139 11.937C1.16734 9.68346 2.05534 7.53876 3.55018 5.82945C5.04501 4.12014 7.06478 2.93987 9.30193 2.46835C11.5391 1.99683 13.8711 2.2599 15.9428 3.2175L16.7558 1.91838C16.9822 1.55679 17.5282 1.62643 17.6565 2.03324L18.8635 5.85986C18.945 6.11851 18.8055 6.39505 18.549 6.48314L14.6564 7.82007C14.2314 7.96603 13.8445 7.52091 14.0483 7.12042L14.6828 5.87345C13.1977 5.18699 11.526 4.9984 9.92231 5.33642C8.31859 5.67443 6.8707 6.52052 5.79911 7.74586C4.72753 8.97119 4.09095 10.5086 3.98633 12.1241C3.8817 13.7395 4.31474 15.3445 5.21953 16.6945C6.12431 18.0446 7.45126 19.0658 8.99832 19.6027C10.5454 20.1395 12.2278 20.1626 13.7894 19.6684C15.351 19.1743 16.7062 18.1899 17.6486 16.8652C18.4937 15.6773 18.9654 14.2742 19.0113 12.8307C19.0201 12.5545 19.2341 12.3223 19.5103 12.3125L21.4799 12.2424Z" fill="#ffffff"></path>
              <path d="M20.0941 18.5594C21.3117 16.848 21.9736 14.8163 21.9993 12.7329C22.0027 12.4569 21.7699 12.2413 21.4941 12.2512L19.5244 12.3213C19.2482 12.3311 19.0342 12.5633 19.0254 12.8395C18.9796 14.283 18.5078 15.6861 17.6628 16.8739C16.7203 18.1986 15.3651 19.183 13.8035 19.6772C12.2419 20.1714 10.5595 20.1483 9.01246 19.6114C7.4654 19.0746 6.13845 18.0534 5.23367 16.7033C4.66562 15.8557 4.28352 14.9076 4.10367 13.9196C4.00935 18.0934 6.49194 21.37 10.008 22.6416C10.697 22.8908 11.4336 22.9852 12.1652 22.9465C13.075 22.8983 13.8508 22.742 14.7105 22.4699C16.8889 21.7805 18.7794 20.4073 20.0941 18.5594Z" fill="#ffffff"></path>
            </svg>
          </button>
        </li>
        <li v-if="canReorder" class="px-2">
          <button type="button" class="btn btn-outline-primary rounded" :disabled="!!selectedEmployeeId" :title="selectedEmployeeId ? 'اختر كل الموظفين لترتيب التقويم' : 'ترتيب الموظفين'" @click="toggleOrderPanel">
            <i class="fa-solid fa-arrow-up-wide-short"></i>
            <span class="ms-1">ترتيب الموظفين</span>
          </button>
        </li>
        <!-- Page Number Before Current -->
        <li v-if="currentPage > 1" class="page-item">
          <button @click="goToPage(currentPage - 1)" class="page-link">{{ currentPage - 1 }}</button>
        </li>
        <!-- Page Number After Current -->
        <li v-if="currentPage < totalPages" class="page-item">
          <button @click="goToPage(currentPage + 1)" class="page-link">{{ currentPage + 1 }}</button>
        </li>
      </ul>
    </nav>
  </div>
  <div v-if="canReorder" class="calendar-employee-filter">
    <button type="button" class="calendar-employee-filter__chip" :class="{ active: isAllCalendarEmployeesSelected }" @click="showAllCalendarEmployees">الكل</button>
    <button v-for="employee in calendarEmployeeOptions" :key="employee.id" type="button" class="calendar-employee-filter__chip" :class="{ active: isCalendarEmployeeSelected(employee.id) }" @click="toggleCalendarEmployee(employee.id)">
      {{ employee.title }}
    </button>
  </div>
  <div v-if="canReorder && orderPanelOpen" class="staff-order-panel">
    <div class="staff-order-panel__header">
      <div>
        <h6 class="mb-1">ترتيب الموظفين في التقويم</h6>
        <small class="text-muted">اسحب الاسم أو استخدم الأسهم ثم احفظ الترتيب.</small>
      </div>
      <button type="button" class="btn btn-sm btn-light" title="إغلاق" @click="orderPanelOpen = false">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <ul class="staff-order-list">
      <li v-for="(employee, index) in employeeOrderList" :key="employee.id" class="staff-order-item" draggable="true" @dragstart="handleOrderDragStart(index)" @dragover.prevent @drop="handleOrderDrop(index)">
        <span class="staff-order-item__handle" title="اسحب لترتيب الموظف">
          <i class="fa-solid fa-grip-vertical"></i>
        </span>
        <div class="staff-order-item__content">
          <div>
            <span class="staff-order-item__name">{{ employee.title }}</span>
            <small v-if="employee.branch_name" class="staff-order-item__meta d-block">{{ employee.branch_name }}</small>
          </div>
          <div class="staff-order-item__controls">
            <label class="staff-order-item__visibility form-check form-switch" :title="employee.is_visible ? 'إخفاء من التقويم' : 'إظهار في التقويم'">
              <input class="form-check-input" type="checkbox" :checked="employee.is_visible" @change="toggleEmployeeVisibility(index)" />
              <span class="staff-order-item__visibility-text">{{ employee.is_visible ? 'ظاهر في التقويم' : 'مخفي من التقويم' }}</span>
            </label>
            <div class="staff-order-item__actions">
              <button type="button" class="btn btn-sm btn-light" :disabled="index === 0" title="رفع" @click="moveEmployeeOrder(index, -1)">
                <i class="fa-solid fa-chevron-up"></i>
              </button>
              <button type="button" class="btn btn-sm btn-light" :disabled="index === employeeOrderList.length - 1" title="خفض" @click="moveEmployeeOrder(index, 1)">
                <i class="fa-solid fa-chevron-down"></i>
              </button>
            </div>
          </div>
        </div>
      </li>
    </ul>
    <div class="staff-order-panel__footer">
      <span v-if="orderSaveState === 'saved'" class="text-success">تم حفظ الترتيب</span>
      <span v-if="orderSaveState === 'error'" class="text-danger">تعذر حفظ الترتيب</span>
      <button type="button" class="btn btn-primary" :disabled="orderSaveState === 'saving'" @click="saveEmployeeOrder">
        <i class="fa-solid fa-floppy-disk me-1"></i>
        {{ orderSaveState === 'saving' ? 'جار الحفظ...' : 'حفظ الترتيب' }}
      </button>
    </div>
  </div>
  <div v-if="selectedCalendarView === 'list'" class="booking-list-view">
    <div v-for="bookingEvent in bookingListEvents" :key="`${bookingEvent.id}-${bookingEvent.start}-${bookingEvent.resourceId}`" class="booking-list-card" :style="{ borderInlineStartColor: getEventCategoryColor(bookingEvent) }" @click="openBookingListEvent(bookingEvent)">
      <div class="booking-list-card__time">
        <span>{{ formatEventTime(bookingEvent.start) }}</span>
        <small>{{ formatEventDate(bookingEvent.start) }}</small>
      </div>
      <div class="booking-list-card__body">
        <div class="booking-list-card__title-row">
          <h6>{{ bookingEvent.title }}</h6>
          <span class="booking-list-card__status">{{ bookingEvent.extendedProps?.status_title || bookingEvent.extendedProps?.status }}</span>
        </div>
        <div class="booking-list-card__details">
          <span><i class="fa-regular fa-user"></i>{{ bookingEvent.extendedProps?.customer_name || 'عميل غير محدد' }}</span>
          <span><i class="fa-solid fa-user-tie"></i>{{ bookingEvent.extendedProps?.employee_name || getEventEmployeeName(bookingEvent.resourceId) }}</span>
          <span v-if="bookingEvent.extendedProps?.created_by_name"><i class="fa-solid fa-user-shield"></i>حجز بواسطة: {{ bookingEvent.extendedProps.created_by_name }}</span>
          <span><i class="fa-regular fa-clock"></i>{{ bookingEvent.extendedProps?.duration || getEventDuration(bookingEvent) }} دقيقة</span>
          <span v-if="bookingEvent.extendedProps?.branch_name"><i class="fa-solid fa-location-dot"></i>{{ bookingEvent.extendedProps.branch_name }}</span>
        </div>
      </div>
    </div>
    <div v-if="bookingListEvents.length === 0" class="booking-list-empty">لا توجد حجوزات في اليوم المحدد</div>
  </div>
  <div v-show="selectedCalendarView !== 'list'" ref="calenderRef" class="calendar-root"></div>
  <booking-form :booking-type="bookingType" :status-list="bookingStatus" @onSubmit="onSubmitEvent" :booking-data="bookingData"></booking-form>
</template>
<script setup>
import { computed, reactive, ref, onMounted, onUnmounted, nextTick } from 'vue'
import { createRequest } from '@/helpers/utilities'

import { createCalendar, destroyCalendar, DayGrid, List, TimeGrid, ResourceTimeGrid, Interaction } from '@event-calendar/core'
import FlatPickr from 'vue-flatpickr-component'
import { Arabic } from 'flatpickr/dist/l10n/ar.js'

import BookingForm from './BookingForm.vue'
import { EMPLOYEE_ORDER_URL, INDEX_URL } from '../constant/booking'
import * as moment from 'moment'
const totalEmployees = ref(0)
const props = defineProps({
  status: { type: String, required: true },
  slotDuration: { type: String },
  branchId: { type: [String, Number] },
  canReorder: { type: Boolean, default: false },
  date: new Date()
})
const canReorder = props.canReorder
let slotsDurations = '00:15'
if (props.slotDuration !== '') {
  slotsDurations = props.slotDuration
}
const bookingStatus = ref(JSON.parse(props.status))
const calenderRef = ref(null)
const calenderInit = ref(null)
const selectedCalendarDate = ref(moment(props.date).format('YYYY-MM-DD'))
const calendarDatePickerConfig = {
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: 'l d-m-Y',
  locale: Arabic
}
const selectedCalendarView = ref('day')
const calendarEventList = ref([])
const calendarViewOptions = [
  { key: 'day', label: 'اليوم', icon: 'fa-solid fa-calendar-day' },
  { key: 'week', label: 'الأسبوع', icon: 'fa-solid fa-calendar-week' },
  { key: 'month', label: 'الشهر', icon: 'fa-regular fa-calendar' },
  { key: 'list', label: 'قائمة', icon: 'fa-solid fa-list-ul' }
]
const calendarViewMap = {
  day: 'resourceTimeGridDay',
  week: 'resourceTimeGridWeek',
  month: 'dayGridMonth',
  list: 'resourceTimeGridDay'
}
const EMPLOYEE_LIST = ref([]) // دي هتتملي من API (employees)
const selectedEmployeeId = ref(null) // ID الموظف المختار
const selectedEmployeeName = ref(null) // اسم الموظف المختار

const ORDER_EMPLOYEE_LIST = ref([])
const authPermissions = Array.isArray(window.auth_permissions) ? window.auth_permissions : []
const OVERRIDE_UNAVAILABLE_SLOT_PERMISSION = 'booking_override_unavailable_slots'
const canOverrideUnavailableSlotBooking = computed(() => authPermissions.includes(OVERRIDE_UNAVAILABLE_SLOT_PERMISSION))
const selectedCalendarEmployeeIds = ref([])
const employeeOrderList = ref([])
const orderPanelOpen = ref(false)
const orderSaveState = ref('idle')
const draggedOrderIndex = ref(null)
const employeeAvailability = ref({})
const bookingType = ref('')
const bookingData = reactive({
  id: 0,
  start_date_time: null,
  employee_id: null,
  employee_name: null,
  branch_id: props.branchId
})
const resourceWidths = ref([])
const resizeState = reactive({
  active: false,
  index: -1,
  startX: 0,
  startWidth: 0
})
const MIN_RESOURCE_WIDTH = 120
const MAX_RESOURCE_WIDTH = 1200
const DEFAULT_RESOURCE_WIDTH = 180
const READABLE_RESOURCE_WIDTH = 220
const RESIZE_HANDLE_SIZE = 6
let resizeHandlersAttached = false
let detachResizeHandlers = null
let detachTopScroll = null
let topScrollState = null
let fixedTimeColumnFrame = null
let lastResourceSizingWidth = 0
let calendarFetchController = null
let calendarFetchTimeoutId = null
let calendarRetryTimeoutId = null
let calendarFetchRequestId = 0
let calendarFetchErrorShown = false
let resourceSizingFrame = null
let calendarColorObserver = null
const CALENDAR_FETCH_TIMEOUT = 15000
const CALENDAR_RETRY_DELAY = 1200
const eventColorRegistry = new WeakMap()

const getHorizontalScrollTargets = () => {
  if (!calenderRef.value) return []
  return Array.from(calenderRef.value.querySelectorAll('.ec-header, .ec-all-day, .ec-body'))
}

const getFixedTimeColumns = () => {
  if (!calenderRef.value) return []
  return Array.from(calenderRef.value.querySelectorAll('.ec-header > .ec-sidebar, .ec-all-day > .ec-sidebar, .ec-body > .ec-content > .ec-sidebar'))
}

const updateFixedTimeColumns = () => {
  const root = calenderRef.value
  if (!root) return

  const rootRect = root.getBoundingClientRect()
  const direction = getComputedStyle(root).direction || document.dir || 'ltr'
  const isRtl = direction === 'rtl'

  getFixedTimeColumns().forEach((column) => {
    column.style.transform = 'none'
    const columnRect = column.getBoundingClientRect()
    const offset = isRtl ? rootRect.right - columnRect.right : rootRect.left - columnRect.left
    column.style.transform = `translateX(${offset}px)`
  })
}

const scheduleFixedTimeColumnsUpdate = () => {
  if (fixedTimeColumnFrame) {
    cancelAnimationFrame(fixedTimeColumnFrame)
  }

  fixedTimeColumnFrame = requestAnimationFrame(() => {
    fixedTimeColumnFrame = null
    updateFixedTimeColumns()
  })
}

const resetFixedTimeColumns = () => {
  if (fixedTimeColumnFrame) {
    cancelAnimationFrame(fixedTimeColumnFrame)
    fixedTimeColumnFrame = null
  }
  getFixedTimeColumns().forEach((column) => {
    column.style.transform = ''
  })
}

const clearCalendarFetchTimeout = () => {
  if (calendarFetchTimeoutId) {
    window.clearTimeout(calendarFetchTimeoutId)
    calendarFetchTimeoutId = null
  }
}

const clearCalendarRetryTimeout = () => {
  if (calendarRetryTimeoutId) {
    window.clearTimeout(calendarRetryTimeoutId)
    calendarRetryTimeoutId = null
  }
}

const notifyCalendarFetchError = (message) => {
  if (calendarFetchErrorShown) {
    return
  }

  calendarFetchErrorShown = true

  if (window.errorSnackbar) {
    window.errorSnackbar(message)
    return
  }

  window.alert(message)
}

const scheduleCalendarRetry = () => {
  if (calendarRetryTimeoutId || !calenderInit.value) {
    return
  }

  calendarRetryTimeoutId = window.setTimeout(() => {
    calendarRetryTimeoutId = null
    reloadCalendarEvents()
  }, CALENDAR_RETRY_DELAY)
}

const applyCalendarResponse = (response = {}) => {
  const employees = response.employees || []
  const data = response.data || []

  totalEmployees.value = response.total_count || employees.length || 0
  EMPLOYEE_LIST.value = employees
  ORDER_EMPLOYEE_LIST.value = (response.order_employees || employees).map((employee) => normalizeOrderEmployee(employee))
  selectedCalendarEmployeeIds.value = selectedCalendarEmployeeIds.value.filter((id) => ORDER_EMPLOYEE_LIST.value.some((employee) => Number(employee.id) === Number(id) && employee.is_visible !== false))

  if (!orderPanelOpen.value) {
    syncEmployeeOrderList()
  }

  employeeAvailability.value = response.availability || {}
  calendarEventList.value = data
  calenderInit.value?.setOption('resources', employees)
  refreshResourceSizing()

  calendarFetchErrorShown = false
  clearCalendarRetryTimeout()

  return data
}

const fetchCalendarEvents = async (fetchInfo) => {
  const visibleDate = fetchInfo && fetchInfo.start ? fetchInfo.start : selectedCalendarDate.value
  if (selectedCalendarView.value !== 'list') {
    selectedCalendarDate.value = moment(visibleDate).format('YYYY-MM-DD')
  }

  const dateRange = getFetchInfoRange(fetchInfo)
  const params = {
    employee_id: props.canReorder ? null : selectedEmployeeId.value,
    employee_ids: props.canReorder && selectedCalendarEmployeeIds.value.length ? selectedCalendarEmployeeIds.value : null,
    branch_id: props.branchId,
    date: selectedCalendarDate.value,
    date_start: dateRange.start,
    date_end: dateRange.end
  }

  if (calendarFetchController) {
    calendarFetchController.abort()
  }

  clearCalendarFetchTimeout()

  const requestId = ++calendarFetchRequestId
  const controller = new AbortController()
  let didTimeout = false
  calendarFetchController = controller
  calendarFetchTimeoutId = window.setTimeout(() => {
    didTimeout = true
    controller.abort()
  }, CALENDAR_FETCH_TIMEOUT)

  try {
    const response = await createRequest(
      INDEX_URL(params),
      {},
      {},
      {
        signal: controller.signal,
        cache: 'no-store'
      }
    )

    if (requestId !== calendarFetchRequestId) {
      return calendarEventList.value
    }

    if (!response || response.status === false) {
      throw new Error(response?.message || 'تعذر تحميل بيانات الكالندر')
    }

    return applyCalendarResponse(response)
  } catch (error) {
    if (requestId !== calendarFetchRequestId) {
      return calendarEventList.value
    }

    if (error?.name === 'AbortError' && !didTimeout) {
      return calendarEventList.value
    }

    notifyCalendarFetchError(didTimeout ? 'تحميل الكالندر استغرق وقتًا طويلًا. جارٍ إعادة المحاولة تلقائيًا.' : 'حصلت مشكلة أثناء تحميل الكالندر. جارٍ إعادة المحاولة.')
    scheduleCalendarRetry()

    return calendarEventList.value
  } finally {
    if (calendarFetchController === controller) {
      calendarFetchController = null
    }
    clearCalendarFetchTimeout()
  }
}

const refreshPage = () => {
  if (calenderInit.value) {
    reloadCalendarEvents()
    return
  }

  window.location.reload()
}

const getCalendarRange = (dateValue = selectedCalendarDate.value, viewKey = selectedCalendarView.value) => {
  const baseDate = moment(dateValue)

  if (viewKey === 'week') {
    return {
      start: baseDate.clone().startOf('week').format('YYYY-MM-DD'),
      end: baseDate.clone().endOf('week').format('YYYY-MM-DD')
    }
  }

  if (viewKey === 'month') {
    return {
      start: baseDate.clone().startOf('month').format('YYYY-MM-DD'),
      end: baseDate.clone().endOf('month').format('YYYY-MM-DD')
    }
  }

  return {
    start: baseDate.format('YYYY-MM-DD'),
    end: baseDate.format('YYYY-MM-DD')
  }
}

const getFetchInfoRange = (fetchInfo) => {
  if (!fetchInfo?.start || !fetchInfo?.end) {
    return getCalendarRange(selectedCalendarDate.value, selectedCalendarView.value)
  }

  return {
    start: moment(fetchInfo.start).format('YYYY-MM-DD'),
    end: moment(fetchInfo.end).subtract(1, 'day').format('YYYY-MM-DD')
  }
}

const reloadCalendarEvents = () => {
  if (!calenderInit.value) return

  resourceWidths.value = []
  calenderInit.value.setOption('date', selectedCalendarDate.value)
  calenderInit.value.refetchEvents()
  refreshResourceSizing()
}

const handleCalendarDateChange = (selectedDates, dateStr) => {
  const nextDate = dateStr || selectedCalendarDate.value
  if (!nextDate || !calenderInit.value) return

  selectedCalendarDate.value = moment(nextDate).format('YYYY-MM-DD')
  reloadCalendarEvents()
}

const changeCalendarView = (viewKey) => {
  if (!calendarViewMap[viewKey]) return

  selectedCalendarView.value = viewKey
  if (calenderInit.value) {
    calenderInit.value.setOption('view', calendarViewMap[viewKey])
  }
  reloadCalendarEvents()
}

const normalizeOrderEmployee = (employee) => ({
  ...employee,
  is_visible: employee.is_visible !== false
})

const calendarEmployeeOptions = computed(() => {
  return (ORDER_EMPLOYEE_LIST.value.length ? ORDER_EMPLOYEE_LIST.value : EMPLOYEE_LIST.value).map((employee) => normalizeOrderEmployee(employee))
})

const visibleCalendarEmployees = computed(() => {
  return calendarEmployeeOptions.value.filter((employee) => employee.is_visible !== false)
})

const findEmployeeById = (employeeId) => {
  return EMPLOYEE_LIST.value.find((item) => String(item.id) === String(employeeId)) || ORDER_EMPLOYEE_LIST.value.find((item) => String(item.id) === String(employeeId))
}

const bookingListEvents = computed(() => {
  return calendarEventList.value
    .filter((event) => event.display !== 'background')
    .filter((event) => moment(event.start).format('YYYY-MM-DD') === selectedCalendarDate.value)
    .sort((firstEvent, secondEvent) => moment(firstEvent.start).valueOf() - moment(secondEvent.start).valueOf())
})

const formatEventDate = (dateValue) => moment(dateValue).locale('ar').format('dddd YYYY-MM-DD')

const formatEventTime = (dateValue) => moment(dateValue).format('hh:mm A')

const getEventCategoryColor = (event) => {
  if (event?.extendedProps?.status === 'check_in') {
    return event?.extendedProps?.status_color || event?.backgroundColor || event?.color || '#6B7280'
  }

  return event?.extendedProps?.category_color || event?.backgroundColor || event?.color || '#BF9456'
}

const setElementCategoryColor = (element, categoryColor) => {
  if (!element || !categoryColor) return

  element.style.setProperty('background-color', categoryColor, 'important')
  element.style.setProperty('border-color', categoryColor, 'important')
  element.style.setProperty('color', '#FFFFFF', 'important')

  const eventBody = element.querySelector('.ec-event-body')
  if (eventBody) {
    eventBody.style.setProperty('background-color', categoryColor, 'important')
    eventBody.style.setProperty('color', '#FFFFFF', 'important')
  }
}

const applyEventCategoryColor = (element, event) => {
  const categoryColor = getEventCategoryColor(event)
  if (!element || !categoryColor) return

  eventColorRegistry.set(element, categoryColor)
  setElementCategoryColor(element, categoryColor)
}

const reapplyObservedEventColor = (element) => {
  const categoryColor = eventColorRegistry.get(element)
  if (!categoryColor) return

  setElementCategoryColor(element, categoryColor)
}

const reapplyObservedEventColorsWithin = (node) => {
  if (!(node instanceof HTMLElement)) return

  if (node.classList.contains('ec-event')) {
    reapplyObservedEventColor(node)
  }

  node.querySelectorAll('.ec-event').forEach((eventElement) => {
    reapplyObservedEventColor(eventElement)
  })
}

const attachCalendarColorObserver = () => {
  if (!calenderRef.value || typeof MutationObserver === 'undefined') return

  if (calendarColorObserver) {
    calendarColorObserver.disconnect()
  }

  calendarColorObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === 'attributes' && mutation.target instanceof HTMLElement) {
        reapplyObservedEventColorsWithin(mutation.target)
      }

      mutation.addedNodes.forEach((node) => {
        reapplyObservedEventColorsWithin(node)
      })
    })
  })

  calendarColorObserver.observe(calenderRef.value, {
    subtree: true,
    childList: true,
    attributes: true,
    attributeFilter: ['style', 'class']
  })
}

const getEventDuration = (event) => {
  if (!event.start || !event.end) return 0
  return Math.max(0, moment(event.end).diff(moment(event.start), 'minutes'))
}

const getEventEmployeeName = (employeeId) => {
  const employee = findEmployeeById(employeeId)
  return employee?.title || ''
}

const openBookingListEvent = (event) => {
  const employee = findEmployeeById(event.resourceId)
  const updatedInfo = {
    id: event.extendedProps?.booking_id || event.id,
    resource: {
      id: event.resourceId,
      branch_id: event.extendedProps?.branch_id
    },
    employee_name: event.extendedProps?.employee_name || employee?.title,
    date: event.start
  }
  showBookingForm(updatedInfo)
}

const isAllCalendarEmployeesSelected = computed(() => selectedCalendarEmployeeIds.value.length === 0)

const isCalendarEmployeeSelected = (employeeId) => {
  return selectedCalendarEmployeeIds.value.includes(Number(employeeId))
}

const showAllCalendarEmployees = () => {
  selectedCalendarEmployeeIds.value = []
  resourceWidths.value = []
  calenderInit.value?.refetchEvents()
  refreshResourceSizing()
}

const toggleCalendarEmployee = (employeeId) => {
  const normalizedId = Number(employeeId)
  const selected = selectedCalendarEmployeeIds.value

  selectedCalendarEmployeeIds.value = selected.includes(normalizedId) ? selected.filter((id) => id !== normalizedId) : [...selected, normalizedId]

  resourceWidths.value = []
  calenderInit.value?.refetchEvents()
  refreshResourceSizing()
}

const syncEmployeeOrderList = () => {
  employeeOrderList.value = (ORDER_EMPLOYEE_LIST.value.length ? ORDER_EMPLOYEE_LIST.value : EMPLOYEE_LIST.value).map((employee) => normalizeOrderEmployee(employee))
}

const toggleOrderPanel = () => {
  orderPanelOpen.value = !orderPanelOpen.value
  orderSaveState.value = 'idle'
  if (orderPanelOpen.value) {
    syncEmployeeOrderList()
  }
}

const moveEmployeeOrder = (index, direction) => {
  const nextIndex = index + direction
  if (nextIndex < 0 || nextIndex >= employeeOrderList.value.length) return

  const nextOrder = [...employeeOrderList.value]
  const [employee] = nextOrder.splice(index, 1)
  nextOrder.splice(nextIndex, 0, employee)
  employeeOrderList.value = nextOrder
  orderSaveState.value = 'idle'
}

const handleOrderDragStart = (index) => {
  draggedOrderIndex.value = index
}

const handleOrderDrop = (index) => {
  if (draggedOrderIndex.value === null || draggedOrderIndex.value === index) return

  const nextOrder = [...employeeOrderList.value]
  const [employee] = nextOrder.splice(draggedOrderIndex.value, 1)
  nextOrder.splice(index, 0, employee)
  employeeOrderList.value = nextOrder
  draggedOrderIndex.value = null
  orderSaveState.value = 'idle'
}

const toggleEmployeeVisibility = (index) => {
  const employee = employeeOrderList.value[index]
  if (!employee) return

  employeeOrderList.value.splice(index, 1, {
    ...employee,
    is_visible: !employee.is_visible
  })
  orderSaveState.value = 'idle'
}

const saveEmployeeOrder = async () => {
  if (!props.canReorder || employeeOrderList.value.length === 0) return

  orderSaveState.value = 'saving'
  try {
    const response = await createRequest(
      EMPLOYEE_ORDER_URL(),
      {},
      {
        branch_id: props.branchId,
        employees: employeeOrderList.value.map((employee) => ({
          id: employee.id,
          is_visible: employee.is_visible !== false
        }))
      }
    )

    if (response?.status === false) {
      throw new Error(response.message || 'Unable to save employee order')
    }

    orderSaveState.value = 'saved'
    selectedCalendarEmployeeIds.value = selectedCalendarEmployeeIds.value.filter((id) => {
      const employee = employeeOrderList.value.find((item) => Number(item.id) === Number(id))
      return employee?.is_visible !== false
    })
    calenderInit.value.refetchEvents()
    refreshResourceSizing()
  } catch (error) {
    orderSaveState.value = 'error'
    if (window.errorSnackbar) {
      window.errorSnackbar('تعذر حفظ ترتيب الموظفين')
    }
  }
}

const setBooking = (info) => {
  const employeeId = getInfoEmployeeId(info)
  const employee = findEmployeeById(employeeId)
  bookingData.id = info.id || 0
  bookingData.employee_id = employeeId
  bookingData.employee_name = employee?.title || info.employee_name || null
  bookingData.start_date_time = getInfoStartDate(info)
  bookingData.branch_id = resolveBranchId(info)
}

const getInfoStartDate = (info) => {
  return info?.date || info?.start || info?.event?.start || null
}

const getInfoEmployeeId = (info) => {
  return info?.resource?.id || info?.resourceIds?.[0] || info?.event?.resourceIds?.[0] || info?.event?.extendedProps?.employee_id || (selectedCalendarEmployeeIds.value.length === 1 ? selectedCalendarEmployeeIds.value[0] : null) || (EMPLOYEE_LIST.value.length === 1 ? EMPLOYEE_LIST.value[0].id : null) || null
}

const resolveBranchId = (info) => {
  const resourceBranchId = info?.resource?.branch_id || info?.resource?.extendedProps?.branch_id
  if (Number(resourceBranchId) > 0) {
    return resourceBranchId
  }

  const employeeId = getInfoEmployeeId(info)
  const employee = findEmployeeById(employeeId)
  if (Number(employee?.branch_id) > 0) {
    return employee.branch_id
  }

  return props.branchId
}

const slotUnavailableMessage = () => {
  const message = 'اختر موعد يكون الموظف متاح من خلاله'
  if (window.errorSnackbar) {
    window.errorSnackbar(message)
    return
  }

  window.alert(message)
}

const pastDateUnavailableMessage = () => {
  const message = 'لا يمكن إضافة موعد في تاريخ سابق لليوم'
  if (window.errorSnackbar) {
    window.errorSnackbar(message)
    return
  }

  window.alert(message)
}

const isPastCalendarDate = (date) => {
  return date && moment(date).startOf('day').isBefore(moment().startOf('day'))
}

const isWithinRange = (date, range) => {
  const clicked = moment(date)
  const start = moment(range.start)
  const end = moment(range.end)

  return clicked.isSameOrAfter(start) && clicked.isBefore(end)
}

const isWithinBreak = (date, range) => {
  return (range.breaks || []).some((staffBreak) => isWithinRange(date, staffBreak))
}

const isAvailableSlot = (info) => {
  const employeeId = getInfoEmployeeId(info)
  const startDate = getInfoStartDate(info)
  if (!employeeId || !startDate) {
    return false
  }

  const ranges = employeeAvailability.value[employeeId] || employeeAvailability.value[String(employeeId)] || []

  return ranges.some((range) => isWithinRange(startDate, range) && !isWithinBreak(startDate, range))
}

const handleCalendarSlotClick = (info) => {
  if (isPastCalendarDate(getInfoStartDate(info))) {
    pastDateUnavailableMessage()
    return
  }

  if (selectedCalendarView.value === 'month') {
    showBookingForm(info)
    return
  }

  if (!isAvailableSlot(info) && !canOverrideUnavailableSlotBooking.value) {
    slotUnavailableMessage()
    return
  }

  showBookingForm(info)
}

const showBookingForm = async (info, type = 'CALENDER_BOOKING') => {
  bookingType.value = type
  setBooking(info)
  await nextTick()
  const elem = document.getElementById('booking-form')
  if (!elem) return
  const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
  form.show()
  document.querySelector('.offcanvas-backdrop')?.remove()
  updateBodyClass('show')
}

const hideBookingForm = () => {
  const elem = document.getElementById('booking-form')
  const form = bootstrap.Offcanvas.getOrCreateInstance(elem)
  form.hide()
  updateBodyClass('hide')
}

const updateBodyClass = (value = 'hide') => {
  if (value == 'show') {
    document.body.classList.add('calender-view')
  } else {
    document.body.classList.remove('calender-view')
  }
}

const createBooking = () => {
  showBookingForm({}, 'CREATE_BOOKING')
}

const filterByEmployee = (employee) => {
  if (employee === null) {
    selectedEmployeeId.value = null
    selectedEmployeeName.value = 'All Employees'
  } else {
    selectedEmployeeId.value = employee.id
    selectedEmployeeName.value = employee.title
  }

  // إعادة تحميل الـ events بعد الاختيار
  calenderInit.value.refetchEvents()
  refreshResourceSizing()
}

const syncTopHorizontalScroll = () => {
  const root = calenderRef.value
  if (!root) return

  const scrollTargets = getHorizontalScrollTargets()
  if (!scrollTargets.length) return

  if (topScrollState) {
    topScrollState.scrollTargets.forEach((target) => {
      target.removeEventListener('scroll', topScrollState.onTargetScroll)
    })
    topScrollState.scrollTargets = scrollTargets
    topScrollState.scrollTargets.forEach((target) => {
      target.addEventListener('scroll', topScrollState.onTargetScroll)
    })
    updateTopScrollbarWidth()
    scheduleFixedTimeColumnsUpdate()
    return
  }

  const scrollbar = document.createElement('div')
  const spacer = document.createElement('div')
  scrollbar.className = 'booking-calendar-scrollbar'
  spacer.className = 'booking-calendar-scrollbar-spacer'
  scrollbar.appendChild(spacer)
  root.insertBefore(scrollbar, root.firstChild)

  let syncing = false
  const updateSpacer = () => {
    const width = Math.max(...topScrollState.scrollTargets.map((target) => target.scrollWidth))
    spacer.style.width = `${width}px`
    scheduleFixedTimeColumnsUpdate()
  }
  const onTopScroll = () => {
    if (syncing) return
    syncing = true
    topScrollState.scrollTargets.forEach((target) => {
      target.scrollLeft = scrollbar.scrollLeft
    })
    scheduleFixedTimeColumnsUpdate()
    syncing = false
  }
  const onTargetScroll = (event) => {
    if (syncing) return
    syncing = true
    scrollbar.scrollLeft = event.target.scrollLeft
    topScrollState.scrollTargets.forEach((target) => {
      if (target !== event.target) {
        target.scrollLeft = event.target.scrollLeft
      }
    })
    scheduleFixedTimeColumnsUpdate()
    syncing = false
  }

  scrollbar.addEventListener('scroll', onTopScroll)
  scrollTargets.forEach((target) => {
    target.addEventListener('scroll', onTargetScroll)
  })
  window.addEventListener('resize', updateSpacer)

  topScrollState = { scrollbar, spacer, scrollTargets, updateSpacer, onTargetScroll }
  updateSpacer()

  detachTopScroll = () => {
    scrollbar.removeEventListener('scroll', onTopScroll)
    topScrollState.scrollTargets.forEach((target) => {
      target.removeEventListener('scroll', onTargetScroll)
    })
    window.removeEventListener('resize', updateSpacer)
    scrollbar.remove()
    topScrollState = null
    resetFixedTimeColumns()
  }
}

const updateTopScrollbarWidth = () => {
  if (!topScrollState) return
  topScrollState.updateSpacer()
}

const clampResourceWidth = (value) => {
  return Math.min(MAX_RESOURCE_WIDTH, Math.max(MIN_RESOURCE_WIDTH, value))
}

const loadResourceWidths = () => {
  try {
    const raw = localStorage.getItem('bookingResourceWidths')
    if (!raw) return []
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return []
    return parsed.map((width) => clampResourceWidth(parseInt(width, 10) || DEFAULT_RESOURCE_WIDTH))
  } catch (error) {
    return []
  }
}

const saveResourceWidths = () => {
  try {
    localStorage.setItem('bookingResourceWidths', JSON.stringify(resourceWidths.value))
  } catch (error) {
    // ignore
  }
}

const syncResourceWidths = (count) => {
  if (count <= 0) return

  const root = calenderRef.value
  const sidebar = root?.querySelector('.ec-header .ec-sidebar, .ec-all-day .ec-sidebar, .ec-body .ec-sidebar')
  const sidebarWidth = sidebar?.getBoundingClientRect().width || 0
  const availableWidth = Math.max(0, (root?.clientWidth || 0) - sidebarWidth)
  const readableWidth = count <= 3 ? READABLE_RESOURCE_WIDTH : DEFAULT_RESOURCE_WIDTH
  const adaptiveWidth = availableWidth > 0 ? Math.floor(availableWidth / count) : readableWidth
  const nextWidth = Math.max(readableWidth, adaptiveWidth)

  resourceWidths.value = Array.from({ length: count }, () => nextWidth)
}

const setElementWidth = (element, width) => {
  const safeWidth = clampResourceWidth(width)
  element.style.flex = `0 0 ${safeWidth}px`
  element.style.minWidth = `${safeWidth}px`
  element.style.maxWidth = `${safeWidth}px`
  element.style.width = `${safeWidth}px`
}

const applyResourceWidths = () => {
  const root = calenderRef.value
  if (!root) return

  const headerResources = Array.from(root.querySelectorAll('.ec-header .ec-resource'))
  const bodyResources = Array.from(root.querySelectorAll('.ec-body .ec-resource'))
  const allDayResources = Array.from(root.querySelectorAll('.ec-all-day .ec-resource'))
  const allDayDays = allDayResources.length ? [] : Array.from(root.querySelectorAll('.ec-all-day .ec-days'))
  const count = Math.max(headerResources.length, bodyResources.length, allDayResources.length, allDayDays.length)

  if (count === 0) return
  const rootWidth = root.clientWidth || 0
  if (!resizeState.active && (resourceWidths.value.length !== count || lastResourceSizingWidth !== rootWidth)) {
    lastResourceSizingWidth = rootWidth
    syncResourceWidths(count)
  }

  headerResources.forEach((element, index) => {
    setElementWidth(element, resourceWidths.value[index] ?? DEFAULT_RESOURCE_WIDTH)
  })

  bodyResources.forEach((element, index) => {
    setElementWidth(element, resourceWidths.value[index] ?? DEFAULT_RESOURCE_WIDTH)
  })

  allDayResources.forEach((element, index) => {
    setElementWidth(element, resourceWidths.value[index] ?? DEFAULT_RESOURCE_WIDTH)
  })

  allDayDays.forEach((element, index) => {
    setElementWidth(element, resourceWidths.value[index] ?? DEFAULT_RESOURCE_WIDTH)
  })
}

const refreshResourceSizing = () => {
  if (!calenderRef.value) return

  if (resourceSizingFrame) {
    cancelAnimationFrame(resourceSizingFrame)
  }

  resourceSizingFrame = requestAnimationFrame(() => {
    resourceSizingFrame = null
    applyResourceWidths()
    attachResizeHandlers()
    syncTopHorizontalScroll()
    updateTopScrollbarWidth()
  })
}

const handleBookingFormHide = () => {
  setBooking({})
  updateBodyClass('hide')
  bookingType.value = ''
}

const attachResizeHandlers = () => {
  if (resizeHandlersAttached || !calenderRef.value) return

  const root = calenderRef.value
  resizeHandlersAttached = true

  const onMouseMove = (event) => {
    if (resizeState.active) return
    const resourceCell = event.target.closest('.ec-resource')
    if (!resourceCell || !root.contains(resourceCell)) {
      root.style.cursor = ''
      return
    }

    const rect = resourceCell.getBoundingClientRect()
    const nearEdge = rect.right - event.clientX <= RESIZE_HANDLE_SIZE
    root.style.cursor = nearEdge ? 'col-resize' : ''
  }

  const onMouseDown = (event) => {
    const resourceCell = event.target.closest('.ec-resource')
    if (!resourceCell || !root.contains(resourceCell)) return

    const rect = resourceCell.getBoundingClientRect()
    if (rect.right - event.clientX > RESIZE_HANDLE_SIZE) return

    const headerCells = Array.from(root.querySelectorAll('.ec-header .ec-resource'))
    const bodyCells = Array.from(root.querySelectorAll('.ec-body .ec-resource'))
    const totalCells = Math.max(headerCells.length, bodyCells.length)

    let index = headerCells.indexOf(resourceCell)
    if (index < 0) {
      index = bodyCells.indexOf(resourceCell)
    }
    if (index < 0) return

    syncResourceWidths(totalCells)
    resizeState.active = true
    resizeState.index = index
    resizeState.startX = event.clientX
    resizeState.startWidth = resourceWidths.value[index] ?? rect.width
    document.body.classList.add('ec-resizing')
    event.preventDefault()
  }

  const onDrag = (event) => {
    if (!resizeState.active) return
    const delta = event.clientX - resizeState.startX
    const nextWidth = clampResourceWidth(resizeState.startWidth + delta)
    if (resourceWidths.value[resizeState.index] !== nextWidth) {
      resourceWidths.value.splice(resizeState.index, 1, nextWidth)
      applyResourceWidths()
    }
  }

  const onMouseUp = () => {
    if (!resizeState.active) return
    resizeState.active = false
    resizeState.index = -1
    document.body.classList.remove('ec-resizing')
    saveResourceWidths()
  }

  root.addEventListener('mousemove', onMouseMove)
  root.addEventListener('mousedown', onMouseDown)
  document.addEventListener('mousemove', onDrag)
  document.addEventListener('mouseup', onMouseUp)

  detachResizeHandlers = () => {
    root.removeEventListener('mousemove', onMouseMove)
    root.removeEventListener('mousedown', onMouseDown)
    document.removeEventListener('mousemove', onDrag)
    document.removeEventListener('mouseup', onMouseUp)
  }
}

onUnmounted(() => {
  if (calenderInit.value) {
    destroyCalendar(calenderInit.value)
    calenderInit.value = null
  }
  window.removeEventListener('booking:create', createBooking)
  if (calendarFetchController) {
    calendarFetchController.abort()
    calendarFetchController = null
  }
  clearCalendarFetchTimeout()
  clearCalendarRetryTimeout()
  if (resourceSizingFrame) {
    cancelAnimationFrame(resourceSizingFrame)
    resourceSizingFrame = null
  }
  if (calendarColorObserver) {
    calendarColorObserver.disconnect()
    calendarColorObserver = null
  }
  const elem = document.getElementById('booking-form')
  if (elem !== null) {
    updateBodyClass('hide')
    elem.removeEventListener('hide.bs.offcanvas', handleBookingFormHide)
  }
  if (detachResizeHandlers) {
    detachResizeHandlers()
    detachResizeHandlers = null
    resizeHandlersAttached = false
  }
  if (detachTopScroll) {
    detachTopScroll()
    detachTopScroll = null
  }
  document.body.classList.remove('ec-resizing')
})
onMounted(() => {
  window.addEventListener('booking:create', createBooking)
  const elem = document.getElementById('booking-form')
  if (elem !== null) {
    elem.addEventListener('hide.bs.offcanvas', handleBookingFormHide)
    const bkid = new URL(location.href).searchParams.get('booking_id')
    if (bkid !== null && bkid !== undefined) {
      bookingType.value = 'CALENDER_BOOKING'
      showBookingForm({ id: bkid })
    }
  }
  if (calenderRef !== null) {
    calenderInit.value = createCalendar(
      calenderRef.value,
      [DayGrid, List, TimeGrid, ResourceTimeGrid, Interaction],
      {
          date: selectedCalendarDate.value,
          locale: 'ar',
          slotEventOverlap: false,
          dragScroll: false,
          view: 'resourceTimeGridDay',
          height: '800px',
          headerToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: ''
          },
          buttonText: function (texts) {
            texts.today = 'اليوم'
            texts.resourceTimeGridDay = 'اليوم'
            texts.resourceTimeGridWeek = 'الأسبوع'
            texts.dayGridMonth = 'الشهر'
            return texts
          },
          eventContent: function (data) {
            //   // console.log(data, data.event.titleHTML)
            if (data.event.titleHTML !== undefined) {
              return { html: data.event.titleHTML + data.timeText }
            }
            return data.timeText
          },
          eventDidMount: function (info) {
            if (info.event.display === 'background') {
              return
            }

            applyEventCategoryColor(info.el, info.event)
          },
          slotLabelFormat: function (data) {
            // Convert the input string to a Date object
            const date = new Date(data)

            // Get the hour and minute from the Date object
            const minute = data.getMinutes()

            // Check if the hour and minute are both "00"
            if (minute === 0) {
              return moment(data).format('hh:mm A')
            } else {
              return ''
            }
          },
          resources: [],
          scrollTime: '09:00:00',
          events: [],
          lazyFetching: false,
          views: {
            dayGridMonth: { pointer: true },
            timeGridWeek: { pointer: true },
            resourceTimeGridWeek: { pointer: true },
            resourceTimeGridDay: {
              pointer: true,
              titleFormat: {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
              }
            }
          },
          eventSources: [
            {
              events: fetchCalendarEvents
            }
          ],
          dateClick: function (info) {
            handleCalendarSlotClick(info)
          },
          select: function (info) {
            handleCalendarSlotClick(info)
          },
          eventClick: function (info) {
            if (info.event.display === 'background') {
              return
            }

            const resourceId = info.event.resourceIds?.[0] || info.event.extendedProps?.employee_id
            const employee = findEmployeeById(resourceId)
            const updatedInfo = {
              id: info.event.extendedProps?.booking_id || info.event.id,
              resource: {
                id: resourceId,
                branch_id: info.event.extendedProps?.branch_id || employee?.branch_id
              },
              employee_name: info.event.extendedProps?.employee_name || employee?.title,
              date: info.event.start
            }
            showBookingForm(updatedInfo)
          },
          eventStartEditable: false,
          slotDuration: slotsDurations,
          dayMaxEvents: true,
          nowIndicator: true,
          selectable: false
      }
    )
    attachCalendarColorObserver()
    refreshResourceSizing()
  }
})

const onSubmitEvent = (booking = {}) => {
  const nextEmployeeId = Number(booking.employee_id)

  if (booking.start_date_time) {
    selectedCalendarDate.value = moment(booking.start_date_time).format('YYYY-MM-DD')
  }

  // ✅ الإصلاح: أضف الموظف الجديد للفلتر بغض النظر عن canReorder
  if (nextEmployeeId > 0 && selectedCalendarEmployeeIds.value.length > 0 && !selectedCalendarEmployeeIds.value.includes(nextEmployeeId)) {
    selectedCalendarEmployeeIds.value = [...selectedCalendarEmployeeIds.value, nextEmployeeId]
  }

  resourceWidths.value = []
  calenderInit.value.setOption('date', selectedCalendarDate.value)
  calenderInit.value.refetchEvents()
  refreshResourceSizing()
}
</script>
<style>
@import '@event-calendar/core/index.css';
body {
  transition: width 400ms ease;
}
.calender-view {
  width: calc(100% - 382px);
  transition: width 400ms ease;
}
.ec-lines {
  width: unset;
  margin-left: 8px;
}
.ec-header .ec-day {
  overflow: inherit !important;
  height: inherit !important;
  line-height: inherit;
  min-height: inherit;
}
.ec-day.ec-today {
  background-color: var(--bs-body-bg);
}
.dark .ec-day.ec-today {
  background-color: #181818;
}
.ec-event {
  border-radius: 0;
  border-bottom: 2px solid var(--bs-border-color);
  cursor: pointer;
}
.calendar-root .ec-bg-event {
  opacity: 1;
  pointer-events: none;
}
.calendar-root .ec-body .ec-day {
  position: relative;
}
.calendar-root .ec-body .ec-day::after {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 23px, rgba(17, 24, 39, 0.32) 23px, rgba(17, 24, 39, 0.32) 24px);
}
.calendar-root .ec-bg-events {
  position: relative;
  z-index: 0;
}
.calendar-root .ec-events {
  position: relative;
  z-index: 2;
}
.ec-body:not(.ec-compact) .ec-line:nth-child(even):after {
  border-bottom-style: solid;
}
.ec-line:not(:first-child):after {
  border-color: rgba(17, 24, 39, 0.28);
}
.ec-header,
.ec-all-day,
.ec-body,
.ec-days,
.ec-day {
  border-color: rgba(17, 24, 39, 0.32);
}
.calendar-root .ec-resource,
.calendar-root .ec-sidebar,
.calendar-root .ec-time,
.calendar-root .ec-line-time,
.calendar-root .ec-lines,
.calendar-root .ec-days,
.calendar-root .ec-day {
  border-color: rgba(17, 24, 39, 0.32) !important;
}
.calendar-root .ec-body:not(.ec-compact) .ec-line:after {
  border-bottom-color: rgba(17, 24, 39, 0.28) !important;
}
.ec-button,
.ec-button:not(:disabled) {
  color: var(--bs-body-color);
  background-color: var(--bs-body-bg);
  border-color: var(--bs-border-color);
}
.dark .ec-button:not(:disabled):hover,
.dark .ec-button.ec-active {
  border-color: var(--bs-border-color);
  background-color: var(--bs-body-bg);
}
.ec-icon.ec-prev:after,
.ec-icon.ec-next:after {
  border-color: var(--bs-body-color);
}
.calendar-root {
  position: relative;
  overflow-x: hidden;
}
.calendar-root .ec-header,
.calendar-root .ec-all-day,
.calendar-root .ec-body {
  overflow-x: auto !important;
  scrollbar-width: none;
}
.calendar-root .ec-header::-webkit-scrollbar,
.calendar-root .ec-all-day::-webkit-scrollbar,
.calendar-root .ec-body::-webkit-scrollbar {
  display: none;
}
.calendar-root .ec-hidden-scroll,
.calendar-root .ec-body > .ec-scroll {
  overflow-x: auto !important;
  scrollbar-width: none;
}
.calendar-root .ec-hidden-scroll::-webkit-scrollbar,
.calendar-root .ec-body > .ec-scroll::-webkit-scrollbar {
  display: none;
}
.calendar-root .ec-hidden-scroll {
  display: none !important;
}
body.ec-resizing {
  user-select: none;
  cursor: col-resize;
}
.pagination-controls {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: space-between;
}
.calendar-date-picker {
  align-items: center;
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}
.calendar-date-picker__label {
  color: var(--bs-secondary-color);
  font-size: 0.84rem;
  margin: 0;
  white-space: nowrap;
}
.calendar-date-picker__control {
  align-items: center;
  display: flex;
  position: relative;
}
.calendar-date-picker__control i {
  color: var(--bs-primary);
  left: 12px;
  pointer-events: none;
  position: absolute;
  z-index: 1;
}
.calendar-date-picker__input,
.calendar-date-picker__control .flatpickr-input {
  font-size: 0.95rem;
  font-weight: 600;
  min-height: 38px;
  min-width: 158px;
  padding-left: 38px;
  padding-right: 12px;
  text-align: left;
}
.calendar-view-switcher {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  display: flex;
  gap: 4px;
  margin-bottom: 12px;
  padding: 4px;
}
.calendar-view-switcher__button {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 6px;
  color: var(--bs-secondary-color);
  display: inline-flex;
  font-size: 0.88rem;
  font-weight: 700;
  gap: 6px;
  min-height: 34px;
  padding: 6px 12px;
  white-space: nowrap;
}
.calendar-view-switcher__button:hover {
  background: rgba(var(--bs-primary-rgb), 0.08);
  color: var(--bs-primary);
}
.calendar-view-switcher__button.active {
  background: var(--bs-primary);
  color: #fff;
}
.calendar-root {
  font-size: 0.94rem;
  width: 100%;
}
.calendar-root .ec {
  min-width: 100%;
  width: 100%;
}
.calendar-root .ec-title,
.calendar-root .ec-day-head,
.calendar-root .ec-resource,
.calendar-root .ec-time,
.calendar-root .ec-button {
  font-weight: 700;
}
.calendar-root .ec-event {
  font-size: 0.86rem;
  line-height: 1.35;
}
.calendar-root .ec-day {
  background-color: var(--bs-body-bg);
}
.calendar-employee-filter {
  align-items: center;
  display: flex;
  flex-wrap: nowrap;
  gap: 10px;
  margin: 0 0 12px;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 4px 4px 12px;
  scrollbar-width: thin;
  white-space: nowrap;
  -webkit-overflow-scrolling: touch;
  width: 100%;
  max-width: 100%;
}
.calendar-employee-filter::-webkit-scrollbar {
  height: 6px;
}
.calendar-employee-filter::-webkit-scrollbar-track {
  background: transparent;
}
.calendar-employee-filter::-webkit-scrollbar-thumb {
  background-color: var(--bs-border-color, #dee2e6);
  border-radius: 4px;
}
.calendar-employee-filter::-webkit-scrollbar-thumb:hover {
  background-color: var(--bs-primary, #0d6efd);
}
.calendar-employee-filter__chip {
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-radius: 50%;
  color: var(--bs-body-color);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 74px;
  font-size: 0.78rem;
  font-weight: 600;
  line-height: 1.2;
  height: 74px;
  max-width: 74px;
  min-height: 74px;
  overflow-wrap: anywhere;
  padding: 8px;
  text-align: center;
  white-space: normal;
  word-break: normal;
}
.calendar-employee-filter__chip:hover {
  border-color: var(--bs-primary);
  color: var(--bs-primary);
}
.calendar-employee-filter__chip.active {
  background: var(--bs-primary);
  border-color: var(--bs-primary);
  color: #fff;
}
.booking-list-view {
  display: grid;
  gap: 10px;
  margin-bottom: 14px;
}
.booking-list-card {
  align-items: stretch;
  background: var(--bs-body-bg);
  border: 1px solid var(--bs-border-color);
  border-inline-start: 4px solid var(--bs-primary);
  border-radius: 8px;
  cursor: pointer;
  display: grid;
  gap: 14px;
  grid-template-columns: 112px 1fr;
  padding: 12px;
}
.booking-list-card:hover {
  border-color: var(--bs-primary);
  box-shadow: 0 6px 18px rgba(17, 24, 39, 0.08);
}
.booking-list-card__time {
  align-items: center;
  background: rgba(var(--bs-primary-rgb), 0.08);
  border-radius: 6px;
  color: var(--bs-primary);
  display: grid;
  justify-items: center;
  padding: 10px;
  text-align: center;
}
.booking-list-card__time span {
  font-size: 1rem;
  font-weight: 800;
}
.booking-list-card__time small {
  color: var(--bs-secondary-color);
  font-weight: 700;
}
.booking-list-card__body {
  display: grid;
  gap: 8px;
  min-width: 0;
}
.booking-list-card__title-row {
  align-items: center;
  display: flex;
  gap: 10px;
  justify-content: space-between;
}
.booking-list-card__title-row h6 {
  font-size: 1rem;
  font-weight: 800;
  margin: 0;
  overflow-wrap: anywhere;
}
.booking-list-card__status {
  background: rgba(var(--bs-primary-rgb), 0.1);
  border-radius: 999px;
  color: var(--bs-primary);
  flex-shrink: 0;
  font-size: 0.78rem;
  font-weight: 800;
  padding: 4px 10px;
}
.booking-list-card__details {
  color: var(--bs-secondary-color);
  display: flex;
  flex-wrap: wrap;
  gap: 8px 14px;
  font-size: 0.88rem;
  font-weight: 600;
}
.booking-list-card__details span {
  align-items: center;
  display: inline-flex;
  gap: 6px;
}
.booking-list-empty {
  border: 1px dashed var(--bs-border-color);
  border-radius: 8px;
  color: var(--bs-secondary-color);
  font-weight: 700;
  padding: 22px;
  text-align: center;
}
.booking-calendar-scrollbar {
  overflow-x: auto;
  overflow-y: hidden;
  height: 8px;
  margin-bottom: 12px;
}
.booking-calendar-scrollbar::-webkit-scrollbar {
  height: 6px;
}
.booking-calendar-scrollbar::-webkit-scrollbar-track {
  background: rgba(var(--bs-primary-rgb), 0.05);
  border-radius: 4px;
}
.booking-calendar-scrollbar::-webkit-scrollbar-thumb {
  background-color: var(--bs-primary, #0d6efd);
  border-radius: 4px;
}
.booking-calendar-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: var(--bs-primary-shade-10, #0a58ca);
}
.booking-calendar-scrollbar-spacer {
  height: 1px;
}
.calendar-root .ec-sidebar,
.calendar-root .ec-time,
.calendar-root .ec-line-time {
  position: sticky;
  inset-inline-start: 0;
  z-index: 5;
  background: var(--bs-body-bg);
}
.calendar-root .ec-sidebar {
  flex-shrink: 0;
  will-change: transform;
}
.calendar-root .ec-header .ec-sidebar,
.calendar-root .ec-header .ec-time {
  z-index: 8;
}
[dir='rtl'] .calendar-root .ec-sidebar,
[dir='rtl'] .calendar-root .ec-time,
[dir='rtl'] .calendar-root .ec-line-time {
  right: 0;
  left: auto;
}
.staff-order-panel {
  border: 1px solid var(--bs-border-color);
  border-radius: 8px;
  background: var(--bs-body-bg);
  margin-bottom: 12px;
  padding: 14px;
}
.staff-order-panel__header,
.staff-order-panel__footer,
.staff-order-item,
.staff-order-item__actions {
  display: flex;
  align-items: center;
}
.staff-order-panel__header,
.staff-order-panel__footer {
  justify-content: space-between;
  gap: 12px;
}
.staff-order-panel__footer {
  margin-top: 12px;
}
.staff-order-list {
  display: grid;
  gap: 8px;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  list-style: none;
  margin: 14px 0 0;
  padding: 0;
}
.staff-order-item {
  border: 1px solid var(--bs-border-color);
  border-radius: 6px;
  background: var(--bs-body-bg);
  align-items: flex-start;
  gap: 12px;
  min-height: 48px;
  padding: 10px 12px;
}
.staff-order-item__handle {
  color: var(--bs-secondary-color);
  cursor: grab;
  line-height: 1.6;
  padding-top: 2px;
}
.staff-order-item__content {
  display: grid;
  flex: 1;
  gap: 8px;
  min-width: 0;
}
.staff-order-item__name {
  color: var(--bs-body-color);
  line-height: 1.45;
  min-width: 0;
  overflow-wrap: anywhere;
  white-space: normal;
  word-break: normal;
}
.staff-order-item__meta {
  color: var(--bs-secondary-color);
  font-size: 0.8rem;
  font-weight: 600;
  margin-top: 2px;
}
.staff-order-item__controls {
  align-items: center;
  display: flex;
  gap: 10px;
  justify-content: space-between;
  min-width: 0;
}
.staff-order-item__visibility {
  align-items: center;
  display: inline-flex;
  gap: 6px;
  margin: 0;
  min-width: 0;
  white-space: nowrap;
}
.staff-order-item__visibility .form-check-input {
  cursor: pointer;
  margin: 0;
}
.staff-order-item__visibility-text {
  color: var(--bs-secondary-color);
  font-size: 0.82rem;
}
.staff-order-item__actions {
  gap: 4px;
  flex-shrink: 0;
}
@media (max-width: 575.98px) {
  .pagination-controls {
    align-items: stretch;
    display: grid;
  }
  .calendar-date-picker {
    align-items: flex-start;
    display: grid;
  }
  .calendar-date-picker__input,
  .calendar-date-picker__control,
  .calendar-date-picker__control .flatpickr-input {
    width: 100%;
  }
  .calendar-view-switcher {
    overflow-x: auto;
  }
  .calendar-view-switcher__button {
    flex: 0 0 auto;
  }
  .booking-list-card {
    grid-template-columns: 1fr;
  }
  .booking-list-card__title-row {
    align-items: flex-start;
    display: grid;
  }
  .staff-order-item__controls {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
