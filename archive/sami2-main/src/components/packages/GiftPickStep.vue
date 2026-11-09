<script setup>
/*
  اختيار الباقة للإهداء — مُرحَّل حرفيًا من gView1() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { PKGS } from '@/data/packages'
import { usePackages, rs } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state } = usePackages()

const I = {
  check: '<path d="M20 6L9 17l-5-5"/>',
  gift:  '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
}

/* نفس منطق الفرز الأصلي */
const list = computed(() =>
  PKGS.slice().sort((a, b) =>
    state.sort === 'low' ? a.price - b.price
      : state.sort === 'high' ? b.price - a.price
        : (b.hot ? 1 : 0) - (a.hot ? 1 : 0))
)
</script>

<template>
  <div class="g-head"><h1>اختر الباقة التي ترغب بإهدائها</h1><p>باقات مميزة تمنح تجربة متكاملة من الاسترخاء والعناية</p></div>
  <div class="gp-sort">
    <select id="gpSort" v-model="state.sort">
      <option value="pop">الأكثر مبيعًا</option>
      <option value="low">السعر: الأقل أولًا</option>
      <option value="high">السعر: الأعلى أولًا</option>
    </select>
    <small>{{ PKGS.length }} باقات متاحة</small>
  </div>
  <div class="gpkgs">
    <div v-for="(p, i) in list" :key="p.id" class="gpkg" :class="{ sel: state.gpkg === p.id }"
         :data-gp="p.id" :style="`--pc:${p.color};animation-delay:${i * 0.06}s`" @click="state.gpkg = p.id">
      <div class="ph"><img :src="p.img" :alt="p.name" /><span class="chk"><SIcon :inner="I.check" :size="13" /></span></div>
      <div class="bd">
        <h4>{{ p.name }}</h4>
        <div class="dur">🕐 {{ p.dur }} دقيقة</div>
        <div class="desc">{{ p.desc }}</div>
        <div class="prc">{{ rs(p.price) }} <small>ر.س</small></div>
        <button class="pick"><SIcon :inner="I.gift" :size="14" /> {{ state.gpkg === p.id ? 'تم الاختيار ✓' : 'أهدِ هذه الباقة' }}</button>
      </div>
    </div>
  </div>
</template>
