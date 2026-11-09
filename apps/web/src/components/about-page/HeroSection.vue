<script setup>
import { useAboutContent } from '@/composables/useAboutContent'
const { page } = useAboutContent()
defineProps({ logo: { type: String, required: true } })
</script>

<template>
  <header class="hero">
    <div class="hero__frame" aria-hidden="true" />
    <span class="hero__particle p1">✦</span><span class="hero__particle p2">❖</span>
    <span class="hero__particle p3">✧</span><span class="hero__particle p4">✦</span>
    <div class="hero__glow" /><div class="hero__ring" />
    <img class="hero__logo" :src="logo" alt="شعار رونق" />
    <h1>{{ page.title }}</h1>
    <p class="hero__en">{{ page.english_title }}</p>
    <p class="hero__tagline">{{ page.tagline }}</p>
    <span v-if="page.location_text" class="hero__verified"><i /> {{ page.location_text }}</span>
  </header>
</template>
