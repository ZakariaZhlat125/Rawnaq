<script setup>
/*
  نجاح الحجز — مُرحَّل حرفيًا من viewSuccess() في src/legacy/booking.html
*/
import { computed } from 'vue'
import { BRANCHES, AR_DAYS, AR_MONTHS } from '@/data/booking'
import { useBooking, fmtTime, fmtDur, rs } from '@/composables/useBooking'

const { state, selSvcs, totalDur, priceParts, buildSession } = useBooking()
const emit = defineEmits(['home', 'calendar', 'share'])

const br = computed(() => BRANCHES.find(b => b.id === state.branch))
const ses = computed(() => buildSession(state.time))
const p = computed(() => priceParts.value)
const dateLabel = computed(() =>
  `${AR_DAYS[state.date.getDay()]} ${state.date.getDate()} ${AR_MONTHS[state.date.getMonth()]} ${state.date.getFullYear()}`
)

const I = {
  pin:   '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  cal:   '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  info:  '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
  scis:  '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4L8.12 15.88M14.47 14.48L20 20M8.12 8.12L12 12"/>',
  user:  '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  card:  '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
}

const AMT = 'color:var(--gold-deep);font-family:var(--font-d);font-size:17px'

const STEPS_NEXT = [
  ['تم تأكيد الحجز', 'تم استلام طلبك بنجاح', '<path d="M20 6L9 17l-5-5"/>', 2],
  ['تذكير قبل الموعد', 'سنذكّرك قبل موعدك بـ 24 ساعة', '<path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/>', 1.8],
  ['الوصول للفرع', 'يتم الترحيب بك في الموعد المحدد', '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>', 1.8],
  ['بدء الجلسة', 'استمتع بتجربة فاخرة', '<path d="M12 2l2.9 6.2 6.6.8-4.9 4.6 1.3 6.6L12 17l-5.9 3.2 1.3-6.6L2.5 9l6.6-.8z"/>', 1.8],
]

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
    <div class="sent-chip"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.1V12a10 10 0 11-5.9-9.1"/><path d="M22 4L12 14l-3-3"/></svg> تم إرسال تفاصيل الحجز إلى بريدك الإلكتروني ورقم جوالك</div>

    <div class="suc-grid">
      <div class="card suc-details">
        <h4>تفاصيل الحجز</h4>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.pin"></svg> الفرع</span><span class="v">{{ br.name }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.cal"></svg> التاريخ</span><span class="v">{{ dateLabel }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.clock"></svg> الوقت</span><span class="v" dir="ltr">{{ fmtTime(state.time) }} – {{ fmtTime(ses.end) }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.info"></svg> مدة الجلسة</span><span class="v">{{ fmtDur(totalDur) }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.scis"></svg> الخدمات</span><span class="v">{{ selSvcs.map(s => s.name).join(' + ') }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.user"></svg> الفريق</span><span class="v">{{ ses.emps.join('، ') }}</span></div>
        <div class="sd-row"><span class="k"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="I.card"></svg> المبلغ المدفوع</span><span class="v" :style="AMT">{{ rs(p.total) }} ر.س</span></div>
      </div>
      <div class="card qr-card">
        <h4 style="font-family:var(--font-d);font-size:16px;color:var(--ink)">رمز الحجز</h4>
        <div class="code">{{ state.bookRef }}</div>
        <div class="qr-box"><canvas id="qrCv"></canvas></div>
        <small>📱 امسح الرمز عند الوصول لإظهار تفاصيل الحجز وتسجيل الحضور</small>
        <div class="wallets">
          <button>🍎 Apple Wallet</button><button>📲 Google Wallet</button>
        </div>
      </div>
    </div>

    <div class="card next-steps">
      <h4>ماذا يحدث الآن؟</h4>
      <div class="ns-track">
        <div v-for="(s, i) in STEPS_NEXT" :key="i" class="ns" :class="{ done: i === 0 }">
          <span class="ni"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="s[3]" v-html="s[2]"></svg></span><b>{{ s[0] }}</b><small>{{ s[1] }}</small>
        </div>
      </div>
    </div>

    <div class="suc-actions">
      <button class="btn btn-gold" @click="emit('home')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/></svg> العودة إلى الرئيسية</button>
      <button class="btn btn-line" id="btnCal" @click="emit('calendar')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/></svg> إضافة للتقويم</button>
      <button class="btn btn-line" id="btnShare" @click="emit('share')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg> مشاركة الحجز</button>
    </div>

    <div class="suc-perks">
      <div v-for="(t, i) in PERKS" :key="i" class="trust"><span class="ti"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" v-html="t[2]"></svg></span><b>{{ t[0] }}</b><small>{{ t[1] }}</small></div>
    </div>
  </div>
</template>
