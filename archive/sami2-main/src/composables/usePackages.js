import { reactive, computed } from 'vue'
import { PKGS, BRANCHES, AR_DAYS, AR_MONTHS } from '@/data/packages'

/* ===== منسّقات الوقت والتاريخ — منقولة حرفيًا ===== */
export function fmtTime(min) {
  let h = Math.floor(min / 60)
  const m = min % 60
  const pm = h >= 12
  h = h % 12 || 12
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')} ${pm ? 'م' : 'ص'}`
}

export function fmtDate(d) {
  return `${AR_DAYS[d.getDay()]} ${d.getDate()} ${AR_MONTHS[d.getMonth()]} ${d.getFullYear()}`
}

export const rs = n => n.toLocaleString('ar-EG-u-nu-latn')

/*
  حالة صفحة الباقات — مُرحَّلة من كائن S في src/legacy/packages-gifts.html

  الصفحة تضم ثلاثة تدفقات:
  • packages : كتالوج الباقات (فلترة + فرز)
  • gift     : رحلة الإهداء   (4 خطوات + نجاح)
  • book     : رحلة حجز باقة  (4 خطوات + نجاح)
*/

const TODAY = new Date(2026, 6, 7)
const STORAGE_KEY = 'samiSiteBranch'

export const pkgOf = id => PKGS.find(p => p.id === id)
export const brOf = id => BRANCHES.find(b => b.id === id)

function readBranch() {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    return BRANCHES.some(b => b.id === v) ? v : null
  } catch { return null }
}

const state = reactive({
  page: 'packages',            // packages | gift | book
  filter: 'all',
  sort: 'pop',

  /* رحلة الإهداء */
  gstep: 0,
  gtype: null,
  gpkg: null,
  name: '', phone: '',
  msg: 'أنت تستحق لحظة من الراحة والاهتمام.\nاستمتع بتجربة فاخرة تمنحك الاسترخاء والعناية.',
  design: 'lux-dark',
  method: 'wa',
  when: 'now',
  schedDate: '',
  pay: null,
  saveCard: true,
  done: false,
  ref: null,

  siteBranch: readBranch(),

  /* رحلة حجز الباقة */
  bk: { step: 0, pkg: null, branch: null, dayIdx: null, period: 'all', time: null, notes: '', pay: null, done: false, ref: null },
})

export function usePackages() {
  /* ===== الكتالوج — نفس منطق الفلترة الأصلي ===== */
  const filteredPkgs = computed(() =>
    PKGS.filter(p => state.filter === 'all' || p.cat === state.filter)
  )

  /* ===== رحلة الإهداء ===== */
  const gCanNext = computed(() => {
    switch (state.gstep) {
      case 0: return !!state.gtype
      case 1: return !!state.gpkg
      case 2: return state.name.trim().length > 1 && state.phone.trim().length >= 9 && (state.when === 'now' || state.schedDate)
      case 3: return !!state.pay
    }
    return false
  })

  const gNextLabel = computed(() =>
    ['التالي: اختيار الهدية', 'التالي: تخصيص الهدية', 'متابعة إلى الدفع', '🔒 إتمام الإهداء'][state.gstep]
  )

  /* ===== رحلة الحجز ===== */
  const bkCanNext = computed(() => {
    const B = state.bk
    switch (B.step) {
      case 0: return B.dayIdx != null && B.time != null
      case 1: return true
    }
    return false
  })

  const bkNextLabel = computed(() =>
    ['التالي: تأكيد الحجز', 'متابعة إلى الدفع'][state.bk.step]
  )

  /* أيام الحجز — 12 يومًا من TODAY */
  const bkDays = () =>
    Array.from({ length: 12 }, (_, i) => {
      const d = new Date(TODAY)
      d.setDate(d.getDate() + i)
      return d
    })

  /* مواعيد اليوم — كل 45 دقيقة من 10ص حتى 9م */
  const bkSlots = day => {
    const out = []
    for (let t = 10 * 60; t <= 21 * 60; t += 45) {
      const busy = ((day.getDate() * 5 + t / 45) % 7 === 0)
      out.push({ t, busy })
    }
    return out
  }

  /* ===== بدء الرحلات ===== */
  function startGift(type, pkg) {
    state.page = 'gift'
    state.gstep = type ? 1 : 0
    state.gtype = type
    state.gpkg = pkg
    state.done = false
    state.ref = null
    scrollTo({ top: 0, behavior: 'smooth' })
  }

  /*
    مكان تنفيذ الخدمة يُحدَّد من النافذة المنبثقة قبل بدء الرحلة،
    فلم تعد هناك خطوة "اختيار الفرع" ضمن الخطوات.
  */
  function startBook(pkgId) {
    state.page = 'book'
    state.bk = { step: 0, pkg: pkgId, branch: state.siteBranch || null, dayIdx: null, period: 'all', time: null, notes: '', pay: null, done: false, ref: null }
    scrollTo({ top: 0, behavior: 'smooth' })
  }

  function backToPackages() {
    state.page = 'packages'
    state.done = false
    scrollTo({ top: 0 })
  }

  function setBranch(id) {
    state.siteBranch = id
    try { localStorage.setItem(STORAGE_KEY, id) } catch { /* التخزين غير متاح */ }
  }

  return {
    state, TODAY,
    pkgOf, brOf,
    filteredPkgs,
    gCanNext, gNextLabel,
    bkCanNext, bkNextLabel,
    bkDays, bkSlots,
    startGift, startBook, backToPackages, setBranch,
  }
}
