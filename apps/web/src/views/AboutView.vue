<script setup>
import { useSiteContact } from '@/composables/useSiteContact'
const { whatsappNumber } = useSiteContact()

import { computed, onMounted, onBeforeUnmount, provide, ref, watch } from 'vue'
import { resolveBackendUrl } from '@/utils/assetPath'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { safeAboutUrl, branchPhone } from '@/composables/useAboutContent'
import FaqSection from '@/components/common/FaqSection.vue'
import AboutExtras from '@/components/about-page/AboutExtras.vue'
import HeroSection from '@/components/about-page/HeroSection.vue'
import QuickActions from '@/components/about-page/QuickActions.vue'
import AboutSection from '@/components/about-page/AboutSection.vue'
import ServicesSection from '@/components/about-page/ServicesSection.vue'
import ContactSection from '@/components/about-page/ContactSection.vue'
import BranchesSection from '@/components/about-page/BranchesSection.vue'
import FeaturesSection from '@/components/about-page/FeaturesSection.vue'
import PageActions from '@/components/about-page/PageActions.vue'
import SiteFooter from '@/components/about-page/SiteFooter.vue'
import StickyActions from '@/components/about-page/StickyActions.vue'
import { useAboutReveal } from '@/composables/useAboutReveal'
import { getHomeContent } from '@/data/home'

import { useLanguage } from '@/composables/useLanguage'
import { localizeRecord } from '@/utils/i18nField'

const toast = ref('')
const serviceCategories = ref([])
const servicesLoading = ref(true)
const servicesError = ref('')
const { state: language, toggle, t } = useLanguage()
const rawPage = ref({})
const translated = (record, key) => {
  const english = record[key + '_en'] || (key === 'title' ? record.english_title : '')
  return t(language.lang === 'en' && english ? english : localizeRecord(record, key, language.lang))
}
const page = computed(() => {
  const data = rawPage.value
  const result = { ...data }
  for (const key of ['title', 'tagline', 'location_text', 'description', 'hours']) result[key] = translated(data, key)
  result.stats = (data.stats || []).map(row => ({ ...row, label: translated(row, 'label') }))
  result.features = (data.features || []).map(row => ({ ...row, name: translated(row, 'name'), detail: translated(row, 'detail') }))
  return result
})
const contentError = ref('')
const reviews = ref([])
const faqs = ref([])
const branchDialog = ref(null)
const { locations, locationsLoading, locationsError, loadServiceLocations } = useServiceLocation()
const branches = computed(() => locations.value.filter(b => !b.home).map(b => ({ ...b,
  phone: branchPhone(b.contact_number),
  map: safeAboutUrl(b.map_url || b.map) || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${b.name} ${b.address || ''}`)}`,
})))
const contact = computed(() => ({ email: page.value.email || '', whatsapp: whatsappNumber.value, website: '/booking' }))
const socialLinks = computed(() => [
  ['instagram', 'إنستغرام', '◎', '#b23986'], ['tiktok', 'تيك توك', '♪', '#161616'],
  ['snapchat', 'سناب شات', '♟', '#c9ad00'], ['facebook', 'فيسبوك', 'f', '#1877f2'], ['youtube', 'يوتيوب', '▶', '#d92323'],
].map(([key,name,mark,color]) => ({ name, mark, color, url: safeAboutUrl(page.value[`${key}_url`]) })).filter(s => s.url))
function openBranchDialog() { branchDialog.value?.showModal() }
provide('aboutContent', { page, branches, contact, socialLinks, openBranchDialog })
let toastTimer

let servicesRequest = 0
async function loadServices() {
  const request = ++servicesRequest
  servicesLoading.value = true
  servicesError.value = ''
  try {
    const homeData = await getHomeContent()
    if (request !== servicesRequest) return
    serviceCategories.value = homeData.categories || []
    reviews.value = homeData.reviews || []
    faqs.value = homeData.faqs || []
  } catch (error) {
    if (request !== servicesRequest) return
    console.error('About services API error:', error)
    servicesError.value = 'تعذر تحميل الخدمات حاليًا'
  } finally {
    if (request === servicesRequest) servicesLoading.value = false
  }
}

function notify(message) {
  toast.value = message
  clearTimeout(toastTimer)

  toastTimer = setTimeout(() => {
    toast.value = ''
  }, 2300)
}

