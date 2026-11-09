<script setup>
import AppIcon from './AppIcon.vue'
import { useAboutContent } from '@/composables/useAboutContent'
const { contact, branches, page } = useAboutContent()
const vEscape = value => String(value || '').replace(/\\/g, '\\\\').replace(/\r?\n/g, '\\n').replace(/;/g, '\\;').replace(/,/g, '\\,')

const emit = defineEmits(['notify'])

function saveContact() {
  const vCard = ['BEGIN:VCARD', 'VERSION:3.0', `FN:${vEscape(page.value.title)}`, `ORG:${vEscape(page.value.english_title)}`,
    ...branches.value.filter(b => b.phone).map(b => `TEL;TYPE=WORK,VOICE:${b.phone}`),
    ...(contact.value.email ? [`EMAIL:${vEscape(contact.value.email)}`] : []), `URL:${location.origin}/page-about`,
    `NOTE:${vEscape(page.value.description)}`, 'END:VCARD'].join('\r\n')
  const link = document.createElement('a')
  link.href = `data:text/vcard;charset=utf-8,${encodeURIComponent(vCard)}`
  link.download = 'SamiCare.vcf'
  link.click()
  emit('notify', 'تم تنزيل جهة الاتصال — افتحها لإضافتها لهاتفك')
}

async function sharePage() {
  const data = { title: page.value.title, text: page.value.tagline, url: location.href }
  try {
    if (navigator.share) return await navigator.share(data)
    await navigator.clipboard.writeText(data.url)
    emit('notify', 'تم نسخ رابط الصفحة — شاركه الآن')
  } catch (error) {
    if (error?.name !== 'AbortError') emit('notify', 'تعذرت المشاركة، انسخ الرابط من شريط العنوان')
  }
}
</script>

<template>
  <div class="page-actions reveal">
    <button class="button button--gold" type="button" @click="saveContact"><AppIcon name="userPlus" :size="17" />حفظ جهة الاتصال في هاتفك</button>
    <button class="button button--outline" type="button" @click="sharePage"><AppIcon name="share" :size="16" />مشاركة الصفحة</button>
  </div>
</template>
