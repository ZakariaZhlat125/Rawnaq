import { reactive } from 'vue'
import { DESIGNS } from '@/data/gifts'
import { fetchGiftDesigns } from '@/services/giftsApi'

/*
  تصاميم بطاقات الهدايا — من لوحة التحكم (/api/gift-designs).
  القائمة الثابتة في data/gifts.js تُستخدم كاحتياط لحد ما يوصل الرد أو لو فشل.
*/
const state = reactive({
  designs: [...DESIGNS],
  valueCard: { min: 50, max: 5000, presets: [100, 200, 300, 500], validity_days: 180 },
  loaded: false,
})
let pending = null

export function useGiftDesigns() {
  if (!state.loaded && !pending) {
    pending = fetchGiftDesigns()
      .then(payload => {
        if (payload.data?.length) state.designs = payload.data
        if (payload.value_card) state.valueCard = payload.value_card
        state.loaded = true
      })
      .catch(() => { /* نكمل بالتصاميم الثابتة */ })
      .finally(() => { pending = null })
  }

  /* تصميم هدية قديمة ممكن يكون اتقفل: نرجع لأقرب تصميم بدل ما البطاقة تختفي */
  const designOf = id => state.designs.find(d => d.id === id) || DESIGNS.find(d => d.id === id) || state.designs[0] || DESIGNS[0]

  return { designs: state, designOf }
}
