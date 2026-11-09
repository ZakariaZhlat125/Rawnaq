<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LocationPicker from '@/components/common/LocationPicker.vue'
import AuthModal from '@/components/common/AuthModal.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import CartDrawer from '@/components/store/CartDrawer.vue'
import FloatingActions from '@/components/common/FloatingActions.vue'
import { useScrollReveal } from '@/composables/useScrollReveal'
import { useSeo } from '@/composables/useSeo'
import { useStore } from '@/composables/useStore'

useSeo()
const route = useRoute()
const router = useRouter()
const media = window.matchMedia('(max-width: 640px)')
const standalone = window.matchMedia('(display-mode: standalone)')
const mobileLayout = () => media.matches || standalone.matches || window.navigator.standalone === true
const navigating = ref(false)
const cartDrawerOpen = ref(false)
const { state: storeState } = useStore()
const isMobile = ref(mobileLayout())
/* روابط ?book= / ?gift= من صفحة الباقة أو العرض بتفتح رحلة الحجز في نسخة Vue لأن تطبيق الجوال مايعرفش يفتح باقة بعينها */
const useMobileFrame = computed(() => isMobile.value && Object.hasOwn(viewByRoute, route.name) && !(route.name === 'packages-gifts' && (route.query.book || route.query.gift)))
const viewByRoute = { account:'account', home:'home', services:'services', 'service-detail':'services', booking:'booking', store:'store', gifts:'gifts', 'packages-gifts':'packages', branches:'branches', contact:'contact', 'gift-recipient':'gifts', terms:'terms', 'home-service':'home-service', cafe:'cafe', 'privacy-policy':'privacy' }
/*
  Ù…Ù‡Ù…: Ø£ÙŠ ØªØ¹Ø¯ÙŠÙ„ Ø¹Ù„Ù‰ public/mobile/index.html Ù„Ø§Ø²Ù… ÙŠØªØ¨Ø¹Ù‡ ØªØºÙŠÙŠØ± Ø§Ù„Ø±Ù‚Ù… Ø¯Ù‡ØŒ
  Ù„Ø£Ù†Ù‡ Ù‡Ùˆ Ø§Ù„Ù„ÙŠ Ø¨ÙŠÙƒØ³Ø± ÙƒØ§Ø´ Ø§Ù„Ù…ØªØµÙØ­ Ù„Ù„Ø¥Ø·Ø§Ø±. Ù…Ù† ØºÙŠØ±Ù‡ Ø§Ù„Ù…ØªØµÙØ­ Ø¨ÙŠÙØ¶Ù„ ÙŠØ¹Ø±Ø¶
  Ø§Ù„Ù†Ø³Ø®Ø© Ø§Ù„Ù‚Ø¯ÙŠÙ…Ø© Ù…Ù‡Ù…Ø§ Ø§ØªØºÙŠÙ‘Ø± Ø§Ù„Ù…Ù„Ù.
*/
const mobileVersion = '20261108-catalog-pages-v117'
let mobileNavigationPath = null
const mobileApiBase = (import.meta.env.VITE_API_BASE_URL || `${window.location.origin}/api`).replace(/\/$/, '')
let notificationInbox
let notificationScript
const notificationCount = ref(0)
const publishNotificationCount = () => {
  document.querySelector('iframe.global-mobile-frame')?.contentWindow?.postMessage({ type: 'sami:notification-count', count: notificationCount.value }, location.origin)
}
watch(useMobileFrame, value => notificationInbox?.setHideButton(value))
const mobileSrc = ref('')
watch(() => route.fullPath, path => {
  if (mobileNavigationPath === path) { mobileNavigationPath = null; return }
  mobileNavigationPath = null
  mobileSrc.value = '/mobile/index.html?' + new URLSearchParams({ view: viewByRoute[route.name] || 'home', page: route.path, api: mobileApiBase, v: mobileVersion })
}, { immediate: true })
const syncMedia = () => { isMobile.value = mobileLayout() }
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
  const frame = document.querySelector('iframe.global-mobile-frame')
  if (event.origin !== location.origin || event.source !== frame?.contentWindow) return
  if (event.data?.type === 'sami:notifications') { notificationInbox?.open(); return }
  if (event.data?.type === 'sami:notifications-ready') { publishNotificationCount(); return }
  if (event.data?.type !== 'sami:navigate' || typeof event.data.path !== 'string') return
  if (event.data.path === route.fullPath || (event.data.path === '/' && route.path === '/')) return
  if (!event.data.path.startsWith('/') || event.data.path.startsWith('//')) return
  mobileNavigationPath = event.data.path
  router.push(event.data.path).finally(() => { mobileNavigationPath = null })
}
let navTimer
const stopBefore = router.beforeEach(() => { clearTimeout(navTimer); navigating.value = true })
const stopAfter = router.afterEach(() => { navTimer = setTimeout(() => { navigating.value = false }, 260) })

