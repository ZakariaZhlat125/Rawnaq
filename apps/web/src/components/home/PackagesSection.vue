<script setup>
import PackagePrice from '@/components/common/PackagePrice.vue'
import { localizeRecord } from '@/utils/i18nField'
import SectionTitle from '@/components/common/SectionTitle.vue'
import AppImage from '@/components/common/AppImage.vue'
import CatalogCarousel from '@/components/common/CatalogCarousel.vue'
import { useLanguage } from '@/composables/useLanguage'

import Skeleton from '@/components/common/SkeletonLoader.vue'

defineProps({
    packages: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

const { state: lang } = useLanguage()

function nameOf(item) {
    return localizeRecord(item, 'name', lang.lang)
}
</script>

<template>
    <section
        data-reveal
        id="packages"
        class="catalog-section"
    >
        <SectionTitle title="باقاتنا المميزة" />

        <!-- Skeleton Loading -->
        <div v-if="loading" class="package-grid package-grid--home">
            <article v-for="n in 3" :key="n" data-reveal style="display:flex;flex-direction:column;align-items:center;gap:8px;min-height:210px;justify-content:center">
                <Skeleton width="100px" height="100px" variant="circle" />
                <Skeleton width="80%" height="14px" variant="text" />
                <Skeleton width="90px" height="30px" border-radius="18px" />
            </article>
        </div>

        <CatalogCarousel v-else label="باقاتنا المميزة">
        <div class="package-grid package-grid--home">
            <article
                v-for="item in packages"
                :key="item.id"
                data-reveal
            >
                <RouterLink :to="`/packages/${item.id}`" class="catalog-card-media">
                    <AppImage
                        :src="item.feature_image"
                        :alt="nameOf(item)"
                    />
                </RouterLink>

                <h3>
                    <RouterLink :to="`/packages/${item.id}`">{{ nameOf(item) }}</RouterLink>
                </h3>
                <PackagePrice :price="item.package_price" :savings="item.savings_amount" :original="item.services_total_price" />

                <p v-if="item.description">
                    {{ localizeRecord(item, 'description', lang.lang) }}
                </p>

                <RouterLink :to="`/packages/${item.id}`">
                    استكشف الباقة ←
                </RouterLink>
            </article>
        </div>
        </CatalogCarousel>
    </section>
</template>
