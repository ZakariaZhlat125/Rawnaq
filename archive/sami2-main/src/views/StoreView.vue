<script setup>
/*
  صفحة المتجر — مُرحَّلة بالكامل من src/legacy/store.html إلى مكوّنات Vue.

  البنية:
    StoreCatalog  → renderStore()
    StoreCheckout → renderCheckout() + bindCheckout()
    StoreSuccess  → renderSuccess()
    CartDrawer    → renderDrawer()

  الحالة كلها في composables/useStore.js، والأنماط تُحقن عبر usePageStyles
  (راجع docs/ARCHITECTURE.md لسبب عدم استخدام <style scoped>).
*/
import { ref, nextTick, watch } from 'vue'
import { usePageStyles } from '@/composables/usePageStyles'
import { useInternalLinks } from '@/composables/useInternalLinks'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useStore } from '@/composables/useStore'
import { rs, shapeParts } from '@/utils/storeHelpers'
import pageCss from '@/assets/styles/pages/store.css?raw'

import StoreCatalog from '@/components/store/StoreCatalog.vue'
import StoreCheckout from '@/components/store/StoreCheckout.vue'
import StoreSuccess from '@/components/store/StoreSuccess.vue'
import CartDrawer from '@/components/store/CartDrawer.vue'

const root = ref(null)
const cartCountEl = ref(null)
const cartBtnEl = ref(null)

const { current, openPicker } = useServiceLocation()
const { state, cartQty, pOf } = useStore()

usePageStyles(pageCss, 'store')
useInternalLinks(root)

/* ===== حالات الواجهة ===== */
const drawerOpen = ref(false)
const sheetOpen = ref(false)
const sheetProduct = ref(null)
const payLoading = ref(false)
const toastMsg = ref('')
const toastOn = ref(false)

function toastTop(msg) {
  toastMsg.value = msg
  toastOn.value = true
  clearTimeout(toastTop._h)
  toastTop._h = setTimeout(() => { toastOn.value = false }, 2000)
}

/* نبض عدّاد السلة — نفس سلوك bumpCart() الأصلي */
function bumpCart() {
  const c = cartCountEl.value, b = cartBtnEl.value
  if (!c || !b) return
  c.classList.remove('pop'); void c.offsetWidth; c.classList.add('pop')
  b.classList.remove('shake'); void b.offsetWidth; b.classList.add('shake')
  setTimeout(() => c.classList.remove('pop'), 350)
}

/* إضافة للسلة — نفس منطق addToCart() بما فيه أنيميشن الزر ولوحة أول إضافة */
function onAdd(id, ev) {
  state.cart[id] = (state.cart[id] || 0) + 1
  nextTick(bumpCart)

  /*
    الزر بيتحوّل لعدّاد − / + فور الإضافة، فالتأكيد بقى ظاهر في الواجهة نفسها.
    شِلنا تعديل innerHTML اليدوي القديم لأنه كان بيتصارع مع إعادة رسم Vue.
  */

  if (state.firstAdd) {
    state.firstAdd = false
    sheetProduct.value = pOf(id)
    sheetOpen.value = true
  } else {
    toastTop('تمت إضافة المنتج للسلة')
  }
}

function openDrawer() { drawerOpen.value = true }
function closeDrawer() { drawerOpen.value = false }

function goCheckout() {
  closeDrawer()
  state.page = 'checkout'
  state.ck.pay = null
  state.ck.terms = false
  scrollTo({ top: 0, behavior: 'smooth' })
}

function backToStore() { state.page = 'store'; scrollTo({ top: 0 }) }

/* إتمام الطلب — نفس التأخير الأصلي (2100ms) قبل شاشة النجاح */
function onPlaced(commit) {
  payLoading.value = true
  setTimeout(() => {
    payLoading.value = false
    commit()
    scrollTo({ top: 0, behavior: 'smooth' })
  }, 2100)
}

