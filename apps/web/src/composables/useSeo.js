import { watch, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useLanguage } from './useLanguage'
import { resolveBackendUrl } from '@/utils/assetPath'
import '../../public/site-seo.js'

export function useSeo() {
  const route = useRoute()
  const { state: language } = useLanguage()
  const seo = globalThis.SamiSeo.create({ endpoint: resolveBackendUrl('/api/seo') })
  let mobilePath = null
  watch(() => route.path, path => {
    mobilePath = null
    seo.update(path, language.lang)
  }, { immediate: true })
  watch(() => language.lang, lang => seo.update(mobilePath || route.path, lang))
  const receive = event => {
    const frame = document.querySelector('iframe.global-mobile-frame')
    if (event.origin !== location.origin || !frame || event.source !== frame.contentWindow || event.data?.type !== 'sami:seo-path') return
    const path = event.data.path
    if (typeof path !== 'string' || !globalThis.SamiSeo.supported(path)) return
    if (route.name === 'service-detail' && path === '/services') return
    mobilePath = path
    seo.update(path, language.lang)
  }
  window.addEventListener('message', receive)
  onBeforeUnmount(() => { seo.dispose(); window.removeEventListener('message', receive) })
}
