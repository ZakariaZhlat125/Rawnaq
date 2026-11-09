<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LocationPicker from '@/components/common/LocationPicker.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import CartDrawer from '@/components/store/CartDrawer.vue'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useStore } from '@/composables/useStore'

const route = useRoute()
const router = useRouter()
const media = window.matchMedia('(max-width: 640px)')
const navigating = ref(false)
const cartDrawerOpen = ref(false)
const { state: storeState } = useStore()
const isMobile = ref(media.matches)
const viewByRoute = { home:'home', services:'services', 'service-detail':'services', booking:'booking', store:'store', gifts:'gifts', 'packages-gifts':'packages', branches:'branches', contact:'account', 'gift-recipient':'gifts' }
/*
  مهم: أي تعديل على public/mobile/index.html لازم يتبعه تغيير الرقم ده،
  لأنه هو اللي بيكسر كاش المتصفح للإطار. من غيره المتصفح بيفضل يعرض
  النسخة القديمة مهما اتغيّر الملف.
*/
const mobileVersion = '20260804-booking-no-stage-shake-v36'
const initialMobileView = viewByRoute[route.name] || 'home'
const mobileSrc = `/mobile/index.html?view=${initialMobileView}&v=${mobileVersion}`
const syncMedia = event => { isMobile.value = event.matches }
const motionTargets = [
  '.hero-box','.hero-person','.about-section > *','.section-title','.home-section > *',
  '.services-grid > *','.package-grid > *','.product-grid > *','.branches-grid > *',
  '.gift-banner','.cafe-strip','.final-cta','.cta-section',
  '.features > *','.promo-card','.lucky-wheel-card','.testimonial-card','.perks > *',
  '.legacy-page main > *','.legacy-page .g-head','.legacy-page .gtype',
  '.legacy-page .branch-card','.legacy-page .branch','.legacy-page .cat',
  '.legacy-page .sub','.legacy-page .gpkg','.legacy-page .pkg',
  '.legacy-page .product-card','.legacy-page .service-card',
  '.legacy-page .card','.legacy-page .panel','.legacy-page .section-title',
  '.legacy-page footer > *'
].join(',')
const openCart = () => { cartDrawerOpen.value = true }
const closeCart = () => { cartDrawerOpen.value = false }
const checkoutCart = async () => {
  closeCart()
  storeState.page = 'checkout'
  await router.push('/store')
  window.scrollTo({ top:0, behavior:'smooth' })
}
let revealObserver
let motionMutationObserver
const registerMotionTargets = root => {
  if (isMobile.value || !revealObserver) return
  const items = []
  if (root instanceof Element && root.matches(motionTargets)) items.push(root)
  root.querySelectorAll?.(motionTargets).forEach(item => items.push(item))
  items.forEach((item,index) => {
    if (item.dataset.desktopMotion) return
    item.dataset.desktopMotion = 'pending'
    item.style.setProperty('--motion-delay',`${Math.min(index % 8,7) * 55}ms`)
    revealObserver.observe(item)
  })
}
const handleMobileNavigation = event => {
  if (event.data?.type !== 'sami:navigate' || typeof event.data.path !== 'string') return
  if (event.data.path === route.fullPath || (event.data.path === '/' && route.path === '/')) return
  router.push(event.data.path)
}
let navTimer
const stopBefore = router.beforeEach(() => { clearTimeout(navTimer); navigating.value = true })
const stopAfter = router.afterEach(() => { navTimer = setTimeout(() => { navigating.value = false }, 260) })

onMounted(() => {
  media.addEventListener('change', syncMedia)
  window.addEventListener('message', handleMobileNavigation)
  if (!isMobile.value && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.documentElement.classList.add('desktop-motion-ready')
    revealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return
        entry.target.dataset.desktopMotion = 'visible'
        revealObserver.unobserve(entry.target)
      })
    },{threshold:.08,rootMargin:'0px 0px -5% 0px'})
    registerMotionTargets(document)
    motionMutationObserver = new MutationObserver(records => {
      records.forEach(record => record.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) registerMotionTargets(node)
      }))
    })
    motionMutationObserver.observe(document.getElementById('app'),{childList:true,subtree:true})
  }
})
onBeforeUnmount(() => {
  clearTimeout(navTimer)
  stopBefore()
  stopAfter()
  media.removeEventListener('change', syncMedia)
  window.removeEventListener('message', handleMobileNavigation)
  revealObserver?.disconnect()
  motionMutationObserver?.disconnect()
  document.documentElement.classList.remove('desktop-motion-ready')
})

useScrollReveal()
</script>

<template>
  <div v-if="isMobile" class="global-mobile-shell">
    <iframe class="global-mobile-frame" :src="mobileSrc" allow="fullscreen; clipboard-write; web-share" title="تطبيق عناية سامي للجوال"></iframe>
  </div>
  <template v-else>
    <!-- الهيدر خارج RouterView: نسخة واحدة تعيش طول عمر التطبيق فلا تُعاد بناؤها عند الانتقال -->
    <AppHeader @open-cart="openCart" />
    <div class="route-progress" :class="{ 'is-active': navigating }" aria-hidden="true"></div>
    <!-- بدون Transition: الصفحة الجديدة تظهر فورًا عند الضغط، بدون نزول وطلوع -->
    <RouterView v-slot="{ Component, route: current }">
      <component :is="Component" :key="current.path" />
    </RouterView>
    <LocationPicker />
    <CartDrawer :open="cartDrawerOpen" @close="closeCart" @checkout="checkoutCart" />
  </template>
</template>

<style>
.global-mobile-shell{width:100%;height:100dvh;background:#e8e1d6;overflow:hidden}.global-mobile-frame{display:block;width:100%;height:100%;border:0;background:#e8e1d6}
@media(max-width:640px){html,body,#app{width:100%;height:100%;margin:0;overflow:hidden}}
</style>
