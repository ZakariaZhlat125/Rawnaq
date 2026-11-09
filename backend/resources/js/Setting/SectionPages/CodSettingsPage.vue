<template>
  <form @submit="formSubmit">
    <CardTitle :title="$t('setting_sidebar.lbl_cod_settings')" icon="fa-solid fa-hand-holding-dollar"></CardTitle>

    <InputField
      type="number"
      :is-required="true"
      :label="$t('setting_general_page.lbl_cod_deposit_percent')"
      v-model="cod_deposit_percent"
      :error-message="errors.cod_deposit_percent"
      :error-messages="errorMessages.cod_deposit_percent"
    />

    <SubmitButton :IS_SUBMITED="IS_SUBMITED"></SubmitButton>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useField, useForm } from 'vee-validate'
import * as yup from 'yup'
import CardTitle from '@/Setting/Components/CardTitle.vue'
import InputField from '@/vue/components/form-elements/InputField.vue'
import SubmitButton from './Forms/SubmitButton.vue'
import { STORE_URL, GET_URL } from '@/vue/constants/setting'
import { useRequest } from '@/helpers/hooks/useCrudOpration'
import { createRequest } from '@/helpers/utilities'

const IS_SUBMITED = ref(false)
const errorMessages = ref({})
const { storeRequest } = useRequest()

const validationSchema = yup.object({
  cod_deposit_percent: yup
    .number()
    .typeError(window.i18n.global.t('admin_validation.deposit_number'))
    .min(0)
    .max(100)
    .required('COD deposit percent is required')
})

const { handleSubmit, errors, resetForm } = useForm({ validationSchema })
const { value: cod_deposit_percent } = useField('cod_deposit_percent')

const setFormData = (data) => {
  resetForm({
    values: {
      cod_deposit_percent: data.cod_deposit_percent ?? 30
    }
  })
}

onMounted(() => {
  createRequest(GET_URL('cod_deposit_percent')).then((response) => {
    setFormData(response)
  })
})

const displaySubmitMessage = (res) => {
  IS_SUBMITED.value = false
  if (res.status) {
    window.successSnackbar(res.message)
  } else {
    window.errorSnackbar(res.message)
    errorMessages.value = res.errors || {}
  }
}

const formSubmit = handleSubmit((values) => {
  IS_SUBMITED.value = true
  storeRequest({
    url: STORE_URL,
    body: {
      cod_deposit_percent: values.cod_deposit_percent
    }
  }).then((res) => displaySubmitMessage(res))
})
</script>
