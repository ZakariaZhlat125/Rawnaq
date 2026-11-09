<script setup>
/*
  نجاح الإهداء — مُرحَّل حرفيًا من view5() في src/legacy/gifts.html
*/
import { computed } from 'vue'
import { SENDS } from '@/data/gifts'
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'

const { state, giftLabel, dsgOf } = useGifts()
const emit = defineEmits(['recipient', 'track', 'new-gift', 'share', 'home', 'copy-ref'])

const AR_M = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
const now = new Date(2026, 6, 7)

const d = computed(() => dsgOf(state.design))
const sendName = computed(() => {
  const m = SENDS.find(x => x.id === state.method)
  return m ? m.n : ''
})
const sentAt = computed(() =>
  state.when === 'now'
    ? `اليوم ${now.getDate()} ${AR_M[now.getMonth()]} ${now.getFullYear()}`
    : 'مجدولة — ' + (state.schedDate || '')
)

const I = {
  user:  '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  send:  '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  copy:  '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>',
  box:   '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7"/>',
  arrow: '<path d="M15 12H3M12 5l7 7-7 7"/>',
  plus:  '<path d="M12 5v14M5 12h14"/>',
  share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
  home:  '<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/>',
}
</script>

<template>
  <div class="gsuccess" style="padding-top:4px">
    <div class="gs-hero">
      <span class="spark" style="top:0;right:8px">✦</span><span class="spark" style="bottom:14px;left:2px;animation-delay:-1.2s">✧</span>
      <span class="circ"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span class="gbox"></span>
    </div>
    <h1>تم إرسال الهدية بنجاح</h1>
    <p class="sub">تم تجهيز هديتك وإرسالها للمستلم بنجاح، وسيتم إشعاره فور استلامها ✨</p>
    <div class="gsuc-box">
      <div class="mini-card" :style="`background:${d.bg};color:${d.fg}`">
        <span class="lg"><img src="/logo.png" style="height:24px;vertical-align:middle" alt="" /><br />SAMI<small>عناية سامي</small></span>
      </div>
      <div>
        <h3>إهداء {{ giftLabel }}</h3>
        <div class="gs-row"><span class="k"><SIcon :inner="I.user" :size="14" /> إلى</span><span class="v">{{ state.name }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.send" :size="14" /> طريقة الإرسال</span><span class="v">{{ sendName }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.clock" :size="14" /> وقت الإرسال</span><span class="v">{{ sentAt }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.copy" :size="14" /> رقم الطلب</span>
          <span class="v copy-ref" id="copyRef" dir="ltr" @click="emit('copy-ref')">{{ state.ref }} ⧉</span></div>
      </div>
    </div>
    <div class="gsuc-actions">
      <button class="btn btn-gold" id="openRecipient" @click="emit('recipient')"><SIcon :inner="I.box" :size="15" /> فتح صفحة المستلم</button>
      <button class="btn btn-line" id="trackGift" @click="emit('track')"><SIcon :inner="I.arrow" :size="15" /> تتبع الهدية</button>
      <button class="btn btn-line" id="newGift" @click="emit('new-gift')"><SIcon :inner="I.plus" :size="15" /> إهداء جديد</button>
      <button class="btn btn-line" id="shareGift" @click="emit('share')"><SIcon :inner="I.share" :size="15" /> مشاركة الهدية</button>
      <button class="btn btn-line" id="goHome" @click="emit('home')"><SIcon :inner="I.home" :size="15" /> العودة للرئيسية</button>
    </div>
    <div class="thanks"><i>❦</i> شكرًا لاختيارك عناية سامي لتقديم تجربة مميزة لمن تحب <i>❦</i></div>
  </div>
</template>
