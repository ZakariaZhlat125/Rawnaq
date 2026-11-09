<script setup>
/*
  شريط خطوات الإهداء — مُرحَّل حرفيًا من stepper() في src/legacy/gifts.html
*/
import { GSTEPS } from '@/data/gifts'
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'

const { state } = useGifts()
const CHECK = '<path d="M20 6L9 17l-5-5"/>'
</script>

<template>
  <div class="g-stepper">
    <div class="g-steps">
      <div v-for="(st, i) in GSTEPS" :key="i" class="g-step"
           :class="{ active: i === state.step && !state.done, done: i < state.step || state.done }" :data-i="i">
        <div class="bar"><i></i></div>
        <span class="bubble">
          <SIcon v-if="i < state.step || state.done" :inner="CHECK" :size="16" />
          <template v-else>{{ i + 1 }}</template>
        </span>
        <b>{{ st.t }}</b><small>{{ st.s }}</small>
      </div>
    </div>
    <div class="step-count">الخطوة {{ Math.min(state.step + 1, 5) }} من 5</div>
  </div>
</template>
