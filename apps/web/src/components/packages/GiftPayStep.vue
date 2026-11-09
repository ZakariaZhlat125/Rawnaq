<script setup>
import { computed } from 'vue'
import { usePackages } from '@/composables/usePackages'
import CheckoutPaymentOptions from '@/components/common/CheckoutPaymentOptions.vue'
const { state, pkgOf } = usePackages()
const total = computed(() => { const subtotal = Math.max(Number(pkgOf(state.gpkg)?.price) || 0, 0); return subtotal + Math.round(subtotal * 0.15) })
</script>
<template>
  <div class="g-head"><h1>أكمل الدفع لإرسال هديتك</h1><p>اختر طريقة الدفع المناسبة</p></div>
  <div class="card" style="padding:22px;margin-bottom:16px"><CheckoutPaymentOptions :state="state" :total="total" /></div>
</template>
