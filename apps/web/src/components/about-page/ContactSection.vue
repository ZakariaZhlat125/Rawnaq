<script setup>
import AppIcon from './AppIcon.vue'
import SectionTitle from './SectionTitle.vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { contact, socialLinks, page, openBranchDialog } = useAboutContent()
</script>

<template>
  <section>
    <SectionTitle title="تابعنا على شبكات التواصل" />
    <div class="list reveal">
      <a v-for="social in socialLinks" :key="social.name" class="list-row" :href="social.url" target="_blank" rel="noopener">
        <span class="list-row__icon social-mark" :class="{ dark: social.dark }" :style="{ background: social.color }">{{ social.mark }}</span>
        <span><b>{{ social.name }}</b><small>تابع حسابنا الرسمي</small></span><span class="list-row__arrow">‹</span>
      </a>
    </div>
  </section>
  <section>
    <SectionTitle title="الدعم وخدمة العملاء" />
    <div class="list reveal">
      <button type="button" class="list-row" style="width:100%;text-align:start" @click="openBranchDialog"><span class="list-row__icon gold"><AppIcon name="phone" :size="18" /></span><span><b>اتصل بأحد فروعنا</b><small>اختر الفرع لعرض رقم الهاتف</small></span><strong class="list-row__action">اتصل الآن</strong></button>
      <a v-if="contact.whatsapp" class="list-row" :href="`https://wa.me/${contact.whatsapp}`" target="_blank" rel="noopener"><span class="list-row__icon green"><AppIcon name="whatsapp" :size="18" /></span><span><b>واتساب خدمة العملاء</b><small>{{ contact.whatsapp }}</small></span><strong class="list-row__action green-text">راسلنا</strong></a>
      <a v-if="contact.email" class="list-row" :href="`mailto:${contact.email}`"><span class="list-row__icon gold"><AppIcon name="mail" :size="18" /></span><span><b>البريد الإلكتروني</b><small>{{ contact.email }}</small></span><span class="list-row__arrow">‹</span></a>
    </div>
    <div v-if="page.hours" class="card hours"><span class="hours__icon"><AppIcon name="clock" :size="19" /></span><span><b>ساعات العمل</b><small>{{ page.hours }}</small></span></div>
  </section>
</template>
