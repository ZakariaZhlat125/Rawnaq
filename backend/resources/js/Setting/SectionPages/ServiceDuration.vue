<template>
  <div class="card">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <CardTitle
            :title="$t('setting_sidebar.lbl_service_duration')"
            icon="fa-solid fa-clock-rotate-left"
          ></CardTitle>
          <p class="text-muted small mb-0">
            {{ show_duration ? $t('setting_sidebar.msg_duration_visible_for_all') : $t('setting_sidebar.hidden_on_site') }}
          </p>
        </div>

        <div class="form-check form-switch form-switch-lg">
          <input
            class="form-check-input custom-switch"
            type="checkbox"
            role="switch"
            id="visibilityToggle"
            v-model="show_duration"
            @change="handleToggle"
            :disabled="isProcessing"
          >
        </div>
      </div>

      <div v-if="isProcessing" class="spinner-border spinner-border-sm text-primary mt-2" role="status">
        <span class="visually-hidden">{{ $t('admin_ui.loading') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref, onMounted} from 'vue'
import axios from 'axios' // Using axios directly for self-contained logic
import CardTitle from '@/Setting/Components/CardTitle.vue'

// Local State
const show_duration = ref(false)
const isProcessing = ref(false)

// 1. Fetch data directly in the component
const fetchSettings = async () => {
  isProcessing.value = true
  try {
    const response = await axios.get('/api/get-visibility-settings')
    if (response.data.status) {
      // Assuming your API returns { status: true, data: { show_duration: true } }
      show_duration.value = response.data.data.show_duration
    }
  } catch (error) {
    console.error("Error fetching settings:", error)
  } finally {
    isProcessing.value = false
  }
}

// 2. Update data directly in the component
const handleToggle = async () => {
  isProcessing.value = true

  try {
    const response = await axios.post('/api/update-service-duration-visibility', {
      show_duration: show_duration.value
    })

    if (response.data.status) {
      if (window.successSnackbar) {
        window.successSnackbar(response.data.message || "Updated successfully")
      }
    } else {
      throw new Error("Update failed")
    }
  } catch (error) {
    // Revert the switch if the API fails
    show_duration.value = !show_duration.value
    if (window.errorSnackbar) {
      window.errorSnackbar(window.i18n.global.t("Failed to update settings"))
    }
  } finally {
    isProcessing.value = false
  }
}

// Initial Load
onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.custom-switch {
  width: 2.8rem;
  height: 1.4rem;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #5a5fed;
  border-color: #5a5fed;
}

/* Add a slight grayscale if disabled */
.form-check-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
