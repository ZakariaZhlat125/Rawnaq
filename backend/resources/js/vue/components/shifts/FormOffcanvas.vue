<!-- resources/js/vue/components/shifts/FormOffcanvas.vue -->
<template>
  <div class="offcanvas offcanvas-end show" tabindex="-1" style="visibility: visible; width: 400px;" aria-modal="true" role="dialog">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">{{ modelValue ? 'تعديل الوردية' : 'إضافة وردية' }}</h5>
      <button type="button" class="btn-close" @click="$emit('close')"></button>
    </div>
    <div class="offcanvas-body">
      <form @submit.prevent="submitForm">
        <div class="mb-3">
          <label class="form-label">اسم الوردية</label>
          <input v-model="form.name" type="text" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">الحالة</label>
          <select v-model="form.status" class="form-control">
            <option :value="1">نشط</option>
            <option :value="0">غير نشط</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">حفظ</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps(['modelValue'])
const emit = defineEmits(['close', 'updated'])

const form = reactive({
  name: '',
  status: 1
})

watch(() => props.modelValue, (val) => {
  if (val) {
    form.name = val.name
    form.status = val.status
  } else {
    form.name = ''
    form.status = 1
  }
})

const submitForm = () => {
  const method = props.modelValue ? 'POST' : 'POST'
  const url = props.modelValue
    ? `/app/shift/update/${props.modelValue.id}`
    : '/app/shift/store'

  fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(form)
  })
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        window.successSnackbar(data.message)
        emit('updated')
        emit('close')
      } else {
          window.errorSnackbar(data.message)
      }
    })
}

// const display_submit_message = (res) => {
//   IS_SUBMITED.value = false
//   if (res.status) {
//     window.successSnackbar(res.message)
//   } else {
//     window.errorSnackbar(res.message)
//   }
// }

</script>
