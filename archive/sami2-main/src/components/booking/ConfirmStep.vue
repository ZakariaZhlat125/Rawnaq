<script setup>
/*
  تأكيد الحجز — مُرحَّل حرفيًا من view5() في src/legacy/booking.html
  يشمل: تفاصيل الموعد · الجدول الزمني للجلسة · المنتجات المقترحة · بيانات العميل
*/
import { computed } from 'vue'
import { BRANCHES, UPSELL, AR_DAYS, AR_MONTHS } from '@/data/booking'
import { useBooking, fmtTime, fmtDur, rs } from '@/composables/useBooking'

const { state, totalDur, buildSession, hasUpsell, toggleUpsell } = useBooking()

const br = computed(() => BRANCHES.find(b => b.id === state.branch))
const ses = computed(() => buildSession(state.time))
const dateLabel = computed(() =>
  `${AR_DAYS[state.date.getDay()]} ${state.date.getDate()} ${AR_MONTHS[state.date.getMonth()]} ${state.date.getFullYear()}`
)

const UP_HINT = 'font-weight:400;color:var(--mute);font-family:var(--font-b);font-size:12px;margin-inline-start:auto'
const NOTE_LBL = 'font-size:11.5px;color:var(--mute);display:block;margin-bottom:7px'
</script>

<template>
  <div class="panel-head"><h1>تأكيد الحجز</h1><p>راجع تفاصيل جلستك قبل تأكيد الحجز والدفع</p></div>

  <div class="ok-banner"><span class="ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg></span>
    <div><b>تم حفظ موعدك بنجاح</b><small>يمكنك تعديل أو إلغاء الحجز قبل 6 ساعات من وقت الموعد</small></div></div>

  <div class="card detail-card">
    <h4><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> تفاصيل الموعد</h4>
    <div class="d-grid">
      <div class="d-cell"><small>📍 الفرع</small><b>{{ br.name }}</b></div>
      <div class="d-cell"><small>📅 التاريخ</small><b>{{ dateLabel }}</b></div>
      <div class="d-cell"><small>🕐 وقت الحجز</small><b>{{ fmtTime(state.time) }}</b></div>
      <div class="d-cell"><small>⏱️ مدة الجلسة</small><b>{{ fmtDur(totalDur) }}</b></div>
    </div>
  </div>

  <div class="card detail-card">
    <h4><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg> تفاصيل الجلسة</h4>
    <div class="timeline-cards">
      <div class="tl-rail"></div>
      <template v-for="(it, i) in ses.items" :key="i">
        <div v-if="it.isBreak" class="coffee-break" :style="`animation-delay:${i * 0.12}s`"><span class="cup">☕</span><span>{{ fmtTime(it.start) }} – {{ fmtTime(it.end) }}</span></div>
        <div v-else class="tl" :style="`border-color:${it.cat.hex}3E;background:${it.cat.hex}0A;color:${it.cat.hex};animation-delay:${i * 0.12}s`">
          <span class="ti" :style="`background:${it.cat.hex}`">{{ it.cat.emoji }}</span>
          <div class="tl-main">
            <b>{{ it.cat.name }}</b>
            <div class="with">{{ it.services.map(x => x.name).join(' • ') }}</div>
            <div class="with">مع {{ it.emp }}</div>
          </div>
          <div class="tl-time">
            <span class="tm" :style="`color:${it.cat.hex}`">{{ fmtTime(it.start) }} – {{ fmtTime(it.end) }}</span>
            <span class="dr">{{ it.dur }} دقيقة</span>
          </div>
        </div>
      </template>
    </div>
    <div class="coord-note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex:none"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg> تم تنسيق الجلسة بعناية{{ state.mode === 'auto' ? ' — اختار النظام الفريق الأنسب ورتّب الأوقات' : '' }} لتمنحك تجربة مريحة وسلسة</div>
  </div>

  <div class="card detail-card products-detail-card">
    <h4><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2l2.9 6.2 6.6.8-4.9 4.6 1.3 6.6L12 17l-5.9 3.2 1.3-6.6L2.5 9l6.6-.8z"/></svg> منتجات مناسبة لجلستك <small :style="UP_HINT">تم اختيار هذه المنتجات بعناية لتناسب خدماتك</small></h4>
    <div class="upsell">
      <div v-for="u in UPSELL" :key="u.id" class="up" :class="{ added: hasUpsell(u.id) }">
        <img :src="u.img" :alt="u.name" />
        <span class="inf"><b>{{ u.name }}</b><small>{{ u.desc }}</small><span class="p">{{ rs(u.price) }} ر.س</span></span>
        <button :data-up="u.id" @click="toggleUpsell(u.id)">{{ hasUpsell(u.id) ? '✓ أضيف' : 'إضافة' }}</button>
      </div>
    </div>
  </div>

  <div class="card detail-card">
    <h4><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> بيانات العميل</h4>
    <div class="cust-grid">
      <div class="fld"><small>👤 الاسم</small><input id="cName" v-model="state.cust.name" /></div>
      <div class="fld"><small>📱 رقم الجوال</small><input id="cPhone" dir="ltr" v-model="state.cust.phone" /></div>
      <div class="fld"><small>✉️ البريد الإلكتروني</small><input id="cMail" dir="ltr" v-model="state.cust.mail" /></div>
    </div>
    <div class="notes-box" style="margin-top:14px">
      <small :style="NOTE_LBL">📝 ملاحظات إضافية (اختياري)</small>
      <textarea id="cNotes" placeholder="اكتب أي ملاحظة أو طلب خاص بالجلسة..." v-model="state.notes"></textarea>
    </div>
    <div class="terms">🛡️ بالتأكيد على الحجز، فإنك توافق على <a href="https://sami-care.sa/TermsAndConditions">الشروط والأحكام</a> و<a href="https://sami-care.sa/TermsAndConditions">سياسة الخصوصية</a></div>
  </div>
</template>
