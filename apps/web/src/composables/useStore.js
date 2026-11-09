import { paymentPolicy } from '@/utils/paymentPolicy'
//import { reactive, computed } from 'vue'
//import { PRODUCTS } from '@/data/store'

import { reactive, computed, ref, watch } from 'vue'
import { useLanguage } from '@/composables/useLanguage'
import { localizeField, translationSource } from '@/utils/i18nField'
import { fetchShopCatalog } from '@/services/shopApi'
import {
  fetchProductDetail, fetchLogisticZones,
  getCartList, addToCart as apiAddToCart, updateCart as apiUpdateCart, removeCart as apiRemoveCart,
  getAddressList, addAddress, placeOrder as apiPlaceOrder,
} from '@/services/productsApi'



/*
  حالة المتجر — مُرحَّلة من كائن S في src/legacy/store.html

  الأصل:
    const S={cat:'all',sort:'new',view:'grid',showAll:false,favs:new Set(),
             cart:new Map(),firstAdd:true,page:'store',order:null,
             ck:{method:'pickup',branch:'qr',addr:'',name:'...',phone:'...',pay:null,terms:false}}

  حُوّلت Set و Map إلى مصفوفة وكائن ليعمل التتبّع التفاعلي في Vue،
  مع الحفاظ على نفس الدلالات تمامًا.
*/

const { state: language } = useLanguage()
const rawProducts = ref([])
const rawCategoryItems = ref([])
const products = computed(() => rawProducts.value.map(p => ({ ...p, n: localizeField(p.n, language.lang), d: localizeField(p.d, language.lang) })))
const categories = computed(() => rawCategoryItems.value.map(c => ({ ...c, n: localizeField(c.n, language.lang) })))
let catalogRequest = 0
let productsLoaded = false

async function loadProducts(force = false) {
  if (productsLoaded && !force) return
  const request = ++catalogRequest
  productsLoaded = true

  try {
    const { categories: rawCategories = [] } = await fetchShopCatalog()
    if (request !== catalogRequest) return
    const byId = new Map()

    rawCategories.forEach(cat => {
      (cat.products || []).forEach(p => {
        if (!byId.has(p.id)) {
          byId.set(p.id, {
            id: p.id,
            n: translationSource(p, 'name'),
            d: translationSource(p, 'short_description') || translationSource(p, 'description') || '',
            pr: Number(p.min_price ?? p.max_price ?? 0),
            image: p.image || p.feature_image || null,
            cat: cat.id,
            branchId: p.branch_id ?? null,
            stockQty: Number(p.stock_qty ?? 0),
            maxPurchaseQty: Number(p.max_purchase_qty ?? 0) || null,
            shape: 'jar',
            best: 0,
            ts: p.id,
          })
        }
      })
    })

    rawProducts.value = Array.from(byId.values())
    rawCategoryItems.value = [
      { id: 'all', n: 'كل المنتجات', shape: 'kit' },
      ...rawCategories.map((c, i) => ({
        id: c.id,
        n: translationSource(c, 'name'),
        shape: ['pump', 'spray', 'dropper', 'jar', 'perfume', 'kit'][i % 6],
      })),
    ]
  } catch (err) {
    if (request !== catalogRequest) return
    productsLoaded = false
    if (rawProducts.value.length) return
    rawProducts.value = []
    rawCategoryItems.value = [{ id: 'all', n: 'كل المنتجات', shape: 'kit' }]
  }
}

// The shell also imports this store on mobile. Only load the full catalogue
// when the desktop store is opened; home cards can seed their own live records.
watch(() => language.lang, () => { if (productsLoaded) loadProducts(true) })

