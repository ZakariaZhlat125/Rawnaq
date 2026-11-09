<script setup>
/*
  نجاح الحجز — مُرحَّل حرفيًا من bkView4() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { BRANCHES } from '@/data/packages'
import { usePackages, fmtTime, fmtDate, rs } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state, pkgOf, bkDays } = usePackages()
const emit = defineEmits(['home', 'calendar', 'share'])

const B = state.bk
const p = computed(() => pkgOf(B.pkg))
const br = computed(() => BRANCHES.find(b => b.id === B.branch))
const d = computed(() => bkDays()[B.dayIdx])

const I = {
  box:   '<path d="M20 12v10H4V12M2 7h20v5H2z"/>',
  pin:   '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  cal:   '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  info:  '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
  card:  '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
  qr:    '<path d="M4 4h16v16H4z"/><path d="M9 9h2v2H9zM13 9h2M9 13h2M13 13h2v2h-2z"/>',
  home:  '<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/>',
  calAdd:'<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/>',
  share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
}

const AMT = 'color:var(--gold-deep);font-family:var(--font-d);font-size:16px'
const HINT = 'display:block;margin-top:12px;font-size:11.5px;color:var(--mute)'
const WALLETS = 'display:flex;gap:8px;margin-top:14px'
const WBTN = 'flex:1;padding:11px;font-size:12px'
</script>

<template>
  <div class="gsuccess">
    <div class="gsuc-head">
      <h1>تم تأكيد حجزك بنجاح!</h1>
      <span class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></span>
    </div>
    <p class="sub">نتطلع لخدمتك وتقديم تجربة استثنائية تليق بك</p>
    <div class="gsuc-grid">
      <div class="card gsuc-details">
        <h4>تفاصيل الحجز</h4>
        <div class="gs-row"><span class="k"><SIcon :inner="I.box" :size="14" /> الباقة</span><span class="v">{{ p.name }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.pin" :size="14" /> الفرع</span><span class="v">{{ br.name }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.cal" :size="14" /> التاريخ</span><span class="v">{{ fmtDate(d) }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.clock" :size="14" /> الوقت</span><span class="v" dir="ltr">{{ fmtTime(B.time) }} – {{ fmtTime(B.time + p.dur) }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.info" :size="14" /> مدة الجلسة</span><span class="v">{{ p.dur }} دقيقة</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.card" :size="14" /> المبلغ المدفوع</span><span class="v" :style="AMT">{{ rs(p.price) }} ر.س</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.qr" :size="14" /> رمز الحجز</span><span class="v" dir="ltr">{{ B.ref }}</span></div>
      </div>
      <div class="card gsuc-details" style="text-align:center">
        <h4 style="text-align:right">رمز الوصول السريع</h4>
        <div class="qr-box"><canvas id="bkQr"></canvas></div>
        <small :style="HINT">📱 امسح الرمز عند الوصول لتسجيل الحضور</small>
        <div :style="WALLETS">
          <button class="btn btn-dark" :style="WBTN">🍎 Apple Wallet</button>
          <button class="btn btn-dark" :style="WBTN">📲 Google Wallet</button>
        </div>
      </div>
    </div>
    <div class="gsuc-actions">
      <button class="btn btn-gold" id="bkHome" @click="emit('home')"><SIcon :inner="I.home" :size="15" /> العودة إلى الرئيسية</button>
      <button class="btn btn-line" id="bkCal" @click="emit('calendar')"><SIcon :inner="I.calAdd" :size="15" /> إضافة إلى التقويم</button>
      <button class="btn btn-line" id="bkShare" @click="emit('share')"><SIcon :inner="I.share" :size="15" /> مشاركة الحجز</button>
    </div>
    <div class="thanks"><i>❦</i> جودة تستحقها.. تجربة لا تنساها <i>❦</i></div>
  </div>
</template>
