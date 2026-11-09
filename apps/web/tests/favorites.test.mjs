import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { ref, computed, nextTick } from 'vue'
const token = ref(null)
let loginRequests = 0, server = [], calls = [], pausedResolve
const auth = { token, isAuthenticated: computed(() => !!token.value), openAuthModal: () => loginRequests++ }
globalThis.__favoriteTest = {
 useAuth: () => auth,
 authFetch: async (path, options = {}) => {
  calls.push([path, options.method || 'GET'])
  if (options.method === 'POST') server.push(options.body)
  if (options.method === 'DELETE') server = server.filter(x => path !== `/favorites/${x.type}/${x.item_id}`)
  if (!options.method && token.value === 'paused') return new Promise(resolve => { pausedResolve = resolve })
  return { status: true, data: server.map(x => ({ ...x })) }
 }
}
let source = await readFile(new URL('../src/composables/useFavorites.js', import.meta.url), 'utf8')
source = source.replace("from 'vue'", `from '${import.meta.resolve('vue')}'`)
source = source.replace("import { authFetch } from '@/services/apiClient'", 'const { authFetch } = globalThis.__favoriteTest')
source = source.replace("import { useAuth } from '@/composables/useAuth'", 'const { useAuth } = globalThis.__favoriteTest')
const { useFavorites } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`)
const favorites = useFavorites()
await favorites.toggle('service', 5)
assert.equal(loginRequests, 1)
assert.equal(calls.length, 0)
token.value = 'first'
await nextTick(); await nextTick()
await favorites.toggle('service', 5)
assert.equal(favorites.has('service', '5'), true)
await favorites.toggle('service', 5)
assert.equal(favorites.has('service', 5), false)
await Promise.all([favorites.toggle('package', 7), favorites.toggle('product', 8)])
assert.equal(favorites.has('package', 7), true)
assert.equal(favorites.has('product', 8), true)
const postsBefore = calls.filter(x => x[1] === 'POST').length
await Promise.all([favorites.toggle('service', 9), favorites.toggle('service', 9)])
assert.equal(calls.filter(x => x[1] === 'POST').length, postsBefore + 1)
token.value = 'paused'; await nextTick()
assert.equal(favorites.items.value.length, 0)
token.value = null; await nextTick()
pausedResolve({ data: [{ type: 'service', item_id: 999 }] })
await nextTick(); await nextTick()
assert.equal(favorites.items.value.length, 0)
assert.equal(favorites.loading.value, false)
console.log('Favorites: guest login, persistence, removal, concurrent additions, duplicate clicks, and account isolation passed.')
