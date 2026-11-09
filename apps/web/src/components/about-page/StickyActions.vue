<script setup>
import AppIcon from './AppIcon.vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { contact } = useAboutContent()
</script>

<template>
  <nav class="sticky-actions" aria-label="إجراءات الحجز">
    <a class="button button--gold" :href="contact.website" target="_blank" rel="noopener"><AppIcon name="calendar" :size="16" />احجز موعدك الآن</a>
    <a v-if="contact.whatsapp" class="button button--whatsapp" :href="`https://wa.me/${contact.whatsapp}`" target="_blank" rel="noopener"><AppIcon name="whatsapp" :size="16" />واتساب</a>
  </nav>
</template>
