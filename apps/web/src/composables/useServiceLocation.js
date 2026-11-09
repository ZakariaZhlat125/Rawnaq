import { ref, computed } from 'vue'
import '../../public/public-api.js'
import { homeService, loadHomeService } from '@/composables/useHomeService'
import { localizeRecord } from '@/utils/i18nField'
import { useLanguage } from '@/composables/useLanguage'
import { resolveBackendUrl } from '@/utils/assetPath'

/*
  مكان تنفيذ الخدمة — حالة مشتركة بين صفحات Vue والصفحات القديمة.
  تستخدم نفس المفتاح والمعرّفات المستخدمة في src/legacy/*.html
  حتى يبقى الاختيار موحّدًا في كل الموقع.
*/

const STORAGE_KEY = 'samiSiteBranch'

// خدمة منزلية — مش فرع فعلي في الـ API، بنضيفها يدويًا لقائمة الأماكن
const HOME_SERVICE = {
  id: 'home-service',
  name: { ar: 'خدمة منزلية', en: 'Home Service' },
  address: { ar: 'نصل إلى موقعك أينما كنت داخل جدة', en: 'We come to you anywhere in Jeddah' },
  image: '/images/generated/branches/home-service-hq.png',
  home: true,
}

// الـ API بيرجع branch_image / address_line_1، والـ modal بيقرا image / address
function normalizeBranch(branch) {
  return {
    ...branch,
    bookable: branch.status === true || Number(branch.status) === 1,
    image: branch.image || branch.branch_image || null,
    address: branch.address || branch.address_line_1 || '',
  }
}


// ======================================================
// Service Locations
// ======================================================

const serviceLocations = ref([])


// حالة تحميل الأماكن
const locationsLoading = ref(false)


// الخطأ في حالة فشل الـ API
const locationsError = ref(null)
const customerPosition = ref(null)
const locating = ref(false)
const locationFailure = ref(null)


// ======================================================
// Load Locations From API
// ======================================================

async function loadServiceLocations() {
  if (locationsLoading.value) return

  locationsLoading.value = true
  locationsError.value = null

  try {
    const response = await globalThis.SamiPublicApi.fetch(resolveBackendUrl('/api/branches?per_page=100'), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`HTTP error: ${response.status}`)
    }

    const result = await response.json()

    /*
      لو الـ API يرجع:

      {
        data: [...]
      }

      نستخدم result.data

      ولو يرجع array مباشرة:
      [...]
      نستخدم result
    */

    const locations = Array.isArray(result)
      ? result
      : result.data

    if (!Array.isArray(locations)) {
      throw new Error('Invalid service locations response')
    }

    for (let page = 2; page <= (result.last_page || 1); page++) {
      const next = await globalThis.SamiPublicApi.fetch(resolveBackendUrl(`/api/branches?per_page=100&page=${page}`), { headers: { Accept: 'application/json' } })
      if (!next.ok) throw new Error('Unable to load branches')
      const payload = await next.json()
      locations.push(...(payload.data || []))
    }
    await loadHomeService()
    serviceLocations.value = [...locations.map(normalizeBranch), ...(homeService.enabled ? [HOME_SERVICE] : [])]

    /*
      لو المكان المحفوظ لم يعد موجودًا في الـ API
      يتم إلغاء الاختيار القديم.
    */
    const storedId = readStored()

    if (
      storedId &&
      serviceLocations.value.some(location => String(location.id) === String(storedId) && location.bookable !== false)
    ) {
      currentId.value = storedId
    } else {
      currentId.value = null

      try {
        localStorage.removeItem(STORAGE_KEY)
      } catch {
        // التخزين غير متاح
      }
    }

  } catch (error) {
    console.error('Failed to load service locations:', error)

    locationsError.value = error

    /*
      مهم:
      لا نمسح currentId هنا.
      لو عند المستخدم location محفوظة مسبقًا،
      يظل قادرًا على استخدامها حتى لو الـ API فشل.
    */

  } finally {
    locationsLoading.value = false
  }
}


