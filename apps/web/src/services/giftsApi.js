import { authFetch } from '@/services/apiClient'
import { useAuth } from '@/composables/useAuth'

export const createGiftCard = payload => authFetch('/mobile/cart/gift-cards', { method: 'POST', body: payload })

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

/* صفحة المستلم عامة؛ لو المستخدم مسجّل نبعت التوكن عشان المُهدي لما يعاين الرابط ما يتسجّلش "فُتحت" */
export async function fetchGiftClaim(token) {
  const { token: authToken } = useAuth()
  const headers = { Accept: 'application/json' }
  if (authToken.value) headers.Authorization = `Bearer ${authToken.value}`
  const response = await fetch(`${apiBaseUrl}/gift-cards/claim/${encodeURIComponent(token)}`, { headers })
  const payload = await response.json().catch(() => null)
  if (!response.ok || !payload?.data) throw new Error(payload?.message || 'Gift not found')
  return payload.data
}

export async function fetchGiftDesigns() {
  const response = await fetch(`${apiBaseUrl}/gift-designs`, { headers: { Accept: 'application/json' } })
  const payload = await response.json().catch(() => null)
  if (!response.ok || !payload?.data) throw new Error('designs unavailable')
  return payload
}

export const fetchSentGifts = () => authFetch('/gifts/sent')
export const fetchSentGift = id => authFetch(`/gifts/sent/${id}`)
export const resendGift = id => authFetch(`/gifts/sent/${id}/resend`, { method: 'POST' })
export const fetchReceivedGifts = () => authFetch('/gifts/received')
export const claimGift = id => authFetch(`/gifts/received/${id}/claim`, { method: 'POST' })
export const bookGift = (id, appointments) => authFetch(`/gifts/received/${id}/book`, { method: 'POST', body: { appointments } })
export const redeemGiftCode = code => authFetch('/gifts/redeem', { method: 'POST', body: { code } })

/* لحجز هدية الخدمة: الموظفين المتاحين والأوقات */
export const fetchGiftStaff = (branchId, serviceId) => authFetch(`/staff?branch_id=${branchId}&service_id=${serviceId}`)
export const fetchGiftTimes = (date, staffId) => authFetch(`/available/${date}/${staffId}`)
