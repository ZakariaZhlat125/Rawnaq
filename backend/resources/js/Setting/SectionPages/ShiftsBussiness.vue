<!-- resources/js/vue/pages/ShiftsBussiness.vue -->
<template>
  <div class="card">
    <div class="card-body">
      <!-- رأس الصفحة -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">الورديات</h5>
        <button class="btn btn-primary" @click="openForm()">
          <i class="fa fa-plus"></i> جديد
        </button>
      </div>

      <!-- جدول -->
      <table id="shift-table" class="table table-striped border table-responsive">
        <thead>
          <tr>
            <th>الاسم</th>
            <th>الحالة</th>
            <th>آخر تعديل</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="shift in shifts" :key="shift.id">
            <td>{{ shift.name }}</td>
            <td>
              <span :class="{ 'text-success': shift.status, 'text-danger': !shift.status }">
                {{ shift.status ? 'نشط' : 'غير نشط' }}
              </span>
            </td>
            <td>{{ shift.updated_at }}</td>
            <td>
              <button class="btn btn-sm btn-primary me-1" @click="openForm(shift)">
                <i class="fa fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" @click="deleteShift(shift.id)">
                <i class="fa fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- الفورم -->
    <FormOffcanvas
      v-if="showForm"
      :model-value="selectedShift"
      @close="showForm = false"
      @updated="fetchShifts"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import $ from 'jquery'
import 'bootstrap' // Ensure Bootstrap JS is loaded
import 'datatables.net-bs5'
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css'
import FormOffcanvas from '@/vue/components/shifts/FormOffcanvas.vue'
import { useRequest } from '@/helpers/hooks/useCrudOpration'
import { EDIT_URL, STORE_URL, UPDATE_URL } from '@/vue/constants/shift.js'


const shifts = ref([])
const showForm = ref(false)
const selectedShift = ref(null)

const fetchShifts = () => {
  fetch('/app/shift/index')
    .then(res => res.json())
    .then(data => {
      shifts.value = data.data || []
      setTimeout(() => {
        $('#shift-table').DataTable()
      }, 100)
    })
}

const openForm = (shift = null) => {
  selectedShift.value = shift
  showForm.value = true
}

const deleteShift = (id) => {
  if (!window.confirm('هل أنت متأكد من حذف هذا الوردية')) return

  fetch(`/app/shift/delete/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json'
    }
  })
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        window.successSnackbar(data.message)
        shifts.value = shifts.value.filter(s => s.id !== id)
      } else {
          window.errorSnackbar(data.message)
      }
    })
}

onMounted(() => fetchShifts())
</script>
