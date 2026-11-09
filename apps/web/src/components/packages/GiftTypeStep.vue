<script setup>
/*
  اختيار نوع الإهداء — مُرحَّل حرفيًا من gView0() في src/legacy/packages-gifts.html
*/
import { GTYPES } from '@/data/packages'
import { usePackages } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state } = usePackages()
const ARROW = '<path d="M19 12H5M11 18l-6-6 6-6"/>'
</script>

<template>
  <div class="g-head"><h1>ماذا ترغب بإهدائه؟</h1><p>اختر ما يناسبك لإهداء تجربة مميزة لمن تحب</p></div>
  <div class="gtype-grid">
    <div v-for="t in GTYPES" :key="t.id" class="gtype" :class="{ sel: state.gtype === t.id }" :data-gt="t.id" @click="state.gtype = t.id">
      <div class="im"><img :src="t.img" alt="" /><span class="ic"><SIcon :inner="t.ic" :size="22" /></span></div>
      <div class="bd"><h3>{{ t.n }}</h3><p>{{ t.d }}</p><span class="go">{{ t.btn }} <SIcon :inner="ARROW" :size="14" /></span></div>
    </div>
  </div>
</template>
