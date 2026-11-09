<script setup>
/*
  اختيار الخدمة أو الباقة — مُرحَّل حرفيًا من view2() في src/legacy/gifts.html

  للدالة الأصلية مساران:
  • gtype === 'svc' → أقسام + خدمات فرعية (اختيار متعدد)
  • gtype === 'pkg' → كاروسيل الباقات (اختيار واحد)
*/
import { computed } from 'vue'
import { CATS, SERVICES, PKGS } from '@/data/gifts'
import { categoryIconPath } from '@/utils/giftIcons'
import { useGifts } from '@/composables/useGifts'
import SIcon from '@/components/common/SIcon.vue'

const { state, hasSvc, toggleSvc, isFav, toggleFav } = useGifts()
const emit = defineEmits(['nav'])

const rs = n => n.toLocaleString('ar-EG-u-nu-latn')

const I = {
  check: '<path d="M20 6L9 17l-5-5"/>',
  heart: '<path d="M12 21s-7-4.4-9.3-8.5C.8 9 2.6 5.5 6 5.5c2 0 3.4 1.1 4 2.3.6-1.2 2-2.3 4-2.3 3.4 0 5.2 3.5 3.3 7-2.3 4.1-9.3 8.5-9.3 8.5z"/>',
  box: '<path d="M20 12v10H4V12M2 7h20v5H2z"/>',
  prev: '<path d="M5 12h14M13 6l6 6-6 6"/>',
  next: '<path d="M19 12H5M11 18l-6-6 6-6"/>',
  chevR: '<path d="M9 6l6 6-6 6"/>',
  chevL: '<path d="M15 6l-6 6 6 6"/>',
}

/* ===== مسار الخدمات ===== */
const CATEGORY_ORDER = ['hair', 'mass', 'skin', 'bath', 'pedi']
const cats = computed(() => CATEGORY_ORDER.map(id => CATS.find(c => c.id === id)))
const hasPicks = catId => SERVICES.some(s => s.cat === catId && hasSvc(s.id))

const activeCat = computed(() => CATS.find(x => x.id === state.activeCat))
const activeList = computed(() => SERVICES.filter(s => s.cat === state.activeCat && !s.hidden))
const selectedCount = computed(() => activeList.value.filter(s => hasSvc(s.id)).length)

/* ===== مسار الباقات — نفس منطق الفرز الأصلي ===== */
const sortedPkgs = computed(() =>
  PKGS.slice().sort((a, b) =>
    state.sort === 'low' ? a.price - b.price
      : state.sort === 'high' ? b.price - a.price
        : (b.hot ? 1 : 0) - (a.hot ? 1 : 0))
)

const HOT_STYLE = 'position:absolute;top:7px;left:34px;background:linear-gradient(135deg,var(--gold-bright),var(--gold));color:var(--ink);font-size:8px;font-weight:700;padding:4px 7px;border-radius:999px;z-index:2'
const INC_STYLE = 'text-align:right;font-size:8.5px;color:var(--mute);margin:5px 0;border-top:1px dashed var(--line);padding-top:6px'
const INC_TITLE_STYLE = 'display:block;color:var(--ink);font-size:9px;margin-bottom:4px'

function scrollCaro(dir) {
  const el = document.getElementById('caro')
  if (el) el.scrollBy({ left: dir * 300, behavior: 'smooth' })
}
</script>

