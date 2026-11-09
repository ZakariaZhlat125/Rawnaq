<script setup>
/*
  صفحة مستقلة لكل باقة (/packages/:id) ولكل عرض (/offers/:id).
  الهدف: رابط مباشر يتحط في الإعلانات والتسويق ويفتح على الباقة/العرض نفسه.
  الحجز والإهداء بيكملوا في صفحة الباقات عن طريق ?book=<id> و ?gift=<id>.
*/
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import PackagePrice from '@/components/common/PackagePrice.vue'
import AppImage from '@/components/common/AppImage.vue'
import { fetchCatalogItem } from '@/services/packagesApi'
import { registerPackage } from '@/composables/usePackages'
import { useLanguage } from '@/composables/useLanguage'
import { localizeRecord } from '@/utils/i18nField'

const route = useRoute()
const router = useRouter()
const { state: lang } = useLanguage()

const kind = computed(() => route.meta.kind === 'offer' ? 'offer' : 'package')
const isOffer = computed(() => kind.value === 'offer')
const en = computed(() => lang.lang === 'en')
const t = (ar, english) => en.value ? english : ar

const item = ref(null)
const loading = ref(true)
const notFound = ref(false)
const failed = ref(false)
let request = 0

async function load() {
  const current = ++request
  loading.value = true
  notFound.value = false
  failed.value = false
  try {
    const data = await fetchCatalogItem(kind.value, route.params.id)
    if (current !== request) return
    item.value = data
  } catch (error) {
    if (current !== request) return
    item.value = null
    if (error.status === 404) notFound.value = true
    else failed.value = true
  } finally {
    if (current === request) loading.value = false
  }
}

watch(() => [route.params.id, kind.value, lang.lang], load, { immediate: true })

const name = computed(() => item.value ? localizeRecord(item.value, 'name', lang.lang) : '')
const description = computed(() => item.value ? localizeRecord(item.value, 'description', lang.lang) : '')
const branchName = computed(() => item.value?.branch ? localizeRecord(item.value.branch, 'name', lang.lang) : '')
const services = computed(() => (item.value?.services || []).map(service => ({
  id: service.id,
  name: localizeRecord(service, 'service_name', lang.lang),
  qty: service.qty,
  duration: service.duration_min,
})))
const totalDuration = computed(() => services.value.reduce((sum, s) => sum + (s.duration || 0) * (s.qty || 1), 0))

const fmtDate = value => {
  if (!value) return ''
  const date = new Date(String(value).slice(0, 10) + 'T00:00:00')
  if (Number.isNaN(date.getTime())) return ''
  return new Intl.DateTimeFormat(en.value ? 'en-GB' : 'ar-SA-u-nu-latn', { day: 'numeric', month: 'long', year: 'numeric' }).format(date)
}
const endsAt = computed(() => isOffer.value ? fmtDate(item.value?.end_date) : '')

const listPath = computed(() => isOffer.value ? '/#offers' : '/#packages')

function book() {
  if (!item.value) return
  registerPackage(item.value)
  router.push({ path: '/packages-gifts', query: { book: item.value.id } })
}

function gift() {
  if (!item.value) return
  router.push({ path: '/packages-gifts', query: { gift: item.value.id } })
}

const shareNote = ref('')
async function share() {
  const url = location.href.split('#')[0]
  try {
    if (navigator.share) {
      await navigator.share({ title: name.value, url })
      return
    }
    await navigator.clipboard.writeText(url)
    shareNote.value = t('تم نسخ الرابط', 'Link copied')
  } catch {
    shareNote.value = ''
    return
  }
  setTimeout(() => { shareNote.value = '' }, 2200)
}
</script>