// ======================================================
// Read Stored Location
// ======================================================

function readStored() {
  try {
    const v = localStorage.getItem(STORAGE_KEY)

    /*
      هنا ممكن الـ API لسه لم يتم تحميله.
      لذلك لو serviceLocations فاضية نرجع القيمة مباشرة.
    */

    if (!v) return null

    return serviceLocations.value.length === 0 ||
      serviceLocations.value.some(b => String(b.id) === String(v))
      ? v
      : null

  } catch {
    return null
  }
}


// ======================================================
// Shared State
// ======================================================

// نقرأ الـ ID المحفوظ
const currentId = ref(readStored())

const pickerOpen = ref(false)

let pendingAction = null


// ======================================================
// Composable
// ======================================================

export function useServiceLocation() {

  const { state: lang } = useLanguage()

  /*
    الـ API بيرجع name كـ {ar, en} (زي باقي الكيانات في الموقع)،
    هنا بنحوّلها لنص واحد حسب اللغة الحالية عشان كل الاستخدامات
    (الهيدر، الـ modal، صفحة الحجز...) تفضل تقرأ .name كنص عادي.
  */
  function withResolvedName(branch) {
    if (!branch) return branch
    return { ...branch, name: localizeRecord(branch, 'name', lang.lang), address: localizeRecord(branch, 'address', lang.lang) }
  }

  const locations = computed(() => window.SamiBranchLocation.rank(serviceLocations.value.map(withResolvedName), customerPosition.value))
  const locationMessage = computed(() => locationFailure.value ? window.SamiBranchLocation.errorMessage(locationFailure.value, lang.lang) : '')
  async function findNearest() {
    if (locating.value) return
    locating.value = true
    locationFailure.value = null
    try {
      customerPosition.value = await window.SamiBranchLocation.locate()
    } catch (error) {
      locationFailure.value = error
    } finally {
      locating.value = false
    }
  }

  const current = computed(
    () =>
      withResolvedName(serviceLocations.value.find(
        b => String(b.id) === String(currentId.value)
      )) || null
  )


  const hasLocation = computed(
    () => !!current.value && current.value.bookable !== false
  )


  // ====================================================
  // Set Location
  // ====================================================

  function setLocation(id) {

    if (
      !serviceLocations.value.some(
        b => String(b.id) === String(id) && b.bookable !== false
      )
    ) {
      return
    }

    currentId.value = id

    try {
      localStorage.setItem(STORAGE_KEY, id)
    } catch {
      // التخزين غير متاح
    }
  }


  // ====================================================
  // Open Picker
  // ====================================================

  function openPicker(action = null) {

    pendingAction =
      typeof action === 'function'
        ? action
        : null

    pickerOpen.value = true
  }


  // ====================================================
  // Close Picker
  // ====================================================

  function closePicker() {

    pickerOpen.value = false

    pendingAction = null
  }


  // ====================================================
  // Require Location
  // ====================================================

  function requireLocation(action) {

    if (hasLocation.value) {

      action()

      return false
    }

    pendingAction = action

    pickerOpen.value = true

    return true
  }


  // ====================================================
  // Confirm Location
  // ====================================================

  function confirmLocation(id) {

    if (!serviceLocations.value.some(b => String(b.id) === String(id) && b.bookable !== false)) return
    setLocation(id)

    pickerOpen.value = false

    const act = pendingAction

    pendingAction = null

    if (act) {
      act()
    }
  }


  // ====================================================
  // Return
  // ====================================================

  return {
    customerPosition,
    locating,
    locationMessage,
    findNearest,
    formatDistance: km => window.SamiBranchLocation.format(km, lang.lang),

    locations,

    current,

    hasLocation,

    pickerOpen,

    locationsLoading,

    locationsError,

    loadServiceLocations,

    setLocation,

    openPicker,

    closePicker,

    requireLocation,

    confirmLocation,
  }
}
