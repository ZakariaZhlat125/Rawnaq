<script setup>
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { modalOpen, closeAuthModal, register, confirmRegister, login, confirmLogin } = useAuth()

const tab = ref('login') // 'login' | 'register'
const stage = ref('form') // 'form' | 'otp'
const referralCode = ref('')
const username = ref('')
const mobile = ref('')
const otp = ref('')
const loading = ref(false)
const error = ref('')

function reset() {
  stage.value = 'form'
  referralCode.value = ''
  username.value = ''
  mobile.value = ''
  otp.value = ''
  error.value = ''
  loading.value = false
}

function switchTab(t) {
  tab.value = t
  reset()
}

function close() {
  closeAuthModal()
  reset()
}

async function submitForm() {
  error.value = ''
  if (!mobile.value.trim()) { error.value = 'أدخل رقم الجوال' ; return }
  if (tab.value === 'register' && !username.value.trim()) { error.value = 'أدخل اسمك' ; return }

  loading.value = true
  try {
    if (tab.value === 'register') {
      await register(username.value.trim(), mobile.value.trim(), referralCode.value.trim())
    } else {
      await login(mobile.value.trim())
    }
    stage.value = 'otp'
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function submitOtp() {
  error.value = ''
  if (!otp.value.trim()) { error.value = 'أدخل رمز التحقق' ; return }

  loading.value = true
  try {
    if (tab.value === 'register') {
      await confirmRegister(otp.value.trim())
    } else {
      await confirmLogin(otp.value.trim())
    }
    reset()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="loc-fade">
      <div v-if="modalOpen" class="loc-ov" @click.self="close">
        <div class="loc-modal auth-modal" role="dialog" aria-modal="true" aria-label="تسجيل الدخول">
          <button class="loc-close" @click="close" aria-label="إغلاق">×</button>

          <div class="loc-head">
            <span class="loc-ic" aria-hidden="true">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" /><circle cx="12" cy="7" r="4" />
              </svg>
            </span>
            <h3>{{ tab === 'register' ? 'إنشاء حساب جديد' : 'تسجيل الدخول' }}</h3>
            <p v-if="stage === 'form'">أدخل بياناتك لإتمام الحجز</p>
            <p v-else>أدخل رمز التحقق المرسل إلى {{ mobile }}</p>
          </div>

          <div v-if="stage === 'form'" class="auth-tabs">
            <button type="button" :class="{ on: tab === 'login' }" @click="switchTab('login')">تسجيل الدخول</button>
            <button type="button" :class="{ on: tab === 'register' }" @click="switchTab('register')">حساب جديد</button>
          </div>

          <form v-if="stage === 'form'" class="auth-form" @submit.prevent="submitForm">
            <input v-if="tab === 'register'" v-model="username" type="text" placeholder="الاسم" autocomplete="name" />
            <input v-model="mobile" type="tel" dir="ltr" placeholder="رقم الجوال" autocomplete="tel" />
            <input v-if="tab === 'register' && stage === 'form'" v-model="referralCode" name="referral_code" type="text" dir="ltr" maxlength="20" placeholder="كود الإحالة (اختياري)" aria-label="كود الإحالة (اختياري)" autocomplete="off" />
            <p v-if="error" class="auth-error">{{ error }}</p>
            <button type="submit" class="auth-submit" :disabled="loading">{{ loading ? 'جارٍ الإرسال...' : 'إرسال رمز التحقق' }}</button>
          </form>

          <form v-else class="auth-form" @submit.prevent="submitOtp">
            <input v-model="otp" type="text" dir="ltr" inputmode="numeric" maxlength="4" placeholder="رمز التحقق" autocomplete="one-time-code" />
            <p v-if="error" class="auth-error">{{ error }}</p>
            <button type="submit" class="auth-submit" :disabled="loading">{{ loading ? 'جارٍ التحقق...' : 'تأكيد' }}</button>
            <button type="button" class="auth-back" @click="stage = 'form'">تعديل البيانات</button>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.auth-tabs { display: flex; gap: 8px; margin-bottom: 18px; }
.auth-tabs button {
  flex: 1; padding: 10px; border-radius: 12px; border: 1.5px solid #e9e0d3;
  background: #fff; color: #6f665c; font-size: 13px; font-weight: 600; cursor: pointer; transition: .22s;
}
.auth-tabs button.on { border-color: #1C9384; background: linear-gradient(110deg, #fff 0%, #fff8e9 100%); color: #7c571d; }

.auth-form { display: grid; gap: 12px; }
.auth-form input {
  width: 100%; padding: 13px 16px; border-radius: 13px; border: 1.5px solid #e9e0d3;
  background: #fff; font-size: 14px; font-family: inherit; color: #241f1b;
}
.auth-form input:focus { outline: none; border-color: #1C9384; }
.auth-error { color: #b42318; font-size: 12px; margin: 0; }
.auth-submit {
  padding: 13px; border: 0; border-radius: 999px; cursor: pointer;
  background: linear-gradient(90deg, #b77420, #f0cf89, #be7920); color: #160f07;
  font-size: 14px; font-weight: 700; transition: .22s;
}
.auth-submit:disabled { opacity: .6; cursor: default; }
.auth-submit:hover:not(:disabled) { filter: brightness(1.05); }
.auth-back { background: none; border: 0; color: #8b8379; font-size: 12px; cursor: pointer; padding: 4px; }
.auth-back:hover { color: #6f665c; }
</style>
