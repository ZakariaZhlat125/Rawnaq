import { paymentPolicy } from '@/utils/paymentPolicy'
import { reactive, computed, ref } from 'vue'
import { BRANCHES, AR_DAYS, AR_MONTHS } from '@/data/packages'
import { fetchPackages } from '@/services/packagesApi'
import { useLanguage } from '@/composables/useLanguage'
import { localizeField, translationSource } from '@/utils/i18nField'

/* ===== منسّقات الوقت والتاريخ ===== */
/* "HH:mm" -> "05:30 م" — الوقت الحقيقي راجع من الـ API كنص جاهز، مش رقم دقائق */
export function fmtTime(hhmm) {
  if (!hhmm) return ''
  let [h, m] = hhmm.split(':').map(Number)
  const pm = h >= 12
  h = h % 12 || 12
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')} ${useLanguage().state.lang === 'en' ? (pm ? 'PM' : 'AM') : (pm ? 'م' : 'ص')}`
}

export function fmtDate(d) {
  if (useLanguage().state.lang === 'en') return new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(d)
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

const TODAY = new Date()
TODAY.setHours(0, 0, 0, 0)
const STORAGE_KEY = 'samiSiteBranch'

/*
  البيانات الخام كما رجعت من الـ API — بترجمات كل حقل ({ar,en}) محفوظة زي
  ما هي، عشان نقدر نعيد حساب النص المعروض فورًا لما اللغة تتغيّر بدون
  إعادة تحميل. الحل النهائي (المترجم للغة الحالية) بيتحسب جوه usePackages().
*/
const rawPackages = ref([])

/* ألوان وأيقونات جاهزة نعيد تدويرها على الباقات الحقيقية — تفصيل شكلي بحت */
const STYLE_ROTATION = [
  { color: 'var(--p-relax)', hex: '#4E9E6F', ico: '<path d="M12 3c-1.2 3-4 4.5-4 8a4 4 0 008 0c0-3.5-2.8-5-4-8z"/><path d="M5 21h14"/>' },
  { color: 'var(--p-fast)', hex: '#D98A3B', ico: '<path d="M13 2L3 14h9l-1 8 10-12h-9z"/>' },
  { color: 'var(--p-full)', hex: '#2F8F86', ico: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>' },
  { color: 'var(--p-groom)', hex: '#8B6FC0', ico: '<path d="M6 3h12l4 6-10 12L2 9z"/><path d="M2 9h20M12 3l-4 6 4 12 4-12-4-6z"/>' },
  { color: 'var(--p-home)', hex: '#3E8E9E', ico: '<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21V12h6v9"/>' },
  { color: 'var(--p-vip)', hex: '#B0642A', ico: '<path d="M2 8l4 4 6-8 6 8 4-4v10a2 2 0 01-2 2H4a2 2 0 01-2-2z"/>' },
]

function asTranslations(text, translations) {
  return translationSource({ text, text_translations: translations }, 'text')
}

function toRawPackage(p, i) {
  const style = STYLE_ROTATION[i % STYLE_ROTATION.length]
  const services = p.services || []

  return {
    id: p.id,
    nameT: asTranslations(p.name, p.name_translations),
    cat: 'all',
    dur: services.reduce((sum, s) => sum + (s.duration_min || 0), 0),
    price: p.package_price,
    savings: p.savings_amount,
    originalPrice: p.services_total_price,
    branchId: p.branch?.id ?? null,
    branchNameT: asTranslations(p.branch?.name, p.branch?.name_translations),
    color: style.color,
    hex: style.hex,
    hot: !!p.is_featured,
    descT: asTranslations(p.description, p.description_translations),
    incT: services.map(s => asTranslations(s.service_name, s.service_name_translations)),
    ico: style.ico,
    img: p.feature_image,
    offer: p.type === 'offer',
  }
}

/* العروض مش ضمن كتالوج الباقات — صفحة العرض بتسجّله هنا قبل ما تفتح رحلة الحجز */
const extraPackages = new Map()

export function registerPackage(p) {
  if (!p?.id) return
  const raw = toRawPackage(p, extraPackages.size + rawPackages.value.length)
  extraPackages.set(p.id, raw)
  rawPackages.value = [...rawPackages.value.filter(item => item.id !== p.id), raw]
}

let packagesPromise = null

export function loadPackages() {
  if (packagesPromise) return packagesPromise
  packagesPromise = fetchPackages()
    .then(data => data.map(toRawPackage))
    .catch(() => [])
    .then(list => {
      const ids = new Set(list.map(item => item.id))
      rawPackages.value = [...list, ...[...extraPackages.values()].filter(item => !ids.has(item.id))]
    })
  return packagesPromise
}

loadPackages()

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
  claimUrl: null,
  claimToken: null,

  siteBranch: readBranch(),

  /* رحلة حجز الباقة */
  bk: {
    step: 0, pkg: null, branch: null, employee: null, dayIdx: null, period: 'all', time: null,
    notes: '', pay: null, done: false, ref: null,
    walletBalance: 0, loyaltyPoints: 0, pointValue: 0.5,
    useWallet: false, walletAmount: 0, useLoyalty: false, loyaltyPointsUsed: 0,
  },
})

