<script setup>
import AppFooter from '@/components/layout/AppFooter.vue'
import HeroSection from '@/components/home/HeroSection.vue'
import AboutSection from '@/components/home/AboutSection.vue'
import ServicesSection from '@/components/home/ServicesSection.vue'
import GiftBanner from '@/components/home/GiftBanner.vue'
import CafeStrip from '@/components/home/CafeStrip.vue'
import PromoCard from '@/components/home/PromoCard.vue'
import LuckyWheelCard from '@/components/home/LuckyWheelCard.vue'
import PackagesSection from '@/components/home/PackagesSection.vue'
import ProductsSection from '@/components/home/ProductsSection.vue'
import BranchesSection from '@/components/home/BranchesSection.vue'
import TestimonialsSection from '@/components/home/TestimonialsSection.vue'
import FinalCta from '@/components/home/FinalCta.vue'

</script>

<template>
  <div class="home-page desktop-home" dir="rtl">
    <main>
      <HeroSection />
      <div class="home-light">
        <AboutSection />
        <ServicesSection />
        <PromoCard />
        <CafeStrip />
        <GiftBanner />
        <LuckyWheelCard />
        <div class="catalog container"><PackagesSection/><ProductsSection/></div>
        <BranchesSection />
        <TestimonialsSection />
        <FinalCta />
      </div>
    </main>
    <AppFooter />
  </div>
</template>

<style>
body{background:#f8f4ee}.home-page{min-height:100vh;background:#f8f4ee;color:#171717}.home-light{position:relative;z-index:4;margin-top:-42px;padding-top:36px;background:#f8f4ee;border-radius:52% 52% 0 0/44px 44px 0 0}
</style>
