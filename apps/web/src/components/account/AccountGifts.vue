<script setup>
import { ref, onMounted } from 'vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import GiftCodeRedeem from '@/components/gifts/GiftCodeRedeem.vue'
import {
  fetchSentGifts, fetchSentGift, resendGift, fetchReceivedGifts,
  claimGift, bookGift, fetchGiftStaff, fetchGiftTimes,
} from '@/services/giftsApi'

const emit = defineEmits(['updated'])

const tab = ref('received')
const sent = ref([])
const received = ref([])
const loading = ref(true)
const error = ref('')
const notice = ref(null)
const openId = ref(null)
const timeline = ref({})
const busy = ref('')

/* حجز هدية الخدمة: موعد لكل خدمة */
const booking = ref(null) // { gift, rows: [{service_id, name, staff:[], employee_id, date, times:[], time}] }

const STATUS_CLASS = { paid: 'st-wait', sent: 'st-wait', claimed: 'st-mid', redeemed: 'st-ok', expired: 'st-off', cancelled: 'st-bad', refunded: 'st-bad' }
/* مراحل الهدية كما يراها المُهدي */
const STAGES = [
  { key: 'sent', label: 'أُرسلت' },
  { key: 'opened', label: 'فُتحت' },
  { key: 'claimed', label: 'استُلمت' },
  { key: 'redeemed', label: 'استُخدمت' },
]

const rs = n => Number(n || 0).toLocaleString('ar-EG-u-nu-latn', { maximumFractionDigits: 2 })
const day = iso => (iso ? String(iso).slice(0, 10) : '')
const today = () => new Date().toISOString().slice(0, 10)

function title(g) {
  if (g.type === 'value') return `بطاقة هدية بقيمة ${rs(g.value_amount)} ر.س`
  return [...g.services, ...g.packages].join(' + ') || 'هدية'
}
function reached(g, key) {
  if (key === 'sent') return !!g.sent_at || g.opened || g.claimed || !!g.redeemed_at
  if (key === 'opened') return g.opened || g.claimed || !!g.redeemed_at
  if (key === 'claimed') return g.claimed || !!g.redeemed_at
  return !!g.redeemed_at
}
function say(ok, text) { notice.value = { ok, text } }

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [s, r] = await Promise.all([fetchSentGifts(), fetchReceivedGifts()])
    sent.value = s.data || []
    received.value = r.data || []
    if (!received.value.length && sent.value.length) tab.value = 'sent'
  } catch (e) {
    error.value = e.message || 'تعذر تحميل الهدايا'
  } finally {
    loading.value = false
  }
}

async function toggle(g) {
  openId.value = openId.value === g.id ? null : g.id
  if (openId.value && !timeline.value[g.id]) {
    try { timeline.value[g.id] = (await fetchSentGift(g.id)).data.timeline } catch { timeline.value[g.id] = [] }
  }
}

async function resend(g) {
  busy.value = `resend-${g.id}`
  try { say(true, (await resendGift(g.id)).message) } catch (e) { say(false, e.message) } finally { busy.value = '' }
}

async function copy(text) {
  try { await navigator.clipboard.writeText(text); say(true, 'تم النسخ') } catch { say(false, text) }
}

async function claim(g) {
  busy.value = `claim-${g.id}`
  try {
    const res = await claimGift(g.id)
    say(true, res.message)
    await load()
    emit('updated')
  } catch (e) { say(false, e.message) } finally { busy.value = '' }
}

async function startBooking(g) {
  const rows = g.service_items.map(item => ({ service_id: item.id, name: item.name, staff: [], employee_id: '', date: '', times: [], time: '' }))
  booking.value = { gift: g, rows }
  await Promise.all(rows.map(async row => {
    try { row.staff = await fetchGiftStaff(g.branch_id, row.service_id) } catch { row.staff = [] }
  }))
}

async function loadTimes(row) {
  row.time = ''
  row.times = []
  if (!row.employee_id || !row.date) return
  try { row.times = await fetchGiftTimes(row.date, row.employee_id) } catch { row.times = [] }
}

async function confirmBooking() {
  const { gift, rows } = booking.value
  busy.value = 'book'
  try {
    const res = await bookGift(gift.id, rows.map(r => ({ service_id: r.service_id, employee_id: Number(r.employee_id), date: r.date, time: r.time })))
    say(true, res.message)
    booking.value = null
    await load()
  } catch (e) { say(false, e.message) } finally { busy.value = '' }
}

async function onRedeemed() {
  await load()
  tab.value = 'received'
  emit('updated')
}

