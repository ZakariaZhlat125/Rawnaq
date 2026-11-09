import { useLanguage } from '@/composables/useLanguage'
import '../../public/public-api.js'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

export async function fetchShopCatalog() {
  const { state: langState } = useLanguage()
  const response = await globalThis.SamiPublicApi.fetch(`${apiBaseUrl}/shop`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })

  const payload = await response.json()

  if (!response.ok || payload?.status === false) {
    throw new Error(payload?.message || 'Unable to load shop catalog')
  }

  return payload.data || { categories: [] }
}
