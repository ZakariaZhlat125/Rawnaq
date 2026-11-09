<script setup>
import { useFavorites } from '@/composables/useFavorites'
defineProps({ type: String, id: [String, Number] })
const { has,toggle,pending,itemErrors,loading } = useFavorites()
</script>
<template><span class="favorite-control"><button type="button" :disabled="loading || pending.has(`${type}:${id}`)" :aria-pressed="has(type,id)" :aria-label="has(type,id) ? 'إزالة من المفضلة' : 'إضافة للمفضلة'" @click.stop.prevent="toggle(type,id)">{{ has(type,id) ? '♥' : '♡' }}</button><small v-if="itemErrors[`${type}:${id}`]" role="alert">{{ itemErrors[`${type}:${id}`] }}</small></span></template>
<style scoped>.favorite-control{display:inline-flex;align-items:center;gap:6px}.favorite-control button{min-width:44px;min-height:44px;border:1px solid #e9e0d3;border-radius:50%;background:white;color:#a56c22;font-size:26px;cursor:pointer}.favorite-control small{color:#b42318;font-size:12px}</style>
