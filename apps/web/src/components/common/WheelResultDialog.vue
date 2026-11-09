<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue'
import { useLanguage } from '@/composables/useLanguage'
const { state: language } = useLanguage()

const props = defineProps({ message: { type: String, default: '' }, error: Boolean, loginRequired: Boolean })
const emit = defineEmits(['close', 'login'])
const dialog = ref(null)

watch(() => props.message, async message => {
  await nextTick()
  if (message && dialog.value && !dialog.value.open) dialog.value.showModal()
  else if (!message) dialog.value?.close()
}, { immediate: true })

function close() {
  dialog.value?.close()
  emit('close')
}
onBeforeUnmount(() => dialog.value?.close())
</script>

<template>
  <Teleport to="body">
    <dialog ref="dialog" class="wheel-result-dialog" aria-label="نتيجة عجلة الحظ" @cancel.prevent="close" @click.self="close" @keydown.stop>
      <div class="wheel-result-dialog__content" dir="rtl">
        <span class="wheel-result-dialog__icon" aria-hidden="true">{{ error ? 'ⓘ' : '🎁' }}</span>
        <h2>{{ error ? 'تنبيه عجلة الحظ' : 'نتيجة عجلة الحظ' }}</h2>
        <p>{{ message }}</p>
        <p v-if="loginRequired">{{ language.lang === 'en' ? 'Sign in to enjoy your prize. Your reward will be added to your account after signing in.' : 'سجّل دخولك علشان تستمتع بجائزتك، وتُضاف المكافأة إلى حسابك بعد تسجيل الدخول.' }}</p>
        <button v-if="loginRequired" type="button" @click="emit('login')" style="margin-bottom:20px">{{ language.lang === 'en' ? 'Sign in to claim your prize' : 'تسجيل الدخول للاستمتاع بالجائزة' }}</button>
        <button type="button" autofocus @click="close">تمام</button>
      </div>
    </dialog>
  </Teleport>
</template>

<style scoped>
.wheel-result-dialog{position:fixed;inset:0;margin:auto;width:min(420px,calc(100% - 32px));max-height:85dvh;padding:0;border:1px solid #caa565;border-radius:22px;background:#fffaf2;color:#241c12;font-family:'Lama Sans',Arial,sans-serif;box-shadow:0 24px 80px #0006;overflow:auto}
.wheel-result-dialog::backdrop{background:rgba(8,6,4,.72);backdrop-filter:blur(5px)}
.wheel-result-dialog__content{padding:30px 24px;text-align:center}
.wheel-result-dialog__icon{display:block;font-size:48px;margin-bottom:14px}
.wheel-result-dialog h2{font-size:23px;margin:0 0 14px;color:#241c12}
.wheel-result-dialog p{margin:0 0 24px;font-size:16px;line-height:1.9;white-space:pre-line;overflow-wrap:anywhere;color:#65543c}
.wheel-result-dialog button{width:100%;padding:12px 20px;border:0;border-radius:24px;background:linear-gradient(135deg,#e7c784,#c49a50);color:#241c12;font:inherit;font-weight:700;cursor:pointer}
.wheel-result-dialog button:focus-visible{outline:3px solid #8a6428;outline-offset:4px}
</style>