function scrollToGrid() {
  const g = document.getElementById('pgrid')
  if (g) g.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

/* حماية: لا يمكن الوصول للدفع بسلة فارغة — نفس شرط render() الأصلي */
watch(() => [state.page, cartQty.value], () => {
  if (state.page === 'checkout' && !cartQty.value) state.page = 'store'
})

const sheetThumb = () => sheetProduct.value ? shapeParts(sheetProduct.value.shape, 46) : null
</script>

<template>
  <div ref="root">
    <div class="shell">
      <div class="wrap" id="storeApp">
        <StoreCatalog v-if="state.page === 'store'" @scroll-to-grid="scrollToGrid" @add="onAdd" />
        <StoreCheckout v-else-if="state.page === 'checkout'" @back="backToStore" @placed="onPlaced" />
        <StoreSuccess v-else-if="state.page === 'success' && state.order" :order="state.order"
                      @back="backToStore" @track="toastTop('حالة طلبك ' + state.order.ref + ': قيد التجهيز')" />
      </div>
    </div>

<footer id="contact" class="site-footer">
  <div class="footer-content container">
    <div>
      <h3>تواصل معنا</h3>
      <form class="phone-form" onsubmit="return false"><span>+966</span><input type="tel" placeholder="رقم الجوال" /></form>
      <a class="whatsapp" href="https://wa.me/966566101963">تواصل واتساب ◉</a>
    </div>
    <div>
      <h3>عن سامي</h3>
      <a href="/">الرئيسية</a><a href="/booking">خدماتنا</a><a href="/store">المنتجات</a><a href="/packages-gifts">الباقات</a>
    </div>
    <div class="footer-brand"><a href="/"><img src="/logo.png" alt="عناية سامي" /></a></div>
    <div>
      <h3>مساعدة</h3>
      <a href="/contact">اتصل معنا</a><a href="#">الشكاوى والاقتراحات</a><a href="#">سياسة الخصوصية</a><a href="#">الشروط والأحكام</a>
    </div>
    <div>
      <h3>عناوين الفروع</h3>
      <b>الفرع الأول</b><p>جدة - الحمدانية، شارع خالد<br />0566101963</p>
      <b>الفرع الثاني</b><p>جدة - الروابي، شارع باريس<br />0530064642</p>
    </div>
  </div>
  <p class="copyright">© 2026 عناية سامي. جميع الحقوق محفوظة.</p>
</footer>

    <!-- لوحة تأكيد الإضافة -->
    <div class="sheet-ov" :class="{ on: sheetOpen }" id="sheetOv" @click="sheetOpen = false"></div>
    <div class="sheet" :class="{ on: sheetOpen }" id="sheet">
      <div class="grab"></div>
      <div class="shead">
        <span class="si"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 7h12l1.5 14h-15zM8 7a4 4 0 018 0"/><path d="M9.5 13.5l1.8 1.8 3.4-3.4"/></svg></span>
        <b>تم إضافة المنتج</b>
      </div>
      <div class="sprod" id="sheetProd">
        <template v-if="sheetProduct">
          <span class="thumb"><svg class="prod" width="46" height="46" :viewBox="sheetThumb().viewBox" v-html="sheetThumb().inner"></svg></span>
          <span style="flex:1"><b>{{ sheetProduct.n }}</b><span class="p">{{ rs(sheetProduct.pr) }} ر.س</span></span>
        </template>
      </div>
      <div class="sacts">
        <button class="btn btn-line" id="sheetCont" @click="sheetOpen = false">متابعة التسوق</button>
        <button class="btn btn-gold" id="sheetView" @click="sheetOpen = false; openDrawer()">عرض السلة</button>
      </div>
    </div>

    <CartDrawer :open="drawerOpen" @close="closeDrawer" @checkout="goCheckout" />

    <div class="pay-loading" :class="{ on: payLoading }" id="payLoading">
      <div class="pl-box"><div class="pl-ring"></div><b>جارٍ معالجة طلبك بأمان…</b><small>لا تغلق الصفحة، سيتم تأكيد طلبك خلال لحظات</small></div>
    </div>

    <div class="toast-top" :class="{ on: toastOn }" id="toastTop">
      <span class="ti"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span id="toastTopMsg">{{ toastMsg }}</span>
    </div>
  </div>
</template>
