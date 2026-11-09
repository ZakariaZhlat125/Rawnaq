import { paymentPolicy } from '@/utils/paymentPolicy'
import { reactive, computed } from 'vue'
import { useGiftDesigns } from '@/composables/useGiftDesigns'
import { useAuth } from '@/composables/useAuth'
import { createGiftCard } from '@/services/giftsApi'
import { initPayment } from '@/services/bookingApi'

/*
  حالة صفحة الإهداء — مبنية على بيانات حقيقية (خدمات/باقات حقيقية من الـ API)
  بدل المصفوفات الوهمية القديمة. راجع useBooking.js/usePackages.js لنفس النمط.

  gtype === 'svc'  → state.svcs مصفوفة خدمات حقيقية {id, categoryId, categoryName, name, dur, price}
  gtype === 'pkg'  → state.pkg الباقة الحقيقية الكاملة (نفس الكائن اللي بترجعه usePackages)
  gtype === 'val'  → state.value مبلغ بطاقة الهدية (بدون ضريبة عند الشراء؛ تُحتسب عند الاستخدام)
*/

const VAT = 0.15

export const dsgOf = id => useGiftDesigns().designOf(id)

const state = reactive({
  step: 0,
  gtype: null,          // 'svc' | 'pkg' | 'val'
  value: null,           // مبلغ بطاقة الهدية
  branch: null,
  activeCat: null,
  pkg: null,             // الباقة الحقيقية الكاملة، أو null
  svcs: [],              // الخدمات الحقيقية المختارة
  sort: 'pop',           // فرز كتالوج الباقات في هذه الرحلة بس
  favs: [],
  sender: '', name: '', phone: '', msg: '',
  design: 'lux-dark',
  method: 'wa',
  when: 'now',
  schedDate: '',
  pay: null,
  terms: false,
  placing: false,
  done: false,
  ref: null,
  claimUrl: null,
  claimToken: null,
  walletBalance: null,   // يتحمّل فى GiftPayStep.vue من /profile — لازم يكون معروف قبل السماح باختيار "المحفظة"
})

export function useGifts() {
  /* ===== اختيار الخدمات ===== */
  const hasSvc = id => state.svcs.some(s => s.id === id)
  const toggleSvc = service => {
    const i = state.svcs.findIndex(s => s.id === service.id)
    if (i === -1) state.svcs.push(service)
    else state.svcs.splice(i, 1)
  }

  /* ===== المفضلة ===== */
  const isFav = id => state.favs.includes(id)
  const toggleFav = id => {
    const i = state.favs.indexOf(id)
    if (i === -1) state.favs.push(id)
    else state.favs.splice(i, 1)
  }

  /* ===== الحسابات ===== */
  const giftValue = computed(() => {
    if (state.gtype === 'svc') return state.svcs.reduce((a, s) => a + s.price, 0)
    if (state.gtype === 'val') return Number(state.value) || 0
    return state.pkg?.price || 0
  })

  const giftLabel = computed(() => {
    if (state.gtype === 'svc') {
      return state.svcs.length ? state.svcs.map(s => s.name).join(' + ') : null
    }
    if (state.gtype === 'val') return giftValue.value ? `بطاقة هدية بقيمة ${giftValue.value} ر.س` : null
    return state.pkg?.name || null
  })

  const priceParts = computed(() => {
    const val = giftValue.value
    const vat = state.gtype === 'val' ? 0 : Math.round(val * VAT)
    return { val, vat, total: val + vat }
  })

  /* ===== التنقل بين الخطوات ===== */
  const canNext = computed(() => {
    switch (state.step) {
      case 0: return !!state.gtype
      case 1: {
        if (state.gtype === 'svc') return state.svcs.length > 0
        if (state.gtype === 'val') {
          const { min, max } = useGiftDesigns().designs.valueCard
          return giftValue.value >= min && giftValue.value <= max
        }
        return !!state.pkg
      }
      case 2: return state.name.trim().length > 1 && state.phone.trim().length >= 9
      case 3: return paymentPolicy.canPay(state, priceParts.value.total) && state.terms && !state.placing
    }
    return false
  })

  function go(dir) {
    if (dir > 0 && !canNext.value) return
    state.step += dir
    if (state.step < 0) state.step = 0
    scrollTo({ top: 0, behavior: 'smooth' })
  }

  function reset() {
    state.step = 0
    state.gtype = null
    state.pkg = null
    state.svcs = []
    state.value = null
    state.done = false
    state.ref = null
    state.claimUrl = null
    state.pay = null
    state.terms = false
    state.useWallet = false
    state.walletAmount = 0
    state.useLoyalty = false
    state.loyaltyPointsUsed = 0
  }

  /* إرسال الهدية فعليًا للباك إند — خدمة أو باقة واحدة بس لكل هدية */
  async function placeGift() {
    if (!canNext.value) return null
    state.placing = true

    try {
      const branchId = state.branch === 'home-service' ? 0 : (Number.isInteger(state.branch) ? state.branch : null)

      const payload = {
        location: {
          recipient_name: state.name.trim(),
          recipient_mobile: state.phone.trim(),
          message: state.msg.trim() || undefined,
          sender_name: state.sender.trim() || undefined,
        },
        design: state.design,
        branch: branchId,
        send_channel: state.method || 'link',
      }

      if (state.gtype === 'val') {
        payload.type = 'value'
        payload.value_amount = giftValue.value
      } else if (state.gtype === 'svc') {
        payload.services = [{ subServices: state.svcs.map(s => ({ id: s.id })) }]
      } else {
        payload.packages = [{ id: state.pkg.id }]
      }

      const created = await createGiftCard(payload)
      const { gateway, ...options } = paymentPolicy.payment(state, priceParts.value.total)
      const payment = await initPayment(gateway, options)
      if (payment.payment_url) { window.location.href = payment.payment_url; return { created, payment } }

      state.ref = created?.data?.gift_card_id ? `#GIFT-${created.data.gift_card_id}` : '#GIFT'
      state.claimUrl = created?.data?.share_url || created?.data?.claim_url || null
      state.claimToken = created?.data?.claim_token || null
      state.done = true
      state.step = 4

      return { created, payment }
    } finally {
      state.placing = false
    }
  }

  return {
    state,
    dsgOf,
    hasSvc, toggleSvc,
    isFav, toggleFav,
    giftValue, giftLabel, priceParts,
    canNext, go, reset,
    placeGift,
  }
}
