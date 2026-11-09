<template>
  <div class="card mt-3">
    <div class="card-body">
      <div class="col-md-12 mb-3">
        <CardTitle :title="$t('setting_home_theme.title')" icon="fa-solid fa-palette"></CardTitle>
      </div>

      <div class="row align-items-end">
        <div class="form-group col-md-8">
          <label class="form-label">{{ $t('setting_home_theme.label') }}</label>
          <select class="form-select" v-model="homeTheme" :disabled="isSubmitting">
            <option v-for="option in homeThemeOptions" :key="option.value" :value="option.value">
              {{ t(option.labelKey) }}
            </option>
          </select>
          <small class="text-muted">{{ $t('setting_home_theme.help') }}</small>
        </div>

        <div class="col-md-4">
          <button @click="onSubmit" class="btn btn-primary w-100" :disabled="isSubmitting">
            <i v-if="isSubmitting" class="fas fa-spinner fa-spin me-1"></i>
            {{ $t('messages.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import CardTitle from '@/Setting/Components/CardTitle.vue'
import { useRequest } from '@/helpers/hooks/useCrudOpration'
import { STORE_URL, GET_URL } from '@/vue/constants/setting'
import { createRequest } from '@/helpers/utilities'

const homeTheme = ref('none')
const isSubmitting = ref(false)

const { t } = useI18n()

const homeThemeOptions = [
  { labelKey: 'setting_home_theme.option_none', value: 'none' },
  { labelKey: 'setting_home_theme.option_ramadan', value: 'ramadan' },
  { labelKey: 'setting_home_theme.option_eid_aladha', value: 'eid_aladha' },
  { labelKey: 'setting_home_theme.option_eid_alfitr', value: 'eid_alfitr' },
  { labelKey: 'setting_home_theme.option_saudi_national_day', value: 'saudi_national_day' }
]

const { storeRequest } = useRequest()

const displaySubmitMessage = (res) => {
  isSubmitting.value = false
  if (res?.status) {
    if (window.successSnackbar) {
      window.successSnackbar(res.message)
    }
  } else if (window.errorSnackbar) {
    window.errorSnackbar(res?.message || t('setting_home_theme.save_failed'))
  }
}

const onSubmit = () => {
  isSubmitting.value = true
  storeRequest({
    url: STORE_URL,
    body: { home_theme: homeTheme.value }
  }).then((res) => displaySubmitMessage(res))
}

onMounted(() => {
  createRequest(GET_URL('home_theme')).then((response) => {
    if (response.home_theme) {
      homeTheme.value = response.home_theme
    }
  })
})
</script>
