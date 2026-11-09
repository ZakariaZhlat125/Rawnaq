import { useLanguage } from '@/composables/useLanguage'
import { reactive, computed, watch } from 'vue'
import { selectedServiceReward } from '@/composables/useServiceRepeatRewards'
import { servicePrice } from '@/utils/servicePrice'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { homeService } from '@/composables/useHomeService'
import { useStore } from '@/composables/useStore'
import { paymentPolicy } from '@/utils/paymentPolicy'

/*
  حالة صفحة الحجز — مبنية على بيانات حقيقية من الباك إند (خدمات حقيقية،
  موظفون حقيقيون، أوقات متاحة حقيقية). كل خدمة مختارة لها موظف ووقت
  خاص بيها (مطابق لشكل payload الحقيقي في POST /mobile/cart/bookings:
  services[].subServices[] كل عنصر فيه id/date/time/staffId مستقل).

  مكان التنفيذ (الفرع) بييجي من useServiceLocation مباشرة في كل مكوّن
  محتاجه — مفيش نسخة تانية منفصلة هنا، عشان نتجنب تكرار نفس الاختيار
  في مصدرين مختلفين زي ما كان حاصل في النسخة الوهمية القديمة.
*/

const VAT = 0.15

export const rs = n => n.toLocaleString('ar-EG-u-nu-latn')

/* "HH:mm" -> "05:30 م" */
export function fmtTimeStr(hhmm) {
  if (!hhmm) return ''
  let [h, m] = hhmm.split(':').map(Number)
  const pm = h >= 12
  h = h % 12 || 12
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')} ${useLanguage().state.lang === 'en' ? (pm ? 'PM' : 'AM') : (pm ? 'م' : 'ص')}`
}

export function fmtDur(min) {
  const h = Math.floor(min / 60)
  const m = min % 60
  if (useLanguage().state.lang === 'en') return [h ? `${h} h` : '', m || !h ? `${m} min` : ''].filter(Boolean).join(' ')
  if (!h) return `${m} د`
  return m ? `${h} س ${m} د` : `${h} س`
}

export function fmtDate(d) {
  if (useLanguage().state.lang === 'en') return new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(d)
  const AR_DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
  const AR_MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
  return `${AR_DAYS[d.getDay()]} ${d.getDate()} ${AR_MONTHS[d.getMonth()]} ${d.getFullYear()}`
}

const state = reactive({
  homeCityId: '',
  homeLatitude: '',
  homeLongitude: '',
  step: 0,
  services: [],      // [{id, categoryId, categoryName, name, dur, price}]
  activeCat: null,    // القسم المفتوح حاليًا في خطوة اختيار الخدمات

  repeatRewardIds: {},
  mode: null,         // 'auto' | 'manual'
  emp: {},            // serviceId -> { id, name }
  staffOptions: {},   // serviceId -> available employees; used by automatic assignment at time selection

  date: null,         // Date واحد مشترك لكل الخدمات المختارة
  time: {},           // serviceId -> "HH:mm"

  cust: { name: '', phone: '', mail: '' },
  notes: '',

  pay: null,
  pointValue: 0.5,
  loyaltyPoints: null,
  rewards: {
    useWallet: false,
    walletAmount: 0,
    useLoyalty: false,
    loyaltyPoints: 0,
    couponCode: '',
    couponApplied: '',
    couponDiscount: 0,
    couponStatus: '',
    couponMessage: '',
  },
  done: false,
  purchasedProducts: [],
  bookRef: null,
  bookingIds: [],
  walletBalance: null,   // يتحمّل فى PayStep.vue من /profile — لازم يكون معروف قبل السماح باختيار "المحفظة"
})

const { current: bookingLocation } = useServiceLocation()
watch(() => bookingLocation.value?.id, (id, previous) => {
  if (String(id) === String(previous) || state.done) return
  state.emp = {}
  state.staffOptions = {}
  state.time = {}
  state.date = null
  state.mode = null
  state.step = 0
  state.rewards.couponApplied = ''
  state.rewards.couponDiscount = 0
})

