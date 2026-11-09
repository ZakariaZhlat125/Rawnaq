<script setup>
/*
  شاشة نجاح الطلب — مُرحَّلة حرفيًا من renderSuccess() في src/legacy/store.html
  قواعد الترحيل: لا عناصر غلاف إضافية · الأيقونات عبر SIcon.
*/
import { computed } from 'vue'
import { rs, shapeParts } from '@/utils/storeHelpers'
import SIcon from '@/components/common/SIcon.vue'

const props = defineProps({ order: { type: Object, required: true } })
const emit = defineEmits(['back', 'track'])

const I = {
  qr:     '<path d="M4 4h16v16H4z"/><path d="M9 9h2v2H9zM13 13h2v2h-2z"/>',
  user:   '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  home:   '<path d="M3 9l1-5h16l1 5M4 9v11h16V9M9 20v-6h6v6"/>',
  truck:  '<path d="M1 3h15v13H1zM16 8h4l3 4v4h-7z"/><circle cx="6" cy="19" r="2"/><circle cx="19" cy="19" r="2"/>',
  card:   '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
  check:  '<path d="M20 6L9 17l-5-5"/>',
  lock:   '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
  bag:    '<path d="M6 7h12l1.5 14h-15zM8 7a4 4 0 018 0"/>',
}

const o = computed(() => props.order)
const p = computed(() => props.order.parts)
const isPickup = computed(() => o.value.method === 'pickup')
const itemsCount = computed(() => o.value.items.reduce((a, i) => a + i.q, 0))
const thumb = shape => shapeParts(shape, 34)
</script>

<template>
  <div class="gsuccess" style="max-width:860px;margin-inline:auto">
    <div class="gsuc-head" style="margin-top:20px">
      <h1>تم تأكيد طلبك بنجاح!</h1>
      <span class="ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M20 6L9 17l-5-5"/></svg></span>
    </div>
    <p class="sub">{{ isPickup ? 'سيكون طلبك جاهزًا للاستلام من ' + o.branch.n + ' خلال ساعات العمل' : 'سيصلك طلبك خلال 24-48 ساعة على العنوان المحدد' }} — وسنرسل لك إشعارًا فور تجهيزه ✨</p>
    <div class="gsuc-grid" style="grid-template-columns:1.2fr 1fr">
      <div class="card gsuc-details">
        <h4>تفاصيل الطلب</h4>
        <div class="gs-row"><span class="k"><SIcon :inner="I.qr" :size="14"/> رقم الطلب</span><span class="v" dir="ltr">{{ o.ref }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.user" :size="14"/> العميل</span><span class="v">{{ o.name }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="isPickup ? I.home : I.truck" :size="14"/> {{ isPickup ? 'الاستلام' : 'التوصيل' }}</span>
          <span class="v">{{ isPickup ? o.branch.n : o.addr }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.card" :size="14"/> طريقة الدفع</span><span class="v">{{ o.pay }}</span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.check" :size="14"/> حالة الطلب</span><span class="v"><span class="chip">قيد التجهيز</span></span></div>
        <div class="gs-row"><span class="k"><SIcon :inner="I.lock" :size="14"/> المبلغ {{ o.pay === 'الدفع عند الاستلام' ? 'المستحق' : 'المدفوع' }}</span>
          <span class="v" style="color:var(--gold-deep);font-family:var(--font-d);font-size:17px">{{ rs(p.total) }} ر.س</span></div>
      </div>
      <div class="card gsuc-details">
        <h4>منتجاتك ({{ itemsCount }})</h4>
        <div v-for="({ p: pr, q }) in o.items" :key="pr.id" class="sum-item"><span class="thumb"><svg class="prod" width="34" height="34" :viewBox="thumb(pr.shape).viewBox" v-html="thumb(pr.shape).inner"></svg></span>
          <span><b>{{ pr.n }}</b><small>الكمية: {{ q }}</small></span><span class="p">{{ rs(pr.pr * q) }} ر.س</span></div>
      </div>
    </div>
    <div class="gsuc-actions">
      <button class="btn btn-gold" id="backStore3" @click="emit('back')"><SIcon :inner="I.bag" :size="15"/> متابعة التسوق</button>
      <button class="btn btn-line" id="trackOrder" @click="emit('track')"><SIcon :inner="I.truck" :size="15"/> تتبع الطلب</button>
    </div>
    <div class="thanks"><i>❦</i> شكرًا لثقتك بمنتجات عناية سامي <i>❦</i></div>
  </div>
</template>
