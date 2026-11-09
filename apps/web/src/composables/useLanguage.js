import { reactive, watch } from 'vue'
import '@/i18n/staticDict'
import '../../public/site-i18n.js'

const KEY = 'samiSiteLang'
function read() {
  try { return (localStorage.getItem(KEY) || localStorage.getItem('samiMobileLang')) === 'en' ? 'en' : 'ar' }
  catch { return 'ar' }
}
const state = reactive({ lang: read() })
function setLanguage(lang) {
  if (lang === 'ar' || lang === 'en') state.lang = lang
}
function apply(lang) {
  document.documentElement.lang = lang
  document.documentElement.dir = lang === 'en' ? 'ltr' : 'rtl'
  try {
    localStorage.setItem(KEY, lang)
    localStorage.setItem('samiMobileLang', lang)
  } catch { /* Storage can be unavailable in private browsing. */ }
}
if (typeof document !== 'undefined') {
  apply(state.lang)
  const translator = globalThis.SamiI18n.createTranslator(document, () => state.lang)
  watch(() => state.lang, lang => {
    apply(lang)
    translator.process(document.documentElement)
    document.querySelectorAll('iframe.global-mobile-frame').forEach(frame => {
      frame.contentWindow?.postMessage({ type: 'sami:language', lang }, location.origin)
    })
  }, { flush: 'post' })
  window.addEventListener('storage', event => {
    if (event.key === KEY || event.key === 'samiMobileLang') setLanguage(event.newValue)
  })
  window.addEventListener('message', event => {
    if (event.origin !== location.origin || event.data?.type !== 'sami:language') return
    const frame = document.querySelector('iframe.global-mobile-frame')
    if (frame && event.source === frame.contentWindow) setLanguage(event.data.lang)
  })
}
export function useLanguage() {
  const toggle = () => setLanguage(state.lang === 'ar' ? 'en' : 'ar')
  const t = value => globalThis.SamiI18n.translate(value, state.lang)
  return { state, toggle, t, setLanguage }
}