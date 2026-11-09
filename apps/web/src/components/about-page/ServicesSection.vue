<script setup>
import { localizeRecord } from '@/utils/i18nField'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from './AppIcon.vue'
import SectionTitle from './SectionTitle.vue'
import { useLanguage } from '@/composables/useLanguage'

const props = defineProps({
  categories: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const router = useRouter()
const { state: language } = useLanguage()

function nameOf(category) {
  return localizeRecord(category, 'name', language.lang)
}

function iconOf(category) {
  const name = String(nameOf(category)).toLowerCase()
  if (name.includes('حلاق') || name.includes('hair')) return 'scissors'
  if (name.includes('مساج') || name.includes('massage')) return 'spa'
  if (name.includes('حمام') || name.includes('bath')) return 'bath'
  if (name.includes('منزل') || name.includes('home')) return 'home'
  return 'star'
}

const visibleCategories = computed(() => props.categories.slice(0, 6))
</script>

<template>
  <section>
    <SectionTitle title="خدماتنا" />

    <div v-if="loading" class="services reveal is-visible" aria-label="جاري تحميل الخدمات">
      <article v-for="n in 6" :key="n" class="service-card service-card--loading">
        <span class="service-card__icon" />
        <i />
        <small />
      </article>
    </div>

    <p v-else-if="error" class="services-message">{{ error }}</p>

    <div v-else class="services reveal is-visible">
      <article
        v-for="category in visibleCategories"
        :key="category.id"
        class="service-card service-card--link"
        tabindex="0"
        @click="router.push(`/services/${category.id}`)"
        @keydown.enter="router.push(`/services/${category.id}`)"
      >
        <span class="service-card__icon"><AppIcon :name="iconOf(category)" :size="21" /></span>
        <b>{{ nameOf(category) }}</b>
        <small>{{ (category.services || []).length }} خدمة</small>
      </article>
    </div>
  </section>
</template>
