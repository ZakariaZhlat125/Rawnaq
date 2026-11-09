import { onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'

/*
  توجيه روابط <a> الداخلية عبر vue-router دون تعديل الماركب.

  لماذا لا نستبدلها بـ <RouterLink>؟
  لأن RouterLink يضيف أصنافًا تلقائية (router-link-active) قد تُطابق
  محدّدات CSS قائمة وتغيّر شكل الرابط. الاعتراض البرمجي يُبقي الماركب
  مطابقًا حرفيًا للصفحة الأصلية.

  الاستخدام:
    const root = ref(null)
    useInternalLinks(root)
    ...
    <div ref="root"> ... </div>
*/

// خريطة الروابط القديمة إلى المسارات الجديدة
const LEGACY_PATHS = {
  'index.html': '/',
  'booking.html': '/booking',
  'store.html': '/store',
  'gifts.html': '/gifts',
  'packages-gifts.html': '/packages-gifts',
  'branches.html': '/branches',
  'contact.html': '/contact',
  'gift-recipient.html': '/gift-recipient',
}

export function useInternalLinks(rootRef) {
  const router = useRouter()

  function onClick(event) {
    const t = event.target
    // عناصر SVG قد لا تملك closest في بعض البيئات — نصعد إلى أقرب عنصر HTML
    const el = t && typeof t.closest === 'function' ? t : (t && t.parentElement)
    if (!el || typeof el.closest !== 'function') return

    // لا نعترض النقرات التي مصدرها زر — فلها معالجاتها الخاصة
    if (el.closest('button')) return

    const link = el.closest('a[href]')
    if (!link) return

    const raw = link.getAttribute('href')
    if (!raw) return

    // تجاهل الروابط الخارجية والمراسي والبروتوكولات الخاصة
    if (
      link.target === '_blank' ||
      raw.startsWith('#') ||
      /^(https?:|mailto:|tel:|wa\.me)/i.test(raw)
    ) return

    const clean = raw.split('?')[0].split('#')[0].replace(/^\.?\//, '')
    const path = LEGACY_PATHS[clean] || (raw.startsWith('/') ? raw : null)
    if (!path) return

    event.preventDefault()
    router.push(path)
  }

  onMounted(() => rootRef.value && rootRef.value.addEventListener('click', onClick))
  onBeforeUnmount(() => rootRef.value && rootRef.value.removeEventListener('click', onClick))
}
