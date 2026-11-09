<script setup>
/*
  كاروسيل لصفوف الرئيسية (الباقات، العروض، المنتجات).
  المحتوى نفسه (package-grid / product-grid) بيتحط في الـ slot، والكاروسيل
  بيخفي شريط التمرير ويضيف أسهم ونقاط وتقليب تلقائي هادي بيقف عند المرور بالماوس.
*/
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  autoplay: { type: Number, default: 5000 },
  label: { type: String, default: '' },
})

const root = ref(null)
const pages = ref(1)
const page = ref(0)
let track = null
let timer = null
let paused = false
let resizeObserver = null
let mutationObserver = null

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
/* في RTL قيمة scrollLeft بتبقى سالبة، فبنشتغل بالقيمة المطلقة ونعكس الاتجاه */
const sign = () => (getComputedStyle(track).direction === 'rtl' ? -1 : 1)

function measure() {
  if (!track) return
  const max = track.scrollWidth - track.clientWidth
  pages.value = max > 2 ? Math.ceil(track.scrollWidth / track.clientWidth - 0.05) : 1
  const progress = max > 0 ? Math.abs(track.scrollLeft) / max : 0
  page.value = Math.min(pages.value - 1, Math.round(progress * (pages.value - 1)))
}

function goTo(index) {
  if (!track) return
  const max = track.scrollWidth - track.clientWidth
  const target = pages.value > 1 ? (max * index) / (pages.value - 1) : 0
  track.scrollTo({ left: sign() * target, behavior: reduceMotion() ? 'auto' : 'smooth' })
}

const next = () => goTo(page.value >= pages.value - 1 ? 0 : page.value + 1)
const prev = () => goTo(page.value <= 0 ? pages.value - 1 : page.value - 1)

function restart() {
  clearInterval(timer)
  if (!props.autoplay || reduceMotion()) return
  timer = setInterval(() => {
    if (!paused && pages.value > 1 && document.visibilityState === 'visible') next()
  }, props.autoplay)
}

const pause = () => { paused = true }
const resume = () => { paused = false }

onMounted(async () => {
  await nextTick()
  track = root.value?.querySelector('.package-grid, .product-grid')
  if (!track) return
  track.classList.add('carousel-track')
  track.addEventListener('scroll', measure, { passive: true })
  resizeObserver = new ResizeObserver(measure)
  resizeObserver.observe(track)
  /* المحتوى بيتبدّل من الهيكل المؤقت للبيانات الحقيقية — نعيد الحساب */
  mutationObserver = new MutationObserver(() => requestAnimationFrame(measure))
  mutationObserver.observe(track, { childList: true })
  measure()
  restart()
})

onBeforeUnmount(() => {
  clearInterval(timer)
  track?.removeEventListener('scroll', measure)
  resizeObserver?.disconnect()
  mutationObserver?.disconnect()
})
</script>

<template>
  <div
    ref="root"
    class="catalog-carousel"
    role="region"
    aria-roledescription="carousel"
    :aria-label="label || undefined"
    @mouseenter="pause"
    @mouseleave="resume"
    @focusin="pause"
    @focusout="resume"
    @touchstart.passive="pause"
  >
    <slot />
    <template v-if="pages > 1">
      <button type="button" class="catalog-carousel__arrow catalog-carousel__arrow--prev" aria-label="السابق" @click="prev">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" /></svg>
      </button>
      <button type="button" class="catalog-carousel__arrow catalog-carousel__arrow--next" aria-label="التالي" @click="next">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" /></svg>
      </button>
      <div class="catalog-carousel__dots">
        <button
          v-for="n in pages"
          :key="n"
          type="button"
          :class="{ on: n - 1 === page }"
          :aria-label="`الصفحة ${n}`"
          :aria-current="n - 1 === page ? 'true' : undefined"
          @click="goTo(n - 1)"
        ></button>
      </div>
    </template>
  </div>
</template>

<style>
.catalog-carousel{position:relative}
.catalog-carousel .carousel-track{overflow-x:auto!important;overflow-y:hidden!important;scrollbar-width:none;-ms-overflow-style:none;scroll-behavior:smooth;align-items:stretch!important}
.catalog-carousel .carousel-track::-webkit-scrollbar{display:none}
.catalog-carousel__arrow{position:absolute;top:50%;transform:translateY(-70%);z-index:3;width:42px;height:42px;border-radius:50%;border:1px solid #dfcfba;background:#fffdf9;color:#8a5a14;display:grid;place-items:center;cursor:pointer;box-shadow:0 8px 20px #22180d1f;transition:background .2s,color .2s,transform .2s}
.catalog-carousel__arrow:hover{background:#1C9384;color:#fff}
.catalog-carousel__arrow svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
/* "السابق" على يمين الصف في العربي (بداية القراءة) و"التالي" على الشمال */
[dir="rtl"] .catalog-carousel__arrow--prev{right:-20px}
[dir="rtl"] .catalog-carousel__arrow--next{left:-20px}
[dir="ltr"] .catalog-carousel__arrow--prev{left:-20px}
[dir="ltr"] .catalog-carousel__arrow--next{right:-20px}
[dir="ltr"] .catalog-carousel__arrow svg{transform:scaleX(-1)}
.catalog-carousel__dots{display:flex;justify-content:center;gap:7px;margin-top:6px}
.catalog-carousel__dots button{width:8px;height:8px;padding:0;border:0;border-radius:8px;background:#dccbb3;cursor:pointer;transition:width .25s,background .25s}
.catalog-carousel__dots button.on{width:24px;background:#1C9384}
@media(max-width:700px){.catalog-carousel__arrow{display:none}}
</style>