export function useBooking() {
  const { cartTotal, cartItems } = useStore()
  const selectedProducts = computed(() => state.done ? state.purchasedProducts : cartItems.value)
  const productsTotal = computed(() => state.done ? state.purchasedProducts.reduce((sum, p) => sum + p.pr * p.qty, 0) : cartTotal.value)
  const hasSvc = id => state.services.some(s => s.id === id)

  const toggleSvc = service => {
    const i = state.services.findIndex(s => s.id === service.id)
    if (i === -1) {
      state.services.push(service)
    } else {
      state.services.splice(i, 1)
      delete state.emp[service.id]
      delete state.time[service.id]
    }
  }

  const selSvcs = computed(() => state.services.map(service => {
    const reward = selectedServiceReward(service.id, state.repeatRewardIds[service.id])
    return { ...service, repeatRewardId: reward?.id || null, price: reward ? 0 : servicePrice(service, current.value) }
  }))

  const subTotal = computed(() => selSvcs.value.reduce((a, s) => a + (Number(s.price) || 0), 0))
  const totalDur = computed(() => selSvcs.value.reduce((a, s) => a + s.dur, 0))

  const { current } = useServiceLocation()
  const homeFee = computed(() => current.value?.home ? Number(homeService.cities.find(c => String(c.id) === String(state.homeCityId))?.home_service_fee || 0) : 0)
  const homeCovered = computed(() => {
    if (!current.value?.home) return true
    if (!homeService.enabled || !state.homeCityId || state.homeLatitude === '' || state.homeLongitude === '') return false
    const lat=Number(state.homeLatitude), lng=Number(state.homeLongitude), rad=n=>n*Math.PI/180
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat)>90 || Math.abs(lng)>180) return false
    const a=Math.sin(rad(lat-homeService.latitude)/2)**2+Math.cos(rad(lat))*Math.cos(rad(homeService.latitude))*Math.sin(rad(lng-homeService.longitude)/2)**2
    return 6371*2*Math.asin(Math.sqrt(Math.min(1,a))) <= Number(homeService.radius_km)
  })
  const priceParts = computed(() => {
    const sub = subTotal.value + productsTotal.value + homeFee.value
    const vat = Math.round(sub * VAT * 100) / 100
    return { homeFee: homeFee.value, sub, products: productsTotal.value, vat, total: sub + vat }
  })

  const pointValue = computed(() => Math.max(Number(state.pointValue) || 0.5, 0.01))
  const couponDiscount = computed(() => {
    if (!state.rewards.couponApplied) return 0
    return Math.min(Math.max(Number(state.rewards.couponDiscount) || 0, 0), priceParts.value.total)
  })
  const walletDiscount = computed(() => {
    if (!state.rewards.useWallet) return 0
    const remaining = Math.max(priceParts.value.total - couponDiscount.value, 0)
    return Math.min(
      Math.max(Number(state.rewards.walletAmount) || 0, 0),
      Math.max(Number(state.walletBalance) || 0, 0),
      remaining,
    )
  })
  const loyaltyPointsUsed = computed(() => {
    if (!state.rewards.useLoyalty) return 0
    const remaining = Math.max(priceParts.value.total - couponDiscount.value - walletDiscount.value, 0)
    const byAmount = Math.ceil(remaining / pointValue.value)
    return Math.min(
      Math.max(parseInt(state.rewards.loyaltyPoints, 10) || 0, 0),
      Math.max(parseInt(state.loyaltyPoints, 10) || 0, 0),
      byAmount,
    )
  })
  const loyaltyDiscount = computed(() => {
    const remaining = Math.max(priceParts.value.total - couponDiscount.value - walletDiscount.value, 0)
    return Math.min(loyaltyPointsUsed.value * pointValue.value, remaining)
  })
  const rewardDiscountTotal = computed(() => couponDiscount.value + walletDiscount.value + loyaltyDiscount.value)
  const payableTotal = computed(() => Math.max(priceParts.value.total - rewardDiscountTotal.value, 0))

  function setEmployee(serviceId, employee) {
    state.emp = { ...state.emp, [String(serviceId)]: employee }
  }

  function setTime(serviceId, time) {
    state.time[serviceId] = time
  }

  const canProceed = computed(() => {
    switch (state.step) {
      case 0:
        return !!current.value && state.services.length > 0
      case 1:
        return !!state.mode && selSvcs.value.every(s => state.emp[s.id])
      case 2:
        return !!state.date && selSvcs.value.every(s => state.time[s.id])
      case 3:
        // بيانات العميل تُقرأ من الحساب عند الدفع؛ حقولها مخفية مؤقتًا في شاشة التأكيد.
        return homeCovered.value
      case 4:
        if (!homeCovered.value) return false
        if (!paymentPolicy.checkoutEnabled) return false
        if (payableTotal.value <= 0) {
          return !!(selSvcs.value.some(s => s.repeatRewardId) || state.rewards.useWallet || state.rewards.useLoyalty || state.rewards.couponApplied || state.pay)
        }
        return paymentPolicy.methods({ total: priceParts.value.total, walletBalance: state.walletBalance,
          payable: payableTotal.value, hasRewards: walletDiscount.value > 0 || loyaltyPointsUsed.value > 0,
        }).some(method => method.id === state.pay && method.enabled)
    }
    return false
  })

  const nextLabel = computed(() =>
    ['التالي: اختيار الموظف',
      state.mode === 'auto' ? 'التالي: اختيار الوقت' : 'متابعة اختيار الوقت',
      'التالي: تأكيد الحجز', 'تأكيد الحجز والدفع', '🔒 إتمام الدفع بأمان'][state.step]
  )

  function go(dir) {
    if (dir > 0 && !canProceed.value) return
    state.step = Math.max(0, state.step + dir)
    scrollTo({ top: 0, behavior: 'smooth' })
  }

  function reset() {
    state.homeCityId = ''
    state.homeLatitude = ''
    state.homeLongitude = ''
    state.repeatRewardIds = {}
    state.step = 0
    state.services = []
    state.activeCat = null
    state.mode = null
    state.emp = {}
    state.staffOptions = {}
    state.date = null
    state.time = {}
    state.notes = ''
    state.pay = null
    state.rewards.useWallet = false
    state.rewards.walletAmount = 0
    state.rewards.useLoyalty = false
    state.rewards.loyaltyPoints = 0
    state.rewards.couponCode = ''
    state.rewards.couponApplied = ''
    state.rewards.couponDiscount = 0
    state.rewards.couponStatus = ''
    state.rewards.couponMessage = ''
    state.purchasedProducts = []
    state.done = false
    state.bookRef = null
    state.bookingIds = []
  }

  return {
    state, VAT, homeFee, homeCovered,
    hasSvc, toggleSvc,
    selSvcs, subTotal, totalDur, priceParts, selectedProducts,
    pointValue, couponDiscount, walletDiscount, loyaltyPointsUsed, loyaltyDiscount, rewardDiscountTotal, payableTotal,
    setEmployee, setTime,
    canProceed, nextLabel,
    go, reset,
  }
}
