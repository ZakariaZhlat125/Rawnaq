<script setup>
defineProps({ label: String, href: { type: String, default: '#' }, variant: { type: String, default: 'gold' } })
</script>
<template><a :href="href" class="button" :class="`button--${variant}`">{{ label }} <span>←</span></a></template>