onMounted(load)
</script>

<template>
  <div class="gifts-view">
    <section class="g-card"><GiftCodeRedeem @redeemed="onRedeemed" /></section>

    <div v-if="notice" class="g-notice" :class="notice.ok ? 'ok' : 'err'" role="status">
      <span>{{ notice.text }}</span><button type="button" aria-label="إغلاق" @click="notice = null">×</button>
    </div>

    <PageSkeleton v-if="loading" variant="account" />
    <div v-else-if="error" class="g-state err">{{ error }}</div>
    <template v-else>
      <div class="g-tabs" role="tablist">
        <button type="button" role="tab" :aria-selected="tab === 'received'" :class="{ on: tab === 'received' }" @click="tab = 'received'">هداياي المستلمة ({{ received.length }})</button>
        <button type="button" role="tab" :aria-selected="tab === 'sent'" :class="{ on: tab === 'sent' }" @click="tab = 'sent'">هداياي المرسلة ({{ sent.length }})</button>
      </div>

      <!-- المستلمة -->
      <template v-if="tab === 'received'">
        <p v-if="!received.length" class="g-state">لا توجد هدايا مستلمة بعد</p>
        <article v-for="g in received" :key="g.id" class="g-item">
          <div class="g-top">
            <div>
              <b>{{ title(g) }}</b>
              <small>من <bdi data-no-i18n>{{ g.sender_name || '—' }}</bdi></small>
              <small v-if="g.expires_at && g.usable">صالحة حتى <bdi>{{ day(g.expires_at) }}</bdi></small>
            </div>
            <span class="status" :class="STATUS_CLASS[g.status]">{{ g.status_label }}</span>
          </div>
          <p v-if="g.message" class="g-msg" data-no-i18n>{{ g.message }}</p>

          <div v-if="g.usable" class="g-actions">
            <button v-if="!g.claimed" class="btn-org" type="button" :disabled="busy === `claim-${g.id}`" @click="claim(g)">
              {{ g.type === 'value' ? 'أضف إلى محفظتي' : 'أضف إلى حسابي' }}
            </button>
            <button v-else-if="g.can_book_online" class="btn-org" type="button" @click="startBooking(g)">احجز موعدك</button>
            <template v-if="g.claimed && g.code">
              <span class="code" dir="ltr">{{ g.code }}</span>
              <small>اعرض هذا الكود في الفرع لاستخدام الهدية</small>
            </template>
          </div>

          <!-- نموذج الحجز -->
          <form v-if="booking && booking.gift.id === g.id" class="g-book" @submit.prevent="confirmBooking">
            <div v-for="row in booking.rows" :key="row.service_id" class="g-book-row">
              <b>{{ row.name }}</b>
              <select v-model="row.employee_id" aria-label="الموظف" required @change="loadTimes(row)">
                <option value="" disabled>اختر الموظف</option>
                <option v-for="s in row.staff" :key="s.id" :value="s.id">{{ s.first_name }} {{ s.last_name }}</option>
              </select>
              <input v-model="row.date" type="date" :min="today()" :max="day(g.expires_at) || undefined" aria-label="التاريخ" required @change="loadTimes(row)" />
              <select v-model="row.time" aria-label="الوقت" required :disabled="!row.times.length">
                <option value="" disabled>{{ row.employee_id && row.date && !row.times.length ? 'لا توجد أوقات متاحة' : 'اختر الوقت' }}</option>
                <option v-for="t in row.times" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="g-actions">
              <button class="btn-org" type="submit" :disabled="busy === 'book' || booking.rows.some(r => !r.time)">تأكيد الحجز</button>
              <button type="button" class="g-link" @click="booking = null">إلغاء</button>
            </div>
          </form>
        </article>
      </template>

      <!-- المرسلة -->
      <template v-else>
        <p v-if="!sent.length" class="g-state">لم ترسل هدايا بعد</p>
        <article v-for="g in sent" :key="g.id" class="g-item">
          <div class="g-top">
            <div>
              <b>{{ title(g) }}</b>
              <small>إلى <bdi data-no-i18n>{{ g.recipient_name || '—' }}</bdi> · <bdi>{{ day(g.created_at) }}</bdi></small>
            </div>
            <span class="status" :class="STATUS_CLASS[g.status]">{{ g.status_label }}</span>
          </div>

          <ol v-if="!['cancelled', 'refunded'].includes(g.status)" class="stages">
            <li v-for="st in STAGES" :key="st.key" :class="{ done: reached(g, st.key) }">{{ st.label }}</li>
          </ol>

          <div class="g-actions">
            <button v-if="g.usable && g.share_url" type="button" class="g-link" @click="copy(g.share_url)">نسخ رابط الهدية</button>
            <button v-if="g.usable && g.code" type="button" class="g-link" dir="ltr" @click="copy(g.code)">{{ g.code }}</button>
            <button v-if="g.usable" type="button" class="g-link" :disabled="busy === `resend-${g.id}`" @click="resend(g)">إعادة الإرسال</button>
            <button type="button" class="g-link" @click="toggle(g)">{{ openId === g.id ? 'إخفاء التفاصيل' : 'التفاصيل' }}</button>
          </div>

          <ul v-if="openId === g.id" class="timeline">
            <li v-for="(e, i) in timeline[g.id] || []" :key="i"><b>{{ e.label }}</b><small dir="ltr">{{ String(e.at).slice(0, 16).replace('T', ' ') }}</small></li>
            <li v-if="g.expires_at && g.usable"><b>صالحة حتى</b><small>{{ day(g.expires_at) }}</small></li>
          </ul>
        </article>
      </template>
    </template>
  </div>
