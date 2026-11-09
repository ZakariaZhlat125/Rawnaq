<script setup>
/*
  شريط خطوات الحجز — مُرحَّل حرفيًا من renderStepper() في src/legacy/booking.html
*/
import { STEPS } from '@/data/booking'
import { useBooking } from '@/composables/useBooking'

const { state } = useBooking()

/* الرجوع لخطوة مكتملة بالنقر على أيقونتها — كما في الأصل */
function goto(i) {
  if (i < state.step) state.step = i
}
</script>

<template>
  <div class="wrap stepper" v-show="!state.done" id="stepperWrap">
    <div class="steps" id="steps">
      <div v-for="(st, i) in STEPS" :key="i" class="step"
           :class="{ active: i === state.step, done: i < state.step }" :data-i="i">
        <div class="bar"><i></i></div>
        <span class="n">{{ i + 1 }}</span>
        <span class="bubble" @click="goto(i)"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" v-html="st.ic"></svg></span>
        <label>{{ st.t }}</label>
      </div>
    </div>
  </div>
</template>
