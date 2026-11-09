<script setup>
import BaseButton from '@/components/common/BaseButton.vue'
import { useHomeContent } from '@/composables/useHomeContent'
const props = defineProps({ content: Object })
const { text } = useHomeContent(props)
</script>
<template><section id="home" class="hero-section"><div class="hero-person" aria-hidden="true"></div><div class="hero-box" data-no-i18n><p class="hero-brand">{{ text('hero_brand') }}</p><p class="eyebrow">{{ text('hero_eyebrow') }}</p><h1 style="white-space:pre-line">{{ text('hero_title') }}</h1><p class="hero-points"><span v-for="i in 3" :key="i">{{ text('hero_point_'+(i-1)) }}</span></p><BaseButton :label="text('hero_cta')" href="/booking" /></div></section></template>
