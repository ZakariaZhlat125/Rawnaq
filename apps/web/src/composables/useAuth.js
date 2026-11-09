import { ref, computed } from 'vue'
import { sendRegisterOtp, verifyRegisterOtp, sendLoginOtp, verifyLoginOtp, logout as logoutApi } from '@/services/authApi'

/*
  حالة تسجيل الدخول — مشتركة بين كل الصفحات، بنفس نمط useServiceLocation.js
  (module-level state + localStorage + طلب تسجيل دخول مع تنفيذ أكشن معلّق بعد النجاح).
*/

const TOKEN_KEY = 'samiAuthToken'
const USER_KEY = 'samiAuthUser'

function readToken() {
  try { return localStorage.getItem(TOKEN_KEY) } catch { return null }
}

function readUser() {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? JSON.parse(raw) : null
  } catch { return null }
}

const token = ref(readToken())
const user = ref(readUser())

const modalOpen = ref(false)
const pendingMobile = ref('')
let pendingAction = null

function persist(data) {
  token.value = data.api_token
  user.value = data
  try {
    localStorage.setItem(TOKEN_KEY, data.api_token)
    localStorage.setItem(USER_KEY, JSON.stringify(data))
  } catch { /* التخزين غير متاح */ }
}

export function useAuth() {
  const isAuthenticated = computed(() => !!token.value)

  function openAuthModal(action = null) {
    pendingAction = typeof action === 'function' ? action : null
    modalOpen.value = true
  }

  function closeAuthModal() {
    modalOpen.value = false
    pendingAction = null
  }

  function requireAuth(action) {
    if (isAuthenticated.value) {
      action()
      return false
    }
    openAuthModal(action)
    return true
  }

  async function register(username, mobile, referralCode = '') {
    const data = await sendRegisterOtp(username, mobile, referralCode)
    pendingMobile.value = data.mobile
  }

  async function confirmRegister(otp) {
    const data = await verifyRegisterOtp(pendingMobile.value, otp)
    persist(data)
    finishAuth()
  }

  async function login(mobile) {
    const data = await sendLoginOtp(mobile)
    pendingMobile.value = data.mobile
  }

  async function confirmLogin(otp) {
    const data = await verifyLoginOtp(pendingMobile.value, otp)
    persist(data)
    finishAuth()
  }

  function finishAuth() {
    modalOpen.value = false
    const act = pendingAction
    pendingAction = null
    if (act) act()
  }

  async function logout() {
    if (token.value) await logoutApi(token.value).catch(() => {})
    token.value = null
    user.value = null
    try {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
    } catch { /* التخزين غير متاح */ }
  }

  return {
    token,
    user,
    isAuthenticated,
    modalOpen,
    pendingMobile,
    openAuthModal,
    closeAuthModal,
    requireAuth,
    register,
    confirmRegister,
    login,
    confirmLogin,
    logout,
  }
}
