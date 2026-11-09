<script setup>
/*
  اختيار نوع الهدية — مُرحَّل حرفيًا من view0() في src/legacy/gifts.html
*/
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'

const { state } = useGifts()
const emit = defineEmits(['pick'])

const I = {
  chair: '<path d="M3 18v-6a2 2 0 012-2h14a2 2 0 012 2v6"/><path d="M5 18v2M19 18v2M7 10V7a2 2 0 012-2h6a2 2 0 012 2v3"/>',
  gift:  '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
  arrow: '<path d="M19 12H5M11 18l-6-6 6-6"/>',
}
</script>

<template>
  <div class="g-head"><h1>ماذا ترغب بإهدائه؟</h1><p>اختر ما يناسبك لإهداء تجربة مميزة لمن تحب</p></div>
  <div class="gtype-grid">
    <div class="gtype" :class="{ sel: state.gtype === 'svc' }" data-gt="svc" @click="emit('pick', 'svc')">
      <div class="im"><img src="/images/gifts/service.jpg" alt="إهداء خدمة" />
        <span class="ic"><SIcon :inner="I.chair" :size="20" /></span></div>
      <div class="bd"><h3>إهداء خدمة</h3><p>اختر خدمة واحدة أو أكثر<br />وأهدِها لمن تحب.</p>
        <span class="go"><SIcon :inner="I.arrow" :size="14" /> اختر خدمة لإهدائها</span></div>
    </div>
    <div class="gtype" :class="{ sel: state.gtype === 'pkg' }" data-gt="pkg" @click="emit('pick', 'pkg')">
      <div class="im"><img src="/images/gifts/package.jpg" alt="إهداء باقة" />
        <span class="ic"><SIcon :inner="I.gift" :size="20" /></span></div>
      <div class="bd"><h3>إهداء باقة</h3><p>اختر باقة مصممة بعناية<br />لتمنح تجربة متكاملة لمن تحب.</p>
        <span class="go"><SIcon :inner="I.arrow" :size="14" /> اختر باقة لإهدائها</span></div>
    </div>
  </div>
</template>
