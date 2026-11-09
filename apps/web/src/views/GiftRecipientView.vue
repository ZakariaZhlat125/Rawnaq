<script setup>
import { computed, onMounted, ref } from 'vue'
import GiftCard from '@/components/gifts/GiftCard.vue'
import SIcon from '@/components/common/SIcon.vue'
import { fetchGiftClaim, redeemGiftCode } from '@/services/giftsApi'
import { useLanguage } from '@/composables/useLanguage'
import { useAuth } from '@/composables/useAuth'
import { useRouter } from 'vue-router'

const loading = ref(true)
const error = ref('')
const gift = ref(null)
const { state: language } = useLanguage()
const { requireAuth } = useAuth()
const router = useRouter()
const adding = ref(false)
const addMsg = ref(null) // { ok, text }
const fmtDate = iso => {
  if (!iso) return ''
  const d = new Date(iso)
  return `${d.getDate()} ${AR_M[d.getMonth()]} ${d.getFullYear()}`
}
const rs = n => Number(n || 0).toLocaleString('ar-EG-u-nu-latn')

/* إضافة الهدية للحساب بالكود: بطاقة القيمة → المحفظة، الخدمة/الباقة → هداياي */
function addToAccount() {
  requireAuth(async () => {
    adding.value = true
    addMsg.value = null
    try {
      const res = await redeemGiftCode(gift.value.redeem_code)
      addMsg.value = { ok: true, text: res.message }
      gift.value = { ...gift.value, claimed: true, usable: res.data?.usable ?? false }
    } catch (e) {
      addMsg.value = { ok: false, text: e.message }
    } finally {
      adding.value = false
    }
  })
}
const AR_M = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
const refCode = computed(() => new URLSearchParams(window.location.search).get('token') || '')
const sentAt = computed(() => {
  if (!gift.value) return ''
  const date = new Date(gift.value.created_at || Date.now())
  return `${date.getDate()} ${AR_M[date.getMonth()]} ${date.getFullYear()}`
})
function localizedName(name) {
  if (!name) return ''
  if (typeof name === 'string') return name
  return language.lang === 'en'
    ? name.en || name.ar || ''
    : name.ar || name.en || ''
}

const giftItems = computed(() => [...(gift.value?.services || []), ...(gift.value?.packages || [])]
  .map(item => ({ id: `${item.id}-${localizedName(item.name)}`, name: localizedName(item.name) }))
  .filter(item => item.name))

async function loadGift() {
  if (!refCode.value) { error.value = 'لا يوجد رابط هدية صالح'; loading.value = false; return }
  try { gift.value = await fetchGiftClaim(refCode.value) }
  catch { error.value = 'تعذّر تحميل تفاصيل الهدية. تأكد من الرابط أو تواصل مع الدعم.' }
  finally { loading.value = false }
}
onMounted(loadGift)
const I = {
  user: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  send: '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  gift: '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7"/>',
}
</script>

