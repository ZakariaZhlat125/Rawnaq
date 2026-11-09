import { reactive, computed } from 'vue'
import { SERVICES, PKGS, DESIGNS, BRANCHES } from '@/data/gifts'

/*
  حالة صفحة الإهداء — مُرحَّلة من كائن S في src/legacy/gifts.html

  الأصل:
    const S={step:0,gtype:null,branch:null,activeCat:null,pkg:null,svcs:new Set(),
             sort:'pop',favs:new Set(),sender:'',name:'',phone:'',msg:'',
             design:'lux-dark',method:'wa',when:'now',schedDate:'',pay:null,
             extraCollapsed:false,terms:false,couponIn:'',coupon:0,couponCode:'',
             done:false,ref:null}

  حُوّلت Set إلى مصفوفة ليعمل التتبّع التفاعلي في Vue، مع الحفاظ على الدلالات.
*/

const VAT = 0.15

export const pkgOf = id => PKGS.find(p => p.id === id)
export const dsgOf = id => DESIGNS.find(d => d.id === id)
export const brOf = id => BRANCHES.find(b => b.id === id)
export const svcOf = id => SERVICES.find(s => s.id === id)

const state = reactive({
  step: 0,
  gtype: null,          // 'svc' | 'pkg'
  branch: null,
  activeCat: null,
  pkg: null,
  svcs: [],             // كان Set
  sort: 'pop',
  favs: [],             // كان Set
  sender: '', name: '', phone: '', msg: '',
  design: 'lux-dark',
  method: 'wa',
  when: 'now',
  schedDate: '',
  pay: null,
  extraCollapsed: false,
  terms: false,
  couponIn: '', coupon: 0, couponCode: '',
  done: false,
  ref: null,
})

export function useGifts() {
  /* ===== اختيار الخدمات ===== */
  const hasSvc = id => state.svcs.includes(id)
  const toggleSvc = id => {
    const i = state.svcs.indexOf(id)
    if (i === -1) state.svcs.push(id)
    else state.svcs.splice(i, 1)
  }

  /* ===== المفضلة ===== */
  const isFav = id => state.favs.includes(id)
  const toggleFav = id => {
    const i = state.favs.indexOf(id)
    if (i === -1) state.favs.push(id)
    else state.favs.splice(i, 1)
  }

  /* ===== الحسابات — منقولة حرفيًا ===== */
  const giftValue = computed(() => {
    if (state.gtype === 'svc') {
      return state.svcs.reduce((a, id) => a + svcOf(id).price, 0)
    }
    return state.pkg ? pkgOf(state.pkg).price : 0
  })

  const giftLabel = computed(() => {
    if (state.gtype === 'svc') {
      const n = state.svcs.length
      return n ? state.svcs.map(id => svcOf(id).name).join(' + ') : null
    }
    return state.pkg ? pkgOf(state.pkg).name : null
  })

  const priceParts = computed(() => {
    const val = giftValue.value
    const fee = state.branch ? brOf(state.branch).fee : 0
    const vat = Math.round((val + fee) * VAT)
    const disc = Math.min(state.coupon, val)
    return { val, fee, vat, disc, total: val + fee + vat - disc }
  })

  /* ===== التنقل بين الخطوات — نفس شروط canNext() ===== */
  const canNext = computed(() => {
    switch (state.step) {
      case 0: return !!state.gtype
      case 1: return state.gtype === 'svc' ? state.svcs.length > 0 : !!state.pkg
      case 2: return true
      case 3: return !!state.pay && state.terms
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
    state.done = false
    state.ref = null
    state.pay = null
    state.terms = false
  }

  return {
    state,
    pkgOf, dsgOf, brOf, svcOf,
    hasSvc, toggleSvc,
    isFav, toggleFav,
    giftValue, giftLabel, priceParts,
    canNext, go, reset,
  }
}
