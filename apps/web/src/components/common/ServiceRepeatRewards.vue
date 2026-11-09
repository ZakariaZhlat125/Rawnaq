<script setup>
import { watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useLanguage } from '@/composables/useLanguage'
import { useBooking } from '@/composables/useBooking'
import { serviceRepeat, loadServiceRepeat, clearServiceRepeat } from '@/composables/useServiceRepeatRewards'
const props = defineProps({ booking: Boolean })
const { isAuthenticated, user } = useAuth()
const { state: language } = useLanguage()
const { state, selSvcs } = useBooking()
watch(() => [isAuthenticated.value, user.value?.id, language.lang], () => {
  state.repeatRewardIds = {}
  if (isAuthenticated.value) loadServiceRepeat(); else clearServiceRepeat()
}, { immediate: true })
function availableFor(service) { return serviceRepeat.rewards.filter(r => r.usable && String(r.service_id) === String(service.id)) }
function toggle(service, enabled) {
  state.rewards.couponApplied = ''; state.rewards.couponDiscount = 0
  if (enabled) state.repeatRewardIds[service.id] = availableFor(service)[0]?.id
  else delete state.repeatRewardIds[service.id]
}
</script>
<template>
  <section v-if="isAuthenticated" class="repeat-rewards card detail-card" data-no-i18n>
    <h4>{{ language.lang === 'en' ? 'Repeat service rewards' : 'مكافآت تكرار الخدمة' }}</h4>
    <p v-if="serviceRepeat.loading">{{ language.lang === 'en' ? 'Loading rewards…' : 'جارٍ تحميل المكافآت…' }}</p>
    <p v-else-if="serviceRepeat.error" role="alert">{{ language.lang === 'en' ? 'Could not load rewards.' : 'تعذّر تحميل المكافآت.' }} <button type="button" @click="loadServiceRepeat">{{ language.lang === 'en' ? 'Retry' : 'إعادة المحاولة' }}</button></p>
    <template v-else>
      <p v-if="!serviceRepeat.progress.length && !serviceRepeat.rewards.length">{{ language.lang === 'en' ? 'No active rewards yet.' : 'لا توجد مكافآت مفعّلة حاليًا.' }}</p>
      <p v-for="p in serviceRepeat.progress.filter(p => !props.booking || selSvcs.some(s => String(s.id) === String(p.service_id)))" :key="p.service_id">
        <b>{{ p.name }}</b> · {{ p.completed }}/{{ p.required_visits }} — {{ language.lang === 'en' ? `${Math.max(0,p.required_visits-p.completed)} completed paid bookings left for a free service` : `باقي ${Math.max(0,p.required_visits-p.completed)} حجوزات مدفوعة مكتملة للحصول على حجز مجاني` }}
      </p>
      <template v-if="props.booking">
        <label v-for="service in selSvcs.filter(s => availableFor(s).length)" :key="service.id" class="repeat-choice">
          <input type="checkbox" :checked="!!state.repeatRewardIds[service.id]" @change="toggle(service,$event.target.checked)">
          {{ language.lang === 'en' ? 'Use a free booking for' : 'استخدام حجز مجاني لخدمة' }} {{ service.name }}
        </label>
        <small>{{ language.lang === 'en' ? 'The service is free; products and home visit fees are charged separately.' : 'المكافأة تغطي سعر الخدمة؛ المنتجات ورسوم الزيارة المنزلية تُحسب بشكل منفصل.' }}</small>
      </template>
      <p v-else v-for="reward in serviceRepeat.rewards" :key="reward.id">
        {{ reward.name }} — {{ language.lang === 'en' ? (reward.status === 'used' ? 'Used' : reward.status === 'reserved' ? 'Reserved for a booking' : reward.usable ? 'Free booking available' : 'Reward paused') : (reward.status === 'used' ? 'مستخدمة' : reward.status === 'reserved' ? 'محجوزة لحجز جارٍ' : reward.usable ? 'حجز مجاني متاح' : 'المكافأة موقوفة مؤقتًا') }}
      </p>
    </template>
  </section>
</template>
<style scoped>
.repeat-rewards{padding:18px;margin:16px 0;border:1px solid #c9a76a;border-radius:14px}.repeat-choice{display:flex;align-items:center;gap:10px;padding:12px 0}.repeat-rewards p{line-height:1.8}.repeat-rewards small{display:block;line-height:1.8}
</style>
