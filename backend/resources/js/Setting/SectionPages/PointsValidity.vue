<template>
  <div class="card mt-3">
    <div class="card-body">
      <div class="col-md-12 mb-3">
        <CardTitle
            :title="$t('setting_sidebar.lbl_points_validity')"
            icon="fa-solid fa-calendar-clock"
        ></CardTitle>
      </div>

      <div class="row align-items-end">
        <div class="form-group col-md-12 mb-3">
          <label class="form-label">{{ $t('setting_sidebar.lbl_validity_period') }}</label>
          <div class="input-group">
            <input
                type="number"
                class="form-control"
                v-model="validity_val"
                placeholder="e.g. 12"
                min="1"
                :disabled="isProcessing"
            />
            <span style="min-width: 100px" class="input-group-text p-0 border-start-0">
           <Multiselect
               v-model="validity_unit"
               v-bind="validity_unit_options"
               :placeholder="$t('setting_sidebar.select_unit')"
               :disabled="isProcessing"
               class="multiselect-input-group"
           />
            </span>
          </div>
          <small class="text-muted">
            {{ $t('setting_sidebar.lbl_points_validity_hint') }}
          </small>
        </div>

        <div class="col-md-2">
          <button
              @click="saveValidity"
              class="btn btn-primary w-100"
              :disabled="isProcessing || !validity_val"
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

const validity_val  = ref(null)
const validity_unit = ref('months')   // default unit
const isProcessing  = ref(false)

import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const validity_unit_options = ref({
  searchable: false,
  options: [
    { label: t('setting_sidebar.days'),   value: 'days' },
    { label: t('setting_sidebar.months'), value: 'months' },
    { label: t('setting_sidebar.years'),  value: 'years' },
  ],
  closeOnSelect: true,
})

// 1. Fetch the current validity settings
const fetchValidity = async () => {
  isProcessing.value = true
  try {
    const response = await axios.get('/api/get-points-validity')
    if (response.data.status) {
      // Assuming API returns { data: { validity: "12", unit: "months" } }
      validity_val.value  = response.data.data.validity
      validity_unit.value = response.data.data.unit ?? 'months'
    }
  } catch (error) {
    console.error('Error loading points validity:', error)
  } finally {
    isProcessing.value = false
  }
}

// 2. Save the validity settings
const saveValidity = async () => {
  isProcessing.value = true
  try {
    const response = await axios.post('/api/update-points-validity', {
      validity : validity_val.value,
      unit     : validity_unit.value,
    })

    if (response.data.status) {
      if (window.successSnackbar) {
        window.successSnackbar(response.data.message || 'Points validity updated')
      }
    }
  } catch (error) {
    if (window.errorSnackbar) {
      window.errorSnackbar(window.i18n.global.t("Failed to save points validity"))
    }
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  fetchValidity()
})
</script>