function seedProducts(items) {
  const byId = new Map(rawProducts.value.map(product => [product.id, product]))
  for (const p of items || []) byId.set(p.id, {
    id: p.id, n: translationSource(p, 'name'),
    d: translationSource(p, 'short_description') || translationSource(p, 'description') || '',
    pr: Number(p.min_price ?? p.max_price ?? 0),
    image: p.product_image || p.image || p.feature_image || null,
    cat: p.category_id, branchId: p.branch_id ?? null,
    stockQty: Number(p.stock_qty ?? 0), maxPurchaseQty: Number(p.max_purchase_qty ?? 0) || null,
    shape: 'jar', best: 0, ts: p.id,
  })
  rawProducts.value = [...byId.values()]
}

/* منطقة الشحن — الموقع بيخدم منطقة واحدة حاليًا، فبنجيبها مرة واحدة ونستخدمها في كل مكان */
const logisticZone = ref(null)
let zoneLoaded = false

async function loadLogisticZone() {
  if (zoneLoaded) return
  zoneLoaded = true
  try {
    const zones = await fetchLogisticZones()
    logisticZone.value = zones?.[0] || null
  } catch {
    logisticZone.value = null
  }
}


/* مفاتيح state.cart دايمًا نصوص (خاصية كائن JS)، بينما id المنتج رقم من الـ API — بنوحّد النوع هنا مرة واحدة */
const pOf = id => products.value.find(p => p.id === Number(id))

