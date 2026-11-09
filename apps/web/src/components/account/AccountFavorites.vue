<script setup>
import { ref,computed } from 'vue'
import { useFavorites } from '@/composables/useFavorites'
import FavoriteButton from '@/components/common/FavoriteButton.vue'
const { items,error,loading,load } = useFavorites()
const filter=ref('all')
const types={all:'الكل',service:'الخدمات',package:'الباقات',product:'المنتجات'}
const visible=computed(()=>items.value.filter(x=>filter.value==='all'||x.type===filter.value))
const link=x=>x.type==='service' ? (x.category_id ? `/services/${x.category_id}` : '/services') : x.type==='package' ? '/packages-gifts' : '/store'
</script>
<template><section><h2>المفضلة</h2><div class="filters"><button v-for="(label,key) in types" :key="key" :aria-pressed="filter===key" @click="filter=key">{{ label }}</button></div><p v-if="loading">جارٍ تحميل المفضلة…</p><p v-else-if="error" role="alert">{{ error }} <button @click="load">إعادة المحاولة</button></p><p v-else-if="!visible.length">لا توجد عناصر في المفضلة.</p><div class="favorites"><article v-for="x in visible" :key="`${x.type}:${x.item_id}`"><FavoriteButton :type="x.type" :id="x.item_id"/><b>{{ x.name }}</b><small>{{ types[x.type] }}</small><router-link :to="link(x)">عرض {{ types[x.type] }}</router-link></article></div></section></template>
<style scoped>.filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}.filters button{padding:10px 18px;border:1px solid #e9e0d3;background:white;border-radius:24px;cursor:pointer}.filters button[aria-pressed=true]{background:#f6e7c8}.favorites{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}.favorites article{padding:18px;border:1px solid #e9e0d3;border-radius:14px;display:flex;flex-direction:column;gap:12px;background:white}</style>
