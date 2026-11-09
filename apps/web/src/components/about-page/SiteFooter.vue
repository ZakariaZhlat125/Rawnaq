<script setup>
import AppIcon from './AppIcon.vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { page } = useAboutContent()
defineProps({ logo: { type: String, required: true } })
</script>

<template>
  <footer class="footer">
    <img :src="logo" alt="رونق" />
    <small>{{ page.title }}<template v-if="page.english_title && page.title !== page.english_title"> — {{ page.english_title }}</template><br />{{ page.location_text }} • <span>جميع الحقوق محفوظة</span> © {{ new Date().getFullYear() }}</small>
    <span><AppIcon name="calendar" :size="14" /> وصلتَ إلينا عبر مسح رمز QR — أهلًا بك</span>
  </footer>
</template>
