<script setup>
import AppIcon from './AppIcon.vue'
import { computed } from 'vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { contact, openBranchDialog } = useAboutContent()

const actions = computed(() => [
  ...(contact.value.whatsapp ? [{ label: 'واتساب', icon: 'whatsapp', href: `https://wa.me/${contact.value.whatsapp}`, green: true }] : []),
  { label: 'الفروع', icon: 'map', href: '#about-branches' },
  { label: 'احجز الآن', icon: 'calendar', href: '/booking' },
])
</script>

<template>
  <nav class="quick-actions" aria-label="روابط سريعة">
    <button type="button" class="quick-action" @click="openBranchDialog"><span class="quick-action__icon"><AppIcon name="phone" :size="19" /></span><b>اتصال</b></button>
    <a v-for="action in actions" :key="action.label" class="quick-action" :class="{ 'quick-action--green': action.green }" :href="action.href" :target="action.href.startsWith('http') ? '_blank' : undefined" rel="noopener">
      <span class="quick-action__icon"><AppIcon :name="action.icon" :size="19" /></span><b>{{ action.label }}</b>
    </a>
  </nav>
</template>
