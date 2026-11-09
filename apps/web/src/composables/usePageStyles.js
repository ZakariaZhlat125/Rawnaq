import { onMounted, onBeforeUnmount } from 'vue'

/*
  حقن أنماط صفحة كاملة عند الدخول وإزالتها عند الخروج.

  لماذا لا نستخدم <style scoped> ببساطة؟
  أنماط الصفحات المُرحَّلة تحتوي محدّدات عامة (:root · body · header · a · button · .wrap)
  تُعرّف منظومة تصميم الصفحة بأكملها:
    • جعلها عامة (غير scoped) → تتسرّب وتغيّر تصميم بقية الصفحات
    • جعلها scoped            → تنكسر (لا يمكن لـ scoped استهداف :root و body)

  لذلك نحقن الأنماط كما هي أثناء وجود الصفحة فقط، ونزيلها عند مغادرتها.
  النتيجة: تطابق بصري حرفي مع الصفحة القديمة، مع منطق وقالب Vue حقيقيين.

  الاستخدام:
    import css from '@/assets/styles/pages/branches.css?raw'
    usePageStyles(css, 'branches')
*/
export function usePageStyles(cssText, pageName = 'page') {
  let el = null

  onMounted(() => {
    el = document.createElement('style')
    el.setAttribute('data-page-styles', pageName)
    el.textContent = cssText
    document.head.appendChild(el)
  })

  onBeforeUnmount(() => {
    if (el && el.parentNode) el.parentNode.removeChild(el)
    el = null
  })
}
