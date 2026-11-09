<template>
  <div class="offcanvas offcanvas-end home-booking-offcanvas" tabindex="-1" id="home-booking-form" aria-labelledby="homeBookingFormLabel">
    <div class="offcanvas-header">
      <div>
        <h4 class="offcanvas-title" id="homeBookingFormLabel">الحجز المنزلي</h4>
        <p class="mb-0 text-muted small">إنشاء حجز منزلي مباشرة من لوحة التحكم</p>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" :aria-label="$t('Close')"></button>
    </div>

    <div class="offcanvas-body border-top">
      <div v-if="isBootstrapping" class="d-flex align-items-center gap-2 text-muted py-4">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>جار تحميل بيانات الحجز المنزلي...</span>
      </div>

      <template v-else>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">المدينة ورسوم الزيارة</label>
            <select v-model="form.city_id" class="form-select"><option value="">اختر المدينة</option><option v-for="city in homeCities" :key="city.id" :value="city.id">{{ parseLocalizedValue(city.name) }} — {{ city.home_service_fee }} ر.س</option></select>
            <small class="text-danger">{{ errors.city_id }}</small>
          </div>
          <div class="col-6"><label>خط العرض<input v-model="form.latitude" type="number" step="any" class="form-control"></label><small class="text-danger">{{ errors.latitude }}</small></div>
          <div class="col-6"><label>خط الطول<input v-model="form.longitude" type="number" step="any" class="form-control"></label><small class="text-danger">{{ errors.longitude }}</small></div>
          <div class="col-12">
            <label class="form-label">اسم العميل</label>
            <input v-model="form.customer_name" type="text" class="form-control" placeholder="اسم العميل" />
            <small v-if="errors.customer_name" class="text-danger">{{ errors.customer_name }}</small>
          </div>

          <div class="col-12">
            <label class="form-label">رقم الجوال</label>
            <input v-model="form.mobile_no" type="text" class="form-control" placeholder="05xxxxxxxx" />
            <small v-if="errors.mobile_no" class="text-danger">{{ errors.mobile_no }}</small>
          </div>

          <div class="col-12">
            <label class="form-label">القسم</label>
            <select v-model="form.service_group_home_id" class="form-select">
              <option value="">اختر القسم</option>
              <option v-for="group in serviceGroups" :key="group.id" :value="stringValue(group.id)">
                {{ group.display_name || group.name }}
              </option>
            </select>
            <small v-if="errors.service_group_home_id" class="text-danger">{{ errors.service_group_home_id }}</small>
          </div>

          <div class="col-12">
            <label class="form-label">الخدمة المنزلية</label>
            <select v-model="form.service_home_id" class="form-select" :disabled="servicesLoading || !form.service_group_home_id">
              <option value="">اختر الخدمة</option>
              <option v-for="service in services" :key="service.id" :value="stringValue(service.id)">
                {{ service.display_name || service.name }}
              </option>
            </select>
            <small v-if="servicesLoading" class="text-muted">جار تحميل الخدمات...</small>
            <small v-else-if="selectedService" class="text-muted">
              المدة: {{ selectedService.duration || '-' }} دقيقة
              <span class="mx-1">|</span>
              السعر: {{ selectedService.price || '-' }}
            </small>
            <small v-if="errors.service_home_id" class="text-danger d-block">{{ errors.service_home_id }}</small>
          </div>

          <div class="col-12">
            <label class="form-label">الموظف</label>
            <select v-model="form.staff_home_id" class="form-select" :disabled="staffLoading || !form.service_home_id">
              <option value="">اختر الموظف</option>
              <option v-for="staffMember in staff" :key="staffMember.id" :value="stringValue(staffMember.id)">
                {{ staffMember.name }}
              </option>
            </select>
            <small v-if="staffLoading" class="text-muted">جار تحميل الموظفين...</small>
            <small v-else-if="!staff.length && form.service_home_id" class="text-muted">لا يوجد موظفون متاحون لهذه الخدمة حاليًا.</small>
            <small v-if="errors.staff_home_id" class="text-danger d-block">{{ errors.staff_home_id }}</small>
          </div>

          <div class="col-md-4">
            <label class="form-label">التاريخ</label>
            <input v-model="form.date" type="date" class="form-control" :min="today" />
            <small v-if="errors.date" class="text-danger">{{ errors.date }}</small>
          </div>

          <div class="col-md-4">
            <label class="form-label">وقت البداية</label>
            <input v-model="form.time" type="time" class="form-control" :disabled="!selectedService" />
            <small v-if="errors.time" class="text-danger">{{ errors.time }}</small>
          </div>

          <div class="col-md-4">
            <label class="form-label">وقت النهاية</label>
            <input v-model="form.end_time" type="time" class="form-control" :disabled="!selectedService" @input="onEndTimeInput" />
            <small v-if="errors.end_time" class="text-danger d-block">{{ errors.end_time }}</small>
            <small v-if="endTimeError" class="text-danger d-block">{{ endTimeError }}</small>
            <small v-else-if="form.time && selectedService" class="text-muted d-block">
              {{ isManualEndTime ? 'تم تحديد وقت النهاية يدويًا.' : 'إذا تركت وقت النهاية فارغًا فسيُحسب تلقائيًا حسب مدة الخدمة.' }}
            </small>
          </div>

          <div class="col-12">
            <div class="small text-muted">
              مدة الحجز: <strong>{{ effectiveDuration }}</strong> دقيقة
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">العنوان</label>
            <textarea v-model="form.address" rows="3" class="form-control" placeholder="اكتب عنوان الزيارة المنزلية"></textarea>
            <small v-if="errors.address" class="text-danger">{{ errors.address }}</small>
          </div>

          <div class="col-12">
            <label class="form-label">ملاحظات</label>
            <textarea v-model="form.notes" rows="3" class="form-control" placeholder="أي ملاحظات إضافية للحجز"></textarea>
            <small v-if="errors.notes" class="text-danger">{{ errors.notes }}</small>
          </div>
        </div>
      </template>
    </div>

    <div class="offcanvas-footer border-top p-3">
      <div class="d-grid">
        <button type="button" class="btn btn-primary btn-lg" :disabled="isSubmitted || !canSubmit" @click="submitForm">
          <template v-if="isSubmitted">
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            جار حفظ الحجز...
          </template>
          <template v-else>
            <i class="fa-solid fa-floppy-disk me-2"></i>حفظ الحجز المنزلي
          </template>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { useOnOffcanvasHide, useOnOffcanvasShow, useRequest } from '@/helpers/hooks/useCrudOpration'
