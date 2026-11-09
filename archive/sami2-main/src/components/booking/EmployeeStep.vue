<script setup>
/*
  اختيار الموظف — مُرحَّل حرفيًا من view3() في src/legacy/booking.html
  وضعان: تلقائي (النظام يختار) أو يدوي (اختيار موظف لكل قسم)
*/
import { SERVICES, EMPLOYEES } from '@/data/booking'
import { useBooking } from '@/composables/useBooking'

const { state, selCats, hasSvc } = useBooking()

const svcCount = catId => SERVICES.filter(s => s.cat === catId && hasSvc(s.id)).length

const AUTO_LIST = [
  'توفير الوقت',
  'أفضل تقييم متاح',
  'تنفيذ الخدمات بالتوازي وتقليل الانتظار',
  'منع أي تعارض في المواعيد',
]
const MANUAL_LIST = [
  'اختر الموظف الذي تفضّله',
  'اطّلع على التقييمات والخبرات',
  'تخصيص كل خدمة لموظف مختلف',
]

const NOTE_IC = 'flex:none;width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--gold-bright),var(--gold));color:var(--ink)'
const NOTE_P = 'font-size:13.5px;color:#5c5442;line-height:1.9'
</script>

<template>
  <div class="panel-head"><h1>اختر طريقة اختيار الموظف</h1><p>يمكنك اختيار الموظف بنفسك أو ترك الأمر لنا لاختيار الأفضل لك</p></div>

  <div class="mode-grid">
    <div class="mode dark" :class="{ sel: state.mode === 'auto' }" data-mode="auto" @click="state.mode = 'auto'">
      <span class="chk"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span class="mi"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M5 21c0-4 3-6 7-6s7 2 7 6"/><path d="M19 3l.9 1.9L22 6l-2.1 1.1L19 9l-.9-1.9L16 6l2.1-1.1z"/></svg></span>
      <h3>اختيار تلقائي</h3><div class="sub-t">سنختار لك أفضل فريق متاح</div>
      <span class="flag">✨ الأسرع والأسهل</span>
      <ul>
        <li v-for="(x, i) in AUTO_LIST" :key="i"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg> {{ x }}</li>
      </ul>
      <div class="glow-msg">سيتم اختيار الفريق الأنسب تلقائيًا بعد تحديد وقت بداية الجلسة</div>
    </div>
    <div class="mode" :class="{ sel: state.mode === 'manual' }" data-mode="manual" @click="state.mode = 'manual'">
      <span class="chk"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span class="mi"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
      <h3>اختيار موظف محدد</h3><div class="sub-t">اختر الموظف المناسب لكل خدمة</div>
      <span class="flag">🎯 تحكم كامل</span>
      <ul>
        <li v-for="(x, i) in MANUAL_LIST" :key="i"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M20 6L9 17l-5-5"/></svg> {{ x }}</li>
      </ul>
      <div class="glow-msg">اختر موظفًا لكل قسم من الأقسام أدناه</div>
    </div>
  </div>

  <div v-if="state.mode === 'manual'" class="emp-section">
    <div v-for="c in selCats" :key="c.id" class="emp-row">
      <div class="emp-cat" :style="`background:linear-gradient(150deg,${c.hex},${c.hex}CC)`">
        <span class="cat-ico">{{ c.emoji }}</span><b>{{ c.name }}</b><small>{{ svcCount(c.id) }} خدمة</small>
      </div>
      <div class="emp-list" :style="`--acc:${c.hex}`">
        <div class="emp any" :class="{ sel: state.emp[c.id] === 'any' }" :data-cat="c.id" data-emp="any" @click="state.emp[c.id] = 'any'">
          <span class="chk"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
          <span class="av"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <b>أي موظف</b><span class="rate">الأنسب تلقائيًا</span>
        </div>
        <div v-for="e in EMPLOYEES[c.id]" :key="e.n" class="emp" :class="{ sel: state.emp[c.id] === e.n }" :data-cat="c.id" :data-emp="e.n" @click="state.emp[c.id] = e.n">
          <span class="chk"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
          <span class="av" aria-hidden="true"></span>
          <b>{{ e.n }}</b><span class="rate">★ {{ e.r }}</span>
        </div>
      </div>
    </div>
  </div>

  <div v-if="state.mode === 'auto'" class="j-note card" style="display:flex;gap:16px;align-items:center;padding:20px 24px;border-color:var(--line)">
    <span :style="NOTE_IC"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg></span>
    <p :style="NOTE_P"><b style="color:var(--ink)">كيف يعمل الاختيار التلقائي؟</b> في الخطوة التالية ستظهر لك <b style="color:var(--ink)">أوقات يمكن تنفيذ جلستك فيها بالكامل</b> — يحسب النظام مدة الخدمات وتوفر الموظفين وتنسيق الأقسام، وبعد اختيار وقت البداية يبني الجلسة كاملة: توزيع الخدمات، اختيار الفريق الأنسب، وترتيب أوقات التنفيذ.<br><b style="color:var(--gold-deep)">✨ عند اختيار الموظف تلقائيًا سيتم إضافة نقاط ولاء لك.</b></p>
  </div>
</template>