/* تحويل رقم/معرّف منتج إلى product_variation_id — مطلوب في كل نداءات السلة الحقيقية */
const variationIdCache = {}
async function resolveVariationId(productId) {
  if (variationIdCache[productId]) return variationIdCache[productId]
  const detail = await fetchProductDetail(productId)
  const id = detail?.variation_data?.[0]?.id
  if (id) variationIdCache[productId] = id
  return id
}


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
  placing: false,
  ck: {
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

  /* حد أقصى مسموح للمنتج — الأقل بين stock_qty و max_purchase_qty (لو موجود) */
  const getMaxQty = id => {
    const p = pOf(id)
    if (!p) return 0
    const stock = p.stockQty ?? 0
    const maxPurchase = p.maxPurchaseQty
    return maxPurchase ? Math.min(stock, maxPurchase) : stock
  }

  const getStock = id => {
    const p = pOf(id)
    return p?.stockQty ?? 0
  }

  const addToCart = id => {
    const current = state.cart[id] || 0
    const max = getMaxQty(id)
    if (max <= 0 || current >= max) return false
    state.cart[id] = current + 1
    return true
  }
  const setQty = (id, qty) => {
    if (qty <= 0) { delete state.cart[id]; return }
    const max = getMaxQty(id)
    state.cart[id] = (max > 0 && qty > max) ? max : qty
  }
  const removeFromCart = id => { delete state.cart[id] }
  const clearCart = () => { Object.keys(state.cart).forEach(k => delete state.cart[k]) }

  /* ===== التصفية والترتيب — منقول حرفيًا من sorted() الأصلية ===== */
  const visibleProducts = computed(() => {
    const l = products.value.filter(p => state.cat === 'all' || p.cat === state.cat).slice()

    if (state.sort === 'low') l.sort((a, b) => a.pr - b.pr)
    else if (state.sort === 'high') l.sort((a, b) => b.pr - a.pr)
    else if (state.sort === 'best') l.sort((a, b) => (b.best || 0) - (a.best || 0) || b.ts - a.ts)
    else l.sort((a, b) => b.ts - a.ts)

    return state.showAll ? l : l.slice(0, 8)
  })

  /* ===== الدفع ===== */
  const ckParts = computed(() => {
    const sub = cartTotal.value
    const ship = logisticZone.value?.standard_delivery_charge ?? 0
    const vat = Math.round(sub * 0.15)
    return { sub, ship, vat, total: sub + vat + ship }
  })

  const ckCan = computed(() => {
    if (!paymentPolicy.checkoutEnabled) return false
    const c = state.ck
    return Object.keys(state.cart).length > 0 &&
      c.name.trim().length > 1 &&
      c.phone.trim().length >= 9 &&
      ['cod', 'wallet'].includes(c.pay) &&
      c.terms &&
      c.addr.trim().length > 4
  })

  /*
    مزامنة السلة المحلية مع سلة حقيقية في الباك إند — الإضافة للسلة نفسها
    تفضل محلية وسريعة أثناء التصفح، والمزامنة الحقيقية بتحصل هنا بس عند الدفع
    (بعد التأكد من تسجيل الدخول)، بنفس فكرة requireAuth في الحجز.
  */
  async function syncCartToBackend(locationId) {
    const existing = (await getCartList())?.data || []
    const localIds = new Set(Object.keys(state.cart).map(Number))

    for (const item of existing) {
      if (!localIds.has(item.product_id)) await apiRemoveCart(item.id).catch(() => {})
    }

    for (const [idStr, qty] of Object.entries(state.cart)) {
      const id = Number(idStr)
      const variationId = await resolveVariationId(id)
      if (!variationId) continue

      const existingItem = existing.find(c => c.product_id === id)
      if (existingItem) {
        await apiUpdateCart({ cart_id: existingItem.id, product_variation_id: variationId, qty })
      } else {
        await apiAddToCart({ product_id: id, product_variation_id: variationId, qty, location_id: locationId })
      }
    }
  }

  /* التأكد من وجود عنوان — بننشئ واحد من بيانات نموذج الدفع لو مفيش عنوان محفوظ */
  async function ensureAddressId() {
    const existing = (await getAddressList())?.data || []
    if (existing.length) return existing[0].id

    const c = state.ck
    const [first_name, ...rest] = c.name.trim().split(/\s+/)
    const created = await addAddress({
      first_name,
      last_name: rest.join(' ') || first_name,
      address_line_1: c.addr,
      city: 166,   // جدة — المدينة الوحيدة المخدومة حاليًا
      state: 4123,
      country: 247,
      is_primary: 1,
    })
    const list = (await getAddressList())?.data || []
    return list[0]?.id ?? created?.id
  }

  /* إنشاء الطلب فعليًا في الباك إند */
  async function placeOrder() {
    if (!ckCan.value) return null
    state.placing = true

    try {
      const c = state.ck
      const itemsSnapshot = Object.entries(state.cart).map(([id, q]) => ({ p: pOf(Number(id)), q }))
      const locationId = itemsSnapshot[0]?.p?.branchId

      await syncCartToBackend(locationId)
      const addressId = await ensureAddressId()
      await loadLogisticZone()

      /* الباك إند (OrdersController@store) بيخصم من المحفظة فورًا لو payment_method='wallet' وبيحوّل
         حالة الدفع paid على طول؛ غير كده (cod) الطلب بيفضل pending لحد الاستلام. */
      const isWallet = c.pay === 'wallet'
      const result = await apiPlaceOrder({
        shipping_address_id: addressId,
        billing_address_id: addressId,
        phone: c.phone,
        location_id: locationId,
        chosen_logistic_zone_id: logisticZone.value?.id,
        shipping_delivery_type: 'standard',
        payment_method: c.pay || 'cod',
        payment_status: isWallet ? 'paid' : 'pending',
      })

      state.order = {
        ref: result?.product?.id ? `#ORD-${result.product.id}` : '#ORD',
        items: itemsSnapshot,
        parts: ckParts.value,
        addr: c.addr,
        pay: isWallet ? 'المحفظة' : 'الدفع عند الاستلام',
        name: c.name,
        phone: c.phone,
      }

      clearCart()
      state.firstAdd = true
      state.page = 'success'
      return state.order
    } finally {
      state.placing = false
    }
  }

  return {
    state,
    loadProducts, loadLogisticZone, seedProducts,
    pOf,
    products,
    categories,
    isFav, toggleFav,
    cartQty, cartTotal, cartItems,
    addToCart, setQty, removeFromCart, clearCart,
    getMaxQty, getStock,
    visibleProducts,
    ckParts, ckCan, placeOrder,
  }


}
