<script setup>
import ToastMessage from '@/components/common/ToastMessage.vue'

const mainSiteUrl = import.meta.env.VITE_MAIN_SITE_URL || '/'
</script>
<template>
  <main class="app">
    <div class="site-nav-bar">
      <a :href="mainSiteUrl" class="back-to-site" title="الرجوع للموقع الرئيسي">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
        <span>الموقع الرئيسي</span>
      </a>
    </div>
    <RouterView/>
  </main>
  <ToastMessage/>
</template>
<style scoped>
.site-nav-bar{display:flex;justify-content:center;padding:10px 16px 0;position:relative;z-index:40}
.back-to-site{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:999px;
  background:rgba(20,16,10,.9);color:#F3E7D0;
  font-size:11.5px;font-weight:700;text-decoration:none;border:1px solid rgba(31,158,143,.35);
  transition:background .2s}
.back-to-site svg{flex:none}
.back-to-site:hover{background:rgba(20,16,10,1)}
</style>
