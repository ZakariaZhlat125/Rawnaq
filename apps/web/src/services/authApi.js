import { useLanguage } from '@/composables/useLanguage'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/$/, '')

async function request(path, body) {
  const { state: langState } = useLanguage()
  const response = await fetch(`${apiBaseUrl}${path}`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'frezka-localization': langState.lang,
    },
    body: JSON.stringify(body),
  })

  let payload = null
  try {
    payload = await response.json()
  } catch {
    payload = null
  }

  if (!response.ok || payload?.status === false) {
    throw new Error(payload?.message || 'حدث خطأ، حاول مرة أخرى')
  }

  return payload.data
}

export const sendRegisterOtp = (username, mobile, referral_code = '') => request('/register', { username, mobile, referral_code })
export const verifyRegisterOtp = (mobile, otp) => request('/verify-register-otp', { mobile, otp })

export const sendLoginOtp = mobile => request('/login', { mobile })
export const verifyLoginOtp = (mobile, otp) => request('/verify-login-otp', { mobile, otp })

export async function logout(token) {
  const { state: langState } = useLanguage()
  await fetch(`${apiBaseUrl}/logout`, {
    headers: {
      Accept: 'application/json',
      'frezka-localization': langState.lang,
      Authorization: `Bearer ${token}`,
    },
  })
}
