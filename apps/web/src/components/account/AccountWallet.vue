<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import { useLanguage } from '@/composables/useLanguage'
import {
  fetchWallet, fetchWalletHistory, fetchTopupOffers, startTopup,
  convertPoints, fetchWithdrawals, requestWithdrawal,
} from '@/services/walletApi'

const emit = defineEmits(['updated'])
const route = useRoute()
const router = useRouter()
const { state: langState } = useLanguage()
const offerTitle = (o) => (langState.lang === 'en' && o.title_en) || o.title_ar

const wallet = ref(null)
const offers = ref([])
const history = ref([])
const historyPage = ref({ current_page: 1, last_page: 1 })
const historyType = ref('')
const withdrawals = ref([])
const loading = ref(true)
const error = ref('')
const notice = ref(null) // { kind: 'ok' | 'err', text }

const topupAmount = ref(null)
const points = ref(null)
const withdrawal = ref({ amount: null, iban: '', account_name: '' })
const busy = ref('')

const FILTERS = [
  { id: '', label: 'الكل' },
  { id: 'topup', label: 'شحن' },
  { id: 'payment', label: 'دفع' },
  { id: 'refund', label: 'استرداد' },
  { id: 'reward', label: 'مكافآت' },
  { id: 'campaign', label: 'عروض الشحن' },
  { id: 'points_conversion', label: 'تحويل نقاط' },
  { id: 'withdraw', label: 'سحب' },
]

const STATUS_CLASS = { pending: 'st-wait', approved: 'st-wait', paid: 'st-ok', rejected: 'st-bad', failed: 'st-bad' }

function rs(n) {
  return Number(n || 0).toLocaleString('ar-EG-u-nu-latn', { maximumFractionDigits: 2 })
}
function day(iso) {
  return iso ? String(iso).slice(0, 10) : ''
}

const topupOffer = computed(() => {
  const amount = Number(topupAmount.value) || 0
  return [...offers.value].filter(o => amount >= Number(o.min_amount)).sort((a, b) => b.min_amount - a.min_amount)[0] || null
})
const pointsValue = computed(() => Math.round((Number(points.value) || 0) * (wallet.value?.point_value || 0) * 100) / 100)

function say(kind, text) {
  notice.value = { kind, text }
}

async function loadWallet() {
  const res = await fetchWallet()
  wallet.value = res.data
}

async function loadHistory(page = 1) {
  const res = await fetchWalletHistory({ type: historyType.value, page })
  history.value = page === 1 ? res.data : [...history.value, ...res.data]
  historyPage.value = res.pagination
}

async function loadAll() {
  loading.value = true
  error.value = ''
  try {
    await loadWallet()
    const [offersRes, withdrawalsRes] = await Promise.all([fetchTopupOffers(), fetchWithdrawals(), loadHistory(1)])
    offers.value = offersRes.data || []
    withdrawals.value = withdrawalsRes.data || []
  } catch (e) {
    error.value = e.message || 'تعذر تحميل بيانات المحفظة'
  } finally {
    loading.value = false
  }
}

/* بعد أي حركة: نحدّث الرصيد والسجل، ونبلّغ صفحة الحساب تحدّث النظرة العامة */
async function refresh() {
  await Promise.all([loadWallet(), loadHistory(1)])
  emit('updated')
}

async function onTopup() {
  const amount = Number(topupAmount.value)
  const { min, max } = wallet.value.topup
  if (!amount || amount < min || amount > max) {
    say('err', `مبلغ الشحن يجب أن يكون بين ${rs(min)} و ${rs(max)} ر.س`)
    return
  }
  busy.value = 'topup'
  try {
    const res = await startTopup(amount)
    if (res.payment_url) window.location.href = res.payment_url
  } catch (e) {
    say('err', e.message)
    busy.value = ''
  }
}

async function onConvert() {
  busy.value = 'convert'
  try {
    const res = await convertPoints(Number(points.value))
    say('ok', res.message)
    points.value = null
    await refresh()
  } catch (e) {
    say('err', e.message)
  } finally {
    busy.value = ''
  }
}

