import { onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { watch } from 'vue'

/*
  الكشف عند التمرير — يُظهر العناصر تدريجيًا عند دخولها الشاشة.

  الاستخدام: يُستدعى مرة واحدة في App.vue فيغطي كل الصفحات.
  يراقب أي عنصر يحمل [data-reveal] ويضيف له الصنف .revealed عند ظهوره.

  العناصر التي تُضاف لاحقًا (عند تغيير الخطوة مثلًا) تُلتقط
  عبر إعادة الفحص عند تغيّر المسار وعبر MutationObserver.
*/

const SELECTOR = '[data-reveal]:not(.revealed)'

export function useScrollReveal() {
  const route = useRoute()
  let io = null
  let mo = null

  const reduced = () =>
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches

  function observeAll() {
    if (!io) return
    document.querySelectorAll(SELECTOR).forEach(el => io.observe(el))
  }

  onMounted(() => {
    if (reduced()) {
      document.querySelectorAll('[data-reveal]').forEach(el => el.classList.add('revealed'))
      return
    }

    io = new IntersectionObserver(
      entries => {
        entries.forEach(e => {
          if (!e.isIntersecting) return
          e.target.classList.add('revealed')
          io.unobserve(e.target)
        })
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
    )

    observeAll()

    /* التقاط العناصر التي تُضاف بعد التركيب (تغيير خطوة، تحميل بيانات...) */
    mo = new MutationObserver(() => observeAll())
    mo.observe(document.body, { childList: true, subtree: true })
  })

  /* عند تغيّر الصفحة: أعِد الفحص بعد رسم المحتوى الجديد */
  watch(() => route.fullPath, () => {
    nextTick(() => requestAnimationFrame(observeAll))
  })

  onBeforeUnmount(() => {
    if (io) { io.disconnect(); io = null }
    if (mo) { mo.disconnect(); mo = null }
  })
}
