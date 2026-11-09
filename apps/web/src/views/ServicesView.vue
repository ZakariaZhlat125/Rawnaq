<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { categoryIconPath } from '@/utils/giftIcons'
import { resolveApiImage } from '@/utils/assetPath'
import { RouterLink, useRouter } from 'vue-router'
import PageSkeleton from '@/components/common/PageSkeleton.vue'
import LocationNotice from '@/components/common/LocationNotice.vue'
import { useLanguage } from '@/composables/useLanguage'
import { serviceDetails } from '@/data/serviceDetails'
import { getCategories } from '@/data/home'
import { localizeField, localizeRecord } from '@/utils/i18nField'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { servicePrice } from '@/utils/servicePrice'

const { state: lang } = useLanguage()
const { current } = useServiceLocation()
const pick = t => localizeField(t, lang.lang)

/*
|--------------------------------------------------------------------------
| Category icons
|--------------------------------------------------------------------------
*/

const CAT_BY_SERVICE = {
  1: 'bath',
  2: 'pedi',
  3: 'hair',
  4: 'skin',
  5: 'mass',
}

const iconOf = id => {
  return categoryIconPath(CAT_BY_SERVICE[id])
}

/*
|--------------------------------------------------------------------------
| API State
|--------------------------------------------------------------------------
*/

const categories = ref([])
const loading = ref(true)
const error = ref(null)

/*
|--------------------------------------------------------------------------
| Load categories from Laravel
|--------------------------------------------------------------------------
*/

let categoryRequest = 0
watch(() => lang.lang, () => loadCategories())
const loadCategories = async () => {
  const request = ++categoryRequest
  try {
    loading.value = true
    error.value = null

    const response = await getCategories()
    if (request !== categoryRequest) return

    console.log('Categories API Response:', response)

    if (response?.status) {
      categories.value = response.data || []
    } else {
      categories.value = []
    }

  } catch (err) {
    if (request !== categoryRequest) return
    console.error('Categories API Error:', err)

    error.value = 'حدث خطأ أثناء تحميل الخدمات'
    categories.value = []

  } finally {
    if (request === categoryRequest) loading.value = false
  }
}

onMounted(() => {
  loadCategories()
})

/*
|--------------------------------------------------------------------------
| Build service cards directly from categories
|--------------------------------------------------------------------------
|
| "الخدمات" هنا هي الأقسام الرئيسية نفسها (حمام مغربي، بديكير، حلاقة...).
| كل قسم بيرجع من /Home/categories ومعاه: image, price_from, durMin, durMax
| (بالإضافة لـ feature_image كـ fallback من الـ media library).
|--------------------------------------------------------------------------
*/

const cards = computed(() =>
  categories.value.map(category => {
    const detail = serviceDetails?.[category.id]

    return {
      id: category.id,

      name: localizeRecord(category, 'name', lang.lang) || detail?.name,

      image:
        resolveApiImage(category.image) ||
        category.feature_image ||
        detail?.heroImage ||
        null,

      tagline:
        localizeRecord(category, 'description', lang.lang) ||
        detail?.tagline ||
        '',

      from: category.services?.length
        ? Math.min(...category.services.map(service => servicePrice(service, current.value)))
        : null,

      durMin: category.durMin ?? null,

      durMax: category.durMax ?? null,

      count: category.services?.length || 0,
    }
  })
)

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

const router = useRouter()

const go = path => {
  router.push(path)
}

/*
|--------------------------------------------------------------------------
| Static Perks
|--------------------------------------------------------------------------
*/

const perks = [
  {
    ic: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    t: 'أعلى معايير النظافة',
    s: 'تعقيم كامل للأدوات قبل وبعد كل جلسة.',
  },

  {
    ic: '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    t: 'مختصون محترفون',
    s: 'فريق مدرّب على أعلى مستوى في كل خدمة.',
  },

  {
    ic: '<path d="M11 20A7 7 0 019.8 6.1C15.5 5 20 9.5 20 15a7 7 0 01-9 5z"/><path d="M2 21c0-9 4-13 13-13"/>',
    t: 'منتجات طبيعية فاخرة',
    s: 'مكونات مختارة بعناية وآمنة على البشرة.',
  },

  {
    ic: '<path d="M6 3h12v4H6z"/><path d="M6 7v10a2 2 0 002 2h8a2 2 0 002-2V7"/>',
    t: 'أجواء راقية وهادئة',
    s: 'خصوصية تامة ومساحة مصممة للاسترخاء.',
  },
]
</script>

