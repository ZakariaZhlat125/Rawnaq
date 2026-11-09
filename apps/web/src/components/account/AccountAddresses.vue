<script setup>
import { ref, onMounted } from 'vue'
import { fetchAddresses, addAddress, removeAddress } from '@/services/accountApi'
import Skeleton from '@/components/common/SkeletonLoader.vue'

const addresses = ref([])
const loading = ref(false)
const error = ref('')
const showForm = ref(false)
const title = ref('')
const line = ref('')
const saving = ref(false)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await fetchAddresses()
    addresses.value = Array.isArray(res.data) ? res.data : []
  } catch (e) {
    error.value = e.message || 'تعذر تحميل العناوين'
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!line.value.trim()) return
  saving.value = true
  try {
    await addAddress({ title: title.value.trim(), address: line.value.trim() })
    title.value = ''
    line.value = ''
    showForm.value = false
    await load()
  } catch (e) {
    error.value = e.message || 'تعذر حفظ العنوان'
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!confirm('حذف هذا العنوان؟')) return
  try {
    await removeAddress(id)
    await load()
  } catch (e) {
    error.value = e.message || 'تعذر حذف العنوان'
  }
}

onMounted(load)
</script>

<template>
  <div class="addresses">
    <div v-if="loading" class="empty" style="display:flex;flex-direction:column;gap:12px">
      <Skeleton height="52px" border-radius="8px" />
      <Skeleton height="52px" border-radius="8px" />
    </div>
    <template v-else>
      <p v-if="error" class="err">{{ error }}</p>
      <div v-if="!addresses.length" class="empty">
        <b>لا يوجد عناوين محفوظة</b>
        <p>أضف عنوانًا جديدًا لتسهيل عمليات التوصيل</p>
      </div>
      <div v-else class="addr-list">
        <div v-for="a in addresses" :key="a.id" class="addr-card">
          <div><b>{{ a.title || a.name || 'عنوان' }}</b><small>{{ a.address || a.address_line }}</small></div>
          <button class="del" @click="remove(a.id)">حذف</button>
        </div>
      </div>

      <button class="btn-line" @click="showForm = !showForm">{{ showForm ? 'إلغاء' : '+ إضافة عنوان جديد' }}</button>
      <form v-if="showForm" class="addr-form" @submit.prevent="submit">
        <input v-model="title" type="text" placeholder="اسم العنوان (المنزل، العمل...)" />
        <input v-model="line" type="text" placeholder="تفاصيل العنوان" />
        <button type="submit" class="btn-org" :disabled="saving">{{ saving ? 'جارٍ الحفظ...' : 'حفظ العنوان' }}</button>
      </form>
    </template>
  </div>
</template>

<style scoped>
.empty { text-align: center; padding: 40px 20px; color: #8b8379; }
.empty b { display: block; color: #241f1b; margin-bottom: 6px; }
.err { color: #b42318; font-size: 12px; margin-bottom: 12px; }
.addr-list { display: grid; gap: 10px; margin-bottom: 16px; }
.addr-card {
  display: flex; justify-content: space-between; align-items: center;
  background: #fff; border: 1px solid #e9e0d3; border-radius: 14px; padding: 14px 16px;
}
.addr-card b { display: block; font-size: 14px; }
.addr-card small { color: #8b8379; font-size: 12px; }
.del { border: 0; background: none; color: #b42318; font-size: 12px; cursor: pointer; }
.btn-line, .btn-org {
  padding: 12px 20px; border-radius: 999px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.btn-line { border: 1.5px solid #e9e0d3; background: #fff; color: #6f665c; }
.btn-org { border: 0; background: linear-gradient(90deg, #b77420, #f0cf89, #be7920); color: #160f07; margin-top: 6px; }
.addr-form { display: grid; gap: 10px; margin-top: 12px; max-width: 420px; }
.addr-form input {
  padding: 12px 14px; border-radius: 12px; border: 1.5px solid #e9e0d3; font-size: 14px; font-family: inherit;
}
</style>
