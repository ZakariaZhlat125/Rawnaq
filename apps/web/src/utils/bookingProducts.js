export function randomProducts(products, count = 3, random = Math.random) {
  const pool = [...new Map(products.filter(p => p.stockQty > 0).map(p => [String(p.id), p])).values()]
  for (let i = pool.length - 1; i > 0; i--) {
    const j = Math.floor(random() * (i + 1))
    ;[pool[i], pool[j]] = [pool[j], pool[i]]
  }
  return pool.slice(0, count)
}

export function productCartPayload(cart) {
  return Object.entries(cart).filter(([, qty]) => qty > 0).map(([id, qty]) => ({ product_id: Number(id), qty }))
}