<template>
  <div class="home-page services-page" :dir="lang.lang === 'en' ? 'ltr' : 'rtl'">

    <main>

      <!-- Hero -->
      <section class="sv-hero">
        <div class="sv-hero__bg"><img src="/images/generated/packages/complete-care-hq.png" alt="" /></div>
        <svg class="sv-hero__ghost" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".8" aria-hidden="true"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 2v20M3 7l9 5 9-5M3 17l9-5 9 5"/></svg>
        <div class="container sv-hero__in">

          <p class="sv-eyebrow">
            عناية رجالية متكاملة
          </p>

          <h1>
            خدماتنا
          </h1>

          <p class="sv-lead">
            مجموعة خدمات مصممة لتمنحك تجربة عناية فاخرة من أول لحظة —
            اختر الخدمة التي تناسبك واطّلع على تفاصيلها وأنواعها وأسعارها قبل الحجز.
          </p>

        </div>
      </section>


      <!-- Services -->
      <section class="home-section container">

        <div class="section-title">
          <i></i>
          <h2>
            اختر الخدمة المناسبة لك
          </h2>
          <i></i>
        </div>

        <div class="sv-loc">
          <LocationNotice />
        </div>


        <!-- Loading -->
        <PageSkeleton v-if="loading" variant="list" />


        <!-- Error -->
        <div
          v-else-if="error"
          class="sv-state sv-state--error"
        >
          {{ error }}
        </div>


        <!-- Empty -->
        <div
          v-else-if="!cards.length"
          class="sv-state"
        >
          لا توجد خدمات متاحة حالياً.
        </div>


        <!-- Services Grid -->
        <div
          v-else
          class="sv-grid"
        >

          <article
            v-for="c in cards"
            :key="c.id"
            class="sv-card"
          >

            <!-- Image -->
            <div class="sv-card__img">

              <img
                v-if="c.image"
                :src="c.image"
                :alt="c.name"
                loading="lazy"
              />

              <div
                v-else
                class="sv-card__placeholder"
              >
                {{ c.name }}
              </div>

              <span
               
                class="sv-card__badge"
              >
                {{ c.count }} أنواع
              </span>

            </div>


            <!-- Icon -->
            <span
              class="sv-card__ic"
              aria-hidden="true"
            >
              <svg
                viewBox="0 0 24 24"
                v-html="iconOf(c.id)"
              ></svg>
            </span>


            <!-- Body -->
            <div class="sv-card__body">

              <h3>
                {{ c.name }}
              </h3>

              <p class="sv-card__desc">
                {{ c.tagline }}
              </p>


              <!-- Meta -->
              <div class="sv-card__meta">

                <span>
                  🕐
                  {{ c.durMin }}
                  <template v-if="c.durMax">
                    – {{ c.durMax }}
                  </template>
                  دقيقة
                </span>


                <span

                  class="sv-card__price"
                >
                  يبدأ من
                  <b>
                    {{ c.from }} ريال
                  </b>
                </span>

              </div>


              <!-- Action -->
              <div class="sv-card__actions">

                <a
                  href="#"
                  class="sv-btn sv-btn--main"
                  @click.prevent="go(`/services/${c.id}`)"
                >
                  التفاصيل ←
                </a>

              </div>

            </div>

          </article>

        </div>

      </section>


      <!-- Why us -->
      <section class="home-section container">

        <div class="section-title">
          <i></i>
          <h2>
            لماذا تختار رونق؟
          </h2>
          <i></i>
        </div>

        <div class="sv-perks">

          <div
            v-for="(p, i) in perks"
            :key="i"
            class="sv-perk"
          >

            <span
              class="sv-perk__ic"
              v-html="`<svg width='22' height='22' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.6'>${p.ic}</svg>`"
            ></span>

            <b>
              {{ p.t }}
            </b>

            <small>
              {{ p.s }}
            </small>

          </div>

        </div>

      </section>


      <!-- CTA -->
      <section class="sv-cta container">

        <h2>
          جاهز لتجربة عناية مختلفة؟
        </h2>

        <p>
          احجز موعدك الآن واختر الخدمة والوقت الذي يناسبك
        </p>

        <RouterLink
          to="/booking"
          class="button button--gold"
        >
          احجز الآن ←
        </RouterLink>

      </section>

    </main>

  </div>
</template>


<style scoped>

.sv-loc {
  text-align: center;
  margin: -4px 0 22px;
}

.sv-hero {
  position: relative;
  width: min(1280px, 94%);
  min-height: 300px;
  margin: 24px auto 0;
  border-radius: 22px;
  overflow: hidden;
  display: flex;
  align-items: center;
  background: #0d0b07;
  color: #fff;
}

