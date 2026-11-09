import { useLanguage } from '@/composables/useLanguage'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

export async function fetchPackages() {
  const { state: langState } = useLanguage()
  const response = await fetch(`${apiBaseUrl}/Home/packages`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })

  const payload = await response.json()

  if (!response.ok || payload?.status === false) {
    throw new Error(payload?.message || 'Unable to load packages')
  }

  return payload.data || []
}

/* تفاصيل باقة أو عرض واحد — kind: 'package' | 'offer' */
export async function fetchCatalogItem(kind, id) {
  const { state: langState } = useLanguage()
  const response = await fetch(`${apiBaseUrl}/${kind === 'offer' ? 'offers' : 'packages'}/${encodeURIComponent(id)}`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })

  const payload = await response.json().catch(() => ({}))

  if (!response.ok || payload?.status === false) {
    const error = new Error(payload?.message || 'Unable to load item')
    error.status = response.status
    throw error
  }

  return payload.data
}
