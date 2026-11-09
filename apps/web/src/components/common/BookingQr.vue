<script setup>
import { ref, watch, onMounted } from 'vue'
import QRCode from '@/vendor/qrcode'

const props = defineProps({ url: { type: String, required: true }, showLink: { type: Boolean, default: true } })
const host = ref(null)
const failed = ref(false)
function draw() {
  if (!host.value) return
  host.value.replaceChildren()
  failed.value = false
  try {
    new QRCode(host.value, { text: props.url, width: 320, height: 320, correctLevel: QRCode.CorrectLevel.M })
  } catch {
    failed.value = true
  }
}
onMounted(draw)
watch(() => props.url, draw)
</script>

<template>
  <div class="booking-qr">
    <div v-show="!failed" ref="host" class="booking-qr-image" role="img" aria-label="رمز QR لفتح بيانات الحجز"></div>
    <p v-if="failed">تعذّر عرض الرمز. يمكنك فتح بيانات الحجز من الرابط.</p>
    <a v-if="showLink" :href="url" target="_blank" rel="noopener">فتح تفاصيل الحجز</a>
    <small>امسح الرمز بالكاميرا لعرض بيانات الحجز</small>
  </div>
</template>

<style scoped>
.booking-qr{text-align:center;margin:16px auto;max-width:360px}
.booking-qr-image{background:#fff;padding:20px;width:100%;box-sizing:border-box;border:1px solid #e4d7c7;border-radius:12px}
.booking-qr-image :deep(img),.booking-qr-image :deep(canvas){width:100%;height:auto;image-rendering:pixelated}
.booking-qr a{display:inline-block;margin-top:12px;text-decoration:underline;color:#8f7134;font-size:14px}
.booking-qr small{display:block;margin-top:8px;color:#766b58;font-size:12px}
</style>