<template>
  <main class="pkg-detail" :dir="en ? 'ltr' : 'rtl'">
    <PageSkeleton v-if="loading" variant="detail" />

    <section v-else-if="notFound || failed" class="pkg-detail__empty container" role="alert">
      <h1>{{ notFound
        ? (isOffer ? t('هذا العرض غير متاح حاليًا', 'This offer is no longer available') : t('هذه الباقة غير متاحة حاليًا', 'This package is not available'))
        : t('تعذر تحميل المحتوى', 'Unable to load content') }}</h1>
      <p v-if="notFound">{{ isOffer ? t('ربما انتهت مدة العرض. تصفح عروضنا الحالية.', 'It may have ended. Browse our current offers.') : t('تصفح باقاتنا المتاحة.', 'Browse our available packages.') }}</p>
      <div class="pkg-detail__actions">
        <button v-if="failed" type="button" class="pkg-btn pkg-btn--gold" @click="load">{{ t('إعادة المحاولة', 'Retry') }}</button>
        <RouterLink class="pkg-btn pkg-btn--line" :to="isOffer ? '/#offers' : '/packages-gifts'">{{ isOffer ? t('العروض الحالية', 'Current offers') : t('كل الباقات', 'All packages') }}</RouterLink>
      </div>
    </section>

    <template v-else-if="item">
      <nav class="pkg-detail__crumbs container" :aria-label="t('مسار التنقل', 'Breadcrumb')">
        <RouterLink to="/">{{ t('الرئيسية', 'Home') }}</RouterLink>
        <span aria-hidden="true">/</span>
        <RouterLink :to="listPath">{{ isOffer ? t('العروض', 'Offers') : t('الباقات', 'Packages') }}</RouterLink>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ name }}</span>
      </nav>

      <article class="pkg-detail__card container">
        <div class="pkg-detail__media">
          <AppImage :src="item.feature_image" :alt="name" />
          <span class="pkg-detail__badge">{{ isOffer ? t('عرض لفترة محدودة', 'Limited-time offer') : t('باقة', 'Package') }}</span>
        </div>

        <div class="pkg-detail__body">
          <h1>{{ name }}</h1>
          <p v-if="endsAt" class="pkg-detail__ends">{{ t('ينتهي العرض في', 'Offer ends on') }} {{ endsAt }}</p>
          <p v-if="description" class="pkg-detail__desc">{{ description }}</p>

          <PackagePrice :price="item.package_price" :savings="item.savings_amount" :original="item.services_total_price" />

          <ul class="pkg-detail__facts">
            <li v-if="branchName"><b>{{ t('الفرع', 'Branch') }}</b>{{ branchName }}</li>
            <li v-if="totalDuration"><b>{{ t('المدة', 'Duration') }}</b>{{ totalDuration }} {{ t('دقيقة', 'min') }}</li>
          </ul>

          <div class="pkg-detail__actions">
            <button type="button" class="pkg-btn pkg-btn--gold" @click="book">{{ isOffer ? t('احجز العرض الآن', 'Book this offer') : t('احجز الباقة الآن', 'Book this package') }}</button>
            <button v-if="!isOffer" type="button" class="pkg-btn pkg-btn--line" @click="gift">{{ t('أهدِ الباقة', 'Gift this package') }}</button>
            <button type="button" class="pkg-btn pkg-btn--ghost" @click="share">{{ t('مشاركة', 'Share') }}</button>
          </div>
          <p class="pkg-detail__note" aria-live="polite">{{ shareNote }}</p>
        </div>
      </article>

      <section v-if="services.length" class="pkg-detail__services container">
        <h2>{{ isOffer ? t('يشمل العرض', 'What’s included') : t('تشمل الباقة', 'What’s included') }}</h2>
        <ul>
          <li v-for="service in services" :key="service.id">
            <span>{{ service.name }}</span>
            <small>
              <template v-if="service.qty > 1">× {{ service.qty }}</template>
              <template v-if="service.duration"> · {{ service.duration }} {{ t('دقيقة', 'min') }}</template>
            </small>
          </li>
        </ul>
      </section>
    </template>
  </main>
</template>

<style scoped>
.pkg-detail{min-height:70vh;background:#f8f4ee;color:#171717;padding:28px 0 64px}
.pkg-detail__crumbs{display:flex;flex-wrap:wrap;gap:8px;font-size:13px;color:#716b64;margin-bottom:18px}
.pkg-detail__crumbs a:hover{color:#1C9384}
.pkg-detail__crumbs [aria-current]{color:#171717;font-weight:600}
.pkg-detail__card{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr);gap:40px;align-items:start;background:#fff;border:1px solid #e4d7c7;border-radius:20px;padding:24px;box-shadow:0 14px 35px #22180d12}
.pkg-detail__media{position:relative;border-radius:16px;overflow:hidden;aspect-ratio:1;background:#f2ece4}
.pkg-detail__media :deep(.image-frame){width:100%;height:100%}
.pkg-detail__media :deep(img){width:100%;height:100%;object-fit:cover;display:block}
.pkg-detail__badge{position:absolute;top:14px;inset-inline-start:14px;background:#080a0b;color:#f2d28e;font-size:12px;font-weight:600;padding:6px 14px;border-radius:20px}
.pkg-detail__body h1{font-size:32px;line-height:1.4;margin:6px 0 8px}
.pkg-detail__ends{display:inline-block;margin:0 0 10px;background:#fbefd9;color:#8a5a14;border-radius:10px;padding:5px 12px;font-size:13px;font-weight:600}
.pkg-detail__desc{font-size:15px;line-height:2;color:#3a342e;margin:0 0 6px;white-space:pre-line}
.pkg-detail__facts{list-style:none;padding:0;margin:4px 0 18px;display:flex;flex-wrap:wrap;gap:10px}
.pkg-detail__facts li{background:#f8f4ee;border:1px solid #e4d7c7;border-radius:12px;padding:8px 14px;font-size:13px}
.pkg-detail__facts b{display:block;font-size:11px;color:#716b64;font-weight:500}
.pkg-detail__actions{display:flex;flex-wrap:wrap;gap:10px}
.pkg-btn{border:0;cursor:pointer;border-radius:30px;padding:12px 28px;font:inherit;font-weight:600;font-size:14px;display:inline-flex;align-items:center;justify-content:center;transition:transform .2s}
.pkg-btn:hover{transform:translateY(-2px)}
.pkg-btn--gold{background:linear-gradient(90deg,#b77420,#f0cf89,#be7920);color:#160f07}
.pkg-btn--line{background:transparent;border:1px solid #1C9384;color:#8a5a14}
.pkg-btn--ghost{background:#f2ece4;color:#3a342e}
.pkg-detail__note{min-height:20px;margin:8px 0 0;font-size:13px;color:#26714d}
.pkg-detail__services{margin-top:28px}
.pkg-detail__services h2{font-size:22px;margin:0 0 12px}
.pkg-detail__services ul{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px}
.pkg-detail__services li{display:flex;justify-content:space-between;align-items:center;gap:10px;background:#fff;border:1px solid #e4d7c7;border-radius:12px;padding:12px 16px;font-size:14px}
.pkg-detail__services small{color:#716b64;white-space:nowrap}
.pkg-detail__empty{text-align:center;padding:80px 0}
.pkg-detail__empty h1{font-size:26px}
.pkg-detail__empty .pkg-detail__actions{justify-content:center}
@media(max-width:800px){
  .pkg-detail{padding-top:16px}
  .pkg-detail__card{grid-template-columns:1fr;gap:18px;padding:14px}
  .pkg-detail__body h1{font-size:24px}
  .pkg-btn{flex:1 1 100%}
}
</style>