</template>

<style scoped>
.gifts-view { display: grid; gap: 14px; }
.g-card, .g-item { background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 16px; }
.g-notice { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; font-size: 14px; }
.g-notice.ok { background: #f0f8ee; color: #2f6b2a; border: 1px solid #cfe6c9; }
.g-notice.err { background: #fff6f6; color: #a33; border: 1px solid #f0c9c9; }
.g-notice button { background: none; border: 0; font-size: 20px; cursor: pointer; color: inherit; }
.g-state { text-align: center; color: #8b8379; padding: 24px 12px; margin: 0; }
.g-state.err { color: #a33; }
.g-tabs { display: flex; gap: 8px; }
.g-tabs button { padding: 9px 16px; border-radius: 999px; border: 1px solid #e9e0d3; background: #fff; cursor: pointer; font-size: 13px; color: #241f1b; }
.g-tabs button.on { border-color: #1C9384; background: #fff8e9; color: #7c571d; }
.g-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
.g-top b { display: block; font-size: 15px; color: #241f1b; margin-bottom: 3px; }
.g-top small { display: block; font-size: 12px; color: #8b8379; }
.g-msg { margin: 10px 0 0; font-size: 13px; color: #6f665c; white-space: pre-line; }
.status { font-size: 12px; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
.st-wait { background: #fff8e9; color: #7c571d; }
.st-mid { background: #eef4ff; color: #2b4f8a; }
.st-ok { background: #f0f8ee; color: #2f6b2a; }
.st-off { background: #f3f1ee; color: #8b8379; }
.st-bad { background: #fff6f6; color: #a33; }
.g-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 12px; }
.g-actions small { color: #8b8379; font-size: 12px; }
.g-link { background: none; border: 0; color: #7c571d; cursor: pointer; font-size: 13px; text-decoration: underline; padding: 0; }
.code { font-family: ui-monospace, monospace; background: #fff8e9; border: 1px dashed #1C9384; border-radius: 8px; padding: 6px 10px; letter-spacing: 1px; font-size: 14px; }
.btn-org:disabled { opacity: .55; cursor: not-allowed; }
.stages { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; list-style: none; padding: 0; margin: 14px 0 0; counter-reset: st; }
.stages li { text-align: center; font-size: 11.5px; color: #b5aca1; padding-top: 18px; position: relative; }
.stages li::before { content: ''; position: absolute; top: 0; inset-inline: 0; height: 6px; border-radius: 6px; background: #eee6da; }
.stages li.done { color: #7c571d; font-weight: 600; }
.stages li.done::before { background: #1C9384; }
.timeline { list-style: none; padding: 0; margin: 12px 0 0; border-inline-start: 2px solid #eee6da; }
.timeline li { padding: 4px 12px 8px; display: flex; justify-content: space-between; gap: 12px; font-size: 13px; }
.timeline small { color: #8b8379; }
.g-book { margin-top: 14px; padding-top: 14px; border-top: 1px solid #f3ede4; display: grid; gap: 10px; }
.g-book-row { display: grid; grid-template-columns: 1.2fr 1.2fr 1fr 1fr; gap: 8px; align-items: center; }
.g-book-row select, .g-book-row input { padding: 9px 10px; border: 1px solid #e9e0d3; border-radius: 10px; font-size: 13px; background: #fff; min-width: 0; }
@media (max-width: 640px) {
  .g-book-row { grid-template-columns: 1fr; }
  .g-top { flex-direction: column; }
}
</style>
