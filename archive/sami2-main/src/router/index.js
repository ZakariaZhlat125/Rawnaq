import { createRouter, createWebHistory } from 'vue-router'

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
    { path: '/index.html', redirect: '/' },
    { path: '/booking', alias: '/booking.html', name: 'booking', component: BookingView },
    { path: '/services', alias: '/services.html', name: 'services', component: ServicesView },
    { path: '/services/:id', name: 'service-detail', component: ServiceDetailView },
    { path: '/store', alias: '/store.html', name: 'store', component: StoreView },
    { path: '/gifts', alias: '/gifts.html', name: 'gifts', component: GiftsView },
    { path: '/packages-gifts', alias: '/packages-gifts.html', name: 'packages-gifts', component: PackagesGiftsView },
    { path: '/branches', alias: '/branches.html', name: 'branches', component: BranchesView },
    { path: '/contact', alias: '/contact.html', name: 'contact', component: ContactView },
    { path: '/gift-recipient', alias: '/gift-recipient.html', name: 'gift-recipient', component: GiftRecipientView },
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ]
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
