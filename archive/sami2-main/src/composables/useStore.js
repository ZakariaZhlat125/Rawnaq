import { reactive, computed } from 'vue'
import { PRODUCTS } from '@/data/store'

/*
  حالة المتجر — مُرحَّلة من كائن S في src/legacy/store.html

  الأصل:
    const S={cat:'all',sort:'new',view:'grid',showAll:false,favs:new Set(),
             cart:new Map(),firstAdd:true,page:'store',order:null,
             ck:{method:'pickup',branch:'qr',addr:'',name:'...',phone:'...',pay:null,terms:false}}

  حُوّلت Set و Map إلى مصفوفة وكائن ليعمل التتبّع التفاعلي في Vue،
  مع الحفاظ على نفس الدلالات تمامًا.
*/

const pOf = id => PRODUCTS.find(p => p.id === id)

const state = reactive({
  cat: 'all',
  sort: 'new',
  view: 'grid',
  showAll: false,
  favs: [],            // كان Set
  cart: {},            // كان Map — { [id]: qty }
  firstAdd: true,
  page: 'store',       // store | checkout | success
  order: null,
  ck: {
    method: 'pickup',
    branch: 'qr',
    addr: '',
    name: 'محمد عبدالله',
    phone: '+966 50 123 4567',
    pay: null,
    terms: false,
  },
})

export function useStore() {
  /* ===== المفضلة ===== */
  const isFav = id => state.favs.includes(id)
  const toggleFav = id => {
    const i = state.favs.indexOf(id)
    if (i === -1) state.favs.push(id)
    else state.favs.splice(i, 1)
  }

  /* ===== السلة ===== */
  const cartQty = computed(() =>
    Object.values(state.cart).reduce((a, q) => a + q, 0)
  )
  const cartTotal = computed(() =>
    Object.entries(state.cart).reduce((a, [id, q]) => {
      const p = pOf(id)
      return a + (p ? p.pr * q : 0)
    }, 0)
  )
  const cartItems = computed(() =>
    Object.entries(state.cart)
      .map(([id, qty]) => ({ ...pOf(id), qty }))
      .filter(x => x.id)
  )

  const addToCart = id => { state.cart[id] = (state.cart[id] || 0) + 1 }
  const setQty = (id, qty) => {
    if (qty <= 0) delete state.cart[id]
    else state.cart[id] = qty
  }
  const removeFromCart = id => { delete state.cart[id] }
  const clearCart = () => { Object.keys(state.cart).forEach(k => delete state.cart[k]) }

  /* ===== التصفية والترتيب — منقول حرفيًا من sorted() الأصلية ===== */
  const visibleProducts = computed(() => {
    const l = PRODUCTS.filter(p => state.cat === 'all' || p.cat === state.cat).slice()

    if (state.sort === 'low') l.sort((a, b) => a.pr - b.pr)
    else if (state.sort === 'high') l.sort((a, b) => b.pr - a.pr)
    else if (state.sort === 'best') l.sort((a, b) => (b.best || 0) - (a.best || 0) || b.ts - a.ts)
    else l.sort((a, b) => b.ts - a.ts)

    return state.showAll ? l : l.slice(0, 8)
  })

  /* ===== الدفع — منقول حرفيًا من ckParts() و ckCan() ===== */
  const ckParts = computed(() => {
    const sub = cartTotal.value
    const ship = state.ck.method === 'delivery' ? 25 : 0
    const vat = Math.round(sub * 0.15)
    return { sub, ship, vat, total: sub + vat + ship }
  })

  const ckCan = computed(() => {
    const c = state.ck
    return Object.keys(state.cart).length > 0 &&
      c.name.trim().length > 1 &&
      c.phone.trim().length >= 9 &&
      !!c.pay &&
      c.terms &&
      (c.method === 'pickup' || c.addr.trim().length > 4)
  })

  /* إنشاء الطلب — نفس منطق placeOrder الأصلي */
  function placeOrder(branches, payMethods) {
    if (!ckCan.value) return null
    const c = state.ck
    state.order = {
      ref: '#ORD-2607-' + Math.floor(1000 + Math.random() * 9000),
      items: Object.entries(state.cart).map(([id, q]) => ({ p: pOf(id), q })),
      parts: ckParts.value,
      method: c.method,
      branch: branches.find(b => b.id === c.branch),
      addr: c.addr,
      pay: payMethods.find(m => m.id === c.pay).n,
      name: c.name,
      phone: c.phone,
    }
    clearCart()
    state.firstAdd = true
    state.page = 'success'
    return state.order
  }

  return {
    state,
    pOf,
    isFav, toggleFav,
    cartQty, cartTotal, cartItems,
    addToCart, setQty, removeFromCart, clearCart,
    visibleProducts,
    ckParts, ckCan, placeOrder,
  }
}