<template>
  <!-- ===== اختيار الخدمات ===== -->
  <template v-if="state.gtype === 'svc'">
    <div class="g-head"><h1>اختر الخدمة التي ترغب بإهدائها</h1><p>يمكنك اختيار خدمة واحدة أو أكثر</p></div>
    <div class="cat-row">
      <div v-for="c in cats" :key="c.id" class="cat"
           :class="{ sel: state.activeCat === c.id, 'has-picks': hasPicks(c.id) }"
           :data-cat="c.id" @click="state.activeCat = c.id">
        <img :src="c.img" :alt="c.name" loading="lazy" />
        <span class="chk"><SIcon :inner="I.check" :size="11" /></span>
        <span class="lbl">{{ c.name }}</span>
      </div>
    </div>

    <div v-if="!state.activeCat" class="empty-hint"><b>اختر خدمة لعرض تفاصيلها</b>اختر إحدى الخدمات الأساسية أعلاه لعرض الخدمات الفرعية المتاحة</div>
    <div v-else class="sub-block">
      <div class="sub-title">
        <span class="sub-title__main">
          خدمات {{ activeCat.name }}
          <span class="tick"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(activeCat.id)"></svg></span>
        </span>
        <small>{{ selectedCount ? selectedCount + ' مختارة' : 'اختر خدمة أو أكثر' }}</small>
      </div>
      <div class="subs">
        <div v-for="s in activeList" :key="s.id" class="sub" :class="{ sel: hasSvc(s.id) }" :data-sv="s.id" @click="toggleSvc(s.id)">
          <div class="top">
            <span class="si"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(activeCat.id)"></svg></span>
            <b>{{ s.name }}</b>
            <span class="chk"><SIcon :inner="I.check" :size="12" /></span>
          </div>
          <small>{{ s.desc }}</small>
          <div class="foot"><span class="dur">🕐 {{ s.dur }} دقيقة</span><span class="prc">{{ rs(s.price) }} <small>ر.س</small></span></div>
        </div>
      </div>
    </div>

    <div class="inline-actions">
      <button class="btn btn-prev" data-nav="back" @click="emit('nav', -1)"><SIcon :inner="I.prev" :size="15" /> رجوع</button>
      <button class="btn btn-gold" data-nav="next" :disabled="!state.svcs.length" @click="emit('nav', 1)">التالي: تخصيص الهدية <SIcon :inner="I.next" :size="15" /></button>
    </div>
  </template>

  <!-- ===== اختيار الباقة ===== -->
  <template v-else>
    <div class="g-head"><h1>اختر الباقة التي ترغب بإهدائها</h1><p>باقات مميزة تمنح تجربة متكاملة من الاسترخاء والعناية</p></div>
    <div class="gp-sort">
      <select id="gpSort" v-model="state.sort">
        <option value="pop">الأكثر طلبًا</option>
        <option value="low">السعر: الأقل أولًا</option>
        <option value="high">السعر: الأعلى أولًا</option>
      </select>
      <small>{{ Math.min(4, PKGS.length) }} باقات متاحة — مرّر لاستعراض المزيد</small>
    </div>
    <div class="caro-wrap">
      <button class="caro-btn r" data-caro="1" @click="scrollCaro(1)"><SIcon :inner="I.chevR" :size="15" /></button>
      <button class="caro-btn l" data-caro="-1" @click="scrollCaro(-1)"><SIcon :inner="I.chevL" :size="15" /></button>
      <div class="caro" id="caro">
        <div v-for="(p, i) in sortedPkgs" :key="p.id" class="gpkg" :class="{ sel: state.pkg === p.id }"
             :data-gp="p.id" :style="`--pc:${p.hex};animation-delay:${i * 0.05}s`" @click="state.pkg = p.id">
          <div class="ph"><img :src="p.img" :alt="p.name" />
            <span v-if="p.hot" :style="HOT_STYLE">الأكثر طلبًا</span>
            <button class="fav" :class="{ on: isFav(p.id) }" :data-fav="p.id" @click.stop="toggleFav(p.id)"><SIcon :inner="I.heart" :size="14" /></button>
            <span class="chk"><SIcon :inner="I.check" :size="13" /></span></div>
          <div class="bd">
            <h4>{{ p.name }}</h4>
            <div class="dur">🕐 {{ p.dur }} دقيقة</div>
            <div class="desc">{{ p.desc }}</div>
            <div :style="INC_STYLE">
              <b :style="INC_TITLE_STYLE">تشمل الباقة</b>
              <div v-for="(x, xi) in p.inc" :key="xi" style="padding:2px 0">✓ {{ x }}</div></div>
            <div class="prc">{{ rs(p.price) }} <small>ر.س</small></div>
            <button class="pick"><SIcon :inner="I.box" :size="14" /> {{ state.pkg === p.id ? 'تم الاختيار ✓' : 'استعرض الباقة' }}</button>
          </div>
        </div>
      </div>
    </div>
    <div class="inline-actions">
      <button class="btn btn-prev" data-nav="back" @click="emit('nav', -1)"><SIcon :inner="I.prev" :size="15" /> رجوع</button>
      <button class="btn btn-gold" data-nav="next" :disabled="!state.pkg" @click="emit('nav', 1)">التالي: تخصيص الهدية <SIcon :inner="I.next" :size="15" /></button>
    </div>
  </template>
</template>
