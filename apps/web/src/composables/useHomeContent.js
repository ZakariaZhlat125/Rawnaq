import { computed } from 'vue'
import { useLanguage } from './useLanguage'
import '../../public/home-page-content.js'
export function useHomeContent(props) {
  const { state } = useLanguage()
  const content = computed(() => ({ ...globalThis.SamiHomeContent, ...props.content }))
  const text = key => content.value[key]?.[state.lang] ?? globalThis.SamiHomeContent[key]?.[state.lang] ?? ''
  return { content, text }
}
