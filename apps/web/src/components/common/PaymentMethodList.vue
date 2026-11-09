<script setup>
defineProps({ methods: { type: Array, required: true }, modelValue: { type: String, default: null } })
defineEmits(['update:modelValue'])
</script>
<template>
    <div class="payment-options">
      <button v-for="m in methods" :key="m.id" type="button" class="payment-option" :class="{ selected: modelValue === m.id }" :disabled="!m.enabled" :aria-pressed="modelValue === m.id" @click="$emit('update:modelValue', m.id)">
        <span class="payment-logo">{{ m.logo }}</span>
        <span class="payment-copy"><b>{{ m.n }}</b><small>{{ m.d }}</small></span>
        <span class="payment-radio" aria-hidden="true">{{ modelValue === m.id ? '●' : '○' }}</span>
      </button>
    </div>
</template>
<style scoped>
.checkout-payment-options h4{margin:0 0 14px;font-size:16px}.payment-options{display:grid;gap:10px}.payment-option{display:flex;align-items:center;gap:12px;width:100%;padding:15px;border:1px solid #e4d7c7;border-radius:14px;background:#fff;color:#2a2519;text-align:start;font:inherit;cursor:pointer}.payment-option.selected{border-color:#1F9E8F;background:#fff8ea}.payment-option:disabled{opacity:.45;cursor:not-allowed}.payment-logo{min-width:54px;font-weight:800;text-align:center}.payment-copy{display:grid;gap:4px}.payment-copy small,.checkout-rewards small{font-size:12px;color:#7d745f}.payment-radio{margin-inline-start:auto;color:#1F9E8F}.checkout-rewards{margin-top:18px;padding:16px;background:#fffaf0;border:1px solid #e4d7c7;border-radius:14px}.checkout-rewards label{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0}.checkout-rewards label span{display:grid;gap:4px}.checkout-rewards input[type=number]{width:100%;padding:10px;border:1px solid #e4d7c7;border-radius:10px;font:inherit}.payment-total{display:flex;justify-content:space-between;gap:12px;margin-top:16px;padding-top:12px;border-top:1px dashed #1F9E8F}
</style>
