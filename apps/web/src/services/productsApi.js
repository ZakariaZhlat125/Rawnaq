import { authFetch } from '@/services/apiClient'
import { useLanguage } from '@/composables/useLanguage'
import '../../public/public-api.js'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

async function publicGet(path) {
  const { state: langState } = useLanguage()
  const response = await globalThis.SamiPublicApi.fetch(`${apiBaseUrl}${path}`, { headers: { Accept: 'application/json', 'frezka-localization': langState.lang } })
  const payload = await response.json()
  if (!response.ok || payload?.status === false) throw new Error(payload?.message || 'حدث خطأ، حاول مرة أخرى')
  return payload.data
}

/* تفاصيل منتج — محتاجينها عشان نجيب product_variation_id (لازم للسلة حتى لو المنتج من غير اختيارات) */
export const fetchProductDetail = id => publicGet(`/product_detail?id=${id}`)

/* منطقة الشحن الوحيدة المفعّلة حاليًا للموقع */
export const fetchLogisticZones = () => publicGet('/get-logisticzone-list')

export const getCartList = () => authFetch('/get-cart-list')
export const addToCart = payload => authFetch('/add-to-cart', { method: 'POST', body: payload })
export const updateCart = payload => authFetch('/update-cart', { method: 'POST', body: payload })
export const removeCart = cartId => authFetch(`/remove-cart?cart_id=${cartId}`)

export const getAddressList = () => authFetch('/address-list')
export const addAddress = payload => authFetch('/add-address', { method: 'POST', body: payload })

export const placeOrder = payload => authFetch('/place-order', { method: 'POST', body: payload })
