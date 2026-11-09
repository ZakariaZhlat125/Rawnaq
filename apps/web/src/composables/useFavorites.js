import { ref, watch } from 'vue'
import { authFetch } from '@/services/apiClient'
import { useAuth } from '@/composables/useAuth'

const items = ref([])
const error = ref('')
const itemErrors = ref({})
const pending = ref(new Set())
const loading = ref(false)
let initialized = false
let generation = 0
let loadSequence = 0

export function useFavorites() {
  const { isAuthenticated, token, openAuthModal } = useAuth()

  async function load() {
    const version = generation
    const sequence = ++loadSequence
    error.value = ''
    loading.value = true
    try {
      const response = await authFetch('/favorites')
      if (version === generation && sequence === loadSequence) {
        items.value = response.data || []
      }
    } catch (e) {
      if (version === generation && sequence === loadSequence) error.value = e.message
    } finally {
      if (version === generation && sequence === loadSequence) loading.value = false
    }
  }

  if (!initialized) {
    initialized = true
    watch(token, () => {
      generation++
      items.value = []
      error.value = ''
      itemErrors.value = {}
      loading.value = false
      if (isAuthenticated.value) load()
    }, { immediate: true })
  }

  const matches = (item, type, id) => item.type === type && Number(item.item_id) === Number(id)
  const has = (type, id) => items.value.some(item => matches(item, type, id))

  async function toggle(type, id) {
    if (!isAuthenticated.value) {
      openAuthModal()
      return
    }
    const key = `${type}:${id}`
    if (pending.value.has(key) || loading.value) return
    pending.value.add(key)
    delete itemErrors.value[key]
    error.value = ''
    const version = generation
    try {
      if (has(type, id)) {
        await authFetch(`/favorites/${type}/${id}`, { method: 'DELETE' })
        if (version === generation) {
          items.value = items.value.filter(item => !matches(item, type, id))
          await load()
        }
      } else {
        await authFetch('/favorites', { method: 'POST', body: { type, item_id: id } })
        if (version === generation) await load()
      }
    } catch (e) {
      if (version === generation) itemErrors.value[key] = e.message
    } finally {
      pending.value.delete(key)
    }
  }

  return { items, error, itemErrors, pending, loading, has, toggle, load }
}
