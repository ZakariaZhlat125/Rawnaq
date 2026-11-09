<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { navigation } from '@/data/home'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useLanguage } from '@/composables/useLanguage'
import { useStore } from '@/composables/useStore'

const route = useRoute()
const emit = defineEmits(['open-cart'])
const open = ref(false)
const scrolled = ref(false)
const { current, openPicker } = useServiceLocation()
const { state: lang, toggle: toggleLang, t } = useLanguage()
const { cartQty } = useStore()
const cartBump = ref(false)

/*
  الهيدر بقى نسخة واحدة ثابتة في App.vue، يعني مش بيتفكّك عند الانتقال.
  النتيجة: قائمة الجوال بتفضل مفتوحة بعد الضغط على رابط — فبنقفلها يدويًا.
*/
watch(() => route.fullPath, () => { open.value = false })
watch(cartQty, async (qty, oldQty) => {
  if (qty === oldQty) return
  cartBump.value = false
  await nextTick()
  cartBump.value = true
  window.setTimeout(() => { cartBump.value = false }, 420)
})

const onScroll = () => { scrolled.value = window.scrollY > 8 }
onMounted(() => { onScroll(); window.addEventListener('scroll', onScroll, { passive: true }) })
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <header class="site-header" :class="{ 'is-scrolled': scrolled }">
    <RouterLink class="brand" to="/"><img src="/logo.png" alt="عناية سامي" /></RouterLink>
    <button class="menu-toggle" @click="open=!open" :aria-expanded="open">☰</button>
    <nav :class="{open}">
      <template v-for="item in navigation" :key="item.label">
        <RouterLink v-if="item.to" :to="item.to" @click="open=false">{{ t(item.label) }}</RouterLink>
        <a v-else :href="item.href" @click="open=false">{{ t(item.label) }}</a>
      </template>
    </nav>
    <div class="header-tools">
      <button class="site-loc" type="button" @click="openPicker" :title="current ? current.address : 'اختر مكان تنفيذ الخدمة'">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M21 10c0 7-9 12-9 12S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        <span>{{ current ? current.name : 'اختر المكان' }}</span>
      </button>
      <button class="lang-toggle" type="button" @click="toggleLang"
              :aria-label="lang.lang === 'ar' ? 'Switch to English' : 'التبديل إلى العربية'"
              :title="lang.lang === 'ar' ? 'English' : 'العربية'">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
          <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15 0 18M12 3c-2.5 2.7-2.5 15 0 18"/>
        </svg>
        <b>{{ lang.lang === 'ar' ? 'EN' : 'ع' }}</b>
      </button>
      <button class="icon-btn cart-header-btn" :class="{ bump: cartBump }" type="button"
              :aria-label="`فتح السلة، ${cartQty} منتجات`" @click="emit('open-cart')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1.6"/><circle cx="19" cy="21" r="1.6"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/></svg>
        <span class="count">{{ cartQty }}</span>
      </button>
      <button class="icon-btn" aria-label="برنامج الولاء" title="برنامج الولاء">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8l4.5 3L12 5l4.5 6L21 8l-1.6 10H4.6z"/></svg>
      </button>
      <button class="icon-btn" aria-label="حسابي" title="حسابي">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </button>
      <RouterLink class="nav-book" to="/booking">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        احجز الآن
      </RouterLink>
    </div>
  </header>
</template>

<style scoped>
.header-tools{display:flex;align-items:center;gap:12px}

/* مبدّل اللغة */
.lang-toggle{display:inline-flex;align-items:center;gap:6px;padding:6px 11px;border-radius:999px;
  border:1px solid rgba(255,255,255,.22);background:transparent;color:inherit;cursor:pointer;
  font-family:inherit;line-height:1;transition:border-color .25s ease,background .25s ease}
.lang-toggle:hover{border-color:var(--gold,#c98b31);background:rgba(255,255,255,.07)}
.lang-toggle b{font-size:11.5px;font-weight:800;letter-spacing:.4px}
.lang-toggle svg{flex:none;opacity:.85}
.site-loc{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,.16);color:#EDE3D2;
  padding:9px 14px;border-radius:999px;font-size:13px;background:rgba(255,255,255,.03);transition:.25s;cursor:pointer;
  font-family:inherit;max-width:180px;white-space:nowrap}
.site-loc:hover{border-color:#C6A15B;color:#F0D9A5}
.site-loc span{overflow:hidden;text-overflow:ellipsis}
.icon-btn{position:relative;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;
  border:1px solid rgba(255,255,255,.16);color:#EDE3D2;transition:.25s;background:transparent;cursor:pointer}
.icon-btn:hover{border-color:#C6A15B;color:#F0D9A5}
.icon-btn .count{position:absolute;top:-5px;left:-5px;min-width:18px;height:18px;border-radius:9px;
  background:linear-gradient(135deg,#F0D9A5,#C6A15B);color:#241f1b;
  font-size:10.5px;font-weight:700;display:grid;place-items:center;padding-inline:4px}
.cart-header-btn.bump{animation:headerCartBump .42s ease}
.cart-header-btn.bump .count{animation:headerCountPop .42s ease}
@keyframes headerCartBump{45%{transform:scale(1.12) rotate(-7deg)}}
@keyframes headerCountPop{45%{transform:scale(1.35)}}
.nav-book{display:inline-flex;align-items:center;gap:7px;border:1px solid #C6A15B;color:#F0D9A5;
  border-radius:999px;padding:9px 18px;font-size:13px;white-space:nowrap;transition:.25s}
.nav-book:hover{background:rgba(198,161,91,.15)}
@media(max-width:1100px){.site-loc span{display:none}.site-loc{padding:9px 11px}}
</style>
