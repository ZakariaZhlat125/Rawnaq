<script setup>
import SectionTitle from './SectionTitle.vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { page } = useAboutContent()
</script>

<template>
  <section>
    <SectionTitle title="نبذة عن المركز" />
    <div class="card about reveal">
      <p style="white-space:pre-line">{{ page.description }}</p>
      <div class="stats"><div v-for="stat in page.stats" :key="stat.label"><b>{{ stat.value }}</b><small>{{ stat.label }}</small></div></div>
    </div>
  </section>
</template>
