<script setup>
import { homeService, loadHomeService } from '@/composables/useHomeService'
loadHomeService().catch(() => {})
import { localizeField, localizeRecord } from '@/utils/i18nField'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import SectionTitle from '@/components/common/SectionTitle.vue'
import AppImage from '@/components/common/AppImage.vue'
import BaseButton from '@/components/common/BaseButton.vue'
import { useLanguage } from '@/composables/useLanguage'
import { useServiceLocation } from '@/composables/useServiceLocation'

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    banner: {
        type: Object,
        default: null,
    },
    offers: {
        type: Array,
        default: () => [],
    },
})

const displayBranches = computed(() => {
    const branches = (props.branches || []).filter(b => homeService.enabled || !(b.home || b.id === 'home-service'))
    if (!homeService.enabled) return branches
    const hasHomeService = branches.some(branch => branch.home || branch.id === 'home-service')

    return hasHomeService
        ? branches
        : [...branches, {
            id: 'home-service',
            home: true,
            name: 'خدمة منزلية',
            description: 'حلاقة وعناية ومساجات طبيعية — في منزلك',
            description_en: 'Haircut, beauty, and natural massages — at home',
            image: '/images/generated/branches/home-service-hq.png',
        }]
})

const { state: lang } = useLanguage()
const router = useRouter()
const { setLocation } = useServiceLocation()

function localized(value, fallback = '') {
    if (!value) return fallback
    return localizeField(value, lang.lang) || fallback
}

function bookBranch(branch) {
    setLocation(branch.home ? 'home-service' : branch.id)
    router.push('/booking')
}

function nameOf(branch) {
    return localizeRecord(branch, 'name', lang.lang)
}

function descriptionOf(branch) {
    return lang.lang === 'en' ? branch.description_en || branch.description : branch.description
}

const activeBanner = computed(() => {
    const banner = props.banner
    if (!banner) {
        return {
            title: 'بمناسبة افتتاح الفرع الجديد',
            description: '',
            discount: '25%',
            image: '/images/generated/branches/offer-person-hq.png',
            color: '#f3eadf',
            link: '/branches',
            overlay: true,
            dynamic: false,
        }
    }

    const value = Number(banner.discount_value || 0)
    const formattedValue = Number.isInteger(value) ? value : value.toFixed(2)

    return {
        title: localizeRecord(banner, 'title', lang.lang) || 'عرض مميز',
        description: localizeRecord(banner, 'description', lang.lang),
        discount: banner.discount_type === 'fixed' ? `${formattedValue} ر.س` : `${formattedValue}%`,
        image: banner.image || '/images/generated/branches/offer-person-hq.png',
        color: banner.color || '#f3eadf',
        link: banner.link || '/branches',
        overlay: banner.overlay !== false,
        dynamic: true,
    }
})

const featuredOffer = computed(() => {
    const offer = props.offers?.[0]
    if (!offer) return null

    return {
        title: localizeRecord(offer, 'name', lang.lang) || 'عرض خاص',
        description: localizeRecord(offer, 'description', lang.lang),
        image: offer.feature_image || '/images/generated/branches/offer-person-hq.png',
        color: '#f3eadf',
        link: `/offers/${offer.id}`,
        overlay: true,
        dynamic: true,
    }
})

const activeCard = computed(() => featuredOffer.value || activeBanner.value)

const bannerStyle = computed(() => ({
    '--branch-banner-image': `url("${String(activeCard.value.image).replace(/"/g, '\\"')}")`,
    '--branch-banner-color': activeCard.value.color || '#f3eadf',
}))
</script>

<template>
    <section data-reveal id="branches" class="home-section container">
        <SectionTitle title="فروعنا" />
        <div class="branches-grid">
            <div
                class="offer-card"
                :class="{ 'offer-card--dynamic': activeCard.dynamic || featuredOffer, 'offer-card--no-overlay': !activeCard.overlay }"
                :style="bannerStyle"
            >
                <p v-if="featuredOffer">عرض لفترة محدودة</p>
                <p v-else>خصم <strong>{{ activeCard.discount }}</strong></p>
                <h3>{{ activeCard.title }}</h3>
                <small v-if="activeCard.description" class="offer-card__description">{{ activeCard.description }}</small>
                <BaseButton :label="featuredOffer ? 'احجز الآن' : 'اعرف المزيد'" :href="activeCard.link" />
            </div>
            <article v-for="branch in displayBranches" :key="branch.id">
                <AppImage :src="branch.image" :alt="nameOf(branch)" />
                <h3>{{ nameOf(branch) }}</h3>
                <p>{{ descriptionOf(branch) }}<!-- {{ localizeRecord(branch, 'address', lang.lang) }} --></p>
                <BaseButton :label="branch.home ? 'احجز الخدمة المنزلية' : 'احجز الآن'" href="#" @click.prevent="bookBranch(branch)" />
            </article>
        </div>
    </section>
</template>