import { SERVICE_GROUPS_URL, SERVICES_URL, STAFF_URL, STORE_URL } from '../constant/home-booking'

const { listingRequest, storeRequest } = useRequest()

const today = new Date().toISOString().slice(0, 10)
const currentLocale = document.documentElement.lang?.startsWith('ar') ? 'ar' : 'en'
const isBootstrapping = ref(false)
const isSubmitted = ref(false)
const servicesLoading = ref(false)
const staffLoading = ref(false)
const homeCities = ref([])
const serviceGroups = ref([])
const services = ref([])
const staff = ref([])
const endTimeError = ref('')
const isManualEndTime = ref(false)
const errors = reactive({})

const defaultForm = () => ({
  customer_name: '',
  mobile_no: '',
  city_id: '',
  latitude: '',
  longitude: '',
  address: '',
  notes: '',
  service_group_home_id: '',
  service_home_id: '',
  staff_home_id: '',
  date: today,
  time: '',
  end_time: ''
})

const form = reactive(defaultForm())

const stringValue = (value) => (value === null || value === undefined ? '' : String(value))

const clearErrors = () => {
  Object.keys(errors).forEach((key) => {
    delete errors[key]
  })
}

const resetForm = () => {
  Object.assign(form, defaultForm())
  services.value = []
  staff.value = []
  clearErrors()
  isSubmitted.value = false
  servicesLoading.value = false
  staffLoading.value = false
  endTimeError.value = ''
  isManualEndTime.value = false
}

