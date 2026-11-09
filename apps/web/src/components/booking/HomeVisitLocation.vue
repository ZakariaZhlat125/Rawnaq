<script setup>
import { ref } from 'vue'
import HomeVisitMap from '@/components/booking/HomeVisitMap.vue'
import { useBooking } from '@/composables/useBooking'
import { homeService } from '@/composables/useHomeService'
import { localizeRecord } from '@/utils/i18nField'
import { useLanguage } from '@/composables/useLanguage'
const { state, homeFee, homeCovered } = useBooking()
const { state: language } = useLanguage()
const error = ref('')
function locate() {
 error.value = ''
 if (!navigator.geolocation) { error.value='تحديد الموقع غير متاح؛ أدخل الإحداثيات.'; return }
 navigator.geolocation.getCurrentPosition(p=> {state.homeLatitude=p.coords.latitude;state.homeLongitude=p.coords.longitude},()=>{error.value='تعذر تحديد الموقع. اسمح بالوصول للموقع أو أدخل الإحداثيات.'})
}
</script>
<template>
 <section class="card" style="padding:20px;margin-bottom:20px" dir="rtl">
  <h3>عنوان الزيارة المنزلية</h3>
  <label>المدينة <select v-model="state.homeCityId" required><option value="" disabled>اختر المدينة</option><option v-for="city in homeService.cities" :key="city.id" :value="city.id">{{ localizeRecord(city, 'name', language.lang) }} — {{ city.home_service_fee }} ر.س</option></select></label>
  <p>رسوم الزيارة: {{ homeFee }} ر.س (قبل الضريبة)</p>
  <button type="button" @click="locate">استخدام موقعي الحالي</button>
  <label>خط العرض <input v-model="state.homeLatitude" type="number" step="any" min="-90" max="90" required></label>
  <label>خط الطول <input v-model="state.homeLongitude" type="number" step="any" min="-180" max="180" required></label>
  <a v-if="state.homeLatitude !== '' && state.homeLongitude !== ''" :href="`https://maps.google.com/?q=${Number(state.homeLatitude)},${Number(state.homeLongitude)}`" target="_blank" rel="noopener">عرض موقع الزيارة على خرائط جوجل</a>
  <HomeVisitMap />
  <p>التغطية حتى {{ homeService.radius_km }} كم من المركز الموضح.</p>
  <p v-if="!homeCovered" role="alert">اختر المدينة وحدد موقعاً داخل نطاق التغطية للمتابعة.</p>
  <p v-if="error" role="alert">{{ error }}</p>
 </section>
</template>
