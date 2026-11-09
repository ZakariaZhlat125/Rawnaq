import { reactive, computed } from 'vue'
import { CATS, SERVICES, UPSELL, EMPLOYEES, BRANCHES, AR_DAYS, AR_MONTHS } from '@/data/booking'

/*
  حالة صفحة الحجز — مُرحَّلة من كائن S في src/legacy/booking.html

  حُوّلت Set إلى مصفوفة ليعمل التتبّع التفاعلي في Vue،
  مع الحفاظ على نفس الدلالات والحسابات حرفيًا.
*/

const VAT = 0.15
const STORAGE_KEY = 'samiSiteBranch'

export const catOf = id => CATS.find(c => c.id === id)
export const svcOf = id => SERVICES.find(s => s.id === id)
export const bestEmp = cat => EMPLOYEES[cat].slice().sort((a, b) => b.r - a.r)[0]

export const rs = n => n.toLocaleString('ar-EG-u-nu-latn')

export function fmtTime(min) {
  let h = Math.floor(min / 60)
  const m = min % 60
  const pm = h >= 12
  h = h % 12 || 12
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')} ${pm ? 'م' : 'ص'}`
}

export function fmtDur(min) {
  const h = Math.floor(min / 60)
  const m = min % 60
  if (!h) return `${m} د`
  return m ? `${h} س ${m} د` : `${h} س`
}

export function fmtDate(d) {
  return `${AR_DAYS[d.getDay()]} ${d.getDate()} ${AR_MONTHS[d.getMonth()]} ${d.getFullYear()}`
}

function readBranch() {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    return BRANCHES.some(b => b.id === v) ? v : null
  } catch { return null }
}

const state = reactive({
  step: 0,
  branch: readBranch(),
  services: [],          // كان Set
  activeCat: null,
  mode: null,            // 'auto' | 'manual'
  emp: {},               // cat -> اسم الموظف أو 'any'
  calStart: new Date(2026, 6, 6),
  date: null,
  period: 'all',
  time: null,
  upsell: [],            // كان Set
  notes: '',
  cust: { name: 'محمد عبدالله', phone: '+966 50 123 4567', mail: 'mohammed@gmail.com' },
  pay: null,
  usePoints: false, pointsVal: 500,
  useWallet: false, walletVal: 120,
  coupon: 0, couponCode: '',
  done: false, bookRef: null,
})

export function useBooking() {
  /* ===== اختيار الخدمات ===== */
  const hasSvc = id => state.services.includes(id)
  const toggleSvc = id => {
    const i = state.services.indexOf(id)
    if (i === -1) state.services.push(id)
    else state.services.splice(i, 1)
  }

  const hasUpsell = id => state.upsell.includes(id)
  const toggleUpsell = id => {
    const i = state.upsell.indexOf(id)
    if (i === -1) state.upsell.push(id)
    else state.upsell.splice(i, 1)
  }

  const selSvcs = computed(() => state.services.map(svcOf))
  const selCats = computed(() => CATS.filter(c => selSvcs.value.some(s => s.cat === c.id)))

  /* ===== الحسابات — منقولة حرفيًا ===== */
  const subTotal = computed(() =>
    selSvcs.value.reduce((a, s) => a + s.price, 0) +
    state.upsell.map(id => UPSELL.find(u => u.id === id)).reduce((a, u) => a + u.price, 0)
  )

  /*
    ملاحظة: الأصل يحسب هنا فاصلًا بين كل خدمة فرعية،
    بينما buildSession يضع الفاصل بين الخدمات الرئيسية فقط.
    نُبقي السلوك كما هو للحفاظ على التطابق.
  */
  const totalDur = computed(() => {
    const svcs = selSvcs.value
    return svcs.reduce((a, s) => a + s.dur, 0) + 15 * Math.max(0, svcs.length - 1)
  })

  const priceParts = computed(() => {
    const sub = subTotal.value
    const pts = state.usePoints ? Math.min(50, sub) : 0
    const wal = state.useWallet ? Math.min(state.walletVal, sub - pts) : 0
    const cpn = Math.min(state.coupon, Math.max(0, sub - pts - wal))
    const net = Math.max(0, sub - pts - wal - cpn)
    const vat = Math.round(net * VAT)
    return { sub, pts, wal, cpn, vat, total: net + vat }
  })

  /* ===== شروط التنقل ===== */
  const canProceed = computed(() => {
    switch (state.step) {
      case 0: return state.services.length > 0
      case 1: return state.mode === 'auto' || (state.mode === 'manual' && selCats.value.every(c => state.emp[c.id]))
      case 2: return !!state.date && state.time != null
      case 3: return !!(state.cust.name.trim() && state.cust.phone.trim())
      case 4: return !!state.pay
    }
    return false
  })

  const nextLabel = computed(() =>
    ['التالي: اختيار الموظف',
      state.mode === 'auto' ? 'التالي: اختيار الوقت' : 'متابعة اختيار الوقت',
      'التالي: تأكيد الحجز', 'تأكيد الحجز والدفع', '🔒 إتمام الدفع بأمان'][state.step]
  )

  /* ===== المواعيد المتاحة ليوم ===== */
  const slotsForDay = d => {
    const dur = totalDur.value
    const open = 9 * 60, close = 23 * 60
    const out = []
    for (let t = open; t + dur <= close; t += 30) {
      const busy = ((d.getDate() * 7 + t / 30) % 9 === 0)
      out.push({ t, busy })
    }
    return out
  }

  /*
    خطة الجلسة — بطاقة واحدة لكل خدمة رئيسية (لا لكل خدمة فرعية)
    مع فاصل 15 دقيقة بين كل خدمة رئيسية والتالية.
  */
  const buildSession = startMin => {
    const svcs = selSvcs.value
    const order = []
    const groups = new Map()
    svcs.forEach(s => {
      if (!groups.has(s.cat)) { groups.set(s.cat, { services: [], dur: 0 }); order.push(s.cat) }
      const g = groups.get(s.cat)
      g.services.push(s)
      g.dur += s.dur
    })

    const items = []
    let cursor = startMin
    order.forEach((catId, idx) => {
      if (idx > 0) {
        items.push({ isBreak: true, start: cursor, end: cursor + 15, dur: 15 })
        cursor += 15
      }
      const g = groups.get(catId)
      const empSel = state.mode === 'manual' && state.emp[catId] && state.emp[catId] !== 'any'
        ? state.emp[catId]
        : bestEmp(catId).n
      items.push({
        isBreak: false,
        cat: catOf(catId),
        emp: empSel,
        services: g.services,
        count: g.services.length,
        dur: g.dur,
        start: cursor,
        end: cursor + g.dur,
      })
      cursor += g.dur
    })
    return { items, end: cursor, parallel: false, emps: [...new Set(items.filter(i => !i.isBreak).map(i => i.emp))] }
  }

  function setBranch(id) {
    state.branch = id
    try { localStorage.setItem(STORAGE_KEY, id) } catch { /* التخزين غير متاح */ }
  }

  function go(dir) {
    if (dir > 0 && !canProceed.value) return
    state.step = Math.max(0, state.step + dir)
    scrollTo({ top: 0, behavior: 'smooth' })
  }

  return {
    state, VAT,
    catOf, svcOf, bestEmp,
    hasSvc, toggleSvc, hasUpsell, toggleUpsell,
    selSvcs, selCats,
    subTotal, totalDur, priceParts,
    canProceed, nextLabel,
    slotsForDay, buildSession,
    setBranch, go,
  }
}
