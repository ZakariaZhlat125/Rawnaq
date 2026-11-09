<template>
  <div class="card mt-3">
    <div class="card-body">
      <div class="col-md-12 mb-3">
        <CardTitle
          :title="$t('setting_sidebar.lbl_cart_duration')"
          icon="fa-solid fa-stopwatch"
        ></CardTitle>
      </div>

      <div class="row align-items-end">
        <div class="form-group col-md-12">
          <label class="form-label">{{ $t('setting_sidebar.lbl_duration_in_minutes') }}</label>
          <div class="input-group">
            <input
              type="number"
              class="form-control"
              v-model="duration_val"
              placeholder="e.g. 30"
              :disabled="isProcessing"
            />
            <span class="input-group-text">{{ $t('setting_sidebar.minutes') }}</span>
          </div>
        </div>

        <div class="col-md-2">
          <button
            @click="saveDuration"
            class="btn btn-primary w-100"
            :disabled="isProcessing || !duration_val"
          >
            <i v-if="isProcessing" class="fas fa-spinner fa-spin me-1"></i>
            {{ $t('messages.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import CardTitle from '@/Setting/Components/CardTitle.vue'

const duration_val = ref(null)
const isProcessing = ref(false)

// 1. Fetch the value
const fetchDuration = async () => {
  isProcessing.value = true
  try {
    const response = await axios.get('/api/get-service-duration-value')
    if (response.data.status) {
      duration_val.value = response.data.data.duration
    }
  } catch (error) {
    console.error("Error loading duration:", error)
  } finally {
    isProcessing.value = false
  }
}

// 2. Save the value
const saveDuration = async () => {
  isProcessing.value = true
  try {
    const response = await axios.post('/api/update-service-duration-value', {
      duration: duration_val.value
    })

    if (response.data.status) {
      if (window.successSnackbar) {
        window.successSnackbar(response.data.message || "Cart duration updated")
      }
    }
  } catch (error) {
    if (window.errorSnackbar) {
      window.errorSnackbar(window.i18n.global.t("Failed to save cart duration"))
    }
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  fetchDuration()
})
</script>
