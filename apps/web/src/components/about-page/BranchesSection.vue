<script setup>
import AppIcon from './AppIcon.vue'
import SectionTitle from './SectionTitle.vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { branches } = useAboutContent()
</script>

<template>
  <section id="about-branches">
    <SectionTitle title="فروعنا في جدة" />
    <div class="branches reveal">
      <article v-for="branch in branches" :key="branch.name" class="branch-card">
        <div class="branch-card__image"><img :src="branch.image" :alt="branch.name" loading="lazy" /><b>{{ branch.name }}</b></div>
        <div class="branch-card__body"><small><AppIcon name="map" :size="13" />{{ branch.address }}</small><a :href="branch.map" target="_blank" rel="noopener">فتح في الخرائط</a><a v-if="branch.phone" :href="`tel:${branch.phone}`">اتصل بالفرع · <span dir="ltr">{{ branch.phone }}</span></a></div>
      </article>
    </div>
  </section>
</template>
