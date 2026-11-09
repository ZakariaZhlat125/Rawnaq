<script setup>
import { servicePrice } from '@/utils/servicePrice'
import FavoriteButton from '@/components/common/FavoriteButton.vue'
import { ref, computed, onMounted } from 'vue'
import { getCategories } from '@/data/home'
import { categoryAccent, categoryIconKey, categoryIconPath } from '@/utils/giftIcons'
import { resolveApiImage } from '@/utils/assetPath'
import { useServiceLocation } from '@/composables/useServiceLocation'
import { useLanguage } from '@/composables/useLanguage'
import { useBooking, rs } from '@/composables/useBooking'
import { localizeField, localizeRecord, translationSource } from '@/utils/i18nField'
import Skeleton from '@/components/common/SkeletonLoader.vue'

const { state, hasSvc, toggleSvc } = useBooking()
const { current, openPicker } = useServiceLocation()
const { state: lang } = useLanguage()
const pick = t => localizeField(t, lang.lang)

const categories = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await getCategories()
    categories.value = response?.status ? (response.data || []) : []
  } catch {
    categories.value = []
  } finally {
    loading.value = false
  }
})

const cats = computed(() => categories.value.map(c => ({
  id: c.id,
  name: localizeRecord(c, 'name', lang.lang),
  image: resolveApiImage(c.image) || c.feature_image || null,
})))

const activeCategory = computed(() => categories.value.find(c => c.id === state.activeCat) || null)
const activeCategoryName = computed(() => localizeRecord(activeCategory.value, 'name', lang.lang))
const activeCategoryIcon = computed(() => categoryIconKey(activeCategory.value))

const list = computed(() =>
  (activeCategory.value?.services || []).map(s => ({
    id: s.id,
    categoryId: activeCategory.value.id,
    categoryNameT: translationSource(activeCategory.value, 'name'),
    get categoryName() { return pick(this.categoryNameT) },
    icon: activeCategoryIcon.value,
    get name() { return localizeRecord(s, 'name', lang.lang) },
    get desc() { return localizeRecord(s, 'description', lang.lang) },
    dur: s.duration_min,
    default_price: s.default_price,
    branches: s.branches,
    price: servicePrice(s, current.value),
  }))
)

const hasPicks = catId => state.services.some(s => s.categoryId === catId)
const selN = computed(() => list.value.filter(s => hasSvc(s.id)).length)
const serviceStyle = service => ({ '--acc': categoryAccent(service.icon) })
const serviceIcon = service => categoryIconPath(service.icon)
</script>

<template>
  <div class="panel-head"><h1>اختر الخدمة التي تناسبك</h1><p>يمكنك اختيار خدمة واحدة أو أكثر من أي قسم</p></div>

  <div class="branch-strip">
    <img v-if="current?.image" :src="current.image" :alt="current.name" />
    <span class="bi">
      <template v-if="current">مكان التنفيذ: <b>{{ current.name }}</b></template>
      <template v-else>يرجى اختيار الفرع لعرض أسعار الخدمات وحجز موعدك</template>
      <button type="button" class="branch-picker-link" @click="openPicker()">{{ current ? 'تغيير الفرع' : 'اختيار الفرع' }}</button>
      <small v-if="current">{{ current.address }}</small>
    </span>
  </div>

  <div v-if="loading" class="empty-hint" style="display:grid;gap:10px">
    <Skeleton height="64px" border-radius="12px" />
    <Skeleton height="92px" border-radius="12px" />
  </div>

  <template v-else>
    <div class="cat-row">
      <div v-for="c in cats" :key="c.id" class="cat"
           :class="{ sel: state.activeCat === c.id, 'has-picks': hasPicks(c.id) }" :data-cat="c.id"
           @click="state.activeCat = c.id">
        <img v-if="c.image" :src="c.image" :alt="c.name" />
        <span class="chk"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
        <span class="lbl"><span class="cat-name">{{ c.name }}</span></span>
      </div>
    </div>

    <div v-if="!state.activeCat" class="empty-hint"><b>اختر خدمة لعرض تفاصيلها</b>اختر إحدى الخدمات الأساسية أعلاه لعرض الخدمات الفرعية المتاحة</div>
    <div v-else class="sub-block">
      <div class="sub-title">
        <span class="sub-title__main">
          خدمات {{ activeCategoryName }}
          <span class="tick"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(activeCategoryIcon)"></svg></span>
        </span>
        <small>{{ selN ? selN + ' مختارة' : 'اختر خدمة أو أكثر' }}</small>
      </div>
      <div v-if="!list.length" class="empty-hint">لا توجد خدمات متاحة حاليًا ضمن هذا القسم</div>
      <div v-else class="subs">
        <div v-for="s in list" :key="s.id" class="sub" :class="{ sel: hasSvc(s.id) }" :data-svc="s.id" :style="serviceStyle(s)" @click="toggleSvc(s)">
          <FavoriteButton type="service" :id="s.id" />
          <div class="top">
            <span class="si"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="serviceIcon(s)"></svg></span>
            <b>{{ s.name }}</b>
            <span class="chk"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
          </div>
          <small>{{ s.desc }}</small>
          <div class="foot"><span class="dur">🕐 {{ s.dur }} دقيقة</span><span class="prc">{{ rs(s.price) }} <small>ر.س</small></span></div>
        </div>
      </div>
    </div>
  </template>
</template>

<style scoped>
.branch-strip { flex-wrap: wrap; gap: 12px; }
.branch-picker-link { display: inline; margin-inline-start: 8px; padding: 0; border: 0; border-radius: 0; background: none; color: #855911; font: inherit; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
</style>
