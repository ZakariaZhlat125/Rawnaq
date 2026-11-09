<script setup>
import AppImage from '@/components/common/AppImage.vue'
import { useHomeContent } from '@/composables/useHomeContent'
const props = defineProps({ content: Object })
const { content, text } = useHomeContent(props)
const icons = ['♙','♔','✿','♕']
</script>
<template><section data-reveal id="about" class="about-section container" data-no-i18n><AppImage :src="content.about_image" :alt="text('about_title')"/><div class="about-copy"><h2>{{ text('about_title') }}</h2><h3>{{ text('about_heading') }}</h3><p>{{ text('about_text') }}</p><h3>{{ text('about_heading_2') }}</h3><p>{{ text('about_text_2') }}</p><div class="features"><article v-for="(icon,i) in icons" :key="i"><span>{{ icon }}</span><b>{{ text('feature_'+i+'_title') }}</b><small>{{ text('feature_'+i+'_text') }}</small></article></div></div></section></template>
