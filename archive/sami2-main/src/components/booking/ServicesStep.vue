<script setup>
/*
  اختيار الخدمات — مُرحَّل حرفيًا من view2() في src/legacy/booking.html
  أيقونات الأقسام مشتركة مع صفحة الإهداء (مسارات متطابقة).
*/
import { computed } from 'vue'
import { BRANCHES, CATS, SERVICES } from '@/data/booking'
import { categoryIconPath } from '@/utils/giftIcons'
import { useBooking, catOf, rs } from '@/composables/useBooking'

const { state, hasSvc, toggleSvc } = useBooking()

const CATEGORY_ORDER = ['hair', 'mass', 'skin', 'bath', 'pedi']
const cats = computed(() => CATEGORY_ORDER.map(id => catOf(id)).filter(Boolean))
const hasPicks = catId => SERVICES.some(s => s.cat === catId && hasSvc(s.id))

const br = computed(() => BRANCHES.find(b => b.id === state.branch))
const activeCat = computed(() => catOf(state.activeCat))
const list = computed(() => SERVICES.filter(s => s.cat === state.activeCat && !s.hidden))
const selN = computed(() => list.value.filter(s => hasSvc(s.id)).length)
</script>

<template>
  <div class="panel-head"><h1>اختر الخدمة التي تناسبك</h1><p>يمكنك اختيار خدمة واحدة أو أكثر من أي قسم</p></div>

  <!-- ملاحظة صغيرة على سطر واحد بدل الكرت الكبير -->
  <div v-if="br" class="branch-strip">
    <img :src="br.img" :alt="br.name" />
    <span class="bi">مكان التنفيذ: <b>{{ br.name }}</b><small>{{ br.addr }}</small></span>
    <span class="cells"><span class="cell">{{ br.hours }}</span></span>
  </div>

  <div class="cat-row">
    <div v-for="c in cats" :key="c.id" class="cat"
         :class="{ sel: state.activeCat === c.id, 'has-picks': hasPicks(c.id) }" :data-cat="c.id"
         @click="state.activeCat = c.id">
      <img :src="c.img" :alt="c.name" />
      <span class="chk"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
      <span class="lbl"><span class="cat-name">{{ c.name }}</span><span class="cat-icon"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(c.id)"></svg></span></span>
    </div>
  </div>

  <div v-if="!state.activeCat" class="empty-hint"><b>اختر خدمة لعرض تفاصيلها</b>اختر إحدى الخدمات الأساسية أعلاه لعرض الخدمات الفرعية المتاحة</div>
  <div v-else class="sub-block">
    <div class="sub-title">
      <span class="sub-title__main">
        خدمات {{ activeCat.name }}
        <span class="tick"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(activeCat.id)"></svg></span>
      </span>
      <small>{{ selN ? selN + ' مختارة' : 'اختر خدمة أو أكثر' }}</small>
    </div>
    <div class="subs">
      <div v-for="s in list" :key="s.id" class="sub" :class="{ sel: hasSvc(s.id) }" :data-svc="s.id" @click="toggleSvc(s.id)">
        <div class="top">
          <span class="si"><svg viewBox="0 0 24 24" aria-hidden="true" v-html="categoryIconPath(activeCat.id)"></svg></span>
          <b>{{ s.name }}</b>
          <span class="chk"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg></span>
        </div>
        <small>{{ s.desc }}</small>
        <div class="foot"><span class="dur">🕐 {{ s.dur }} دقيقة</span><span class="prc">{{ rs(s.price) }} <small>ر.س</small></span></div>
      </div>
    </div>
  </div>
</template>
