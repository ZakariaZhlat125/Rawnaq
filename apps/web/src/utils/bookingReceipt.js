export function bookingReceiptUrl(payload) {
  const code = btoa(unescape(encodeURIComponent(JSON.stringify(payload))))
    .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
  return `${window.location.origin}/booking-receipt?receipt=${code}`
}

export function savePendingReceipt(payload, attempt) {
  // Keep the booking details across the payment gateway redirect in this tab.
  try {
    sessionStorage.setItem('sami-pending-receipt', JSON.stringify({ payload, attempt, created: Date.now() }))
  } catch { /* Checkout must remain available if storage is disabled. */ }
}

export function returnedReceiptUrl(invoice, attempt) {
  try {
    const saved = JSON.parse(sessionStorage.getItem('sami-pending-receipt'))
    if (!invoice || !attempt || !saved?.attempt || String(saved.attempt) !== String(attempt) || Date.now() - saved.created > 86400000) return ''
    return bookingReceiptUrl({ ...saved.payload, r: invoice })
  } catch { return '' }
}
