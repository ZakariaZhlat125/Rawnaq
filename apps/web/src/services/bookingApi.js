import { authFetch } from '@/services/apiClient'
import { useLanguage } from '@/composables/useLanguage'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

/*
  الموظفون والأوقات المتاحة عبارة عن نداءات عامة (بدون تسجيل دخول)
  وبترجع array خام مباشرة، بعكس باقي الـ APIs اللي بترجع {status,data}.
*/
export async function fetchStaff({ branchId, serviceId }) {
  const { state: langState } = useLanguage()
  const params = new URLSearchParams({ branch_id: branchId ?? 0, service_id: serviceId ?? 0 })
  const response = await fetch(`${apiBaseUrl}/staff?${params}`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })
  if (!response.ok) throw new Error('تعذّر تحميل قائمة الموظفين')
  return response.json()
}

export async function fetchAvailableTimes({ date, staffId, durationMin }) {
  const { state: langState } = useLanguage()
  const params = new URLSearchParams({ Increasing: durationMin ?? 30 })
  const response = await fetch(`${apiBaseUrl}/available/${date}/${staffId}?${params}`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })
  if (!response.ok) throw new Error('تعذّر تحميل الأوقات المتاحة')
  return response.json()
}

export async function createBooking(payload) {
  return authFetch('/mobile/cart/bookings', { method: 'POST', body: payload })
}

export async function createPackageBooking(payload) {
  return authFetch('/mobile/cart/packages', { method: 'POST', body: payload })
}

/*
  gateway المسموح بيه في الباك إند: card/tabby/tamara/telr/cod فقط — "wallet" و"loyalty" مش
  بوابات دفع مستقلة، دي أعلام إضافية (booleans) بتتفعّل فوق أي بوابة عشان تغطي المبلغ كله أو
  جزء منه من رصيد المحفظة/النقاط قبل ما الباقي (لو فيه) يتحصّل بالبوابة المختارة.
*/
export async function initPayment(gateway, { wallet, walletAmount, loyalty, loyaltyPoints, couponCode, paymentSource } = {}) {
  return authFetch('/payments/init', {
    method: 'POST',
    body: {
      gateway: gateway === 'mada' ? 'card' : gateway,
      ...(gateway === 'mada' || paymentSource ? { payment_source: paymentSource || 'src_sa.mada' } : {}),
      page_type: 'cart',
      ...(wallet ? { wallet: true } : {}),
      ...(walletAmount ? { wallet_amount: walletAmount } : {}),
      ...(loyalty ? { loyalty: true } : {}),
      ...(loyaltyPoints ? { loyalty_points: loyaltyPoints } : {}),
      ...(couponCode ? { coupon_code: couponCode } : {}),
    },
  })
}

export async function fetchLoyaltyPointValue() {
  return authFetch('/loyalty/point-value')
}

export async function validateInvoiceCoupon(couponCode, serviceIds = []) {
  const params = new URLSearchParams({ coupon_code: couponCode })
  serviceIds.forEach(id => params.append('service_ids[]', id))
  return authFetch(`/validate-invoice-coupon?${params}`)
}

export const syncBookingProducts = products => authFetch('/mobile/cart/products', { method: 'PUT', body: { products } })
