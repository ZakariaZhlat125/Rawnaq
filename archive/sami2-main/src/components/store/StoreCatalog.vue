<script setup>
/*
  كتالوج المتجر — مُرحَّل حرفيًا من renderStore() في src/legacy/store.html

  قواعد الترحيل المتَّبعة:
  • لا عناصر غلاف إضافية (راجع docs/ARCHITECTURE.md)
  • الأيقونات عبر SIcon الذي جذره <svg> نفسه
  • أشكال المنتجات عبر shapeParts لتُرسم كعناصر SVG حقيقية
*/
import { computed } from 'vue'
import { CATS, PRODUCTS, STORE_PROMOS } from '@/data/store'
import { shapeParts } from '@/utils/storeHelpers'
import { ICON } from '@/utils/storeIcons'
import { useStore } from '@/composables/useStore'
import SIcon from '@/components/common/SIcon.vue'
import ProductCard from './ProductCard.vue'

const { state, isFav, toggleFav, visibleProducts, setQty } = useStore()

const qtyOf = id => state.cart[id] || 0

/* نفس ترتيب الأصل: CATS.slice(1).concat(CATS[0]) */
const catList = computed(() => CATS.slice(1).concat(CATS[0]))

/* عدد المنتجات بعد التصفية قبل القص — للعدّاد وزر "عرض الكل" */
const filteredCount = computed(() => {
  const l = PRODUCTS.filter(p => state.cat === 'all' || p.cat === state.cat)
  return l.length
})

const bestProducts = computed(() => PRODUCTS.filter(p => p.best))

/* الجزيئات المتحركة في الهيرو — نفس المعادلة الأصلية */
const particles = computed(() =>
  Array.from({ length: 7 }, (_, i) => ({
    ch: i % 3 ? '✦' : '❖',
    style: `top:${10 + ((i * 13) % 75)}%;${i % 2 ? 'right' : 'left'}:${4 + ((i * 9) % 42)}%;animation-delay:-${i * 0.7}s`,
  }))
)

const catShape = id => {
  const c = CATS.find(x => x.id === id)
  return shapeParts(c.shape, 52)
}

const PERKS = [
  ['جودة مختارة بعناية', 'منتجات أصلية 100%', ICON.star],
  ['تغليف فاخر', 'اهتمام بكل تفاصيل الطلب', ICON.box],
  ['استلام من الفرع', 'جاهزة للاستلام من أقرب فرع', ICON.home],
  ['إهداء سهل', 'أرسل المنتج أو الباقة كهدية', ICON.chat],
]

const emit = defineEmits(['scroll-to-grid', 'add'])

function pickCat(id) { state.cat = id; state.showAll = false }
function toggleShowAll() { state.showAll = !state.showAll }
function setView(v) { state.view = v }
</script>

