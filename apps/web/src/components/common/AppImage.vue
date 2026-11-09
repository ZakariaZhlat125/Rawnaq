<script setup>
import { ref } from 'vue'
defineProps({ src: String, alt: String })
const failed = ref(false)
</script>
<template><div class="image-frame"><img v-if="!failed" :src="src" :alt="alt" loading="lazy" @error="failed=true" /><div v-else class="image-placeholder"><span>أضيفي الصورة هنا</span><small>{{ src }}</small></div></div></template>
