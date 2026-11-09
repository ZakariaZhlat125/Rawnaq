<script setup>
/*
  سلة التسوق الجانبية — مُرحَّلة من renderDrawer() والماركب الثابت
  للدرج في src/legacy/store.html.

  قواعد الترحيل: لا عناصر غلاف إضافية · الأيقونات عبر SIcon (جذره <svg>)
  · أشكال المنتجات عبر shapeParts لتُرسم كعناصر SVG حقيقية.
*/
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { rs, shapeParts } from '@/utils/storeHelpers'
import { ICON } from '@/utils/storeIcons'
import { useStore } from '@/composables/useStore'
import SIcon from '@/components/common/SIcon.vue'

const props = defineProps({ open: { type: Boolean, default: false } })
const emit = defineEmits(['close', 'checkout'])

const { state, cartQty, cartTotal, cartItems, setQty, removeFromCart } = useStore()
const freeLimit = 200
const freeRemaining = computed(() => Math.max(0, freeLimit - cartTotal.value))
const freeProgress = computed(() => Math.min(100, Math.round((cartTotal.value / freeLimit) * 100)))

watch(() => props.open, open => document.body.classList.toggle('cart-drawer-open', open), { immediate:true })
const closeOnEscape = event => { if (event.key === 'Escape' && props.open) emit('close') }
onMounted(() => window.addEventListener('keydown', closeOnEscape))
onBeforeUnmount(() => {
  document.body.classList.remove('cart-drawer-open')
  window.removeEventListener('keydown', closeOnEscape)
})

const TRASH = '<path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M6 6l1 15h10l1-15M10 11v6M14 11v6"/>'
const LOCK = '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'

const thumb = shape => shapeParts(shape, 44)
</script>

<template>
  <Teleport to="body">
  <div class="global-cart-ov" :class="{ on: open }" @click="emit('close')"></div>
  <aside class="global-cart" :class="{ on: open }" aria-label="سلة التسوق" :aria-hidden="!open">
    <div class="global-cart__head">
      <div class="global-cart__title"><span class="global-cart__bag"><SIcon :inner="ICON.bag" :size="20"/></span>
        <span><b>سلة التسوق</b><small>{{ cartQty ? `${cartQty} منتجات مختارة` : 'جاهزة لاختياراتك' }}</small></span>
      </div>
      <button class="global-cart__close" aria-label="إغلاق السلة" @click="emit('close')">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div v-if="cartItems.length" class="global-cart__reward">
      <div><b v-if="freeRemaining">أضف {{ rs(freeRemaining) }} ر.س لتحصل على تغليف فاخر مجاني</b><b v-else>مبروك! حصلت على التغليف الفاخر مجانًا</b><span>{{ freeProgress }}%</span></div>
      <i><span :style="`width:${freeProgress}%`"></span></i>
    </div>

    <div class="global-cart__body">
      <div v-if="!cartItems.length" class="global-cart__empty">
        <span class="ei"><SIcon :inner="ICON.bag" :size="22"/></span>
        <b>سلتك فارغة الآن</b><p>أضف منتجات العناية المفضلة لديك، وستظهر هنا مباشرة.</p>
        <button @click="emit('close')">متابعة التسوق</button>
      </div>
      <div v-else v-for="(it, i) in cartItems" :key="it.id" class="global-cart__item" :style="`--item-delay:${i * 55}ms`">
        <span class="global-cart__thumb"><svg class="prod" width="52" height="52" :viewBox="thumb(it.shape).viewBox" v-html="thumb(it.shape).inner"></svg></span>
        <span class="global-cart__info"><b>{{ it.n }}</b><small>{{ it.d }}</small><span class="p">{{ rs(it.pr * it.qty) }} ر.س</span>
          <span class="global-cart__qty">
            <button :data-dec="it.id" aria-label="نقص" @click="setQty(it.id, it.qty - 1)">−</button><span>{{ it.qty }}</span><button :data-inc="it.id" aria-label="زيادة" @click="setQty(it.id, it.qty + 1)">+</button>
          </span></span>
        <button class="global-cart__del" :data-del="it.id" aria-label="حذف المنتج" @click="removeFromCart(it.id)"><SIcon :inner="TRASH" :size="15"/></button>
      </div>
    </div>

    <div class="global-cart__foot">
      <template v-if="cartItems.length">
        <div class="global-cart__total"><span><small>الإجمالي</small><em>شامل ضريبة القيمة المضافة</em></span><b>{{ rs(cartTotal) }} <small>ر.س</small></b></div>
        <button class="global-cart__checkout" @click="emit('checkout')"><SIcon :inner="LOCK" :size="15"/> متابعة إتمام الطلب <span>←</span></button>
        <p><SIcon :inner="LOCK" :size="12"/> دفع آمن ومشفّر بالكامل</p>
      </template>
    </div>
  </aside>
  </Teleport>
