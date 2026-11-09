<script setup>
import { computed } from 'vue'
import BookingQr from '@/components/common/BookingQr.vue'
import { bookingReceiptUrl } from '@/utils/bookingReceipt'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useBooking, fmtTimeStr, fmtDur, fmtDate, rs } from '@/composables/useBooking'

const { state, selSvcs, totalDur, priceParts } = useBooking()
const { current } = useServiceLocation()
const emit = defineEmits(['home', 'calendar', 'share'])

const p = computed(() => priceParts.value)
const dateLabel = computed(() => (state.date ? fmtDate(state.date) : ''))
const employeeNames = computed(() => [...new Set(selSvcs.value.map(s => state.emp[s.id]?.name).filter(Boolean))])
const receiptPayload = computed(() => ({
  r: state.bookRef || '',
  b: current.value?.name || '',
  d: dateLabel.value,
  u: fmtDur(totalDur.value),
  e: employeeNames.value.join('، '),
  p: p.value.total,
  s: selSvcs.value.map(s => [s.name, fmtTimeStr(state.time[s.id]), state.emp[s.id]?.name || '', s.price]),
}))
const receiptUrl = computed(() => bookingReceiptUrl(receiptPayload.value))

const I = {
  pin: '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  cal: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  info: '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
  scis: '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.12 15.88M14.47 14.48L20 20M8.12 8.12L12 12"/>',
  user: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  card: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
}

const AMT = 'color:var(--gold-deep);font-family:var(--font-d);font-size:17px'

const PERKS = [
  ['خدمة VIP', 'تجربة فاخرة مصممة لراحتك', '<path d="M2 8l4 4 6-8 6 8 4-4v10a2 2 0 01-2 2H4a2 2 0 01-2-2z"/>'],
  ['بياناتك آمنة', 'نضمن لك خصوصية وأمان بياناتك', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'],
  ['تعديل الحجز', 'يمكنك تعديل أو إلغاء الحجز قبل موعده', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
  ['دعم عملاء 24/7', 'نحن هنا لخدمتك دائمًا', '<path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>'],
]
</script>

<template>
  <div class="success-wrap">
    <div class="suc-ic">
      <span class="spark s1">✦</span><span class="spark s2">✧</span>
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6L9 17l-5-5"/></svg>
    </div>
    <h1>تم تأكيد حجزك بنجاح!</h1>
    <p class="sub">نتطلع لخدمتك وتقديم تجربة استثنائية تليق بك</p>

    <div class="suc-grid">
      <div class="card suc-details">
        <h4>تفاصيل الحجز</h4>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.pin"></svg> الفرع</span><span class="v">{{ current?.name || '—' }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.cal"></svg> التاريخ</span><span class="v">{{ dateLabel }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.info"></svg> مدة الجلسة</span><span class="v">{{ fmtDur(totalDur) }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.scis"></svg> الخدمات</span><span class="v">{{ selSvcs.map(s => `${s.name} (${fmtTimeStr(state.time[s.id])})`).join(' + ') }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.user"></svg> الفريق</span><span class="v">{{ employeeNames.join('، ') }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.card"></svg> المبلغ التقديري</span><span class="v" :style="AMT">{{ rs(p.total) }} ر.س</span></div>
      </div>
      <div class="card qr-card">
        <h4 style="font-family:var(--font-d);font-size:16px;color:var(--ink)">رقم الفاتورة</h4>
        <div class="code">{{ state.bookRef || '—' }}</div>
        <BookingQr :url="receiptUrl" />
      </div>
    </div>

    <div class="suc-actions">
      <button class="btn btn-gold" @click="emit('home')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/></svg> العودة إلى الرئيسية</button>
      <button class="btn btn-line" @click="emit('calendar')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/></svg> إضافة للتقويم</button>
      <button class="btn btn-line" @click="emit('share')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg> مشاركة الحجز</button>
    </div>

    <div class="suc-perks">
      <div v-for="(t, i) in PERKS" :key="i" class="trust"><span class="ti"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" v-html="t[2]"></svg></span><b>{{ t[0] }}</b><small>{{ t[1] }}</small></div>
    </div>
  </div>
</template>
