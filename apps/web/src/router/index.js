import { homeService, loadHomeService } from '@/composables/useHomeService'
import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const HomeView = () => import('@/views/HomeView.vue')
const BookingView = () => import('@/views/BookingView.vue')
const ServiceDetailView = () => import('@/views/ServiceDetailView.vue')
const ServicesView = () => import('@/views/ServicesView.vue')
const StoreView = () => import('@/views/StoreView.vue')
const GiftsView = () => import('@/views/GiftsView.vue')
const PackagesGiftsView = () => import('@/views/PackagesGiftsView.vue')
const BranchesView = () => import('@/views/BranchesView.vue')
const ContactView = () => import('@/views/ContactView.vue')
const GiftRecipientView = () => import('@/views/GiftRecipientView.vue')
const BlogView = () => import('@/views/BlogView.vue')
const BlogDetailView = () => import('@/views/BlogDetailView.vue')
const AccountView = () => import('@/views/AccountView.vue')
const TermsView = () => import('@/views/TermsView.vue')
const PrivacyPolicyView = () => import('@/views/PrivacyPolicyView.vue')
const AboutView = () => import('@/views/AboutView.vue')
const PackageDetailView = () => import('@/views/PackageDetailView.vue')

const router = createRouter({
  history: createWebHistory(),

  /* الانتقال بقى فوري بدون حركة، فالتمرير يرجع لأعلى في نفس اللحظة بدون تأخير */
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },

  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/home', redirect: '/' },
    { path: '/index.html', redirect: '/' },
    { path: '/booking', alias: '/booking.html', name: 'booking', component: BookingView, meta: { requiresAuth: true } },
    { path: '/booking-receipt', name: 'booking-receipt', component: BookingView },
    { path: '/services', alias: '/services.html', name: 'services', component: ServicesView },
    { path: '/services/:id', name: 'service-detail', component: ServiceDetailView },
    { path: '/store', alias: '/store.html', name: 'store', component: StoreView },
    { path: '/gifts', alias: '/gifts.html', name: 'gifts', component: GiftsView },
    { path: '/packages-gifts', alias: '/packages-gifts.html', name: 'packages-gifts', component: PackagesGiftsView },
    { path: '/packages/:id(\\d+)', name: 'package-detail', component: PackageDetailView, meta: { kind: 'package' } },
    { path: '/offers/:id(\\d+)', name: 'offer-detail', component: PackageDetailView, meta: { kind: 'offer' } },
    { path: '/branches', alias: '/branches.html', name: 'branches', component: BranchesView },
    { path: '/contact', alias: '/contact.html', name: 'contact', component: ContactView },
    { path: '/gift-recipient', alias: '/gift-recipient.html', name: 'gift-recipient', component: GiftRecipientView },
    { path: '/account', name: 'account', component: AccountView },
    { path: '/terms', alias: '/TermsAndConditions', name: 'terms', component: TermsView },
    { path: '/home-service', name: 'home-service', component: () => import('@/views/HomeServiceView.vue') },
    { path: '/cafe', name: 'cafe', component: () => import('@/views/CafeView.vue') },
    { path: '/privacy-policy', name: 'privacy-policy', component: PrivacyPolicyView },
    { path: '/page-about', alias: '/about.html', name: 'about', component: AboutView },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFoundView.vue') },
    { path: '/blog', alias: '/blog.html', name: 'blog', component: BlogView },
    { path: '/blog/:slug', name: 'blog-detail', component: BlogDetailView },
    
  ]
})

router.beforeEach(async to => {
  if (to.name === 'home-service') {
    try { await loadHomeService() } catch {}
    if (!homeService.enabled) return { name: 'home' }
  }
  if (!to.meta.requiresAuth) return true
  const { isAuthenticated, openAuthModal } = useAuth()
  if (isAuthenticated.value) return true

  openAuthModal(() => router.push(to.fullPath))
  return { name: 'home' }
})

/*
  الصفحات محمّلة كسولًا (lazy) — أول ضغطة على أي رابط كانت بتستنى تحميل ملفها
  فبيحصل تأخير قبل ما الحركة تبدأ. بنحمّلها في الخلفية بعد ما المتصفح يفضى،
  فالانتقال بيبقى فوري.
*/
const prefetch = () => {
  const idle = window.requestIdleCallback || (cb => setTimeout(cb, 1200))
  const queue = [
    BookingView, ServicesView, StoreView, GiftsView,
    PackagesGiftsView, BranchesView, ContactView, ServiceDetailView, GiftRecipientView
  ]
  const next = () => {
    const load = queue.shift()
    if (!load) return
    load().catch(() => {}).finally(() => idle(next))
  }
  idle(next)
}

if (typeof window !== 'undefined' && !window.matchMedia('(max-width: 640px)').matches) {
  router.isReady().then(prefetch)
}

export default router
