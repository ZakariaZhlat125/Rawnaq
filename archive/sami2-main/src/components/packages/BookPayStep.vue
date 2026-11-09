<script setup>
/*
  الدفع — مُرحَّل حرفيًا من bkView3() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { BRANCHES, BK_PAYS } from '@/data/packages'
import { usePackages, fmtTime, fmtDate, rs } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state, pkgOf, bkDays } = usePackages()
const emit = defineEmits(['pay'])

const B = state.bk
const p = computed(() => pkgOf(B.pkg))
const br = computed(() => BRANCHES.find(b => b.id === B.branch))
const d = computed(() => bkDays()[B.dayIdx])

const LOCK = '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'
const SECURE = 'display:flex;gap:8px;align-items:center;justify-content:center;margin-top:14px;font-size:12px;color:var(--mute)'
</script>

<template>
  <div class="g-head"><h1>اختر طريقة الدفع</h1><p>ادفع بأمان وسهولة</p></div>
  <div class="card pay-summary-strip">
    <b>{{ p.name }}</b>
    <small>{{ br.name }} — {{ fmtDate(d) }} • {{ fmtTime(B.time) }}</small>
    <span class="amt">{{ rs(p.price) }} ر.س</span>
  </div>
  <div class="card" style="padding:20px">
    <div class="bk-pays">
      <div v-for="m in BK_PAYS" :key="m.id" class="bkpm" :class="{ sel: B.pay === m.id }" :data-bkpay="m.id" @click="B.pay = m.id">
        <span class="lg">{{ m.logo }}</span><b>{{ m.n }}</b><span class="rad"><i></i></span>
      </div>
    </div>
    <button class="btn-paynow" id="bkPayNow" :disabled="!B.pay" @click="emit('pay')"><SIcon :inner="LOCK" :size="16" /> ادفع الآن — {{ rs(p.price) }} ر.س</button>
    <div :style="SECURE">🌐 جميع المعاملات مؤمّنة وآمنة</div>
  </div>
</template>
