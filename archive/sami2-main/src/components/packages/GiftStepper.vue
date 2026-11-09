<script setup>
/*
  شريط خطوات إهداء الباقة — مُرحَّل حرفيًا من gStepper() في src/legacy/packages-gifts.html
*/
import { GSTEPS } from '@/data/packages'
import { usePackages } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state } = usePackages()
const CHECK = '<path d="M20 6L9 17l-5-5"/>'
</script>

<template>
  <div class="g-stepper">
    <div class="g-steps">
      <div v-for="(st, i) in GSTEPS" :key="i" class="g-step"
           :class="{ active: i === state.gstep, done: i < state.gstep || state.done }" :data-i="i">
        <div class="bar"><i></i></div>
        <span class="bubble">
          <SIcon v-if="i < state.gstep || state.done" :inner="CHECK" :size="17" />
          <template v-else>{{ i + 1 }}</template>
        </span>
        <b>{{ st.t }}</b><small>{{ st.s }}</small>
      </div>
    </div>
  </div>
</template>
