import { useAuth } from '@/composables/useAuth'
import { useLanguage } from '@/composables/useLanguage'
import '../../public/public-api.js'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

export async function fetchWheelInfo() {
  const { state: langState } = useLanguage()
  const response = await globalThis.SamiPublicApi.fetch(`${apiBaseUrl}/Home/wheel/prizes`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })

  const payload = await response.json()

  if (!response.ok || payload?.status === false) {
    throw new Error(payload?.message || 'Unable to load wheel prizes')
  }

  return payload.data || {}
}

export async function fetchWheelPrizes() {
  const data = await fetchWheelInfo()

  return data.prizes || []
}

export async function spinWheel() {
  const { token } = useAuth()
  const { state: langState } = useLanguage()

  const response = await fetch(`${apiBaseUrl}/Home/wheel/spin`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'frezka-localization': langState.lang,
      ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
    },
  })

  let payload = null
  try {
    payload = await response.json()
  } catch {
    payload = null
  }

  if (!response.ok && response.status !== 409) {
    throw new Error(payload?.message || 'Unable to spin the wheel')
  }

  return payload
}
