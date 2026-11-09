import { authFetch } from '@/services/apiClient'

export const fetchWallet = () => authFetch('/wallet')

export function fetchWalletHistory({ type = '', page = 1 } = {}) {
  const params = new URLSearchParams({ page: String(page), per_page: '15' })
  if (type) params.set('type', type)
  return authFetch(`/wallet/history?${params}`)
}

export const fetchTopupOffers = () => authFetch('/wallet/topup-offers')

/* بيرجّع payment_url — الرصيد بيتضاف بعد ما بوابة الدفع تأكد العملية، مش هنا */
export const startTopup = (amount) => authFetch('/wallet/topup', { method: 'POST', body: { amount } })

export const convertPoints = (points) => authFetch('/wallet/convert-points', { method: 'POST', body: { points } })

export const fetchWithdrawals = () => authFetch('/wallet/withdrawals')

export const requestWithdrawal = (data) => authFetch('/wallet/withdrawals', { method: 'POST', body: data })
