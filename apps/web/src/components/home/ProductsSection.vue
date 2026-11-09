<script setup>
import { useLanguage } from '@/composables/useLanguage'
import { localizeRecord } from '@/utils/i18nField'
const { state: language } = useLanguage()
const nameOf = item => localizeRecord(item, 'name', language.lang)
import ProductStock from '@/components/common/ProductStock.vue'
import { ref } from 'vue'
import SectionTitle from '@/components/common/SectionTitle.vue'
import AppImage from '@/components/common/AppImage.vue'
import CatalogCarousel from '@/components/common/CatalogCarousel.vue'
import Skeleton from '@/components/common/SkeletonLoader.vue'
import { useStore } from '@/composables/useStore'
import { fetchProductDetail } from '@/services/productsApi'

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
})


const { state, setQty } = useStore()

const pendingProductId = ref(null)
const cartMessage = ref('')

const currentQty = id => state.cart[id] || 0

async function changeHomeQty(product, delta) {
    const current = currentQty(product.id)
    const nextQty = current + delta

    if (nextQty <= 0) {
        setQty(product.id, 0)
        return
    }

    if (pendingProductId.value === product.id) return

    pendingProductId.value = product.id
    cartMessage.value = ''

    try {
        const detail = await fetchProductDetail(product.id)
        const variation = detail?.variation_data?.[0]

        const availableQty = Number(
            variation?.product_stock_qty ??
            detail?.stock_qty ??
            product.stock_qty ??
            0
        )

        if (availableQty <= 0) {
            cartMessage.value = 'هذا المنتج غير متوفر حاليًا'
            return
        }

        if (nextQty > availableQty) {
            cartMessage.value = `الكمية المتاحة فقط ${availableQty}`
            return
        }

        setQty(product.id, nextQty)
    } catch (error) {
        console.error('Product quantity error:', error)
        cartMessage.value = 'تعذر تعديل الكمية'
    } finally {
        pendingProductId.value = null
    }
}


const formatPrice = value => `${value ?? 0} ريال`

</script>

<template>
    <section data-reveal id="products" class="catalog-section">
        <SectionTitle title="منتجات العناية" />

        <!-- Skeleton Loading -->
        <div v-if="loading" class="product-grid">
            <article v-for="n in 3" :key="n" data-reveal
                style="display:flex;flex-direction:column;align-items:center;gap:8px;min-height:230px">
                <Skeleton width="100%" height="145px" border-radius="8px" />
                <Skeleton width="75%" height="13px" variant="text" />
                <Skeleton width="50%" height="13px" variant="text" />
            </article>
        </div>

        <CatalogCarousel v-else label="منتجات العناية">
        <div class="product-grid">
            <article v-for="item in products" :key="item.id" data-reveal>
                <RouterLink to="/store">

                    <AppImage :src="item.product_image" :alt="nameOf(item)" />

                    <h3>{{ nameOf(item) }}</h3>

                </RouterLink>

                <b>
                    {{ formatPrice(item.max_price) }}
                </b>

                <ProductStock :quantity="item.stock_qty" />
                <div v-if="currentQty(item.id) === 0">
                    <button type="button" class="product-cart-link" :disabled="pendingProductId === item.id"
                        @click="changeHomeQty(item, 1)">
                        🛒
                    </button>
                </div>

                <div v-else class="home-qty-control">
                    <button type="button" aria-label="زيادة الكمية" :disabled="pendingProductId === item.id"
                        @click="changeHomeQty(item, 1)">
                        +
                    </button>

                    <input type="number" :value="currentQty(item.id)" readonly aria-label="الكمية" />

                    <button type="button" aria-label="إنقاص الكمية" @click="changeHomeQty(item, -1)">
                        −
                    </button>
                </div>

            </article>
        </div>
        </CatalogCarousel>

        <p v-if="cartMessage" class="home-cart-message">
            {{ cartMessage }}
        </p>

        <RouterLink class="show-all" to="/store">
            عرض جميع المنتجات ←
        </RouterLink>

    </section>

    <!-- Toast -->
    <Transition name="toast-fade">
        <div v-if="toastOn" class="product-toast">{{ toastMsg }}</div>
    </Transition>
</template>

<style scoped>
/* ===== Quantity Controls ===== */

.product-qty-controls {
    position: absolute;
    right: 8px;
    bottom: 8px;
    display: flex;
    align-items: center;
    gap: 0;
    background: linear-gradient(135deg, #1a1408, #2c1e0a);
    border-radius: 22px;
    padding: 3px;
    box-shadow: 0 4px 14px -4px rgba(0,0,0,.35);
    animation: qtyFadeIn .25s ease-out;
}

@keyframes qtyFadeIn {
    from { opacity: 0; transform: scale(.85); }
    to   { opacity: 1; transform: scale(1); }
}

.qty-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background .2s, transform .15s;
}

.qty-btn--plus {
    background: linear-gradient(135deg, #dda745, #a96818);
    color: #fff;
}

.qty-btn--plus:hover {
    filter: brightness(1.1);
    transform: scale(1.08);
}

.qty-btn--minus {
    background: rgba(255,255,255,.12);
    color: #e8d5b5;
}

.qty-btn--minus:hover {
    background: rgba(255,80,80,.25);
    color: #ff9090;
    transform: scale(1.08);
}

.qty-value {
    min-width: 26px;
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    color: #f0cf89;
    font-family: var(--font-d, sans-serif);
    user-select: none;
}


/* ===== Out of Stock ===== */

.product-out-badge {
    position: absolute;
    right: 8px;
    bottom: 8px;
    background: rgba(180, 35, 24, .85);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
    backdrop-filter: blur(3px);
    letter-spacing: .3px;
}


/* ===== Disabled Plus ===== */

.qty-btn--disabled,
.qty-btn--disabled:hover {
    opacity: .35;
    cursor: not-allowed;
    filter: none;
    transform: none;
}


/* ===== Toast ===== */

.product-toast {
    position: fixed;
    bottom: 90px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(12, 10, 8, .88);
    color: #f0cf89;
    font-size: 13px;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 26px;
    z-index: 9999;
    backdrop-filter: blur(6px);
    box-shadow: 0 8px 28px -8px rgba(0,0,0,.4);
    white-space: nowrap;
}

.toast-fade-enter-active { transition: all .25s ease-out; }
.toast-fade-leave-active { transition: all .2s ease-in; }
.toast-fade-enter-from,
.toast-fade-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(12px);
}
</style>