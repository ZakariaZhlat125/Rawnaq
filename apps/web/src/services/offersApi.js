import { useLanguage } from '@/composables/useLanguage'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

export async function fetchOffers() {
  const { state: langState } = useLanguage()
  const response = await fetch(`${apiBaseUrl}/offers?per_page=6`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })

  const payload = await response.json()

  if (!response.ok || payload?.status === false) {
    throw new Error(payload?.message || 'Unable to load offers')
  }

  return payload.data || []
}
