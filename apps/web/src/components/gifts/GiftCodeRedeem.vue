<script setup>
/*
  "لديك كود هدية؟" — يُستخدم في حسابي وفي صفحة الدفع.
  بطاقة القيمة تُضاف لرصيد المحفظة، وهدية الخدمة/الباقة تُضاف لهداياي المستلمة.
*/
import { ref } from 'vue'
import { redeemGiftCode } from '@/services/giftsApi'

const props = defineProps({ compact: { type: Boolean, default: false } })
const emit = defineEmits(['redeemed'])

const open = ref(!props.compact)
const code = ref('')
const busy = ref(false)
const result = ref(null) // { ok, text }

async function submit() {
  if (!code.value.trim()) return
  busy.value = true
  result.value = null
  try {
    const res = await redeemGiftCode(code.value.trim())
    result.value = { ok: true, text: res.message }
    code.value = ''
    emit('redeemed', res.data)
  } catch (e) {
    result.value = { ok: false, text: e.message }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="gcr">
    <button v-if="compact && !open" type="button" class="gcr-toggle" @click="open = true">🎁 لديك كود هدية؟</button>
    <form v-else class="gcr-form" @submit.prevent="submit">
      <label for="gcr-code">لديك كود هدية؟</label>
      <div class="gcr-row">
        <input
          id="gcr-code" v-model="code" dir="ltr" autocomplete="off" maxlength="20"
          placeholder="SAMI-XXXX-XXXX" aria-label="كود الهدية"
          @input="code = code.toUpperCase()"
        />
        <button class="btn-org" type="submit" :disabled="busy || !code.trim()">{{ busy ? '…' : 'استخدام' }}</button>
      </div>
      <p v-if="result" class="gcr-msg" :class="result.ok ? 'ok' : 'err'" role="status">{{ result.text }}</p>
    </form>
  </div>
</template>

<style scoped>
.gcr-toggle { background: none; border: 0; color: #7c571d; font-size: 13px; cursor: pointer; padding: 4px 0; text-decoration: underline; }
.gcr-form label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #241f1b; }
.gcr-row { display: flex; gap: 8px; }
.gcr-row input { flex: 1; min-width: 0; padding: 10px 12px; border: 1px solid #e9e0d3; border-radius: 10px; font-size: 14px; letter-spacing: 1px; background: #fff; }
.btn-org:disabled { opacity: .55; cursor: not-allowed; }
.gcr-msg { font-size: 12.5px; margin: 8px 0 0; }
.gcr-msg.ok { color: #2f6b2a; }
.gcr-msg.err { color: #a33; }
</style>
