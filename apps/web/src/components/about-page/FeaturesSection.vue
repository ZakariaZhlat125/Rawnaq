<script setup>
import AppIcon from './AppIcon.vue'
import SectionTitle from './SectionTitle.vue'
import { computed } from 'vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { page } = useAboutContent()
const features = computed(() => page.value.features || [])
</script>

<template>
  <section>
    <SectionTitle title="مزايا إضافية" />
    <div class="features reveal"><article v-for="feature in features" :key="feature.name"><span><AppIcon :name="feature.icon" :size="18" /></span><div><b>{{ feature.name }}</b><small>{{ feature.detail }}</small></div></article></div>
  </section>
</template>