.sv-hero__bg { position:absolute;inset:0 }
.sv-hero__bg img { width:100%;height:100%;object-fit:cover;object-position:75% center;filter:brightness(.85) }
.sv-hero__bg::after { content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(7,18,20,.92) 0%,rgba(7,18,20,.72) 42%,rgba(7,18,20,.1) 100%) }
.sv-hero__ghost { position:absolute;left:5%;top:50%;transform:translateY(-50%);width:220px;opacity:.14;z-index:1;color:var(--gold) }
.sv-hero__in {
  position:relative;
  z-index:2;
  width:100%;
  max-width:none;
  padding:52px;
  text-align:start;
}

.sv-eyebrow {
  color: var(--gold);
  font-size: 12px;
  letter-spacing: 1px;
  margin: 0 0 8px;
}

.sv-hero h1 {
  font-size: 44px;
  margin: 0 0 14px;
}

.sv-lead {
  font-size: 14px;
  line-height: 2;
  color: #cfc7bb;
  max-width: 60ch;
  margin: 0;
  max-width: 44ch;
}


/* States */

.sv-state {
  text-align: center;
  padding: 50px 20px;
  color: var(--muted);
  font-size: 14px;
}

.sv-state--error {
  color: #b42318;
}


/* Services */

.sv-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
}

@media(max-width:1100px) {
  .sv-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media(max-width:720px) {
  .sv-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
  }
}


.sv-card {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: #fff;
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
  transition:
    transform .28s,
    box-shadow .28s;
  position: relative;
}

.sv-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 26px 46px -24px rgba(80,60,20,.45);
}


/* Image */

.sv-card__img {
  position: relative;
  aspect-ratio: 4/3;
  overflow: hidden;
  background: linear-gradient(135deg,#ece4d9,#d5c1a5);
}

.sv-card__img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .6s;
}

.sv-card:hover .sv-card__img img {
  transform: scale(1.05);
}

.sv-card__placeholder {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  color: var(--muted);
  font-size: 13px;
}


.sv-card__badge {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(12,10,8,.72);
  color: var(--gold-light);
  font-size: 10px;
  border-radius: 20px;
  padding: 4px 12px;
  backdrop-filter: blur(3px);
}


/* Icon */

.sv-card__ic {
  position: relative;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  margin: -23px auto 0;
  border-radius: 50%;
  background: #fff;
  border: 1.5px solid rgba(201,139,49,.42);
  color: var(--gold);
  box-shadow: 0 8px 18px -10px rgba(80,60,20,.45);
}

.sv-card__ic svg {
  width: 23px;
  height: 23px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.6;
  stroke-linecap: round;
  stroke-linejoin: round;
}


/* Body */

.sv-card__body {
  padding: 12px 14px 16px;
  display: flex;
  flex-direction: column;
  flex: 1;
  text-align: center;
}

.sv-card__body h3 {
  font-size: 15px;
  margin: 6px 0 6px;
}

.sv-card__desc {
  font-size: 11.5px;
  line-height: 1.85;
  color: var(--muted);
  margin: 0 0 12px;

  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}


/* Meta */

.sv-card__meta {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--muted);
  padding-top: 10px;
  border-top: 1px dashed var(--border);
  margin-top: auto;
}

.sv-card__price b {
  color: var(--gold);
  font-size: 13px;
}


/* Buttons */

.sv-card__actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
  margin-top: 12px;
}

.sv-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 22px;
  padding: 9px 10px;
  font-size: 12px;
  font-weight: 600;
  transition: .22s;
}

.sv-btn--main {
  background: linear-gradient(90deg,#b77420,#f0cf89,#be7920);
  color: #160f07;
}

.sv-btn--main:hover {
  filter: brightness(1.05);
}


/* Perks */

.sv-perks {
  display: grid;
  grid-template-columns: repeat(4,1fr);
  gap: 16px;
}

.sv-perk {
  text-align: center;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 22px 16px;
  background: #fff;
}

.sv-perk__ic {
  display: inline-grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: #f7efe0;
  color: var(--gold);
  margin-bottom: 10px;
}

.sv-perk b {
  display: block;
  font-size: 13px;
  margin-bottom: 5px;
}

.sv-perk small {
  font-size: 11px;
  color: var(--muted);
  line-height: 1.7;
}


/* CTA */

.sv-cta {
  text-align: center;
  background: var(--black);
  color: #fff;
  border-radius: var(--radius);
  padding: 50px 20px;
  margin: 24px auto 44px;
}

.sv-cta h2 {
  color: var(--gold);
  font-size: 27px;
  margin: 0 0 8px;
}

.sv-cta p {
  font-size: 13px;
  color: #cfc7bb;
  margin: 0 0 20px;
}


@media(max-width:900px) {

  .sv-hero__in { padding:32px 24px }

  .sv-hero h1 {
    font-size: 34px;
  }

  .sv-perks {
    grid-template-columns: repeat(2,1fr);
  }

}

</style>
