<script setup>
import { onBeforeUnmount, ref } from 'vue'
import LuckyWheelCard from '@/components/home/LuckyWheelCard.vue'
import { useSiteContact } from '@/composables/useSiteContact'
const wheelOpen = ref(false)
const mobileLang = ref('ar')
const { whatsappUrl } = useSiteContact()
const closeWheel = () => { wheelOpen.value = false }
const onKeydown = event => { if (event.key === 'Escape') closeWheel() }
const onMessage = event => { if (event.data?.type === 'sami:language') mobileLang.value = event.data.lang === 'en' ? 'en' : 'ar' }
if (typeof window !== 'undefined') { window.addEventListener('keydown', onKeydown); window.addEventListener('message', onMessage) }
onBeforeUnmount(() => { window.removeEventListener('keydown', onKeydown); window.removeEventListener('message', onMessage) })
</script>
<template>
<div class="floating-contact-actions" :class="{ mobileEnglish: mobileLang === 'en' }" aria-label="روابط التواصل السريعة">
<a class="floating-action whatsapp-action" v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener noreferrer" aria-label="إرسال رسالة عبر واتساب" title="واتساب"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 1.9 17.7L.3 23.5l5.9-1.6A11.7 11.7 0 0 0 12 23.4h.1A11.8 11.8 0 0 0 20.5 3.5Zm-8.4 17.9h-.1a9.7 9.7 0 0 1-5-1.4l-.4-.2-3.5.9.9-3.4-.2-.4a9.8 9.8 0 1 1 8.3 4.5Zm5.4-7.3c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.2l-.9 1.1c-.2.2-.4.2-.7.1-1.8-.9-3-1.7-4.2-3.8-.3-.5.3-.5.9-1.6.1-.2.1-.4 0-.6l-.9-2c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.6c.2.2 2.4 3.7 5.9 5.2 2.2.9 3.1 1 4.2.8 1.3-.2 1.7-.8 1.9-1.6.2-.8.2-1.4.1-1.6-.1-.2-.3-.3-.6-.4Z"/></svg></a>
<button class="floating-action wheel-action" type="button" aria-label="فتح عجلة الحظ" title="عجلة الحظ" @click="wheelOpen = true"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4"/><circle cx="12" cy="12" r="2.2"/></svg></button>
</div>
<Teleport to="body"><Transition name="floating-wheel"><div v-if="wheelOpen" class="floating-wheel-modal" role="dialog" aria-modal="true" aria-label="عجلة الحظ" @click.self="closeWheel"><div class="floating-wheel-panel"><button class="floating-wheel-close" type="button" aria-label="إغلاق" @click="closeWheel">×</button><LuckyWheelCard class="floating-wheel-content" /></div></div></Transition></Teleport>
</template>
<style scoped>
.floating-contact-actions{position:fixed;z-index:880;left:24px;bottom:28px;display:flex;flex-direction:column;gap:11px}:global(html[dir="rtl"]) .floating-contact-actions{left:auto;right:24px}.floating-action{width:58px;height:58px;border:0;border-radius:50%;display:grid;place-items:center;color:#fff;cursor:pointer;box-shadow:0 10px 26px rgba(0,0,0,.28);transition:.2s}.floating-action:hover{transform:translateY(-3px)}.floating-action svg{width:29px;height:29px;fill:currentColor}.whatsapp-action{background:#25d366}.wheel-action{background:linear-gradient(145deg,#e5bc6e,#a86b1d)}.wheel-action svg{fill:none;stroke:currentColor;stroke-width:1.7}.floating-wheel-modal{position:fixed;z-index:1000;inset:0;display:grid;place-items:center;padding:24px;background:rgba(8,6,4,.72);backdrop-filter:blur(7px)}.floating-wheel-panel{position:relative;width:min(760px,100%)}.floating-wheel-close{position:absolute;z-index:5;top:12px;right:12px;width:38px;height:38px;border:1px solid rgba(255,255,255,.35);border-radius:50%;background:rgba(0,0,0,.48);color:#fff;font-size:27px;cursor:pointer}.floating-wheel-content{padding:0!important}.floating-wheel-enter-active,.floating-wheel-leave-active{transition:opacity .22s}.floating-wheel-enter-from,.floating-wheel-leave-to{opacity:0}@media(max-width:640px){.floating-contact-actions{bottom:92px;left:14px!important;right:auto!important}.floating-contact-actions.mobileEnglish{left:auto!important;right:14px!important}.floating-action{width:52px;height:52px}.floating-wheel-modal{padding:12px}.floating-wheel-panel{max-height:88dvh;overflow:auto;border-radius:20px}.floating-wheel-content{width:100%!important}.floating-wheel-close{top:8px;right:8px}}
</style>