useAboutReveal()
onMounted(loadServices)
onMounted(loadServiceLocations)
let contentRequest = 0
async function loadContent() {
  const request = ++contentRequest
  contentError.value = ''
  try {
    const response = await fetch(resolveBackendUrl('/api/about-page'), { headers: { Accept: 'application/json', 'frezka-localization': language.lang, 'Accept-Language': language.lang } })
    if (!response.ok) throw new Error('About content unavailable')
    const result = await response.json()
    if (!result.status || !result.data) throw new Error('Invalid about content')
    if (request !== contentRequest) return
    rawPage.value = result.data
  } catch { if (request !== contentRequest) return; contentError.value = 'تعذّر تحميل محتوى الصفحة. يرجى المحاولة لاحقًا.' }
}
onMounted(loadContent)
watch(() => language.lang, () => { loadContent(); loadServices() })
onBeforeUnmount(() => { clearTimeout(toastTimer); contentRequest++; servicesRequest++ })
</script>

<template>
  <div class="about-design-page" :dir="language.lang === 'en' ? 'ltr' : 'rtl'">
    <main class="about-app-shell">
      <button class="about-language-toggle" type="button" translate="no" @click="toggle" :aria-label="language.lang === 'en' ? 'التبديل للعربية' : 'Switch to English'">{{ language.lang === 'en' ? 'العربية' : 'English' }}</button>
      <HeroSection logo="/logo.png" />
      <p v-if="contentError" role="alert">{{ contentError }}</p>
      <QuickActions />
      <AboutSection />
      <ServicesSection :categories="serviceCategories" :loading="servicesLoading" :error="servicesError" />
      <ContactSection />
      <BranchesSection />
      <FeaturesSection />
      <AboutExtras :reviews="reviews" :loading="servicesLoading" />
      <PageActions @notify="notify" />
      <FaqSection :items="faqs" />
      <SiteFooter class="sami-unified-footer" logo="/logo.png" />
    </main>

    <StickyActions />
    <dialog ref="branchDialog" class="about-branch-dialog" aria-labelledby="branch-call-title" @click="event => { if (event.target === branchDialog) branchDialog.close() }">
      <div class="branch-dialog-head"><div><h2 id="branch-call-title">اتصل بأحد فروعنا</h2><p>اختر الفرع للتواصل معه مباشرة</p></div><button type="button" aria-label="إغلاق" @click="branchDialog.close()">×</button></div>
      <p v-if="locationsLoading">جارٍ تحميل الفروع…</p>
      <p v-else-if="locationsError">تعذّر تحميل الفروع. <button type="button" @click="loadServiceLocations">إعادة المحاولة</button></p>
      <p v-else-if="!branches.length">لا توجد فروع متاحة حاليًا</p>
      <div v-for="branch in branches" :key="branch.id" class="branch-call-card">
        <img v-if="branch.image" :src="branch.image" :alt="branch.name">
        <div><b>{{ branch.name }}</b><small>{{ branch.address }}</small><a v-if="branch.phone" :href="`tel:${branch.phone}`"><span dir="ltr">{{ branch.phone }}</span> · اتصل الآن</a><small v-else>رقم الهاتف غير متاح حاليًا</small></div>
      </div>
    </dialog>

    <Transition name="toast">
      <div v-if="toast" class="toast" role="status">
        {{ toast }}
      </div>
    </Transition>
  </div>
</template>

<style src="@/assets/styles/about-page.css"></style>

<style>
.about-branch-dialog{position:fixed;inset:0;margin:auto;width:min(420px,calc(100% - 32px));max-height:85dvh;overflow:auto;border:1px solid #d9bc83;border-radius:24px;padding:22px;background:#faf6ef;color:#221f1f;box-shadow:0 24px 80px #0004;direction:inherit;font-family:'Lama Sans',sans-serif}
.about-branch-dialog::backdrop{background:#17110999;backdrop-filter:blur(5px)}
.branch-dialog-head{display:flex;justify-content:space-between;gap:14px;margin-bottom:18px}.branch-dialog-head h2{font-size:19px}.branch-dialog-head p{font-size:12px;color:#8a7b6c;margin-top:7px}.branch-dialog-head button{font-size:28px;align-self:start;padding:0 8px}
.branch-call-card{display:flex;gap:12px;padding:14px;margin-top:10px;background:#fff;border:1px solid #eadfcc;border-radius:16px}.branch-call-card img{width:64px;height:72px;object-fit:cover;border-radius:10px}.branch-call-card small{display:block;font-size:11px;color:#8a7b6c;margin:5px 0}.branch-call-card a{display:block;color:#926319;font-size:13px;padding:8px 0}
#app:has(.about-design-page) > .site-header,
#app:has(.about-design-page) > .global-site-footer {
  display: none !important;
}
@media (min-width: 641px) {
    body {
        padding-top: 0 !important;
    }
}


</style>


