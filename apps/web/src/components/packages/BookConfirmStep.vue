<script setup>
/*
  تأكيد الحجز — مُرحَّل حرفيًا من bkView2() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { usePackages, fmtTime, fmtDate, rs } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state, pkgOf, bkDays } = usePackages()

const B = state.bk
const p = computed(() => pkgOf(B.pkg))
const d = computed(() => bkDays()[B.dayIdx])

const I = {
  check: '<path d="M20 6L9 17l-5-5"/>',
  pin:   '<path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
  cal:   '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  info:  '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
}

const PR_SMALL = 'font-size:12px;color:var(--mute);font-family:var(--font-b)'
const TERMS_BOX = 'display:flex;gap:9px;align-items:center;font-size:12px;color:var(--mute);background:rgba(198,161,91,.08);border-radius:11px;padding:11px 15px;margin-top:14px'
const TERMS_LINK = 'color:var(--gold-deep);font-weight:600;text-decoration:underline'
</script>

<template>
  <div class="g-head"><h1>تأكيد الحجز</h1><p>راجع تفاصيل الحجز قبل المتابعة للدفع</p></div>
  <div class="ok-banner"><span class="ic"><SIcon :inner="I.check" :size="16" /></span>
    <div><b>تم حفظ موعدك بنجاح</b><small>يمكنك تعديل أو إلغاء الحجز قبل 6 ساعات من وقت الموعد</small></div></div>
  <div class="card bk-pkg-hero" :style="`--pc:${p.color}`">
    <img :src="p.img" :alt="p.name" />
    <div class="t">
      <h4>{{ p.name }}</h4>
      <small>🕐 {{ p.dur }} دقيقة — {{ p.desc }}</small>
      <div class="incs"><span v-for="(x, i) in p.inc" :key="i">✓ {{ x }}</span></div>
    </div>
    <div class="pr">{{ rs(p.price) }} <small :style="PR_SMALL">ر.س</small></div>
  </div>
  <div class="card bk-rows" style="margin-bottom:16px">
    <div class="gs-row"><span class="k"><SIcon :inner="I.pin" :size="15" /> الفرع</span><span class="v">{{ p.branchName }}</span></div>
    <div class="gs-row"><span class="k"><SIcon :inner="I.cal" :size="15" /> التاريخ</span><span class="v">{{ fmtDate(d) }}</span></div>
    <div class="gs-row"><span class="k"><SIcon :inner="I.clock" :size="15" /> الوقت</span><span class="v" dir="ltr">{{ fmtTime(B.time) }}</span></div>
    <div class="gs-row"><span class="k"><SIcon :inner="I.info" :size="15" /> مدة الجلسة</span><span class="v">{{ p.dur }} دقيقة</span></div>
  </div>
  <div class="card" style="padding:20px">
    <div class="fld" style="margin:0"><label>📝 ملاحظات (اختياري)</label>
      <textarea id="bkNotes" placeholder="أضف أي ملاحظة..." v-model="B.notes"></textarea></div>
    <div :style="TERMS_BOX">
      🛡️ بالتأكيد على الحجز، فإنك توافق على <RouterLink to="/terms" :style="TERMS_LINK">الشروط والأحكام</RouterLink></div>
  </div>
</template>
