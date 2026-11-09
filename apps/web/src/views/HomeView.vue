<script setup>
import { ref, onMounted, watch } from 'vue'
import { useLanguage } from '@/composables/useLanguage'
const { state: language } = useLanguage()
let homeRequest = 0
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import HeroSection from '@/components/home/HeroSection.vue'
import AboutSection from '@/components/home/AboutSection.vue'
import ServicesSection from '@/components/home/ServicesSection.vue'
import GiftBanner from '@/components/home/GiftBanner.vue'
import CafeStrip from '@/components/home/CafeStrip.vue'
import LuckyWheelCard from '@/components/home/LuckyWheelCard.vue'
import PackagesSection from '@/components/home/PackagesSection.vue'
import OffersSection from '@/components/home/OffersSection.vue'
import ProductsSection from '@/components/home/ProductsSection.vue'
import BranchesSection from '@/components/home/BranchesSection.vue'
import TestimonialsSection from '@/components/home/TestimonialsSection.vue'
import FaqSection from '@/components/common/FaqSection.vue'
import FinalCta from '@/components/home/FinalCta.vue'
import { getHomeContent } from '@/data/home'
import { useStore } from '@/composables/useStore'
const { seedProducts } = useStore()

const homeData = ref({
    categories: [],
    offers: [],
    products: [],
    packages: [],
    branches: [],
    branch_banner: null,
    reviews: [],
    wheel_prizes: [],
})

const loading = ref(true)
const error = ref(null)

const loadHomeData = async () => {
    const request = ++homeRequest
    try {
        loading.value = true
        error.value = null

        const result = await getHomeContent()
        if (request !== homeRequest) return
        homeData.value = result
        seedProducts(result.products)
    } catch (err) {
        if (request !== homeRequest) return
        console.error('Home API Error:', err)

        error.value = 'حدث خطأ أثناء تحميل بيانات الصفحة الرئيسية'
    } finally {
        if (request === homeRequest) loading.value = false
    }
}

onMounted(() => {
    loadHomeData()
})

watch(() => language.lang, loadHomeData)
</script>

<template>
  <div class="home-page desktop-home" :dir="language.lang === 'en' ? 'ltr' : 'rtl'">
    <PageSkeleton v-if="loading" variant="detail" />
    <div v-else-if="error" role="alert" style="padding:100px 24px;text-align:center">
      <p>{{ language.lang === 'en' ? 'Unable to load content.' : 'تعذر تحميل المحتوى.' }}</p>
      <button type="button" @click="loadHomeData">{{ language.lang === 'en' ? 'Retry' : 'إعادة المحاولة' }}</button>
    </div>
    <main v-else>
      <HeroSection :content="homeData.home_content" />
      <div class="home-light">
        <AboutSection :content="homeData.home_content" />
          <ServicesSection
                    :categories="homeData.categories"
                    :loading="loading"
                />

       <!-- كل قسم في صف لوحده: الباقات، ثم العروض، ثم المنتجات -->
       <div class="catalog catalog--rows container">

                    <!-- Packages -->
                    <PackagesSection
                        :packages="homeData.packages"
                        :loading="loading"
                    />

                    <!-- Offers -->
                    <OffersSection
                        :offers="homeData.offers"
                        :loading="loading"
                    />

                    <!-- Products -->
                    <ProductsSection
                        :products="homeData.products"
                        :loading="loading"
                    />

                </div>
        <CafeStrip :image="homeData.home_content?.cafe_image" />
        <GiftBanner />
       <LuckyWheelCard
                    :prizes="homeData.wheel_prizes"
                />

                <BranchesSection
                    :branches="homeData.branches"
                    :banner="homeData.branch_banner"
                    :offers="homeData.offers"
                />

                <!-- Reviews -->
                <TestimonialsSection
                    :reviews="homeData.reviews"
                    :loading="loading"
                />
        
        <FaqSection :items="homeData.faqs || []" />
        <FinalCta />
      </div>
    </main>
  
  </div>
</template>

<style>
/* الباقات والعروض والمنتجات: كل قسم صف واحد بعرض الصفحة، والزيادة تتمرر أفقيًا */
.home-page .catalog.catalog--rows{display:flex;flex-direction:column;gap:34px}
.home-page .catalog--rows .catalog-section{min-width:0}
.home-page .catalog--rows .package-grid,
.home-page .catalog--rows .product-grid{display:grid;grid-template-columns:none;grid-auto-flow:column;grid-auto-columns:calc((100% - 3 * 16px) / 4);gap:16px;overflow-x:auto;overscroll-behavior-x:contain;scroll-snap-type:x mandatory;padding:4px 2px 12px}
.home-page .catalog--rows .package-grid > article,
.home-page .catalog--rows .product-grid > article{scroll-snap-align:start;max-width:none;width:auto;margin:0}
.home-page .catalog--rows .catalog-card-media{display:block}
.home-page .catalog--rows h3 a:hover{color:var(--gold)}
@media(max-width:900px){.home-page .catalog--rows .package-grid,.home-page .catalog--rows .product-grid{grid-auto-columns:calc((100% - 16px) / 2.3)}}
@media(max-width:560px){.home-page .catalog--rows .package-grid,.home-page .catalog--rows .product-grid{grid-auto-columns:72%}}
body{background:#f8f4ee}.home-page{min-height:100vh;background:#f8f4ee;color:#171717}.home-light{position:relative;z-index:4;margin-top:-42px;padding-top:36px;background:#f8f4ee;border-radius:52% 52% 0 0/44px 44px 0 0}
</style>
