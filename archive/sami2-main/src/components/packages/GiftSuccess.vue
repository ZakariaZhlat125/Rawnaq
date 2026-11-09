<script setup>
/*
  نجاح إهداء الباقة — مُرحَّل حرفيًا من gView4() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { SEND_METHODS } from '@/data/packages'
import { usePackages } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'
import GiftCard from '@/components/gifts/GiftCard.vue'

const { state } = usePackages()
const emit = defineEmits(['recipient', 'new-gift', 'copy-self', 'share', 'home'])

const AR_M = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
const now = new Date()
const dateStr = `${now.getDate()} ${AR_M[now.getMonth()]} ${now.getFullYear()}`

const confetti = '✦✧✦✧'.split('').map((c, i) => ({
  c,
  style: `top:${10 + i * 16}%;${i % 2 ? 'right' : 'left'}:${4 + i * 3}%;animation-delay:-${i}s`,
}))

const sendName = computed(() => {
  const m = SEND_METHODS.find(x => x.id === state.method)
  return m ? m.n : ''
})

const I = {
  user:  '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  send:  '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>',
  phone: '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  cal:   '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  card:  '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
  check: '<path d="M20 6L9 17l-5-5"/>',
  box:   '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7"/>',
  gift:  '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
  mail:  '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 6L2 7"/>',
  share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/>',
  home:  '<path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/>',
}

const OK_BOX = 'display:flex;gap:10px;align-items:center;background:var(--green-bg);border-radius:12px;padding:13px 15px;margin-top:14px;font-size:12.5px;color:#1E6B41'
const OK_IC = 'flex:none;width:26px;height:26px;border-radius:50%;background:var(--green);color:#fff;display:grid;place-items:center'
</script>

<template>
  <div class="gsuccess">
    <span v-for="(f, i) in confetti" :key="i" class="confetti" :style="f.style">{{ f.c }}</span>
    <div class="gsuc-head">
      <h1>تم إرسال هديتك بنجاح</h1>
      <span class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></span>
    </div>
    <p class="sub">لقد أسعدت قلبًا اليوم 🤍 شكرًا لاختيارك عناية سامي</p>
    <div class="gsuc-grid">
      <div class="gsuc-card-col">
        <GiftCard :design="state.design" />
        <div class="gcard-msg"><span class="q q1">"</span><span class="q q2">"</span><p>{{ state.msg }}</p></div>
      </div>
      <div class="card gsuc-details">
        <h4>تفاصيل الإرسال</h4>
        <div class="gs-row"><span class="k"><SIcon :inner="I.user" :size="14" /> المهدى إليه</span><span class="v">{{ state.name }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.send" :size="14" /> طريقة الإرسال</span><span class="v">{{ sendName }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.phone" :size="14" /> رقم الجوال</span><span class="v" dir="ltr">{{ state.phone }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.clock" :size="14" /> وقت الإرسال</span><span class="v"><span class="chip">✓ {{ state.when === 'now' ? 'تم الإرسال الآن' : 'مجدولة' }}</span></span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.cal" :size="14" /> تاريخ الإرسال</span><span class="v">{{ dateStr }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.card" :size="14" /> رقم العملية</span><span class="v" dir="ltr">{{ state.ref }}</span></div>
        <div :style="OK_BOX">
          <span :style="OK_IC"><SIcon :inner="I.check" :size="13" /></span>
          تم إرسال الهدية بنجاح إلى المستلم — ستظهر الهدية في رسالة خاصة من عناية سامي
        </div>
      </div>
    </div>
    <div class="gsuc-actions">
      <button class="btn btn-gold" id="openRecipient" @click="emit('recipient')"><SIcon :inner="I.box" :size="15" /> فتح صفحة المستلم</button>
      <button class="btn btn-dark" id="newGift" @click="emit('new-gift')"><SIcon :inner="I.gift" :size="15" /> إهداء جديد</button>
      <button class="btn btn-line" id="copySelf" @click="emit('copy-self')"><SIcon :inner="I.mail" :size="15" /> إرسال نسخة لنفسي</button>
      <button class="btn btn-line" id="shareGift" @click="emit('share')"><SIcon :inner="I.share" :size="15" /> مشاركة الهدية</button>
      <button class="btn btn-line" id="backHome" @click="emit('home')"><SIcon :inner="I.home" :size="15" /> العودة للرئيسية</button>
    </div>
    <div class="thanks"><i>❦</i> شكرًا لاختيارك عناية سامي لتقديم تجربة مميزة لمن تحب <i>❦</i></div>
  </div>
</template>
