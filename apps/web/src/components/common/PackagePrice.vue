<script setup>
import { useLanguage } from '@/composables/useLanguage'
defineProps({ price: [Number, String], savings: [Number, String], original: [Number, String] })
const { state: language } = useLanguage()
const money = value => new Intl.NumberFormat(language.lang === 'en' ? 'en-SA' : 'ar-SA', { style: 'currency', currency: 'SAR' }).format(Number(value) || 0)
</script>
<template>
  <div class="package-price" data-no-i18n>
    <del v-if="Number(savings) > 0 && Number(original) > Number(price)">{{ money(original) }}</del>
    <strong>{{ money(price) }}</strong>
    <p v-if="Number(savings) > 0" class="package-savings">{{ language.lang === 'en' ? 'You save' : 'ستوفر مبلغ' }} {{ money(savings) }}</p>
  </div>
</template>
<style scoped>
.package-price{margin:12px 0;line-height:1.8}.package-price strong{font-size:20px}.package-price del{display:block;color:#777;font-size:13px}.package-savings{margin:4px 0;color:#26714d;font-weight:700;font-size:14px}
</style>
