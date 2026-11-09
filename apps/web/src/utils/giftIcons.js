/*
  أيقونات أقسام الخدمات — مستخرجة حرفيًا من categoryIcon() في src/legacy/gifts.html
  تُستخدم داخل <svg viewBox="0 0 24 24"> في المكوّنات.
*/
export const CAT_ICONS = {
  hair:'<path d="M7 20c4-1 7-5 7-10 0-3-1-5-3-7-1 4-3 7-6 9-2 2-2 5 0 7 .6.6 1.3 1 2 1z"/><path d="M11 5c4 1 6 4 6 8 0 3-1 5-3 7"/>',
  skin:'<path d="M7 5c2-2 8-2 10 0l1 5c1 5-2 10-6 10s-7-5-6-10z"/><path d="M8 10h2m4 0h2m-6 4c1 1 3 1 4 0"/><path d="M12 3v3M4 8l3 1m10 0 3-1"/>',
  pedi:'<path d="M6 3v8c0 4 2 8 6 10 2 1 5 0 6-2 1-3-3-4-5-4-2-1-2-5-2-8V3"/><path d="M8 3v4m3-4v3m3-3v4m3-3v5"/>',
  mass:'<path d="M4 20c2-4 4-6 7-6h4c3 0 5 2 5 6"/><circle cx="15" cy="6" r="3"/><path d="M11 10l-3 3m5-4-2 5M5 15l-2-2m2 2-2 2"/>',
  bath:'<path d="M6 3v10m12-10v10M4 13h16v2c0 4-3 6-8 6s-8-2-8-6z"/><path d="M8 7h8M7 10h10"/>'
}

export const CAT_ICON_BY_ID = {
  1: 'bath',
  2: 'pedi',
  3: 'hair',
  4: 'skin',
  5: 'mass',
}

export const CAT_ACCENTS = {
  hair: 'var(--c-hair)',
  skin: 'var(--c-skin)',
  pedi: 'var(--c-pedi)',
  mass: 'var(--c-mass)',
  bath: 'var(--c-bath)',
}

const HINTS = [
  ['bath', ['bath', 'hammam', 'moroccan']],
  ['pedi', ['pedi', 'pedicure']],
  ['skin', ['skin', 'facial']],
  ['mass', ['mass', 'massage']],
  ['hair', ['hair', 'barber', 'haircut']],
]

export const categoryIconKey = category => {
  if (!category) return 'hair'

  const id = typeof category === 'object' ? category.id : category
  const byId = CAT_ICON_BY_ID[id]
  if (byId) return byId

  const text = typeof category === 'object'
    ? `${category.slug || ''} ${category.key || ''} ${category.image || ''} ${category.feature_image || ''}`.toLowerCase()
    : String(category).toLowerCase()

  return HINTS.find(([, words]) => words.some(word => text.includes(word)))?.[0] || 'hair'
}

export const categoryAccent = key => CAT_ACCENTS[key] || CAT_ACCENTS.hair

export const categoryIconPath = id => CAT_ICONS[id] || CAT_ICONS.hair
