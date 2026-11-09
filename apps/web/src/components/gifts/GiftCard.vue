<script setup>
import { computed } from 'vue'
import { useGiftDesigns } from '@/composables/useGiftDesigns'

const props = defineProps({
  design: { type: String, default: 'lux-dark' },
  recipient: { type: String, default: '' },
  sender: { type: String, default: '' },
  message: { type: String, default: '' },
})

const { designOf } = useGiftDesigns()
const d = computed(() => designOf(props.design))
/* تصميم بصورة مرفوعة من لوحة التحكم: الصورة فوق التدرّج */
const bgStyle = computed(() => d.value.image
  ? `background:${d.value.bg};background-image:url('${encodeURI(d.value.image)}');background-size:cover;background-position:center;color:${d.value.fg}`
  : `background:${d.value.bg};color:${d.value.fg}`)
</script>

<template>
  <div class="gcard royal" :style="bgStyle">
    <div class="royal-frame"></div>
    <div class="royal-glow"></div>
    <svg class="bow" viewBox="0 0 64 40" fill="none" stroke="currentColor" stroke-width="2"><path d="M32 30C18 30 10 24 12 15s16-5 20 8c4-13 18-17 20-8s-6 15-20 15z"/><path d="M28 30h8v8h-8z"/></svg>
    <div class="leaves">
      <svg style="bottom:8px;right:10px" width="78" height="78" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".85"><path d="M4 20C6 12 12 6 20 4c-2 8-8 14-16 16zM4 20c4-2 8-6 10-10"/></svg>
      <svg style="top:14px;left:10px;transform:rotate(180deg)" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width=".85"><path d="M4 20C6 12 12 6 20 4c-2 8-8 14-16 16z"/></svg>
    </div>
    <div class="royal-seal">{{ d.icon || '✦' }}</div>
    <div class="lg"><img src="/logo.png" style="height:24px;vertical-align:middle" alt="" /> SAMI <small>رونق</small></div>
    <h3 style="visibility:hidden" aria-hidden="true">{{ d.n }}</h3>
    <div class="tg">هدية فاخرة بتجربة عناية ملكية</div>
    <div class="hr" :style="`background:${d.fg}`"></div>
    <div v-if="recipient || sender || message" class="gift-card-personal">
      <b v-if="recipient">إلى: {{ recipient }}</b>
      <p>{{ message || 'اكتب رسالتك الشخصية لتظهر هنا' }}</p>
      <small v-if="sender">من: {{ sender }}</small>
    </div>
  </div>
</template>

<style scoped>
.gift-card-personal{position:relative;z-index:3;width:min(88%,420px);margin:12px auto 0;padding:12px 14px;border:1px solid currentColor;border-radius:13px;background:rgba(0,0,0,.14);backdrop-filter:blur(3px);text-align:center}
.gift-card-personal b{display:block;font-size:13px;margin-bottom:6px}
.gift-card-personal p{margin:0;white-space:pre-line;overflow-wrap:anywhere;font-size:12px;line-height:1.75;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
.gift-card-personal small{display:block;margin-top:7px;color:inherit;font-size:10px;opacity:.88}
@media(max-width:640px){.gift-card-personal{width:92%;padding:10px 11px}.gift-card-personal p{font-size:11px}}
</style>