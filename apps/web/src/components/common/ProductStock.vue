<script setup>
import { computed } from 'vue'
const props = defineProps({ quantity: { type: [Number, String], default: null } })
const stock = computed(() => props.quantity !== null && props.quantity !== '' && Number.isFinite(Number(props.quantity)) ? Math.max(0, Math.floor(Number(props.quantity))) : null)
</script>

<template>
  <p class="product-stock" :class="{ 'product-stock--empty': stock === 0 }">
    {{ stock === null ? 'المخزون غير متاح' : stock === 0 ? 'نفد المخزون' : `المتوفر بالمخزون: ${stock}` }}
  </p>
</template>

<style scoped>
.product-stock{display:block;margin:6px 0;font-size:12px;line-height:1.6;color:#36704c;font-weight:600}
.product-stock--empty{color:#b42318}
</style>
