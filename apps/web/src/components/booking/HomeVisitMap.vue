<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { homeService } from '@/composables/useHomeService'
import { useBooking } from '@/composables/useBooking'
import { resolveBackendUrl } from '@/utils/assetPath'

const { state } = useBooking()
const element = ref(null)
const error = ref('')
const loading = ref(true)
let widget
let disposed = false
const showError = event => { error.value = event.detail; loading.value = false }

onMounted(async () => {
  window.addEventListener('sami-map-error', showError)
  try {
    if (!homeService.maps_key) throw new Error('الخريطة غير متاحة حالياً. يمكنك استخدام موقعي الحالي لتحديد عنوان الزيارة.')
    if (!window.SamiHomeMap) {
      await new Promise((resolve, reject) => {
        const script = document.createElement('script')
        script.src = resolveBackendUrl('/js/home-service-map.js')
        script.onload = resolve
        script.onerror = () => reject(new Error('تعذر تحميل الخريطة. يمكنك استخدام موقعي الحالي.'))
        document.head.appendChild(script)
      })
    }
    if (disposed) return
    widget = await window.SamiHomeMap.create(element.value, {
      key: homeService.maps_key, latitude: homeService.latitude, longitude: homeService.longitude,
      radiusKm: homeService.radius_km,
      onSelect: position => { state.homeLatitude = position.latitude; state.homeLongitude = position.longitude },
    })
    if (disposed) { widget?.destroy(); return }
    widget?.select(state.homeLatitude, state.homeLongitude)
  } catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})
watch(() => [state.homeLatitude, state.homeLongitude], ([lat, lng]) => widget?.select(lat, lng))
onBeforeUnmount(() => { disposed = true; widget?.destroy(); window.removeEventListener('sami-map-error', showError) })
</script>

<template>
  <div>
    <p>اضغط على الخريطة لتحديد موقع الزيارة داخل نطاق التغطية المظلل.</p>
    <p v-if="loading" role="status">جارٍ تحميل الخريطة...</p>
    <p v-if="error" role="status">{{ error }}</p>
    <div ref="element" v-show="!error" aria-label="خريطة موقع الزيارة ونطاق التغطية" style="height:340px;width:100%;border-radius:12px"></div>
  </div>
</template>
