import { reactive, watch } from 'vue'

/*
  تبديل لغة الواجهة (عربي / إنجليزي).

  الاتجاه واللغة بيتغيّروا على <html> فورًا، والاختيار بيتخزّن.
  ملاحظة: نصوص الصفحات نفسها لسه عربية — ده الأساس اللي تتبني عليه
  الترجمة، والقاموس تحت بيغطّي الهيدر فقط حاليًا.
*/

const KEY = 'samiSiteLang'

const DICT = {
  ar: {
    'الرئيسية': 'الرئيسية', 'خدماتنا': 'خدماتنا', 'الباقات والهدايا': 'الباقات والهدايا',
    'المتجر': 'المتجر', 'الفروع': 'الفروع', 'تواصل معنا': 'تواصل معنا', 'احجز الآن': 'احجز الآن',
  },
  en: {
    'الرئيسية': 'Home', 'خدماتنا': 'Services', 'الباقات والهدايا': 'Packages & Gifts',
    'المتجر': 'Store', 'الفروع': 'Branches', 'تواصل معنا': 'Contact', 'احجز الآن': 'Book Now',
  },
}

function read() {
  try {
    const v = localStorage.getItem(KEY)
    return v === 'en' || v === 'ar' ? v : 'ar'
  } catch { return 'ar' }
}

const state = reactive({ lang: read() })

function apply(lang) {
  const el = document.documentElement
  el.setAttribute('lang', lang)
  el.setAttribute('dir', lang === 'en' ? 'ltr' : 'rtl')
  try { localStorage.setItem(KEY, lang) } catch { /* التخزين ممكن يكون مقفول */ }
}

if (typeof document !== 'undefined') apply(state.lang)
watch(() => state.lang, apply)

export function useLanguage() {
  const toggle = () => { state.lang = state.lang === 'ar' ? 'en' : 'ar' }
  const t = key => (DICT[state.lang] && DICT[state.lang][key]) || key
  return { state, toggle, t }
}
