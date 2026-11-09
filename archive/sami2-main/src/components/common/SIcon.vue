<script setup>
/*
  أيقونة SVG — جذر المكوّن هو <svg> نفسه، فلا يُضاف أي عنصر غلاف.
  تُنتج نفس ناتج الدالة svg(path,size) في الصفحة الأصلية حرفيًا.
*/
defineProps({
  inner: { type: String, required: true },
  size: { type: [Number, String], default: 17 },
})
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="1.8" v-html="inner"></svg>
</template>
