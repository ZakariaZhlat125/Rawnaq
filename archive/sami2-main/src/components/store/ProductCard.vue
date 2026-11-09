<script setup>
/*
  بطاقة المنتج — مُرحَّلة حرفيًا من الدالة card(p,i) في src/legacy/store.html

  قواعد اتُّبعت للحفاظ على تطابق DOM:
  • لا عناصر غلاف إضافية — الـ SVG يُرسم كعنصر حقيقي عبر shapeParts.
    (‎.pimg هي display:grid، فأي غلاف كان سيصبح عنصر الشبكة بدل الـ SVG)
  • الأيقونات الصغيرة مكتوبة كـ SVG مباشر بنفس سماتها الأصلية.
  • السمات data-fav / data-add مُبقاة كما كانت في الأصل.
  • العناصر المتجاورة بلا مسافة تبقى متجاورة.
*/
import { computed } from 'vue'
import { rs, shapeParts } from '@/utils/storeHelpers'

const props = defineProps({
  p: { type: Object, required: true },
  index: { type: Number, default: 0 },
  view: { type: String, default: 'grid' },
  fav: { type: Boolean, default: false },
  qty: { type: Number, default: 0 },
})

defineEmits(['toggle-fav', 'add', 'set-qty'])

const delay = computed(() => `animation-delay:${Math.min(props.index * 0.05, 0.4)}s`)
const size = computed(() => (props.view === 'list' ? 96 : 118))
const shape = computed(() => shapeParts(props.p.shape, size.value))
</script>

<template>
  <article class="pcard" :style="delay">
    <div class="pimg"><div class="shelf"></div>
      <span v-if="p.badge" class="badge-tag" :class="{ new: p.new }">{{ p.badge }}</span>
      <button class="fav2" :class="{ on: fav }" :data-fav="p.id" aria-label="مفضلة"
              @click="$emit('toggle-fav', p.id)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M12 21s-7-4.4-9.3-8.5C.8 9 2.6 5.5 6 5.5c2 0 3.4 1.1 4 2.3.6-1.2 2-2.3 4-2.3 3.4 0 5.2 3.5 3.3 7-2.3 4.1-9.3 8.5-9.3 8.5z"/>
        </svg>
      </button>
      <svg class="prod" :width="size" :height="size" :viewBox="shape.viewBox" v-html="shape.inner"></svg>
    </div>
    <div class="pbody">
      <b>{{ p.n }}</b><small>{{ p.d }}</small>
      <div class="prow">
        <!-- قبل الإضافة: زر عادي. بعد الإضافة: عدّاد − / + شغّال -->
        <button v-if="!qty" class="addbtn" :data-add="p.id" @click="$emit('add', p.id, $event)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M6 7h12l1.5 14h-15zM8 7a4 4 0 018 0"/>
          </svg> إضافة للسلة
        </button>
        <div v-else class="qstep" role="group" :aria-label="`كمية ${p.n}`">
          <button class="qstep__btn" :aria-label="qty === 1 ? 'إزالة من السلة' : 'إنقاص الكمية'"
                  @click="$emit('set-qty', p.id, qty - 1)">
            <svg v-if="qty === 1" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
            </svg>
            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path d="M5 12h14"/>
            </svg>
          </button>
          <span class="qstep__val">{{ qty }}</span>
          <button class="qstep__btn" aria-label="زيادة الكمية" @click="$emit('set-qty', p.id, qty + 1)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
              <path d="M12 5v14M5 12h14"/>
            </svg>
          </button>
        </div>
        <span class="pr">{{ rs(p.pr) }} <small>ر.س</small></span>
      </div>
    </div>
  </article>
</template>
