<script setup>
/* بطاقة هدية بقيمة: يختار المُهدي المبلغ، والمستلم يضيفه لمحفظته ويصرفه على الخدمات والمتجر */
import { computed } from 'vue'
import { useGifts } from '@/composables/useGifts'
import { useGiftDesigns } from '@/composables/useGiftDesigns'
import SIcon from '@/components/common/SIcon.vue'

const { state, canNext } = useGifts()
const { designs } = useGiftDesigns()
const emit = defineEmits(['nav'])

const limits = computed(() => designs.valueCard)
const months = computed(() => Math.round((limits.value.validity_days || 180) / 30))
const rs = n => Number(n || 0).toLocaleString('ar-EG-u-nu-latn')
const outOfRange = computed(() => state.value !== null && state.value !== '' && !canNext.value)

const I = {
  prev: '<path d="M5 12h14M13 6l6 6-6 6"/>',
  next: '<path d="M19 12H5M11 18l-6-6 6-6"/>',
}
</script>

<template>
  <div class="g-head"><h1>اختر قيمة بطاقة الهدية</h1><p>يضيفها المستلم إلى محفظته ويستخدمها في الخدمات والمتجر</p></div>
  <div class="card gv-card">
    <div class="gv-presets">
      <button
        v-for="p in limits.presets" :key="p" type="button"
        :class="{ sel: Number(state.value) === p }" @click="state.value = p"
      ><b>{{ rs(p) }}</b> ر.س</button>
    </div>
    <div class="fld">
      <label for="gv-amount">أو أدخل مبلغاً آخر</label>
      <input id="gv-amount" v-model.number="state.value" type="number" inputmode="decimal" :min="limits.min" :max="limits.max" step="1" placeholder="المبلغ" />
      <small :class="{ err: outOfRange }">المبلغ بين {{ rs(limits.min) }} و {{ rs(limits.max) }} ر.س · صالحة لمدة {{ months }} أشهر</small>
    </div>
    <ul class="gv-notes">
      <li>تصل للمستلم مع كود ورابط خاص</li>
      <li>تُضاف لمحفظة المستلم كرصيد ترويجي</li>
      <li>لا تُستبدل نقداً</li>
    </ul>
    <div class="inline-actions">
      <button class="btn btn-prev" @click="emit('nav', -1)"><SIcon :inner="I.prev" :size="15" /> رجوع</button>
      <button class="btn btn-gold" :disabled="!canNext" @click="emit('nav', 1)">متابعة <SIcon :inner="I.next" :size="15" /></button>
    </div>
  </div>
</template>

<style scoped>
.gv-card { padding: 20px; }
.gv-presets { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
.gv-presets button { padding: 16px 8px; border-radius: 14px; border: 1px solid #e9e0d3; background: #fff; cursor: pointer; font-size: 13px; color: #6f665c; transition: .2s; }
.gv-presets button b { display: block; font-size: 20px; color: #241f1b; }
.gv-presets button.sel { border-color: #1C9384; background: #fff8e9; box-shadow: 0 0 0 2px rgba(201,139,49,.25); }
.fld small { display: block; margin-top: 6px; color: #8b8379; font-size: 12px; }
.fld small.err { color: #a33; }
.gv-notes { margin: 16px 0; padding: 0; list-style: none; display: grid; gap: 6px; font-size: 13px; color: #6f665c; }
.gv-notes li::before { content: '✦'; color: #1C9384; margin-inline-end: 8px; }
@media (max-width: 640px) { .gv-presets { grid-template-columns: repeat(2, 1fr); } }
</style>
