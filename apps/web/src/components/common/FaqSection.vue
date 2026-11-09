<script setup>
import { useLanguage } from '@/composables/useLanguage'
import { localizeField } from '@/utils/i18nField'
defineProps({ items: { type: Array, default: () => [] } })
const { state: language } = useLanguage()
</script>
<template>
  <section v-if="items.length" class="sami-faq-section" :dir="language.lang === 'en' ? 'ltr' : 'rtl'">
    <h2>{{ language.lang === 'en' ? 'Frequently asked questions' : 'الأسئلة الشائعة' }}</h2>
    <details v-for="(item, index) in items" :key="index">
      <summary>{{ localizeField(item.question, language.lang) }}</summary>
      <p>{{ localizeField(item.answer, language.lang) }}</p>
    </details>
  </section>
</template>
<style scoped>
.sami-faq-section{max-width:1120px;margin:40px auto;padding:0 24px;color:#29221b;text-align:start}
.sami-faq-section h2{font-size:clamp(22px,3vw,32px);margin-bottom:20px}
.sami-faq-section details{background:#fff;border:1px solid #e9e0d3;border-radius:14px;margin:12px 0;padding:0 20px}
.sami-faq-section summary{cursor:pointer;font-weight:700;padding:18px 0;line-height:1.7;overflow-wrap:anywhere}
.sami-faq-section p{white-space:pre-line;line-height:1.9;margin:0;padding:0 0 20px;overflow-wrap:anywhere}
@media(max-width:600px){.sami-faq-section{padding:0 16px;margin:28px auto}.sami-faq-section details{padding:0 14px}}
</style>
