<script setup>
import BookingRating from './BookingRating.vue'
import BookingQr from '../common/BookingQr.vue'
import { computed } from 'vue'

const props = defineProps({ profile: { type: Object, default: null } })

const STATUS_AR = { pending: 'قيد الانتظار', confirmed: 'مؤكد', completed: 'مكتمل', canceled: 'ملغي', cancelled: 'ملغي' }

const all = computed(() => [
  ...(props.profile?.current_bookings || []),
  ...(props.profile?.completed_bookings || []),
].sort((a, b) => Number(b.id || 0) - Number(a.id || 0)))

function rs(n) {
  return Number(n || 0).toLocaleString('ar-EG-u-nu-latn')
}

function bookingTotal(b) {
  return (b.services || []).reduce((a, s) => a + (Number(s.price) || 0), 0)
}

function serviceNames(b) {
  return (b.services || []).map((s) => s.service_name).filter(Boolean).join('، ') || 'حجز'
}
</script>

<template>
  <div class="bookings">
    <div v-if="!all.length" class="empty">
      <b>لا توجد حجوزات بعد</b>
      <p>ابدأ رحلتك واحجز خدمتك الأولى الآن</p>
      <router-link class="btn-org" to="/booking">احجز الآن</router-link>
    </div>
    <div v-else class="booking-list">
      <div v-for="b in all" :key="b.id" class="booking-card">
        <div class="bk-head">
          <b>{{ serviceNames(b) }}</b>
          <span class="status">{{ STATUS_AR[b.status] || b.status }}</span>
        </div>
        <small>رقم الحجز #{{ b.id }}</small>
        <div class="bk-meta">
          <span>{{ b.start_date }}</span>
          <span>{{ b.start_time }}</span>
          <span>{{ b.branch?.name }}</span>
          <span>{{ rs(bookingTotal(b)) }} ر.س</span>
        </div>
        <BookingRating v-if="b.status === 'completed'" :booking="b" />
        <p>{{ b.arrived_at ? 'تم تأكيد الوصول' : 'لم يتم تأكيد الوصول بعد' }}</p>
        <details v-if="b.arrival_qr_url">
          <summary>عرض QR لتأكيد الوصول عند الاستقبال</summary>
          <BookingQr :url="b.arrival_qr_url" :show-link="false" />
          <small>اعرض الرمز لموظف الاستقبال ليؤكد وصولك.</small>
        </details>
      </div>
    </div>
  </div>
</template>

<style scoped>
.empty { text-align: center; padding: 50px 20px; color: #8b8379; }
.empty b { display: block; color: #241f1b; font-size: 16px; margin-bottom: 6px; }
.empty p { margin-bottom: 18px; }
.btn-org {
  display: inline-block; padding: 12px 28px; border-radius: 999px; text-decoration: none;
  background: linear-gradient(90deg, #b77420, #f0cf89, #be7920); color: #160f07;
  font-size: 14px; font-weight: 700;
}
.booking-list { display: grid; gap: 12px; }
.booking-card { background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 16px 18px; }
.bk-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.bk-head b { font-size: 14.5px; }
.status { font-size: 11px; padding: 3px 10px; border-radius: 999px; background: #f6e7c8; color: #7c571d; }
.booking-card > small { color: #8b8379; font-size: 11px; }
.bk-meta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; font-size: 12px; color: #6f665c; }
</style>