<template>
  <div class="gift-recipient-page">
    <div v-if="loading" class="gift-recipient-state"><p>جارٍ تحميل تفاصيل الهدية...</p></div>
    <div v-else-if="error" class="gift-recipient-state error"><h2>تعذّر فتح الهدية</h2><p>{{ error }}</p><button class="btn btn-gold" @click="$router.push('/gifts')">العودة إلى صفحة الهدايا</button></div>
    <article v-else-if="gift" class="gift-recipient-shell">
      <header><small>هدية خاصة من رونق</small><h1>هدية صُنعت لك بكل اهتمام</h1></header>
      <GiftCard :design="gift.design" :recipient="gift.recipient_name" :sender="gift.sender_name" :message="gift.message" />
      <section class="gift-recipient-meta">
        <div class="meta-row"><span class="k"><SIcon :inner="I.user" :size="15" /> المهدى إليه</span><span class="v">{{ gift.recipient_name || '—' }}</span></div>
        <div v-if="gift.sender_name" class="meta-row"><span class="k"><SIcon :inner="I.send" :size="15" /> من</span><span class="v">{{ gift.sender_name }}</span></div>
        <div class="meta-row"><span class="k"><SIcon :inner="I.clock" :size="15" /> تاريخ الإرسال</span><span class="v">{{ sentAt }}</span></div>
      </section>
      <section class="gift-recipient-meta">
        <div v-if="gift.type === 'value'" class="meta-row"><span class="k"><SIcon :inner="I.gift" :size="15" /> قيمة البطاقة</span><span class="v">{{ rs(gift.value_amount) }} ر.س</span></div>
        <div v-if="gift.expires_at" class="meta-row"><span class="k"><SIcon :inner="I.clock" :size="15" /> صالحة حتى</span><span class="v">{{ fmtDate(gift.expires_at) }}</span></div>
        <div class="meta-row"><span class="k">الحالة</span><span class="v">{{ gift.status_label }}</span></div>
        <div v-if="gift.redeem_code" class="meta-row"><span class="k">كود الهدية</span><span class="v code" dir="ltr">{{ gift.redeem_code }}</span></div>
      </section>
      <section v-if="giftItems.length" class="gift-recipient-items"><h2><SIcon :inner="I.gift" :size="17" /> تفاصيل الهدية</h2><ul><li v-for="item in giftItems" :key="item.id">{{ item.name }}</li></ul></section>
      <p v-if="gift.type !== 'value'" class="no-price-note">قيمة الهدية وبيانات الدفع خاصة بالمُهدي ولا تظهر للمستلم.</p>
      <p v-if="addMsg" class="add-msg" :class="addMsg.ok ? 'ok' : 'err'" role="status">{{ addMsg.text }}</p>
      <p v-if="!gift.usable && !gift.claimed" class="add-msg err"><span>هذه الهدية غير متاحة للاستخدام حالياً</span> — <span>{{ gift.status_label }}</span></p>
      <div class="gift-recipient-actions">
        <button v-if="gift.usable && !gift.claimed && gift.redeem_code" class="btn btn-gold" :disabled="adding" @click="addToAccount">{{ gift.type === 'value' ? 'أضف إلى محفظتي' : 'أضف إلى حسابي' }}</button>
        <button v-else-if="gift.claimed" class="btn btn-gold" @click="router.push(gift.type === 'value' ? '/account?tab=wallet' : '/account?tab=gifts')">{{ gift.type === 'value' ? 'عرض المحفظة' : 'عرض هداياي' }}</button>
        <button class="btn btn-line" @click="$router.push('/')">العودة للرئيسية</button>
      </div>
    </article>
  </div>
</template>

<style scoped>
.gift-recipient-page{min-height:70vh;display:grid;place-items:center;padding:48px 20px;background:linear-gradient(180deg,#f6efe3 0%,#fff 100%)}
.gift-recipient-shell{width:min(760px,100%);display:grid;gap:18px}.gift-recipient-shell>header{text-align:center}.gift-recipient-shell>header small{color:var(--gold-deep);font-weight:800}.gift-recipient-shell>header h1{margin:7px 0 0;font-family:var(--font-d,'Lama Sans',sans-serif)}
.gift-recipient-meta,.gift-recipient-items{display:grid;gap:10px;padding:18px;background:#fff;border:1px solid rgba(143,113,52,.18);border-radius:18px;box-shadow:0 18px 40px -30px rgba(0,0,0,.3)}
.meta-row{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:11px 13px;border-radius:11px;background:#fffaf1}.meta-row .k{display:inline-flex;align-items:center;gap:8px;color:var(--gold-deep);font-weight:700}.meta-row .v{font-weight:700}.gift-recipient-items h2{display:flex;align-items:center;gap:8px;margin:0;font-size:17px}.gift-recipient-items ul{display:grid;gap:8px;margin:0;padding:0;list-style:none}.gift-recipient-items li{padding:10px 12px;border-radius:10px;background:#fffaf1;font-weight:600}.gift-recipient-items li::before{content:'✦';color:var(--gold-deep);margin-left:7px}.no-price-note{text-align:center;margin:0;color:#877b6d;font-size:12px}.gift-recipient-actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}.gift-recipient-state{text-align:center;padding:48px 20px}.meta-row .v.code{font-family:ui-monospace,monospace;letter-spacing:1px}.add-msg{text-align:center;margin:0;font-size:14px}.add-msg.ok{color:#2f6b2a}.add-msg.err{color:#a33}.gift-recipient-state.error{color:#7a2f2f}
@media(max-width:640px){.gift-recipient-page{padding:24px 14px 40px}.gift-recipient-shell{gap:13px}.gift-recipient-shell>header h1{font-size:23px}.meta-row{align-items:flex-start}.gift-recipient-actions .btn{flex:1}}
</style>