async function onWithdraw() {
  busy.value = 'withdraw'
  try {
    const res = await requestWithdrawal({ ...withdrawal.value, amount: Number(withdrawal.value.amount) })
    say('ok', res.message)
    withdrawal.value = { amount: null, iban: '', account_name: '' }
    const list = await fetchWithdrawals()
    withdrawals.value = list.data || []
    await refresh()
  } catch (e) {
    say('err', e.message)
  } finally {
    busy.value = ''
  }
}

watch(historyType, () => loadHistory(1).catch(e => say('err', e.message)))

onMounted(async () => {
  await loadAll()
  /* رجوع من بوابة الدفع: /account?tab=wallet&topup=paid|failed|cancelled */
  const result = route.query.topup
  if (result) {
    if (result === 'paid') {
      say('ok', 'تم شحن المحفظة بنجاح')
      emit('updated')
    } else {
      say('err', result === 'cancelled' ? 'تم إلغاء عملية الدفع ولم يُضف أي مبلغ' : 'لم تكتمل عملية الدفع ولم يُضف أي مبلغ')
    }
    const { topup, ...query } = route.query
    router.replace({ query })
  }
})
</script>

<template>
  <div class="wallet-view">
    <PageSkeleton v-if="loading" variant="account" />
    <div v-else-if="error" class="w-state err">{{ error }}</div>

    <template v-else-if="wallet">
      <div v-if="notice" class="w-notice" :class="notice.kind" role="status">
        <span>{{ notice.text }}</span>
        <button type="button" aria-label="إغلاق" @click="notice = null">×</button>
      </div>

      <div v-if="!wallet.active" class="w-state err">المحفظة موقوفة حالياً. تواصل مع الإدارة.</div>

      <!-- الرصيد -->
      <section class="w-balance">
        <div class="total">
          <small>رصيد المحفظة</small>
          <b>{{ rs(wallet.balance) }} <em>ر.س</em></b>
        </div>
        <div class="split">
          <div><small>رصيد نقدي</small><b>{{ rs(wallet.cash_balance) }}</b><span>قابل للاسترداد</span></div>
          <div><small>رصيد ترويجي</small><b>{{ rs(wallet.bonus_balance) }}</b><span>يُصرف أولاً، غير قابل للسحب</span></div>
        </div>
        <p v-if="wallet.next_expiry" class="expiry">
          ينتهي {{ rs(wallet.next_expiry.amount) }} ر.س من الرصيد الترويجي بتاريخ {{ day(wallet.next_expiry.expires_at) }}
        </p>
      </section>

      <!-- الشحن -->
      <section class="w-card">
        <h3>شحن المحفظة</h3>
        <div class="presets">
          <button
            v-for="p in wallet.topup.presets" :key="p" type="button"
            :class="{ on: Number(topupAmount) === p }" @click="topupAmount = p"
          >{{ rs(p) }} ر.س</button>
        </div>
        <div class="row">
          <input
            v-model.number="topupAmount" type="number" inputmode="decimal"
            :min="wallet.topup.min" :max="wallet.topup.max" step="1"
            placeholder="مبلغ آخر" aria-label="مبلغ الشحن"
          />
          <button class="btn-org" type="button" :disabled="busy === 'topup' || !wallet.active" @click="onTopup">
            {{ busy === 'topup' ? 'جارٍ التحويل للدفع…' : 'شحن' }}
          </button>
        </div>
        <p v-if="topupOffer" class="gain">🎁 ستحصل على {{ rs(topupOffer.bonus_amount) }} ر.س رصيداً ترويجياً مع هذا الشحن</p>
        <ul v-if="offers.length" class="offers">
          <li v-for="o in offers" :key="o.id" :class="{ on: topupOffer?.id === o.id }">
            <b data-no-i18n>{{ offerTitle(o) }}</b>
            <span>اشحن {{ rs(o.min_amount) }} ر.س واحصل على {{ rs(o.bonus_amount) }} ر.س</span>
            <small v-if="o.bonus_expiry_days">صلاحية المكافأة {{ o.bonus_expiry_days }} يوم</small>
          </li>
        </ul>
        <p class="hint">يُضاف الرصيد بعد تأكيد الدفع من البوابة. الحد: {{ rs(wallet.topup.min) }} – {{ rs(wallet.topup.max) }} ر.س</p>
      </section>

      <!-- تحويل النقاط -->
      <section v-if="wallet.points_conversion.enabled" class="w-card">
        <h3>حوّل نقاطك إلى رصيد</h3>
        <p class="hint">لديك {{ rs(wallet.points_balance) }} نقطة · النقطة = {{ wallet.point_value }} ر.س · الحد الأدنى {{ rs(wallet.points_conversion.min) }} نقطة</p>
        <div class="row">
          <input
            v-model.number="points" type="number" inputmode="numeric"
            :min="wallet.points_conversion.min" :max="wallet.points_balance" step="1"
            placeholder="عدد النقاط" aria-label="عدد النقاط"
          />
          <button
            class="btn-org" type="button"
            :disabled="busy === 'convert' || !points || points < wallet.points_conversion.min || points > wallet.points_balance"
            @click="onConvert"
          >تحويل</button>
        </div>
        <p v-if="pointsValue > 0" class="gain">= {{ rs(pointsValue) }} ر.س رصيد ترويجي</p>
      </section>

      <!-- السجل -->
      <section class="w-card">
        <h3>سجل المحفظة</h3>
        <div class="filters" role="tablist">
          <button
            v-for="f in FILTERS" :key="f.id" type="button" role="tab"
            :aria-selected="historyType === f.id" :class="{ on: historyType === f.id }"
            @click="historyType = f.id"
          >{{ f.label }}</button>
        </div>
        <p v-if="!history.length" class="empty">لا توجد حركات</p>
        <ul v-else class="history">
          <li v-for="h in history" :key="h.id">
            <div>
              <b>{{ h.label }}</b>
              <small>{{ h.datetime }}</small>
              <small v-if="h.expires_at">ينتهي {{ day(h.expires_at) }}</small>
            </div>
            <div class="amt" :class="h.direction">
              <b>{{ h.direction === 'debit' ? '−' : '+' }}{{ rs(h.amount) }}</b>
              <small v-if="h.balance_after !== null">الرصيد {{ rs(h.balance_after) }}</small>
            </div>
          </li>
        </ul>
        <button
          v-if="historyPage.current_page < historyPage.last_page" type="button" class="more"
          @click="loadHistory(historyPage.current_page + 1)"
        >عرض المزيد</button>
      </section>

      <!-- السحب -->
      <section v-if="wallet.withdrawal.enabled" class="w-card">
        <h3>طلب استرداد الرصيد النقدي</h3>
        <p class="hint">يُحوَّل إلى حسابك البنكي بعد المراجعة. المتاح للسحب: {{ rs(wallet.cash_balance) }} ر.س · الحد الأدنى {{ rs(wallet.withdrawal.min) }} ر.س</p>
        <form class="wd-form" @submit.prevent="onWithdraw">
          <input v-model.number="withdrawal.amount" type="number" inputmode="decimal" step="0.01" :min="wallet.withdrawal.min" :max="wallet.cash_balance" placeholder="المبلغ" aria-label="المبلغ" required />
          <input v-model.trim="withdrawal.iban" dir="ltr" placeholder="SA00 0000 0000 0000 0000 0000" aria-label="رقم الآيبان" required />
          <input v-model.trim="withdrawal.account_name" placeholder="اسم صاحب الحساب" aria-label="اسم صاحب الحساب" required />
          <button class="btn-org" type="submit" :disabled="busy === 'withdraw' || wallet.cash_balance < wallet.withdrawal.min">إرسال طلب السحب</button>
        </form>
        <ul v-if="withdrawals.length" class="history wd-list">
          <li v-for="w in withdrawals" :key="w.id">
            <div>
              <b>{{ rs(w.amount) }} ر.س</b>
              <small>{{ day(w.created_at) }}</small>
              <small v-if="w.iban_last4" dir="ltr">•••• {{ w.iban_last4 }}</small>
              <small v-if="w.note" data-no-i18n>{{ w.note }}</small>
            </div>
            <span class="status" :class="STATUS_CLASS[w.status]">{{ w.status_label }}</span>
          </li>
        </ul>
      </section>

      <p v-if="wallet.refund_policy.refund_on_cancel" class="policy">
        عند إلغاء حجز مدفوع قبل موعده بـ {{ rs(wallet.refund_policy.free_cancel_hours) }} ساعة أو أكثر يُرجع المبلغ كاملاً إلى محفظتك،
        وبعد ذلك تُخصم رسوم {{ rs(wallet.refund_policy.late_cancel_fee_percent) }}%.
      </p>
    </template>
  </div>
