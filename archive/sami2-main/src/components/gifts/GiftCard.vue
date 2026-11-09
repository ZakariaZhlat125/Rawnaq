<script setup>
/*
  بطاقة الإهداء (المعاينة) — مُرحَّلة حرفيًا من giftCardHTML()
  مشتركة بين صفحة الإهداء وصفحة الباقات (الدالة متطابقة في الملفين).

  يستقبل معرّف التصميم كخاصية بدل قراءته من حالة صفحة بعينها،
  ليعمل مع أي من الصفحتين.
*/
import { computed } from 'vue'
import { DESIGNS } from '@/data/gifts'

const props = defineProps({
  design: { type: String, default: 'lux-dark' },
})

const d = computed(() => DESIGNS.find(x => x.id === props.design) || DESIGNS[0])
</script>

<template>
  <div class="gcard royal" :style="`background:${d.bg};color:${d.fg}`">
    <div class="royal-frame"></div>
    <div class="royal-glow"></div>
    <svg class="bow" viewBox="0 0 64 40" fill="none" stroke="currentColor" stroke-width="2"><path d="M32 30C18 30 10 24 12 15s16-5 20 8c4-13 18-17 20-8s-6 15-20 15z"/><path d="M28 30h8v8h-8z"/></svg>
    <div class="leaves">
      <svg style="bottom:8px;right:10px" width="78" height="78" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".85"><path d="M4 20C6 12 12 6 20 4c-2 8-8 14-16 16zM4 20c4-2 8-6 10-10"/></svg>
      <svg style="top:14px;left:10px;transform:rotate(180deg)" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".85"><path d="M4 20C6 12 12 6 20 4c-2 8-8 14-16 16z"/></svg>
    </div>
    <div class="royal-seal">{{ d.icon || '✦' }}</div>
    <div class="lg"><img src="/logo.png" style="height:24px;vertical-align:middle" alt="" /> SAMI <small>عناية سامي</small></div>
    <h3>{{ d.n }}</h3>
    <div class="tg">هدية فاخرة بتجربة عناية ملكية</div>
    <div class="hr" :style="`background:${d.fg}`"></div>
  </div>
</template>
