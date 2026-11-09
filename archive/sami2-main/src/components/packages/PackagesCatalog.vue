<script setup>
/*
  كتالوج الباقات — مُرحَّل حرفيًا من viewPackages() في src/legacy/packages-gifts.html
*/
import { computed } from 'vue'
import { FILTERS, BRANCHES } from '@/data/packages'
import { usePackages } from '@/composables/usePackages'
import SIcon from '@/components/common/SIcon.vue'

const { state, filteredPkgs } = usePackages()
const emit = defineEmits(['book', 'gift', 'gift-now', 'pick-branch'])

const rs = n => n.toLocaleString('ar-EG-u-nu-latn')

const branchName = computed(() => {
  const b = BRANCHES.find(x => x.id === state.siteBranch)
  return b ? b.name : ''
})

const gridCols = computed(() => `grid-template-columns:repeat(${Math.min(filteredPkgs.value.length, 5)},1fr)`)

const I = {
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
  gift:  '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7s-2-5-5-5-3 5 0 5h5zM12 7s2-5 5-5 3 5 0 5h-5z"/>',
}

const MINI_FEATS = [
  ['بطاقة إهداء رقمية', '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>'],
  ['رسالة مخصصة من قلبك', '<path d="M21 11.5a8.4 8.4 0 01-9 8.4 8.5 8.5 0 01-3.8-.9L3 21l2-5.2a8.4 8.4 0 011.5-9.8 8.5 8.5 0 0114.5 5.5z"/>'],
  ['إرسال فوري عبر واتساب أو بريد', '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>'],
  ['جدولة الإهداء في الوقت المناسب', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'],
]

const PERKS = [
  ['غرفة VIP خاصة', 'لباقات مختارة', '<path d="M2 8l4 4 6-8 6 8 4-4v10a2 2 0 01-2 2H4a2 2 0 01-2-2z"/>'],
  ['منتجات فاخرة', 'ضمن باقاتك', '<path d="M20 12v9H4v-9M2 7h20v5H2z"/>'],
  ['أولوية في الحجز', 'مواعيد مرنة', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M9 16l2 2 4-4"/>'],
  ['ضيافة فاخرة', 'مشروبات مختارة', '<path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4z"/>'],
  ['نقاط ولاء مضاعفة', 'مع كل باقة', '<path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7"/>'],
  ['ضمان الجودة', 'أفضل تجربة', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'],
]
</script>

<template>
  <section class="pk-hero">
    <div class="bg"><img src="/images/generated/packages/complete-care-hq.png" alt="" /></div>
    <svg class="ghost-logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".8"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 2v20M3 7l9 5 9-5M3 17l9-5 9 5"/></svg>
    <div class="in">
      <div class="eyebrow">تجارب متكاملة</div>
      <h1>لراحتـك وأناقتـك</h1>
      <p>اختر الباقة التي تناسب احتياجاتك واستمتع بتجربة عناية متكاملة توفر عليك الوقت والجهد</p>
    </div>
  </section>

  <div class="branch-gate">
    <template v-if="state.siteBranch">
      <span>📍 الفرع المختار: <b>{{ branchName }}</b></span><button id="branchGateChange" @click="emit('pick-branch')">تغيير الفرع</button>
    </template>
    <template v-else>
      <span>حدد فرعك أولًا لعرض الأسعار والمواعيد المتاحة بدقة</span><button id="branchGateChange" class="primary" @click="emit('pick-branch')">اختر الفرع</button>
    </template>
  </div>

  <div class="filters">
    <button v-for="f in FILTERS" :key="f.id" class="flt" :class="{ on: state.filter === f.id }" :data-f="f.id" @click="state.filter = f.id"><SIcon :inner="f.ic" :size="16" /> {{ f.n }}</button>
  </div>

  <div class="pkgs" :style="gridCols">
    <article v-for="(p, i) in filteredPkgs" :key="p.id" class="pkg" :class="{ hot: p.hot }" :style="`--pc:${p.color};animation-delay:${i * 0.07}s`">
      <div class="ph"><img :src="p.img" :alt="p.name" />
        <template v-if="p.hot"><span class="hotflag">الأكثر طلبًا</span><span class="starflag">★</span></template>
      </div>
      <span class="badge" :style="`border-color:${p.hex}`"><SIcon :inner="p.ico" :size="22" /></span>
      <div class="body">
        <h3>{{ p.name }}</h3>
        <div class="dur"><SIcon :inner="I.clock" :size="13" /> {{ p.dur }} دقيقة</div>
        <div class="desc">{{ p.desc }}</div>
        <div class="inc"><b>تشمل الباقة</b><ul><li v-for="(x, xi) in p.inc" :key="xi"><i>✓</i>{{ x }}</li></ul></div>
        <div class="price">{{ rs(p.price) }} <small>ر.س</small></div>
        <div class="acts">
          <button class="book" :data-book="p.id" @click="emit('book', p.id)">احجز الباقة</button>
          <button class="gift-mini" :data-gift="p.id" @click="emit('gift', p.id)"><SIcon :inner="I.gift" :size="14" /> أهدِ هذه الباقة</button>
        </div>
      </div>
    </article>
  </div>

  <section class="gift-banner">
    <div class="gb-box"><div class="face"></div><span class="tag">هدية لكم من القلب 💛</span></div>
    <div class="txt">
      <h2>أهدِ تجربة فاخرة لمن تحب</h2>
      <p>اختر الباقة، أضف رسالة مخصصة، وسيصلك المهدى إليه بشكل أنيق في الوقت المناسب</p>
      <div class="mini-feats">
        <div v-for="(m, i) in MINI_FEATS" :key="i" class="mf"><span class="mi"><SIcon :inner="m[1]" :size="16" /></span>{{ m[0] }}</div>
      </div>
    </div>
    <div class="cta"><button class="btn btn-gold" id="giftNow" @click="emit('gift-now')"><SIcon :inner="I.gift" :size="16" /> أهدِ باقة الآن</button></div>
  </section>

  <div class="perks">
    <div v-for="(p, i) in PERKS" :key="i" class="perk"><span class="pi"><SIcon :inner="p[2]" :size="17" /></span><b>{{ p[0] }}</b><small>{{ p[1] }}</small></div>
  </div>
</template>
