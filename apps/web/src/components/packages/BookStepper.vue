<script setup>
/*
  شريط خطوات حجز الباقة — مُرحَّل حرفيًا من bkStepper() في src/legacy/packages-gifts.html
*/
import { BK_STEPS } from '@/data/packages'
import { usePackages } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state } = usePackages()
const CHECK = '<path d="M20 6L9 17l-5-5"/>'
</script>

<template>
  <div class="g-stepper">
    <div class="g-steps">
      <div v-for="(st, i) in BK_STEPS" :key="i" class="g-step"
           :class="{ active: i === state.bk.step && !state.bk.done, done: i < state.bk.step || state.bk.done }" :data-bi="i">
        <div class="bar"><i></i></div>
        <span class="bubble">
          <SIcon v-if="i < state.bk.step || state.bk.done" :inner="CHECK" :size="17" />
          <template v-else>{{ i + 1 }}</template>
        </span>
        <b>{{ st.t }}</b><small>{{ st.s }}</small>
      </div>
    </div>
  </div>
</template>
