import { reactive } from 'vue'
import '../../public/public-api.js'
import { resolveBackendUrl } from '@/utils/assetPath'
export const homeService = reactive({ enabled: false, cities: [], latitude: 0, longitude: 0, radius_km: 0 })
let pending
export function loadHomeService() {
  if (!pending) pending = globalThis.SamiPublicApi.fetch(resolveBackendUrl('/api/home-service-settings'), { headers: { Accept: 'application/json' } })
    .then(r => { if (!r.ok) throw new Error('Unable to load home service'); return r.json() })
    .then(r => Object.assign(homeService, r.data))
    .catch(e => { pending = null; homeService.enabled = false; throw e })
  return pending
}