onMounted(() => {
  notificationScript = document.createElement('script')
  notificationScript.src = '/customer-notifications.js?v=20261024-pusher-1'
  notificationScript.onload = () => {
    notificationInbox = window.mountSamiNotifications({ apiBase: mobileApiBase, hideButton: useMobileFrame.value, onCount: count => {
      notificationCount.value = count
      publishNotificationCount()
    } })
  }
  document.head.append(notificationScript)
  media.addEventListener('change', syncMedia)
  standalone.addEventListener('change', syncMedia)
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
  notificationScript.onload = null
  notificationScript.remove()
  notificationInbox?.destroy()
  clearTimeout(navTimer)
  stopBefore()
  stopAfter()
  media.removeEventListener('change', syncMedia)
  standalone.removeEventListener('change', syncMedia)
  window.removeEventListener('message', handleMobileNavigation)
  revealObserver?.disconnect()
  motionMutationObserver?.disconnect()
  document.documentElement.classList.remove('desktop-motion-ready')
})

useScrollReveal()
</script>

<template>
  <div v-if="useMobileFrame" class="global-mobile-shell">
    <iframe class="global-mobile-frame" :src="mobileSrc" allow="geolocation; fullscreen; clipboard-write; web-share" title="ØªØ·Ø¨ÙŠÙ‚ Ø¹Ù†Ø§ÙŠØ© Ø³Ø§Ù…ÙŠ Ù„Ù„Ø¬ÙˆØ§Ù„"></iframe>
  </div>
  <template v-else>
    <!-- Ø§Ù„Ù‡ÙŠØ¯Ø± Ø®Ø§Ø±Ø¬ RouterView: Ù†Ø³Ø®Ø© ÙˆØ§Ø­Ø¯Ø© ØªØ¹ÙŠØ´ Ø·ÙˆÙ„ Ø¹Ù…Ø± Ø§Ù„ØªØ·Ø¨ÙŠÙ‚ ÙÙ„Ø§ ØªÙØ¹Ø§Ø¯ Ø¨Ù†Ø§Ø¤Ù‡Ø§ Ø¹Ù†Ø¯ Ø§Ù„Ø§Ù†ØªÙ‚Ø§Ù„ -->
    <AppHeader @open-cart="openCart" />
    <div class="route-progress" :class="{ 'is-active': navigating }" aria-hidden="true"></div>
    <!-- Ø¨Ø¯ÙˆÙ† Transition: Ø§Ù„ØµÙØ­Ø© Ø§Ù„Ø¬Ø¯ÙŠØ¯Ø© ØªØ¸Ù‡Ø± ÙÙˆØ±Ù‹Ø§ Ø¹Ù†Ø¯ Ø§Ù„Ø¶ØºØ·ØŒ Ø¨Ø¯ÙˆÙ† Ù†Ø²ÙˆÙ„ ÙˆØ·Ù„ÙˆØ¹ -->
    <RouterView v-slot="{ Component, route: current }">
      <component :is="Component" :key="current.path" />
    </RouterView>
    <AppFooter v-if="!['booking', 'packages-gifts', 'gifts'].includes(route.name)" class="global-site-footer" />
    <LocationPicker />
    <CartDrawer :open="cartDrawerOpen" @close="closeCart" @checkout="checkoutCart" />
  </template>
  <!-- عائم على كل الأجهزة (موبايل + ديسكتوب) -->
  <FloatingActions />
  <AuthModal />
</template>

<style>
.global-mobile-shell{position:fixed;inset:0;width:100%;height:100dvh;background:#e8e1d6;overflow:hidden}.global-mobile-frame{display:block;width:100%;height:100dvh;border:0;background:#e8e1d6;overflow:auto;-webkit-overflow-scrolling:touch}
#app footer:not(.sami-unified-footer){display:none!important}
@media(max-width:640px){
  html:has(.global-mobile-shell),body:has(.global-mobile-shell),#app:has(.global-mobile-shell){width:100%;height:100dvh;margin:0;overflow:hidden}
  html:not(:has(.global-mobile-shell)),body:not(:has(.global-mobile-shell)),#app:not(:has(.global-mobile-shell)){height:auto;min-height:100%;overflow-x:hidden;overflow-y:auto}
}
</style>

