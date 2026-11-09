import { reactive } from 'vue'
import { authFetch } from '@/services/apiClient'

export const serviceRepeat = reactive({ progress: [], rewards: [], loading: false, error: '' })
let generation = 0
export function clearServiceRepeat() {
  generation++
  serviceRepeat.progress = []; serviceRepeat.rewards = []; serviceRepeat.error = ''; serviceRepeat.loading = false
}
export async function loadServiceRepeat() {
  const request = ++generation
  serviceRepeat.loading = true; serviceRepeat.error = ''
  try {
    const result = await authFetch('/service-repeat-rewards', { cache: 'no-store' })
    if (request !== generation) return
    serviceRepeat.progress = result.data?.progress || []
    serviceRepeat.rewards = result.data?.rewards || []
  } catch (error) {
    if (request === generation) { serviceRepeat.error = error.message; serviceRepeat.rewards = []; serviceRepeat.progress = [] }
  } finally { if (request === generation) serviceRepeat.loading = false }
}
export function selectedServiceReward(serviceId, rewardId) {
  return serviceRepeat.rewards.find(r => r.usable && String(r.service_id) === String(serviceId) && String(r.id) === String(rewardId))
}
