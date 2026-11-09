<script setup>
import { homeService, loadHomeService } from '@/composables/useHomeService'
loadHomeService().catch(() => {})
import { useLanguage } from '@/composables/useLanguage'
import { localizeRecord } from '@/utils/i18nField'
const { state: language } = useLanguage()
const field = (item, key) => localizeRecord(item, key, language.lang)
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/common/BaseButton.vue'
import SectionTitle from '@/components/common/SectionTitle.vue'
import Skeleton from '@/components/common/SkeletonLoader.vue'

const props = defineProps({
    homeImage: String,
    offers: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

const router = useRouter()

const HOME_ICON = 'M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z'
const HOME_ICON_DOOR = 'M9 21V12h6v9'

const promoCards = computed(() => {
  const apiOffers = (props.offers || []).slice(0, 1).map((offer, index) => ({
    id: offer.id || `offer-${index}`,
    type: 'offer',
    badge: field(offer, 'badge') || 'خصم',
    eyebrow: field(offer, 'eyebrow') || '✦ عرض خاص',
    title: field(offer, 'name') || field(offer, 'title') || 'عرض مميز',
    text: field(offer, 'description') || field(offer, 'text') || 'استمتع بالعروض المميزة من رونق',
    cta: field(offer, 'cta') || 'اكتشف العرض',
    href: offer.href || '/packages-gifts',
  }))

  return [
    ...apiOffers,
    /*{
      id: 'home-service',
      type: 'home',
      eyebrow: '✦ نصل إليك',
      title: 'الخدمات المنزلية',
      text: 'حلاقة وعناية ومساجات طبيعية في راحة منزلك داخل جدة',
      cta: 'احجز الخدمة المنزلية',
      href: '/booking',
    },
    {
      id: 'home-service',
      type: 'home',
      eyebrow: '✦ نصل إليك',
      title: 'الخدمات المنزلية',
      text: 'حلاقة وعناية ومساجات طبيعية في راحة منزلك داخل جدة',
      cta: 'احجز الخدمة المنزلية',
      href: '/booking',
    },*/

    {id:'home-service',type:'home',eyebrow:'✦ نصل إليك',title:'الخدمات المنزلية',text:'حلاقة وعناية وماسكات طبيعية في راحة منزلك داخل جدة',cta:'احجز الخدمة المنزلية',href:'/booking?branch=hm'}
  ].filter(card => homeService.enabled || card.type !== 'home')
})

function goHomeService() {
  router.push({ path: '/booking', query: { branch: 'hm' } })
}
</script>

<template>
  <section data-reveal class="home-section container promo-section" aria-label="إعلانات وعروض">
    <SectionTitle v-if="(offers && offers.length) || loading" title="العروض الحالية" />

    <!-- Skeleton Loading -->
    <template v-if="loading">
      <article v-for="n in 2" :key="n" class="promo-card">
        <div class="promo-card__badge" style="display:flex;align-items:center;justify-content:center">
          <Skeleton width="86px" height="86px" variant="circle" />
        </div>
        <div class="promo-card__text" style="display:flex;flex-direction:column;gap:8px;flex:1">
          <Skeleton width="100px" height="12px" variant="text" />
          <Skeleton width="200px" height="20px" variant="text" />
          <Skeleton width="85%" height="14px" variant="text" />
        </div>
        <Skeleton width="130px" height="42px" border-radius="30px" />
      </article>
    </template>

    <!-- Real Content -->
    <template v-else>
    <article v-for="promo in promoCards" :key="promo.id" class="promo-card"
             :class="{ 'promo-card--home': promo.type === 'home', 'has-home-image': promo.type === 'home' && homeImage }">
      <img v-if="promo.type === 'home' && homeImage" class="home-promo-image" :src="homeImage" alt="" loading="lazy" decoding="async" />
      <div class="promo-card__badge" aria-hidden="true">
        <template v-if="promo.type === 'home'">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
            <path :d="HOME_ICON" /><path :d="HOME_ICON_DOOR" />
          </svg>
        </template>
        <template v-else>
          <b>{{ promo.badge }}</b>
          <small>خصم</small>
        </template>
      </div>
      <div class="promo-card__text">
        <span class="promo-card__eyebrow">{{ promo.eyebrow || '✦ عرض خاص' }}</span>
        <h3>{{ promo.title }}</h3>
        <p>{{ promo.text }}</p>
      </div>
      <div v-if="promo.type === 'home'" class="promo-card__actions">
        <BaseButton class="promo-card__cta" :label="promo.cta"
                    href="/booking?branch=hm" @click.prevent="goHomeService" />
        <BaseButton class="promo-card__cta" label="تفاصيل الخدمة المنزلية"
                    href="/home-service" @click.prevent="router.push('/home-service')" />
      </div>
      <BaseButton v-else class="promo-card__cta" :label="promo.cta" :href="promo.href" />
    </article>
    </template>
  </section>
</template>

<style scoped>
.has-home-image { position:relative; isolation:isolate; background-image:none!important; overflow:hidden }
.has-home-image::before,.has-home-image::after { background-image:none!important }
.home-promo-image { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:-1 }
.promo-card__actions { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: 10px; align-self: start; }
.promo-card__actions .promo-card__cta { white-space: normal; text-align: center; }
/* أيقونة البيت بدل شارة الخصم — نفس الدائرة الذهبية بلون داكن للأيقونة */
.promo-card--home :deep(.promo-card__badge),
.promo-card--home .promo-card__badge { color: #2a1f0c }
@media (min-width: 951px) {
  .promo-section .promo-card--home { grid-column: 1 / -1; min-height: 230px }
}
</style>
