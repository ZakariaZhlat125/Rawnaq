import { ref, computed } from 'vue'

/*
  مكان تنفيذ الخدمة — حالة مشتركة بين صفحات Vue والصفحات القديمة.
  تستخدم نفس المفتاح والمعرّفات المستخدمة في src/legacy/*.html
  حتى يبقى الاختيار موحّدًا في كل الموقع.
*/
const STORAGE_KEY = 'samiSiteBranch'

export const serviceLocations = [
  {
    id: 'bg',
    name: 'البغدادية الغربية',
    address: 'شارع حائل، حي البغدادية الغربية — جدة',
    image: '/images/generated/branches/branch-1-hq.png',
    home: false,
  },
  {
    id: 'qr',
    name: 'البوادي',
    address: 'شارع قريش، حي البوادي — جدة',
    image: '/images/generated/branches/branch-2-hq.png',
    home: false,
  },
  {
    id: 'hm',
    name: 'الخدمة المنزلية',
    address: 'نصل إليك أينما كنت داخل جدة',
    image: '/images/generated/branches/home-service-hq.png',
    home: true,
  },
]

function readStored() {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    return serviceLocations.some(b => b.id === v) ? v : null
  } catch {
    return null
  }
}

// حالة مشتركة على مستوى الوحدة (module-level) — نسخة واحدة لكل التطبيق
const currentId = ref(readStored())
const pickerOpen = ref(false)
let pendingAction = null   // ما سيُنفَّذ بعد اختيار المكان

export function useServiceLocation() {
  const current = computed(
    () => serviceLocations.find(b => b.id === currentId.value) || null
  )
  const hasLocation = computed(() => !!currentId.value)

  function setLocation(id) {
    if (!serviceLocations.some(b => b.id === id)) return
    currentId.value = id
    try { localStorage.setItem(STORAGE_KEY, id) } catch { /* التخزين غير متاح */ }
  }

  /* فتح النافذة لتغيير المكان يدويًا من الهيدر */
  function openPicker(action = null) {
    pendingAction = typeof action === 'function' ? action : null
    pickerOpen.value = true
  }

  function closePicker() {
    pickerOpen.value = false
    pendingAction = null
  }

  /*
    البوابة: إن لم يُحدَّد مكان الخدمة، تُفتح النافذة أولًا ويُؤجَّل الانتقال
    حتى يختار المستخدم. وإن كان محدَّدًا، يُنفَّذ الانتقال مباشرة.
    ترجع true إن كان الانتقال مؤجَّلًا (أي أن على المُنادي منع السلوك الافتراضي).
  */
  function requireLocation(action) {
    if (hasLocation.value) {
      action()
      return false
    }
    pendingAction = action
    pickerOpen.value = true
    return true
  }

  /* تُستدعى من النافذة عند الاختيار */
  function confirmLocation(id) {
    setLocation(id)
    pickerOpen.value = false
    const act = pendingAction
    pendingAction = null
    if (act) act()
  }

  return {
    locations: serviceLocations,
    current,
    hasLocation,
    pickerOpen,
    setLocation,
    openPicker,
    closePicker,
    requireLocation,
    confirmLocation,
  }
}
