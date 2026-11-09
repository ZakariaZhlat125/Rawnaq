import { onBeforeUnmount, onMounted } from 'vue'

export function useAboutReveal() {
  let observer
  onMounted(() => {
    const elements = document.querySelectorAll('.reveal')
    if (!('IntersectionObserver' in window)) return elements.forEach((el) => el.classList.add('is-visible'))
    observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return
        entry.target.classList.add('is-visible')
        observer.unobserve(entry.target)
      })
    }, { threshold: 0.12 })
    elements.forEach((el) => observer.observe(el))
  })
  onBeforeUnmount(() => observer?.disconnect())
}
