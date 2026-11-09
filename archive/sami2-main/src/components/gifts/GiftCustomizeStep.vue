<script setup>
/*
  تخصيص الهدية — مُرحَّل حرفيًا من view3() في src/legacy/gifts.html
  يشمل: المعاينة الحيّة · بيانات الإهداء · تصميم البطاقة · طريقة وتوقيت الإرسال
*/
import { computed } from 'vue'
import { DESIGNS, SENDS } from '@/data/gifts'
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'
import GiftCard from './GiftCard.vue'

const { state, canNext } = useGifts()
const emit = defineEmits(['nav'])

const remain = computed(() => 200 - state.msg.length)

const I = {
  eye:   '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
  user:  '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  brush: '<path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5zM2 2l7.6 7.6"/><circle cx="11" cy="11" r="2"/>',
  send:  '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>',
  check: '<path d="M20 6L9 17l-5-5"/>',
  prev:  '<path d="M5 12h14M13 6l6 6-6 6"/>',
  next:  '<path d="M19 12H5M11 18l-6-6 6-6"/>',
}
</script>

<template>
  <div class="g-head"><h1>خصص هديتك لتكون أجمل 🤍</h1><p>أضف لمستك الخاصة مع رسالة وتصميم بطاقة الإهداء</p></div>
  <div class="custom-grid">
    <div class="preview-col">
      <h4><SIcon :inner="I.eye" :size="16" /> معاينة بطاقة الإهداء</h4>
      <div id="cardPrev"><GiftCard :design="state.design" /></div>
      <div class="gcard-msg"><span class="q q1">"</span><span class="q q2">"</span>
        <b>إلى: <span id="pvName">{{ state.name || '—' }}</span></b>
        <p id="pvMsg">{{ state.msg || 'اكتب رسالتك الخاصة لتظهر هنا' }}</p>
        <small style="display:block;margin-top:8px;font-size:11px;color:var(--mute)">من: <span id="pvSender">{{ state.sender || 'مجهول جميل' }}</span></small>
      </div>
    </div>
    <div class="form-col">
      <div class="card" style="margin-bottom:14px">
        <h4><SIcon :inner="I.user" :size="16" /> بيانات الإهداء</h4>
        <div class="two">
          <div class="fld"><label>اسم المُهدي (أنت)</label><input id="gSender" placeholder="اسمك" v-model="state.sender" /></div>
          <div class="fld"><label>اسم المهدى إليه</label><input id="gName" placeholder="أدخل الاسم" v-model="state.name" /></div>
        </div>
        <div class="two">
          <div class="fld" style="margin-bottom:4px"><label>رقم جوال المستلم</label><input id="gPhone" dir="ltr" placeholder="05xxxxxxxx" v-model="state.phone" /></div>
        </div>
        <div class="fld" style="margin-bottom:4px"><label>الرسالة الشخصية</label>
          <textarea id="gMsg" maxlength="200" placeholder="اكتب رسالتك الخاصة هنا..." v-model="state.msg"></textarea>
          <span class="cnt" :class="{ max: remain < 20 }" id="gCnt">{{ state.msg.length }}/200</span>
        </div>
      </div>
      <div class="card" style="margin-bottom:14px">
        <h4><SIcon :inner="I.brush" :size="16" /> تصميم بطاقة الإهداء</h4>
        <div class="designs">
          <div v-for="x in DESIGNS" :key="x.id" class="design" :class="{ sel: state.design === x.id }" :data-d="x.id" @click="state.design = x.id">
            <div class="sw" :style="`background:${x.bg};color:${x.fg}`"><span class="dchk"><SIcon :inner="I.check" :size="10" /></span>SAMI</div>
            <small>{{ x.n }}</small>
          </div>
        </div>
      </div>
      <div class="card">
        <h4><SIcon :inner="I.send" :size="16" /> طريقة وتوقيت الإرسال</h4>
        <div class="methods">
          <div v-for="m in SENDS" :key="m.id" class="mth" :class="{ sel: state.method === m.id }" :data-m="m.id" @click="state.method = m.id"><span class="mi"><SIcon :inner="m.ic" :size="17" /></span>{{ m.n }}</div>
        </div>
        <div class="send-opts" style="margin-top:16px;margin-bottom:0">
          <span class="ro" :class="{ on: state.when === 'now' }" data-w="now" @click="state.when = 'now'"><i></i> 🚀 إرسال الآن — سيتم إرسال الهدية فورًا</span>
          <span class="ro" :class="{ on: state.when === 'later' }" data-w="later" @click="state.when = 'later'"><i></i> 📅 جدولة لاحقًا</span>
        </div>
        <div class="sched-box" :class="{ open: state.when === 'later' }">
          <div class="fld" style="margin:0"><label>اختر التاريخ والوقت المناسب</label><input type="datetime-local" id="gSched" v-model="state.schedDate" /></div>
        </div>
      </div>
      <div class="inline-actions">
        <button class="btn btn-prev" data-nav="back" @click="emit('nav', -1)"><SIcon :inner="I.prev" :size="15" /> رجوع</button>
        <button class="btn btn-gold" data-nav="next" :disabled="!canNext" @click="emit('nav', 1)">متابعة إلى الدفع <SIcon :inner="I.next" :size="15" /></button>
      </div>
    </div>
  </div>
</template>