<template>
  <section class="st-hero" id="stHero">
    <svg class="veins" viewBox="0 0 800 430" preserveAspectRatio="none" fill="none">
      <path d="M-20 340 C160 300 240 380 420 330 S 700 250 830 300" stroke="rgba(198,161,91,.14)" stroke-width="1.2"/>
      <path d="M-20 120 C120 160 300 60 460 110 S 720 190 830 140" stroke="rgba(198,161,91,.1)" stroke-width="1"/>
      <path d="M100 430 C180 340 320 420 430 350" stroke="rgba(233,207,142,.08)" stroke-width="1"/>
    </svg>
    <div class="hero-frame"></div>
    <span v-for="(pt, i) in particles" :key="i" class="hp" :style="pt.style">{{ pt.ch }}</span>
    <div class="in">
      <span class="eyebrow2"><i></i> منتجات مختارة بعناية — جودة الصالون في منزلك</span>
      <h1>لتجربة يومية<br /><span class="g">استثنائيــــة</span></h1>
      <p>منتجات فاخرة بجودة عالية لرجل يهتم بأدق التفاصيل — من العناية بالشعر واللحية إلى العطور الفاخرة.</p>
      <div class="hero-ctas">
        <button class="btn btn-gold" id="heroCta" @click="emit('scroll-to-grid')">اكتشف المجموعة <SIcon :inner="ICON.arrowL" :size="15"/></button>
        <button class="btn btn-outline-l" id="heroBest" @click="emit('scroll-to-grid')">الأكثر مبيعًا</button>
      </div>
      <div class="hero-stats">
        <div class="hstat"><b>+30</b><small>منتج عناية فاخر</small></div>
        <div class="hstat"><b>100%</b><small>منتجات أصلية</small></div>
        <div class="hstat"><b>4.9 ★</b><small>تقييم عملائنا</small></div>
      </div>
    </div>
    <div class="hero-scene" id="heroScene">
      <div class="glow"></div><div class="ring2"></div>
      <div class="hp-card hp-c1" data-depth="16">
        <img src="https://sami-care.sa//storage/uploads/406/uGGoLhrbcMofuyS2kedJ44ejvBqYnz1M0mA0zTMP.jpg" alt="زيت اللحية الذهبي" loading="eager"
             onerror="this.style.display='none';this.nextElementSibling.style.display='grid'" />
        <div class="fb"><svg class="prod" width="90" height="90" :viewBox="shapeParts('dropper',90).viewBox" v-html="shapeParts('dropper',90).inner"></svg></div>
        <span class="tag2">زيت اللحية الذهبي</span>
      </div>
      <div class="hp-card hp-c2" data-depth="26">
        <img src="https://sami-care.sa//storage/uploads/403/HyxbPuOgHBgbg0pgpMZr9bcFv2OryQtxjRMLnhxb.jpg" alt="كريم الترطيب"
             onerror="this.style.display='none';this.nextElementSibling.style.display='grid'" />
        <div class="fb"><svg class="prod" width="70" height="70" :viewBox="shapeParts('jar',70).viewBox" v-html="shapeParts('jar',70).inner"></svg></div>
        <span class="tag2">كريم الترطيب</span>
      </div>
      <div class="hp-card hp-c3" data-depth="24">
        <img src="https://sami-care.sa//storage/uploads/405/L2sxjQjZgTztP0swgRTlXV0jVjCLuS5GJyypCJmT.jpg" alt="غسول البشرة"
             onerror="this.style.display='none';this.nextElementSibling.style.display='grid'" />
        <div class="fb"><svg class="prod" width="70" height="70" :viewBox="shapeParts('pump',70).viewBox" v-html="shapeParts('pump',70).inner"></svg></div>
        <span class="tag2">غسول البشرة</span>
      </div>
      <div class="hp-badge hp-b1" data-depth="34">
        <span class="bi2"><SIcon :inner="ICON.star" :size="16"/></span>
        <span><b>منتجات أصلية 100%</b><small>مختارة من عناية سامي</small></span>
      </div>
      <div class="hp-badge hp-b2" data-depth="30">
        <span class="bi2"><SIcon :inner="ICON.box" :size="16"/></span>
        <span><b>تغليف فاخر</b><small>اهتمام بكل تفاصيل الطلب</small></span>
      </div>
    </div>
  </section>
  <div class="st-strip">
    <span class="si"><SIcon :inner="ICON.bag" :size="19"/></span>
    <p>منتجات مختارة خصيصًا لتكمل تجربة عناية سامي داخل المنزل</p>
    <button class="btn btn-line" id="stripCta" style="padding:11px 24px;font-size:12.5px" @click="emit('scroll-to-grid')">تسوق المجموعة الكاملة <SIcon :inner="ICON.arrowL" :size="13"/></button>
  </div>
  <div class="sec-line"><h2>تسوق حسب الفئة</h2><a data-cat="all" @click="pickCat('all')"><SIcon :inner="ICON.arrowL" :size="13"/> عرض جميع الفئات</a></div>
  <div class="cats">
    <div v-for="c in catList" :key="c.id" class="catc" :class="{ on: state.cat === c.id }" :data-cat="c.id" @click="pickCat(c.id)">
      <span class="ic-mini"><SIcon :inner="ICON.star" :size="12"/></span>
      <div class="ill" style="background:radial-gradient(70px 40px at 50% 85%,rgba(198,161,91,.28),transparent 65%),linear-gradient(150deg,#2A2318,#0F0C07);border-radius:11px"><svg class="prod" width="52" height="52" :viewBox="catShape(c.id).viewBox" v-html="catShape(c.id).inner"></svg></div>
      <b>{{ c.n }}</b>
    </div>
  </div>
  <div v-if="STORE_PROMOS.length" class="store-promos">
    <div v-for="pr in STORE_PROMOS" :key="pr.id" class="store-promo">
      <span class="store-promo__badge">{{ pr.badge }}</span>
      <div class="store-promo__txt"><h3>{{ pr.title }}</h3><p>{{ pr.text }}</p></div>
      <button class="btn btn-gold" :data-promo="pr.id" @click="emit('scroll-to-grid')">{{ pr.cta }} <SIcon :inner="ICON.arrowL" :size="13"/></button>
    </div>
  </div>
  <div class="sec-line"><h2>الأكثر مبيعًا</h2><a data-showall="1" @click="state.showAll = true"><SIcon :inner="ICON.arrowL" :size="13"/> عرض جميع المنتجات</a></div>
  <div class="best-wrap">
    <button class="caro-btn r" data-caro="1"><SIcon :inner="ICON.chevR" :size="15"/></button>
    <button class="caro-btn l" data-caro="-1"><SIcon :inner="ICON.chevL" :size="15"/></button>
    <div class="best-caro" id="bestCaro">
      <ProductCard v-for="(p, i) in bestProducts" :key="p.id" :p="p" :index="i" :view="state.view"
                   :fav="isFav(p.id)" :qty="qtyOf(p.id)" @toggle-fav="toggleFav"
                   @add="(id, ev) => emit('add', id, ev)" @set-qty="setQty"/>
    </div>
  </div>
  <div class="sec-line"><h2>منتجات عناية سامي</h2></div>
  <div class="grid-tools">
    <button class="gt-filter"><SIcon :inner="ICON.filter" :size="15"/> تصفية المنتجات</button>
    <span class="gt-count">عرض 1 - {{ visibleProducts.length }} من {{ filteredCount }} منتج</span>
    <div class="view-tg">
      <button :class="{ on: state.view === 'grid' }" data-view="grid" aria-label="شبكة" @click="setView('grid')"><SIcon :inner="ICON.grid" :size="15"/></button>
      <button :class="{ on: state.view === 'list' }" data-view="list" aria-label="قائمة" @click="setView('list')"><SIcon :inner="ICON.list" :size="15"/></button>
    </div>
    <div class="gt-sort">ترتيب
      <select id="sortSel" v-model="state.sort">
        <option value="new">الأحدث</option>
        <option value="best">الأكثر مبيعًا</option>
        <option value="low">السعر: الأقل أولًا</option>
        <option value="high">السعر: الأعلى أولًا</option>
      </select>
    </div>
  </div>
  <div class="pgrid" :class="{ list: state.view === 'list' }" id="pgrid">
    <ProductCard v-for="(p, i) in visibleProducts" :key="p.id" :p="p" :index="i" :view="state.view"
                 :fav="isFav(p.id)" :qty="qtyOf(p.id)" @toggle-fav="toggleFav"
                   @add="(id, ev) => emit('add', id, ev)" @set-qty="setQty"/>
  </div>
  <div v-if="filteredCount > 8" class="show-more">
    <button id="moreBtn" @click="toggleShowAll">
      <template v-if="state.showAll">إخفاء المنتجات <SIcon :inner="ICON.chevUp" :size="14"/></template>
      <template v-else>عرض جميع المنتجات ({{ filteredCount }}) <SIcon :inner="ICON.chevDown" :size="14"/></template>
    </button>
  </div>
  <div class="perks" style="grid-template-columns:repeat(4,1fr);margin-top:26px">
    <div v-for="(pk, i) in PERKS" :key="i" class="perk">
      <span class="pi"><SIcon :inner="pk[2]" :size="17"/></span><b>{{ pk[0] }}</b><small>{{ pk[1] }}</small>
    </div>
  </div>
</template>
