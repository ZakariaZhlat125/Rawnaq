<template>
  <form @submit="formSubmit" class="work-hour">
    <div class="col-md-12 d-flex justify-content-between mb-4">
      <CardTitle :title="$t('setting_sidebar.lbl_work_hours')" icon="fa-solid fa-business-time" />
    </div>

    <ul class="data-scrollbar list-group list-group-flush">
      <li v-for="(day, index) in weekdays" class="form-group col-md-12 list-group-item" :key="index">
        <h5 class="mb-3 text-capitalize">{{ index + 1 }}. {{ day.day }}</h5>

        <div class="col-md-12 row row-cols-2 g-3">
          <flat-pickr v-model="day.start_time" :config="config" class="form-control" />
          <flat-pickr v-model="day.end_time" :config="config" class="form-control" />
        </div>
      </li>
    </ul>

    <div class="mt-4">
      <SubmitButton :IS_SUBMITED="IS_SUBMITED" />
    </div>
  </form>
</template>

<script setup>
import { ref } from 'vue'
import FlatPickr from 'vue-flatpickr-component'
import CardTitle from '@/Setting/Components/CardTitle.vue'
import SubmitButton from './Forms/SubmitButton.vue'
import moment from 'moment'
import { useRequest } from '@/helpers/hooks/useCrudOpration'
import { STORE_URL, LISTING_URL } from '@/vue/constants/work_hours'

const IS_SUBMITED = ref(false)

const config = {
  dateFormat: 'H:i:S',
  altInput: true,
  altFormat: 'h:i K',
  enableTime: true,
  noCalendar: true,
  defaultHour: 9,
  defaultMinute: 0,
  defaultSeconds: 0,
  static: true,
}

const { storeRequest, getRequest } = useRequest()

const defaultData = () => [
  { day: 'saturday', start_time: '09:00:00', end_time: '18:00:00' },
  { day: 'sunday', start_time: '09:00:00', end_time: '18:00:00' },
  { day: 'monday', start_time: '09:00:00', end_time: '18:00:00' },
  { day: 'tuesday', start_time: '09:00:00', end_time: '18:00:00' },
  { day: 'wednesday', start_time: '09:00:00', end_time: '18:00:00' },
  { day: 'thursday', start_time: '09:00:00', end_time: '18:00:00' },
  { day: 'friday', start_time: '09:00:00', end_time: '18:00:00' },
  
]

const weekdays = ref(defaultData())

// Load from API
getRequest({ url: LISTING_URL }).then((res) => {
  if (res.status && res.data.length > 0) {
    weekdays.value = res.data
  }
})

const formSubmit = () => {
  IS_SUBMITED.value = true
  storeRequest({
    url: STORE_URL,
    body: { weekdays: weekdays.value }
  }).then((res) => {
    IS_SUBMITED.value = false
    if (res.status) {
      window.successSnackbar(res.message)
    } else {
      window.errorSnackbar(res.message)
    }
  })
}
</script>

<style scoped>
.work-hour .data-scrollbar {
  max-height: 600px;
  overflow-y: auto;
}
</style>