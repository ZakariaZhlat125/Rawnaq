<template>
  <div class="booking-status-control d-flex align-items-center justify-content-between w-100 border-top p-3">
    <!-- Current Status Display -->
    <div class="d-flex align-items-center gap-3">
      <span class="p-2 border border-light rounded-circle"
        :style="{ 'background-color': filterStatus(status)?.color_hex }">
        <span class="visually-hidden">{{ $t('admin_ui.status') }}</span>
      </span>
      <h5 class="mb-0">{{ filterStatus(status)?.title }}</h5>
    </div>
    <!-- Dropdown Select for Status Change -->
    <select v-if="props.booking_id > 0 && props.employee_id > 0" class="form-select booking-status-control__select" v-model="selectedStatus" @change="changeBookingStatus(selectedStatus)">
      <option v-for="(item, key) in filteredStatusList" :key="key" :value="key" v-if="key !== 'completed'">
        {{ item.title }}
      </option>
      <option v-if="!isPaidBooking" value="__paid">{{ $t('booking.status_paid') }}</option>
      <option v-else value="__paid" disabled>{{ $t('booking.status_paid') }}</option>
    </select>
    <template v-for="(singleStatus, key) in filteredStatusList" :key="key" >
      <button type="button"
          v-if="canShowButton(key, singleStatus?.next_status)" class="btn btn-outline-primary rounded-pill" @click="changeBookingStatus(singleStatus?.next_status)">
        {{ filterStatus(singleStatus?.next_status)?.title }}
      </button>

    </template>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { UPDATE_PAYMENT_STATUS, UPDATE_STATUS } from '../../constant/booking'
import { useRequest } from '@/helpers/hooks/useCrudOpration'

import { computed } from 'vue'

const filteredStatusList = computed(() => {
  const excluded = ['completed']
  return Object.fromEntries(
    Object.entries(props.statusList).filter(([key]) => !excluded.includes(key))
  )
})


// Props
const props = defineProps({
  status: String,
  statusList: { type: Object, default: () => [] },
  isPaid: { type: [Boolean, Number, String], default: false },
  employee_id: Number | String,
  booking_id: Number | String
})

// Emit
const emit = defineEmits(['statusUpdate'])

// State
const selectedStatus = ref(props.status)
const isPaidBooking = computed(() => Number(props.isPaid) === 1 || props.isPaid === true)
// Request
const { updateRequest } = useRequest()
// Watch for prop changes and sync
watch(() => props.status, (newStatus) => {
  selectedStatus.value = newStatus
})

// Utils
const filterStatus = (value) => {
  return props.statusList[value]
}

// Check if we should show the button
const canShowButton = (statusKey, nextStatus) => {
  return props.booking_id > 0 &&
    props.employee_id > 0 &&
    filterStatus(nextStatus)?.title &&
    props.status === statusKey
}



// Change Status
const changeBookingStatus = (status) => {
  if (status === '__paid') {
    markBookingAsPaid()
    return
  }

  const data = {
    status: status
  }
  updateRequest({ url: UPDATE_STATUS, id: props.booking_id, body: data }).then((res) => {
    if (res.status) {
      emit('statusUpdate', res.data)
      window.successSnackbar(res.message)
    }
  })
}

const markBookingAsPaid = () => {
  selectedStatus.value = props.status

  updateRequest({
    url: UPDATE_PAYMENT_STATUS,
    id: props.booking_id,
    body: {
      action_type: 'update-payment-status',
      value: 1,
      transaction_type: 'cash'
    }
  }).then((res) => {
    if (res.status) {
      if (res.data) {
        emit('statusUpdate', res.data)
      }
      window.successSnackbar(res.message)
    }
  })
}
</script>

<style scoped>
.booking-status-control {
  gap: 12px;
}

.booking-status-control__select {
  min-width: 168px;
  max-width: 220px;
  padding-inline-start: 14px;
  padding-inline-end: 42px;
  text-align: start;
}

[dir='rtl'] .booking-status-control__select {
  background-position: left 0.75rem center;
  padding-inline-start: 42px;
  padding-inline-end: 14px;
}
</style>
