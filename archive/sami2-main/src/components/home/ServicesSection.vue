<script setup>
import SectionTitle from '@/components/common/SectionTitle.vue'
import AppImage from '@/components/common/AppImage.vue'
import { useRouter } from 'vue-router'
import { services } from '@/data/home'
import { useServiceLocation } from '@/composables/useServiceLocation'

const router = useRouter()
const { requireLocation } = useServiceLocation()

// لا ينتقل قبل تحديد مكان تنفيذ الخدمة
function go(path) {
  requireLocation(() => router.push(path))
}
</script>

<template>
  <section data-reveal id="services" class="home-section container">
    <SectionTitle title="خدماتنا" />
    <div class="services-grid">
      <article v-for="service in services" :key="service.id" class="service-card" data-reveal>
        <AppImage :src="service.image" :alt="service.name" />
        <div class="card-overlay">
          <h3>{{ service.name }}</h3>
          <a href="#" @click.prevent="go(`/services/${service.id}`)">التفاصيل <span>←</span></a>
        </div>
      </article>
    </div>
    <a href="#" class="show-all" @click.prevent="go('/services')">عرض جميع الخدمات ←</a>
  </section>
</template>
