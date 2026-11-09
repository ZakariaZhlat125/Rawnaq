<script setup>
import { localizeRecord } from '@/utils/i18nField'
import { computed } from 'vue'
import SectionTitle from '@/components/common/SectionTitle.vue'
import AppImage from '@/components/common/AppImage.vue'
import Skeleton from '@/components/common/SkeletonLoader.vue'
import { useRouter } from 'vue-router'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useLanguage } from '@/composables/useLanguage'
import { resolveApiImage } from '@/utils/assetPath'

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

const router = useRouter()
const { requireLocation } = useServiceLocation()
const { state: lang } = useLanguage()

function nameOf(category) {
    return localizeRecord(category, 'name', lang.lang)
}

const services = computed(() => {
    return props.categories.flatMap(category => {
        return (category.services || []).map(service => ({
            ...service,
            category_name: category.name,
        }))
    })
})

function go(path) {
    requireLocation(() => router.push(path))
}
</script>

<template>
  <section data-reveal id="services" class="home-section container">
    <SectionTitle title="خدماتنا" />

    <!-- Skeleton Loading -->
    <div v-if="loading" class="services-grid">
      <div v-for="n in 5" :key="n" class="service-card" style="position:relative;overflow:hidden">
        <Skeleton width="100%" height="100%" border-radius="14px" />
        <div style="position:absolute;bottom:0;left:0;right:0;padding:14px;display:flex;flex-direction:column;gap:8px">
          <Skeleton width="70%" height="16px" variant="text" />
          <Skeleton width="100%" height="28px" border-radius="20px" />
        </div>
      </div>
    </div>

    <!-- Real Content -->
    <template v-else>
      <div class="services-grid">
        <article v-for="service in categories" :key="service.id" class="service-card" data-reveal>
          <AppImage :src="resolveApiImage(service.image) || service.feature_image" :alt="nameOf(service)" />
          <div class="card-overlay">
            <h3>{{ nameOf(service) }}</h3>
            <a href="#" @click.prevent="go(`/services/${service.id}`)">التفاصيل <span>←</span></a>
          </div>
        </article>
      </div>
      <a href="#" class="show-all" @click.prevent="go('/services')">عرض جميع الخدمات ←</a>
    </template>
  </section>
</template>
