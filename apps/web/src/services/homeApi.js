import { useLanguage } from '@/composables/useLanguage'
import '../../public/public-api.js'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

async function request(path) {
  const { state: langState } = useLanguage()
  const response = await globalThis.SamiPublicApi.fetch(`${apiBaseUrl}${path}`, {
    headers: { Accept: 'application/json', 'frezka-localization': langState.lang },
  })

  

  let payload = null
  try {
    payload = await response.json()
  } catch {
    payload = null
  }

  if (!response.ok || payload?.status === false) {
    throw new Error(payload?.message || 'Unable to load home data')
  }

  return payload
}

export async function fetchHomeData() {
  const payload = await request('/Home/all')
  return payload.data || {}
}

export async function fetchFaqs() {
  const payload = await request('/faqs')
  return Array.isArray(payload.data) ? payload.data : []
}

export async function fetchBranches() {
  try {
    const payload = await request('/branch-list')
    return payload.data || payload.branches || []
  } catch {
    try {
      const payload = await request('/branches')
      return payload.data || payload.branches || []
    } catch {
      return []
    }
  }
}