export function usePackages() {
  const { state: lang } = useLanguage()
  const pick = t => localizeField(t, lang.lang) || ''

  /* الباقات مترجَمة للغة الحالية — بتتحدّث فورًا لما المستخدم يبدّل اللغة */
  const packages = computed(() => rawPackages.value.map(p => ({
    id: p.id,
    get name() { return pick(p.nameT) },
    cat: p.cat,
    dur: p.dur,
    price: p.price,
    savings: p.savings,
    originalPrice: p.originalPrice,
    branchId: p.branchId,
    get branchName() { return pick(p.branchNameT) },
    color: p.color,
    hex: p.hex,
    hot: p.hot,
    get desc() { return pick(p.descT) },
    get inc() { return p.incT.map(pick).filter(Boolean) },
    ico: p.ico,
    img: p.img,
    offer: p.offer,
  })))

  const pkgOf = id => packages.value.find(p => p.id === id)

  /* ===== الكتالوج — نفس منطق الفلترة الأصلي ===== */
  const filteredPkgs = computed(() =>
    packages.value.filter(p => !p.offer && (state.filter === 'all' || p.cat === state.filter))
  )

  /* ===== رحلة الإهداء ===== */
  const gCanNext = computed(() => {
    switch (state.gstep) {
      case 0: return !!state.gtype
      case 1: return !!state.gpkg
      case 2: return state.name.trim().length > 1 && state.phone.trim().length >= 9 && (state.when === 'now' || state.schedDate)
      case 3: { const subtotal = Math.max(Number(pkgOf(state.gpkg)?.price) || 0, 0); return paymentPolicy.canPay(state, subtotal + Math.round(subtotal * 0.15)) }
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
      case 0: return B.dayIdx != null && B.time != null && !!B.employee
      case 1: return true
    }
    return false
  })

  const bkNextLabel = computed(() =>
    ['التالي: تأكيد الحجز', 'متابعة إلى الدفع'][state.bk.step]
  )

  /* أيام الحجز — 12 يومًا من اليوم الحقيقي */
  const bkDays = () =>
    Array.from({ length: 12 }, (_, i) => {
      const d = new Date()
      d.setHours(0, 0, 0, 0)
      d.setDate(d.getDate() + i)
      return d
    })

  /* ===== بدء الرحلات ===== */
  function startGift(type, pkg) {
    state.pay = null
    state.useWallet = false
    state.walletAmount = 0
    state.useLoyalty = false
    state.loyaltyPointsUsed = 0
    state.page = 'gift'
    state.gstep = type ? 1 : 0
    state.gtype = type
    state.gpkg = pkg
    state.done = false
    state.ref = null
    state.claimUrl = null
    state.claimToken = null
    scrollTo({ top: 0, behavior: 'smooth' })
  }

  function startBook(pkgId) {
    /* الباقة مربوطة بفرع محدد من الباك إند — نستخدمه هو، مش مكان التنفيذ العام للموقع */
    const branchId = pkgOf(pkgId)?.branchId ?? null
    state.page = 'book'
    state.bk = { step: 0, pkg: pkgId, branch: branchId, employee: null, dayIdx: null, period: 'all', time: null, notes: '', pay: null, done: false, ref: null }
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
    packages,
    filteredPkgs,
    gCanNext, gNextLabel,
    bkCanNext, bkNextLabel,
    bkDays,
    startGift, startBook, backToPackages, setBranch,
  }
}
