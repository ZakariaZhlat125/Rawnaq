<script setup>
import { onMounted, ref, watch } from 'vue'
import { useLanguage } from '@/composables/useLanguage'
import { localizeRecord } from '@/utils/i18nField'
const { state: language } = useLanguage()
const field = (item, key) => localizeRecord(item, key, language.lang)
let pageRequest = 0
import { usePageStyles } from '@/composables/usePageStyles'
import { authFetch } from '@/services/apiClient'
import pageCss from '@/assets/styles/pages/legal.css?raw'

usePageStyles(pageCss, 'cafe')

const page = ref({ title: 'المقهى', subtitle: '', sections: [] })
const loading = ref(true)
const error = ref('')

async function loadPage() {
  const request = ++pageRequest
  loading.value = true
  error.value = ''
  try {
    const result = await authFetch('/cafe-page')
    if (request === pageRequest) page.value = result.data
  } catch {
    if (request !== pageRequest) return
    error.value = 'تعذر تحميل تفاصيل المقهى. حاول مرة أخرى.'
  } finally {
    if (request === pageRequest) loading.value = false
  }
}

onMounted(loadPage)
watch(() => language.lang, loadPage)
</script>

<template>
  <div class="shell legal-page">
    <div class="wrap">
      <div class="page-head">
        <h1>{{ field(page, 'title') }}</h1>
        <p v-if="page.subtitle">{{ field(page, 'subtitle') }}</p>
      </div>
      <div class="legal-content" :aria-busy="loading">
        <p v-if="loading" role="status">جارٍ تحميل المحتوى...</p>
        <div v-else-if="error" role="alert">
          <p>{{ error }}</p>
          <button type="button" class="retry-button" @click="loadPage">إعادة المحاولة</button>
        </div>
        <template v-else>
          <section v-for="(section, index) in page.sections" :key="index">
            <h2><i aria-hidden="true"></i> {{ field(section, 'heading') }}</h2>
            <p class="section-body">{{ field(section, 'body') }}</p>
          </section>
          <p v-if="!page.sections.length">سيتم إضافة تفاصيل المقهى قريبًا.</p>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.section-body { white-space: pre-line; overflow-wrap: anywhere; }
.retry-button { color: var(--gold-deep); text-decoration: underline; }
</style>
