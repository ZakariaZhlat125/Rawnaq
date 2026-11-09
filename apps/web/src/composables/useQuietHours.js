import { ref, onMounted } from 'vue'

export function useQuietHours() {
  const settings = ref([])
  onMounted(async () => {
    try { settings.value = await window.SamiQuietHours.load(import.meta.env.VITE_API_BASE_URL || '/api') }
    catch { /* Booking eligibility is always determined by the server. */ }
  })
  return { bonus: (branchId, date, time) => window.SamiQuietHours?.points(settings.value, branchId, date, time) || 0 }
}
