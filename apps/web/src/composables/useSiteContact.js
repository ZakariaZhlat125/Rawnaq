import { computed, ref } from 'vue'
import { resolveBackendUrl } from '@/utils/assetPath'
import '../../public/site-contact.js'

const whatsappNumber = ref('')
let request

export function useSiteContact() {
  if (!request) {
    request = globalThis.SamiSiteContact.load(resolveBackendUrl('/api/public-contact'))
      .then(number => { whatsappNumber.value = number })
      .catch(error => {
        console.warn('Unable to load WhatsApp number from dashboard', error)
        request = null
      })
  }
  const whatsappUrl = computed(() => whatsappNumber.value ? `https://wa.me/${whatsappNumber.value}` : '')
  return { whatsappNumber, whatsappUrl }
}
