<script setup>
import { useRouter } from 'vue-router'
import BaseButton from '@/components/common/BaseButton.vue'
import { promos } from '@/data/home'
import { useServiceLocation } from '@/composables/useServiceLocation'

/*
  بطاقتان: عرض ترويجي + الخدمة المنزلية.
  بطاقة الخدمة المنزلية بتثبّت مكان التنفيذ على 'hm' قبل ما تودّي للحجز،
  فالمستخدم ميتسألش عن المكان تاني.
*/
const router = useRouter()
const { setLocation } = useServiceLocation()

const HOME_ICON = 'M3 9l9-6 9 6v11a1 1 0 01-1 1H4a1 1 0 01-1-1z'
const HOME_ICON_DOOR = 'M9 21V12h6v9'

function goHomeService() {
  setLocation('hm')
  router.push('/booking')
}
</script>

<template>
  <section data-reveal class="home-section container promo-section" aria-label="إعلانات وعروض">
    <article v-for="promo in promos" :key="promo.id" class="promo-card"
             :class="{ 'promo-card--home': promo.type === 'home' }">
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
      <BaseButton v-if="promo.type === 'home'" class="promo-card__cta" :label="promo.cta"
                  href="#" @click.prevent="goHomeService" />
      <BaseButton v-else class="promo-card__cta" :label="promo.cta" :href="promo.href" />
    </article>
  </section>
</template>

<style scoped>
/* أيقونة البيت بدل شارة الخصم — نفس الدائرة الذهبية بلون داكن للأيقونة */
.promo-card--home :deep(.promo-card__badge),
.promo-card--home .promo-card__badge { color: #2a1f0c }
</style>
