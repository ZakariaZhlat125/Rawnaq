<script setup>
/*
  صفحة الفروع — بتقرأ الفروع الحقيقية من useServiceLocation (نفس المصدر
  المستخدم في الهيدر والـ modal)، فبتتحدّث تلقائيًا حسب اللغة المختارة
  ومفيش بيانات وهمية.
*/
import { ref } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import AppFooter from '@/components/layout/AppFooter.vue'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import pageCss from '@/assets/styles/pages/branches.css?raw'

const root = ref(null)
const { locations, locationsLoading, loadServiceLocations, setLocation } = useServiceLocation()

usePageStyles(pageCss, 'branches')
useInternalLinks(root)

loadServiceLocations()

function bookHere(branch) {
  setLocation(branch.id)
  location.href = '/booking'
}
</script>

<template>
  <div ref="root" class="">
<div class="shell">
  <div class="wrap">
    <div class="page-head">
      <h1>فروعنا الفاخرة</h1>
      <p>اختر الفرع الأقرب إليك لبدء حجز خدماتك. فروعنا مجهزة بالكامل لتقدم لك أرقى تجارب العناية والاسترخاء.</p>
    </div>

    <PageSkeleton v-if="locationsLoading" variant="list" />

    <div v-else class="branches-grid">
      <div v-for="branch in locations" :key="branch.id" class="branch-card">
        <div class="branch-img"><img v-if="branch.image" :src="branch.image" :alt="branch.name" /></div>
        <div class="branch-info">
          <h3>{{ branch.name }}</h3>
          <div class="branch-meta">
            <span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ branch.address }}</span>
            <span v-if="branch.home"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z"/><path d="M9 21V12h6v9"/></svg>خدمة VIP</span>
            <span v-else-if="branch.contact_number"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>{{ branch.contact_number }}</span>
            <span v-if="branch.rating_star"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.9 6.2 6.6.8-4.9 4.6 1.3 6.6L12 17l-5.9 3.2 1.3-6.6L2.5 9l6.6-.8z"/></svg>تقييم {{ branch.rating_star }}</span>
          </div>
        </div>
        <a href="#" class="btn btn-gold" style="width: 100%" @click.prevent="bookHere(branch)">{{ branch.home ? 'احجز الخدمة المنزلية' : 'احجز في هذا الفرع' }}</a>
      </div>
    </div>
  </div>
</div>


  </div>
</template>