const selectedService = computed(() => services.value.find((item) => stringValue(item.id) === form.service_home_id) || null)
const defaultDuration = computed(() => Math.max(1, Number(selectedService.value?.duration || 30)))
const effectiveDuration = computed(() => {
  if (!form.time || !form.end_time) {
    return defaultDuration.value
  }

  const startMoment = parseTimeForDate(form.time)
  const endMoment = parseTimeForDate(form.end_time)

  if (!startMoment || !endMoment) {
    return defaultDuration.value
  }

  const diff = Math.round((endMoment.getTime() - startMoment.getTime()) / 60000)
  return diff > 0 ? diff : defaultDuration.value
})

const canSubmit = computed(() =>
  Boolean(
    form.customer_name &&
      form.mobile_no &&
      form.address &&
      form.service_group_home_id &&
      form.service_home_id &&
      form.staff_home_id &&
      form.date &&
      form.time &&
      !endTimeError.value
  )
)

const extractItems = (response) => {
  if (Array.isArray(response)) {
    return response
  }

  if (Array.isArray(response?.data)) {
    return response.data
  }

  return []
}

const parseTimeForDate = (timeValue) => {
  const value = String(timeValue || '').trim()
  if (!form.date || !value) {
    return null
  }

  const isoDate = `${form.date}T${value}:00`
  const parsed = new Date(isoDate)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

const syncEndTimeWithServiceDuration = () => {
  if (!form.time || !selectedService.value) {
    form.end_time = ''
    endTimeError.value = ''
    isManualEndTime.value = false
    return
  }

  if (isManualEndTime.value && form.end_time) {
    validateEndTime()
    return
  }

  const startMoment = parseTimeForDate(form.time)
  if (!startMoment) {
    form.end_time = ''
    endTimeError.value = ''
    return
  }

  const computedEndTime = new Date(startMoment.getTime() + defaultDuration.value * 60000)
  form.end_time = `${String(computedEndTime.getHours()).padStart(2, '0')}:${String(computedEndTime.getMinutes()).padStart(2, '0')}`
  endTimeError.value = ''
  isManualEndTime.value = false
}

const validateEndTime = () => {
  if (!form.end_time) {
    endTimeError.value = ''
    isManualEndTime.value = false
    syncEndTimeWithServiceDuration()
    return true
  }

  const startMoment = parseTimeForDate(form.time)
  const endMoment = parseTimeForDate(form.end_time)

  if (!startMoment || !endMoment) {
    endTimeError.value = ''
    return true
  }

  if (Math.round((endMoment.getTime() - startMoment.getTime()) / 60000) <= 0) {
    endTimeError.value = 'وقت النهاية يجب أن يكون بعد وقت البداية.'
    return false
  }

  endTimeError.value = ''
  return true
}

const onEndTimeInput = () => {
  isManualEndTime.value = Boolean(form.end_time)
  validateEndTime()
}

const parseLocalizedValue = (value) => {
  if (value && typeof value === 'object') {
    return value[currentLocale] || value.ar || value.en || Object.values(value)[0] || ''
  }

  const stringValue = String(value ?? '').trim()
  if (!stringValue) {
    return ''
  }

  try {
    const parsedValue = JSON.parse(stringValue)
    if (parsedValue && typeof parsedValue === 'object') {
      return parsedValue[currentLocale] || parsedValue.ar || parsedValue.en || Object.values(parsedValue)[0] || stringValue
    }
  } catch (error) {
    // Keep the original string when it is not JSON.
  }

  return stringValue
}

const buildStaffName = (staffMember) => {
  const fullName = String(staffMember?.full_name || '').trim()
  if (fullName) {
    return fullName
  }

  return [staffMember?.first_name, staffMember?.last_name]
    .map((value) => String(value || '').trim())
    .filter(Boolean)
    .join(' ')
}

const loadServiceGroups = async () => {
  isBootstrapping.value = true
  const response = await listingRequest({ url: SERVICE_GROUPS_URL })
  serviceGroups.value = extractItems(response).map((group) => ({
    ...group,
    display_name: parseLocalizedValue(group.name)
  }))
  isBootstrapping.value = false
}

const loadServices = async (serviceGroupId) => {
  if (!serviceGroupId) {
    services.value = []
    return
  }

  servicesLoading.value = true
  const response = await listingRequest({ url: SERVICES_URL, data: { service_group_home_id: serviceGroupId } })
  services.value = extractItems(response).map((service) => ({
    ...service,
    display_name: parseLocalizedValue(service.name),
    price: service.price ?? service.default_price ?? null,
    duration: service.duration ?? service.duration_min ?? null
  }))
  servicesLoading.value = false
}

const loadStaff = async (serviceHomeId) => {
  if (!serviceHomeId) {
    staff.value = []
    return
  }

  staffLoading.value = true
  const response = await listingRequest({ url: STAFF_URL, data: { service_home_id: serviceHomeId } })
  staff.value = extractItems(response).map((staffMember) => ({
    ...staffMember,
    name: buildStaffName(staffMember)
  }))
  staffLoading.value = false
}

watch(
  () => form.service_group_home_id,
  async (value) => {
    form.service_home_id = ''
    form.staff_home_id = ''
    form.time = ''
    form.end_time = ''
    services.value = []
    staff.value = []
    endTimeError.value = ''
    isManualEndTime.value = false

    if (value) {
      await loadServices(value)
    }
  }
)

watch(
  () => form.service_home_id,
  async (value) => {
    form.staff_home_id = ''
    form.time = ''
    form.end_time = ''
    staff.value = []
    endTimeError.value = ''
    isManualEndTime.value = false

    if (value) {
      await loadStaff(value)
    }
  }
)

watch(
  () => [form.staff_home_id, form.date],
  () => {
    form.time = ''
    form.end_time = ''
    endTimeError.value = ''
    isManualEndTime.value = false
  }
)

watch(
  () => form.time,
  () => {
    syncEndTimeWithServiceDuration()
  }
)

watch(
  () => selectedService.value?.id,
  () => {
    syncEndTimeWithServiceDuration()
  }
)

const normalizeErrors = (response) => {
  clearErrors()

  if (response?.errors && typeof response.errors === 'object') {
    Object.entries(response.errors).forEach(([key, value]) => {
      errors[key] = Array.isArray(value) ? value[0] : value
    })
  }
}

const hideForm = () => {
  const elem = document.getElementById('home-booking-form')
  if (!elem) return

  bootstrap.Offcanvas.getOrCreateInstance(elem).hide()
}

const openForm = async () => {
  const homeResponse = await listingRequest({ url: () => ({ path: '/api/home-service-settings', method: 'GET' }) })
  homeCities.value = homeResponse?.data?.cities || []

  if (!serviceGroups.value.length) {
    await loadServiceGroups()
  }

  const elem = document.getElementById('home-booking-form')
  if (!elem) return

  bootstrap.Offcanvas.getOrCreateInstance(elem).show()
}

const submitForm = async () => {
  clearErrors()
  isSubmitted.value = true

  const response = await storeRequest({
    url: STORE_URL,
    body: {
      ...form,
      service_group_home_id: Number(form.service_group_home_id),
      service_home_id: Number(form.service_home_id),
      staff_home_id: Number(form.staff_home_id),
      end_time: form.end_time || null
    }
  })

  if (response?.status) {
    window.successSnackbar?.(response.message || 'تم حفظ الحجز المنزلي بنجاح.')
    hideForm()
    return
  }

  normalizeErrors(response)
  window.errorSnackbar?.(response?.message || 'تعذر حفظ الحجز المنزلي.')
  isSubmitted.value = false
}

const onCreateRequest = () => {
  openForm()
}

useOnOffcanvasHide('home-booking-form', () => {
  resetForm()
})

useOnOffcanvasShow('home-booking-form', () => {
  if (!serviceGroups.value.length) {
    loadServiceGroups()
  }
})

onMounted(() => {
  window.addEventListener('home-booking:create', onCreateRequest)
  window.openHomeBookingForm = openForm

  const params = new URLSearchParams(window.location.search)
  if (params.get('home_booking') === '1') {
    window.setTimeout(() => {
      openForm()
      params.delete('home_booking')
      const nextQuery = params.toString()
      const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}`
      window.history.replaceState({}, '', nextUrl)
    }, 150)
  }
})

onUnmounted(() => {
  window.removeEventListener('home-booking:create', onCreateRequest)
  delete window.openHomeBookingForm
})
</script>

<style scoped>
.home-booking-offcanvas {
  width: min(560px, 100%);
}
</style>