</template>

<style scoped>
:global(body.cart-drawer-open){overflow:hidden}
.global-cart-ov{position:fixed;inset:0;z-index:1100;background:rgba(8,7,5,.58);backdrop-filter:blur(0);opacity:0;pointer-events:none;transition:opacity .38s ease,backdrop-filter .38s ease}.global-cart-ov.on{opacity:1;pointer-events:auto;backdrop-filter:blur(7px)}
.global-cart{--gc-gold:#c6933e;position:fixed;inset:0 0 0 auto;z-index:1101;width:min(470px,92vw);background:#fffdf9;display:flex;flex-direction:column;box-shadow:-30px 0 80px rgba(0,0,0,.28);transform:translate3d(104%,0,0);opacity:.8;transition:transform .55s cubic-bezier(.16,1,.3,1),opacity .35s ease;direction:rtl;color:#251d13}.global-cart.on{transform:translate3d(0,0,0);opacity:1}
.global-cart__head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px 18px;background:linear-gradient(120deg,#100d09,#21180e);color:#fff;border-bottom:1px solid rgba(198,147,62,.35)}.global-cart__title{display:flex;align-items:center;gap:12px}.global-cart__title>span:last-child{display:flex;flex-direction:column;gap:2px}.global-cart__title b{font-family:var(--font-d,inherit);font-size:20px}.global-cart__title small{font-size:10.5px;color:#d7c5a8}.global-cart__bag{width:43px;height:43px;border-radius:14px;display:grid;place-items:center;color:#21170c;background:linear-gradient(135deg,#f3d691,#b97b27);box-shadow:0 8px 22px rgba(198,147,62,.25)}
.global-cart__close{width:38px;height:38px;border-radius:50%;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);color:#fff;display:grid;place-items:center;cursor:pointer;transition:.3s}.global-cart__close:hover{background:rgba(255,255,255,.14);border-color:var(--gc-gold);transform:rotate(90deg)}
.global-cart__reward{padding:14px 24px 13px;background:#faf3e5;border-bottom:1px solid #eadcc1}.global-cart__reward>div{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px}.global-cart__reward b{font-size:10.5px;font-weight:700;color:#68502a}.global-cart__reward div span{font-size:10px;font-weight:800;color:#a16d23}.global-cart__reward>i{display:block;height:5px;border-radius:99px;background:#e3d5bd;overflow:hidden}.global-cart__reward>i span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#a86b20,#edc775);transition:width .45s cubic-bezier(.22,1,.36,1)}
.global-cart__body{flex:1;overflow:auto;padding:10px 24px;overscroll-behavior:contain}.global-cart__item{position:relative;display:grid;grid-template-columns:76px minmax(0,1fr) 34px;gap:13px;align-items:center;padding:15px 0;border-bottom:1px solid #eee4d4;animation:cartItemIn .5s cubic-bezier(.22,1,.36,1) both;animation-delay:var(--item-delay)}@keyframes cartItemIn{from{opacity:0;transform:translateX(25px)}to{opacity:1;transform:none}}
.global-cart__thumb{width:76px;height:76px;border-radius:16px;display:grid;place-items:center;background:radial-gradient(55px 35px at 50% 78%,rgba(220,176,91,.4),transparent 70%),linear-gradient(145deg,#2a2218,#0d0b08);box-shadow:0 10px 24px -17px #000}.global-cart__info{min-width:0;display:block}.global-cart__info>b{display:block;font-family:var(--font-d,inherit);font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.global-cart__info>small{display:block;color:#968976;font-size:9.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin:3px 0}.global-cart__info .p{display:block;color:#a66e20;font-weight:800;font-size:13px;margin-top:4px}
.global-cart__qty{direction:ltr;display:inline-flex;align-items:center;margin-top:8px;border:1px solid #dfcda9;border-radius:10px;padding:2px;background:#fff}.global-cart__qty button{width:27px;height:27px;border:0;border-radius:8px;background:#f8f1e5;color:#99641c;font-size:17px;line-height:1;cursor:pointer;transition:.2s}.global-cart__qty button:hover{background:#c6933e;color:#fff;transform:scale(1.06)}.global-cart__qty span{min-width:30px;text-align:center;font-size:12px;font-weight:800}.global-cart__del{width:32px;height:32px;border:0;border-radius:10px;background:transparent;color:#a65443;opacity:.6;display:grid;place-items:center;cursor:pointer;transition:.25s}.global-cart__del:hover{opacity:1;background:#fff0ec;transform:translateY(-2px)}
.global-cart__empty{height:100%;min-height:390px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#8d806e}.global-cart__empty .ei{width:80px;height:80px;border-radius:27px;display:grid;place-items:center;margin-bottom:18px;color:#a97429;background:linear-gradient(145deg,#fff,#f3e8d5);box-shadow:0 18px 35px -25px #6e4b1c;animation:emptyBag 3s ease-in-out infinite}@keyframes emptyBag{50%{transform:translateY(-7px) rotate(-2deg)}}.global-cart__empty b{font-family:var(--font-d,inherit);font-size:20px;color:#2d241a}.global-cart__empty p{max-width:270px;font-size:11.5px;line-height:1.9;margin:8px 0 18px}.global-cart__empty button{border:1px solid #c6933e;border-radius:999px;padding:10px 25px;background:#fff;color:#94611d;font-weight:700;cursor:pointer}
.global-cart__foot{padding:18px 24px 20px;border-top:1px solid #e7dbc8;background:#fff;box-shadow:0 -18px 40px -35px #000}.global-cart__total{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:14px}.global-cart__total>span{display:flex;flex-direction:column}.global-cart__total small{font-size:11px;color:#776b5b}.global-cart__total em{font-style:normal;font-size:8.5px;color:#aaa093;margin-top:3px}.global-cart__total>b{font-family:var(--font-d,inherit);font-size:24px;color:#98641d}.global-cart__total>b small{font-size:11px;color:#98641d}.global-cart__checkout{width:100%;height:50px;border:0;border-radius:13px;background:linear-gradient(100deg,#a86b20,#edc775,#b67624);color:#201609;font-weight:800;display:flex;align-items:center;justify-content:center;gap:9px;cursor:pointer;box-shadow:0 13px 28px -17px #6f4210;position:relative;overflow:hidden;transition:.3s}.global-cart__checkout:hover{transform:translateY(-2px);box-shadow:0 18px 30px -17px #6f4210}.global-cart__checkout span{position:absolute;left:18px;font-size:18px;transition:transform .25s}.global-cart__checkout:hover span{transform:translateX(-4px)}.global-cart__foot>p{display:flex;justify-content:center;align-items:center;gap:5px;margin:9px 0 0;color:#9c9183;font-size:9px}
@media(max-width:640px){.global-cart-ov,.global-cart{display:none}}
@media(prefers-reduced-motion:reduce){.global-cart,.global-cart-ov,.global-cart__item,.global-cart__empty .ei{transition:none;animation:none}}
</style>