</template>

<style scoped>
.wallet-view { display: grid; gap: 16px; }
.w-state { padding: 16px; border-radius: 14px; background: #fff; border: 1px solid #e9e0d3; text-align: center; color: #8b8379; }
.w-state.err { color: #a33; border-color: #f0c9c9; background: #fff6f6; }
.w-notice { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; font-size: 14px; }
.w-notice.ok { background: #f0f8ee; color: #2f6b2a; border: 1px solid #cfe6c9; }
.w-notice.err { background: #fff6f6; color: #a33; border: 1px solid #f0c9c9; }
.w-notice button { background: none; border: 0; font-size: 20px; cursor: pointer; color: inherit; line-height: 1; }

.w-balance { background: linear-gradient(120deg, #7c571d 0%, #1C9384 100%); color: #fff; border-radius: 18px; padding: 20px; }
.w-balance .total small { opacity: .85; font-size: 13px; }
.w-balance .total b { display: block; font-size: 32px; margin-top: 2px; }
.w-balance .total em { font-style: normal; font-size: 16px; opacity: .9; }
.w-balance .split { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px; }
.w-balance .split div { background: rgba(255,255,255,.14); border-radius: 12px; padding: 10px 12px; }
.w-balance .split small, .w-balance .split span { display: block; font-size: 11px; opacity: .85; }
.w-balance .split b { display: block; font-size: 18px; margin: 2px 0; }
.w-balance .expiry { margin: 12px 0 0; font-size: 12px; opacity: .9; }

.w-card { background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 16px; }
.w-card h3 { margin: 0 0 12px; font-size: 16px; color: #241f1b; }
.hint { font-size: 12px; color: #8b8379; margin: 8px 0 0; }
.gain { font-size: 13px; color: #7c571d; margin: 8px 0 0; }
.row { display: flex; gap: 8px; }
.row input, .wd-form input { flex: 1; min-width: 0; padding: 10px 12px; border: 1px solid #e9e0d3; border-radius: 10px; font-size: 14px; background: #fff; }
.btn-org:disabled { opacity: .55; cursor: not-allowed; }

.presets { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
.presets button, .filters button {
  padding: 8px 14px; border-radius: 999px; border: 1px solid #e9e0d3; background: #fff;
  font-size: 13px; color: #241f1b; cursor: pointer; transition: .2s;
}
.presets button.on, .filters button.on { border-color: #1C9384; background: #fff8e9; color: #7c571d; }

.offers { list-style: none; padding: 0; margin: 12px 0 0; display: grid; gap: 8px; }
.offers li { border: 1px dashed #e0c79c; border-radius: 12px; padding: 10px 12px; display: grid; gap: 2px; font-size: 13px; }
.offers li.on { border-style: solid; border-color: #1C9384; background: #fff8e9; }
.offers small { color: #8b8379; font-size: 11px; }

.filters { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; margin-bottom: 8px; scrollbar-width: none; }
.filters button { white-space: nowrap; }
.history { list-style: none; padding: 0; margin: 0; }
.history li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3ede4; }
.history li:last-child { border-bottom: 0; }
.history b { display: block; font-size: 14px; color: #241f1b; }
.history small { display: block; font-size: 11px; color: #8b8379; margin-top: 2px; }
.amt { text-align: end; white-space: nowrap; }
.amt.credit b { color: #2f6b2a; }
.amt.debit b { color: #a33; }
.empty { text-align: center; color: #8b8379; font-size: 13px; margin: 12px 0; }
.more { width: 100%; margin-top: 8px; padding: 10px; border-radius: 10px; border: 1px solid #e9e0d3; background: #fff; cursor: pointer; color: #7c571d; }

.wd-form { display: grid; grid-template-columns: 1fr 2fr 1.5fr auto; gap: 8px; margin-top: 10px; }
.wd-list { margin-top: 12px; }
.status { font-size: 12px; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
.st-wait { background: #fff8e9; color: #7c571d; }
.st-ok { background: #f0f8ee; color: #2f6b2a; }
.st-bad { background: #fff6f6; color: #a33; }

.policy { font-size: 12px; color: #8b8379; margin: 0; line-height: 1.7; }

@media (max-width: 640px) {
  .w-balance .total b { font-size: 26px; }
  .wd-form { grid-template-columns: 1fr; }
}
</style>
