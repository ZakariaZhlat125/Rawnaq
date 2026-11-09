import { authFetch } from '@/services/apiClient'

export async function fetchProfile() {
  return authFetch('/profile')
}

export async function fetchCheckoutBalances() {
  const res = await authFetch('/wallet', { cache: 'no-store', credentials: 'omit' })
  const data = res?.data
  if (data?.balance == null || !Number.isFinite(Number(data.balance))) {
    throw new Error('تعذر تحميل رصيد المحفظة')
  }
  return { wallet: data.active === false ? 0 : Number(data.balance), loyalty_points: Number(data.points_balance) || 0 }
}

export async function updateProfile(data) {
  return authFetch('/profile/update', { method: 'POST', body: data })
}

export async function fetchAddresses() {
  return authFetch('/address-list')
}

export async function addAddress(data) {
  return authFetch('/add-address', { method: 'POST', body: data })
}

export async function editAddress(id, data) {
  return authFetch('/edit-address', { method: 'POST', body: { id, ...data } })
}

export async function removeAddress(id) {
  return authFetch(`/remove-address?id=${encodeURIComponent(id)}`)
}

export async function sendSupportMessage(message) {
  return authFetch('/contact', { method: 'POST', body: { message } })
}

/* /contact محمي بـ auth:sanctum وبيطلب name/email كحقول إجبارية (مش بس message) —
   نفس الـ endpoint اللي بتستخدمه sendSupportMessage لكن بكل الحقول اللي فورمة "تواصل معنا" بتجمعها. */
export async function sendContactMessage({ name, email, phone, message }) {
  return authFetch('/contact', { method: 'POST', body: { name, email, phone, message } })
}

export async function deleteAccount() {
  return authFetch('/delete-account', { method: 'POST' })
}
