<script setup>
import { computed } from 'vue'
import SectionTitle from './SectionTitle.vue'
import { safeAboutUrl, useAboutContent } from '@/composables/useAboutContent'
const props = defineProps({ reviews: { type: Array, default: () => [] }, loading: Boolean })
const { page } = useAboutContent()
const apps = computed(() => [['android_url','تحميل تطبيق أندرويد'],['ios_url','تحميل تطبيق آيفون'],['app_url','تحميل التطبيق']]
  .map(([key,label]) => ({ label, url: safeAboutUrl(page.value[key]) })).filter(a => a.url))
const reviewUrl = computed(() => safeAboutUrl(page.value.google_review_url))
const reviews = computed(() => props.reviews.map(r => ({ ...r, rating: Math.max(0, Math.min(5, Number(r.rating) || 0)) })))
const average = computed(() => reviews.value.length ? (reviews.value.reduce((sum,r) => sum + r.rating, 0) / reviews.value.length).toFixed(1) : '')
</script>
<template>
  <section v-if="apps.length" class="about-extras">
    <h2 class="extras-title">تطبيق رونق</h2>
    <div class="app-links"><a v-for="app in apps" :key="app.label" :href="app.url" target="_blank" rel="noopener">{{ app.label }} <span>↗</span></a></div>
  </section>
  <section class="about-extras" aria-label="تقييمات جوجل">
    <SectionTitle title="آراء عملائنا" />
    <p v-if="loading">جارٍ تحميل التقييمات…</p>
    <template v-else>
      <div class="about-review-summary"><b class="google-mark">G</b><span>تقييمات جوجل<small>تجارب عملائنا</small></span><strong v-if="average">{{ average }} <small>★ / 5</small></strong></div>
      <div class="about-review-list">
        <article v-for="review in reviews" :key="review.id" class="about-review">
          <div><b>{{ review.name }}</b><span class="stars">{{ '★'.repeat(Math.round(review.rating)) }}</span></div>
          <small>{{ review.place }} · {{ review.when }}</small><p>{{ review.text }}</p>
        </article>
      </div>
      <p v-if="!reviews.length">لا توجد تقييمات متاحة حاليًا.</p>
      <a v-if="reviewUrl" class="review-link" :href="reviewUrl" target="_blank" rel="noopener">قيّم تجربتك على جوجل ↗</a>
    </template>
  </section>
</template>
<style scoped>
.about-extras{padding:0 18px;margin:24px 0}.extras-title{font-size:19px;margin-bottom:14px}.app-links{display:grid;gap:10px}.app-links a{display:flex;justify-content:space-between;padding:16px;border:1px solid #d7b778;border-radius:14px;background:linear-gradient(120deg,#21190e,#463018);color:#f4d69c;font-size:13px}.about-review-summary{display:flex;align-items:center;gap:12px;background:#fff;padding:16px;border:1px solid #eadfce;border-radius:16px;margin-bottom:12px}.about-review-summary small{display:block;font-size:11px;color:#8a7b6c}.about-review-summary strong{margin-inline-start:auto;color:#12665C}.google-mark{font-size:30px;color:#4285f4}.about-review-list{display:flex;overflow:auto;gap:12px;scroll-snap-type:x mandatory}.about-review{flex:0 0 92%;scroll-snap-align:start;border:1px solid #eadfce;border-radius:16px;padding:16px;background:#fff}.about-review>div{display:flex;justify-content:space-between;gap:8px}.about-review small{font-size:11px;color:#8a7b6c}.about-review p{font-size:13px;line-height:1.9;margin-top:9px}.stars{color:#c18a2d;white-space:nowrap}.review-link{display:block;text-align:center;padding:14px;color:#12665C;text-decoration:underline}
</style>
