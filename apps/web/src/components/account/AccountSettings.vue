<script setup>
import { ref, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { updateProfile, deleteAccount } from '@/services/accountApi'

const props = defineProps({ profile: { type: Object, default: null } })
const emit = defineEmits(['updated'])
const { logout } = useAuth()

const firstName = ref('')
const lastName = ref('')
const mobile = ref('')
const email = ref('')
const dateOfBirth = ref('')
const yesterday = new Date()
yesterday.setDate(yesterday.getDate() - 1)
const maxBirthday = `${yesterday.getFullYear()}-${String(yesterday.getMonth() + 1).padStart(2, '0')}-${String(yesterday.getDate()).padStart(2, '0')}`
const saving = ref(false)
const error = ref('')
const saved = ref(false)

function syncFromProfile() {
  const u = props.profile?.user
  if (!u) return
  firstName.value = u.first_name || ''
  lastName.value = u.last_name || ''
  mobile.value = u.mobile || ''
  email.value = u.email || ''
  dateOfBirth.value = u.date_of_birth?.slice(0, 10) || ''
}
syncFromProfile()
watch(() => props.profile, syncFromProfile)

async function submit() {
  saving.value = true
  error.value = ''
  saved.value = false
  try {
    await updateProfile({
      first_name: firstName.value.trim(),
      last_name: lastName.value.trim(),
      mobile: mobile.value.trim(),
      email: email.value.trim(),
      date_of_birth: dateOfBirth.value || null,
    })
    saved.value = true
    emit('updated')
  } catch (e) {
    error.value = e.message || 'تعذر حفظ التعديلات'
  } finally {
    saving.value = false
  }
}

async function onDelete() {
  if (!confirm('هل أنت متأكد من حذف حسابك نهائيًا؟ لا يمكن التراجع عن هذا الإجراء.')) return
  try {
    await deleteAccount()
    logout()
  } catch (e) {
    error.value = e.message || 'تعذر حذف الحساب'
  }
}
</script>

<template>
  <div class="settings">
    <form class="settings-form" @submit.prevent="submit">
      <input v-model="firstName" type="text" placeholder="الاسم الأول" />
      <input v-model="lastName" type="text" placeholder="اسم العائلة" />
      <input v-model="mobile" type="tel" dir="ltr" placeholder="رقم الجوال" />
      <input v-model="email" type="email" dir="ltr" placeholder="البريد الإلكتروني" />
      <label for="birthday">تاريخ الميلاد (اختياري)</label>
      <input id="birthday" v-model="dateOfBirth" type="date" dir="ltr" :max="maxBirthday" />
      <p v-if="error" class="err">{{ error }}</p>
      <p v-if="saved" class="ok">تم حفظ التعديلات بنجاح</p>
      <button type="submit" class="btn-org" :disabled="saving">{{ saving ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}</button>
    </form>

    <div class="danger-zone">
      <button class="btn-line" @click="logout()">تسجيل الخروج</button>
      <button class="btn-danger" @click="onDelete">حذف الحساب</button>
    </div>
  </div>
</template>

<style scoped>
.settings-form { display: grid; gap: 10px; max-width: 420px; }
.settings-form input {
  padding: 12px 14px; border-radius: 12px; border: 1.5px solid #e9e0d3; font-size: 14px; font-family: inherit;
}
.err { color: #b42318; font-size: 12px; margin: 0; }
.ok { color: #2e8b57; font-size: 12px; margin: 0; }
.btn-org, .btn-line, .btn-danger {
  padding: 12px 20px; border-radius: 999px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-org { border: 0; background: linear-gradient(90deg, #b77420, #f0cf89, #be7920); color: #160f07; }
.btn-line { border: 1.5px solid #e9e0d3; background: #fff; color: #6f665c; }
.btn-danger { border: 1.5px solid #f0c4bc; background: #fff; color: #b42318; }
.danger-zone { display: flex; gap: 10px; margin-top: 28px; }
</style>
