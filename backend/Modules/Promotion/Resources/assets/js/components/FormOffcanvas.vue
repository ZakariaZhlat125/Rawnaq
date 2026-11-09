<template>
  <form @submit="formSubmit">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
      <FormHeader :currentId="currentId" :editTitle="editTitle" :createTitle="createTitle"></FormHeader>
      <div class="offcanvas-body">
        <InputField label="الحد الأدنى لقيمة الحجز قبل الضريبة (ر.س)" type="number" min="0" step="0.01" v-model="minimum_booking_amount" :error-message="errors.minimum_booking_amount" />
        <p class="text-muted">الكوبون للحجوزات فقط. المنتجات والهدايا ورسوم الزيارة لا تدخل في الحد الأدنى أو الخصم. عند تخصيصه لخدمات يجب أن تكون كل خدمات الحجز ضمن القائمة.</p>
        <div class="form-group">
          <div class="col-md-12 text-center">
            <img :src="ImageViewer || defaultImage" alt="feature-image"
              class="img-fluid mb-2 avatar-140 avatar-rounded" />
            <div v-if="validationMessage" class="text-danger mb-2">{{ validationMessage }}</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <input type="file" ref="profileInputRef" class="form-control d-none" id="feature_image"
                name="feature_image" @change="fileUpload" accept=".jpeg, .jpg, .png, .gif" />
              <label class="btn btn-info" for="feature_image">{{ $t('messages.upload') }}</label>
              <input type="button" class="btn btn-danger" name="remove" :value="$t('messages.remove')"
                @click="removeLogo()" v-if="ImageViewer" />
            </div>
          </div>
        </div>
        <InputField class="col-md-12" type="text" :is-required="true" :label="$t('promotion.lbl_name')"
          :placeholder="$t('admin_ui.enter_name')" v-model="name" :error-message="errors['name']"></InputField>
        <InputField class="col-md-12" type="textarea" :is-required="true" :label="$t('promotion.description')"
          :placeholder="$t('admin_ui.enter_description')" v-model="description" :error-message="errors['description']"
          :error-messages="errorMessages['description']"></InputField>
        <!-- <InputField class="col-md-12" type="datetime-local" :is-required="true" :label="$t('promotion.start_datetime')" placeholder="" v-model="start_date_time" :error-message="errors['start_date_time']" :error-messages="errorMessages['start_date_time']"></InputField>
        <InputField class="col-md-12" type="datetime-local" :is-required="true" :label="$t('promotion.end_datetime')" placeholder="" v-model="end_date_time" :error-message="errors['end_date_time']" :error-messages="errorMessages['end_date_time']"></InputField> -->

        <div class="form-group">
          <div class="col-md-12">
            <label class="form-label" for="start_date">{{ $t('promotion.start_datetime') }}</label>
            <div class="w-100">
              <flat-pickr id="start_date" class="form-control" :config="config" v-model="start_date_time"
                :value="start_date_time" placeholder="YYYY-MM-DD"></flat-pickr>
              <span class="text-danger">{{ errors['start_date_time'] }}</span>
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="col-md-12">
            <label class="form-label" for="end_date">{{ $t('promotion.end_datetime') }}</label>
            <div class="w-100">
              <flat-pickr id="end_date" class="form-control" :config="config" v-model="end_date_time"
                :value="end_date_time" placeholder="YYYY-MM-DD"></flat-pickr>
              <span class="text-danger">{{ errors['end_date_time'] }}</span>
            </div>
          </div>
        </div>
        <!-- Services Selection Field -->
        <div class="form-group">
          <div class="col-md-12">
            <label class="form-label">{{ $t('promotion.lbl_services') }} <span class="text-danger">*</span></label>

            <!-- Select All / Clear All buttons -->
            <div class="mb-2">
              <button 
                type="button" 
                class="btn btn-sm btn-primary me-2" 
                @click="selectAllServices"
                >
                {{ $t('Select All') }} ({{ serviceOptions.length }})
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-secondary" 
                @click="clearAllServices"

                v-if="services && services.length > 0">
                {{ $t("Clear All") }}
              </button>
            </div>

            <Multiselect 
              v-model="services" 
              :value="services" 
              v-bind="multiSelectOption" 
              :options="serviceOptions"
              id="services" 
              autocomplete="off" 

              track-by="value" 
              label="label" 
              mode="tags"
              :close-on-select="false" 
              :searchable="true" 
              :create-option="false"
              :placeholder="$t('promotion.select_services')" 
            />
            <span class="text-danger">{{ errors['services'] }}</span>
          </div>
        </div>
        <div class="form-group">
          <div class="row">
            <div class="col-md-12">
              <label class="form-label">{{ $t('promotion.coupon_type') }}</label>
              <Multiselect v-model="coupon_type" :value="coupon_type" v-bind="singleSelectOption"
                :options="couponOptions" id="type" autocomplete="off" :disabled="ISREADONLY"></Multiselect>
            </div>
          </div>
        </div>
        <div class="form-group col-md-12" v-if="coupon_type">
          <div v-if="coupon_type == 'custom'">
            <InputField class="col-md-12" type="text" :is-required="true" :label="$t('promotion.coupon_code')"
              :placeholder="$t('admin_ui.enter_coupon')" v-model="coupon_code" :error-message="errors['coupon_code']"
              :error-messages="errorMessages['coupon_code']" :is-read-only="ISREADONLY"></InputField>
          </div>
          <div v-else-if="coupon_type == 'bulk'">
            <InputField class="col-md-12" type="number" :is-required="true" :label="$t('promotion.number_of_coupon')"
              placeholder="" v-model="number_of_coupon" :error-message="errors['number_of_coupon']"
              :error-messages="errorMessages['number_of_coupon']" :is-read-only="ISREADONLY"></InputField>
          </div>
        </div>

        <div class="form-group">
          <div class="col-md-12">
            <label class="form-label">{{ $t('promotion.percent_or_fixed') }}</label>
            <Multiselect v-model="discount_type" :value="discount_type" v-bind="singleSelectOption"
              :options="typeOptions" id="type" autocomplete="off"></Multiselect>
          </div>
        </div>
        <div class="col-md-12" v-if="discount_type">
          <div v-if="discount_type == 'percent'">
            <InputField type="number" step="any" :is-required="true" :label="$t('promotion.discount_percentage')"
              :placeholder="$t('admin_ui.enter_discount_percentage')" v-model="discount_percentage"
              :error-message="errors['discount_percentage']"></InputField>
          </div>
          <div v-else-if="discount_type == 'fixed'">
            <InputField type="number" :is-required="true" :label="$t('promotion.discount_amount')"
              :placeholder="$t('admin_ui.enter_discount_amount')" v-model="discount_amount" :error-message="errors['discount_amount']">
            </InputField>
          </div>
        </div>

        <div>
          <InputField class="col-md-12" type="number" :is-required="true" :label="$t('promotion.use_limit')"
            placeholder="" v-model="use_limit" :error-message="errors['use_limit']"
            :error-messages="errorMessages['use_limit']" :is-read-only="coupon_type == 'bulk' || ISREADONLY">
          </InputField>
        </div>

        <div class="form-group">
          <div class="d-flex justify-content-between align-items-center">
            <label class="form-label" for="category-status">{{ $t('service.lbl_status') }}</label>
            <div class="form-check form-switch">
              <input class="form-check-input" :value="status" :true-value="1" :false-value="0" :checked="status"
                name="status" id="category-status" type="checkbox" v-model="status" />
            </div>
          </div>
        </div>
        <!-- <div v-for="field in customefield" :key="field.id">
          <FormElement v-model="custom_fields_data" :name="field.name" :label="field.label" :type="field.type" :required="field.required" :options="field.value" :field_id="field.id"></FormElement>
        </div> -->
      </div>
      <FormFooter :IS_SUBMITED="IS_SUBMITED"></FormFooter>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { readFile } from '@/helpers/utilities'
import { EDIT_URL, STORE_URL, UPDATE_URL, TIME_ZONE_LIST, UNIQUE_CHECK } from '../constant'
import { useField, useForm } from 'vee-validate'
import InputField from '@/vue/components/form-elements/InputField.vue'
import { useModuleId, useRequest, useOnOffcanvasHide } from '@/helpers/hooks/useCrudOpration'
import { buildMultiSelectObject } from '@/helpers/utilities'
import * as yup from 'yup'
import FormHeader from '@/vue/components/form-elements/FormHeader.vue'
import FormFooter from '@/vue/components/form-elements/FormFooter.vue'
import FormElement from '@/helpers/custom-field/FormElement.vue'
import FlatPickr from 'vue-flatpickr-component'

// props

const props = defineProps({
  createTitle: { type: String, default: '' },
  editTitle: { type: String, default: '' },
  customefield: { type: Array, default: () => [] },
  defaultImage: { type: String, default: 'https://dummyimage.com/600x300/cfcfcf/000000.png' },
  services: { type: Array, default: () => [] } // ADD THIS
})
const multiSelectOption = ref({
  mode: 'tags',
  searchable: true,
  closeOnSelect: false,
  canClear: true,
  canDeselect: true
})
const ImageViewer = ref(null)
const profileInputRef = ref(null)
const validationMessage = ref('');
const fileUpload = async (e) => {
  let file = e.target.files[0];
  const maxSizeInMB = 2;
  const maxSizeInBytes = maxSizeInMB * 1024 * 1024;

  if (file) {
    if (file.size > maxSizeInBytes) {
      // File is too large
      validationMessage.value = `File size exceeds ${maxSizeInMB} MB. Please upload a smaller file.`;
      // Clear the file input
      profileInputRef.value.value = '';
      return;
    }

    await readFile(file, (fileB64) => {
      ImageViewer.value = fileB64;
      profileInputRef.value.value = '';
      validationMessage.value = '';
    });
    feature_image.value = file;
  } else {
    validationMessage.value = '';
  }
};

// Function to delete Images
const removeImage = ({ imageViewerBS64, changeFile }) => {
  imageViewerBS64.value = null
  changeFile.value = null
}

const removeLogo = () => removeImage({ imageViewerBS64: ImageViewer, changeFile: feature_image })

const { getRequest, storeRequest, updateRequest, listingRequest } = useRequest()

// flatpicker
const config = ref({
  dateFormat: 'Y-m-d',
  static: true,
  minDate: new Date()
})

const singleSelectOption = ref({
  closeOnSelect: true,
  searchable: true
})


const currentId = useModuleId(() => {
  if (currentId.value > 0) {
    getRequest({ url: EDIT_URL, id: currentId.value }).then((res) => {
      if (res.status) {
        setFormData(res.data)
        ISREADONLY.value = true
      }
    })
  } else {
    resetForm()
    setFormData(defaultData())
  }
})

const resetMyForm = () => resetForm()

useOnOffcanvasHide('form-offcanvas', () => {
  setFormData(defaultData())
  resetMyForm()
})

// Default FORM DATA
const defaultData = () => {
  errorMessages.value = {}
  ISREADONLY.value = false

  return {
    name: '',
    description: '',
    services: [],
    start_date_time: new Date().toJSON().slice(0, 10),
    end_date_time: new Date(new Date().setDate(new Date().getDate() + 1)).toJSON().slice(0, 10),
    feature_image: null,
    status: 1,
    discount_amount: 0,
    discount_percentage: 0,
    discount_type: 'fixed',
    coupon_type: 'bulk',
    number_of_coupon: 1,
    coupon_code: '',
    use_limit: 1,
    minimum_booking_amount: 0,

    custom_fields_data: {},
    coupon: [
      {
        discount_amount: 0,
        discount_percentage: 0,
        discount_type: 'fixed',
        coupon_type: 'bulk',
        number_of_coupon: 1,
        coupon_code: '',
        use_limit: 1
      }
    ]
  }
}

//  Reset Form
const setFormData = (data) => {
  if (data.feature_image === props.defaultImage) {
    ImageViewer.value = null
  } else {
    ImageViewer.value = data.feature_image
  }

  // Parse services safely
  let parsedServices = []
  if (data.coupon && data.coupon[0] && data.coupon[0].services) {
    try {
      if (typeof data.coupon[0].services === 'string') {
        parsedServices = JSON.parse(data.coupon[0].services)
      } else if (Array.isArray(data.coupon[0].services)) {
        parsedServices = data.coupon[0].services
      }
    } catch (e) {
      console.error('Error parsing services:', e)
      parsedServices = []
    }
  }

  while (typeof parsedServices === 'string') {
    try { parsedServices = JSON.parse(parsedServices) } catch { parsedServices = []; break }
  }
  parsedServices = Array.isArray(parsedServices) ? parsedServices.map(Number) : []

  // Safe access to coupon data
  const firstCoupon = data.coupon && data.coupon[0] ? data.coupon[0] : {}

  resetForm({
    values: {
      minimum_booking_amount: firstCoupon.minimum_booking_amount ?? 0,
      name: data.name || '',
      description: data.description || '',
      services: parsedServices,
      start_date_time: data.start_date_time,
      end_date_time: data.end_date_time,
      feature_image: data.feature_image !== props.defaultImage ? data.feature_image : null,
      status: data.status !== undefined ? data.status : 1,
      discount_amount: firstCoupon.discount_amount || 0,
      discount_percentage: firstCoupon.discount_percentage || 0,
      discount_type: firstCoupon.discount_type || 'fixed',
      coupon_type: data.coupon && data.coupon.length === 1 ? 'custom' : 'bulk',
      number_of_coupon: data.coupon ? data.coupon.length : 1,
      coupon_code: firstCoupon.coupon_code || '',
      use_limit: firstCoupon.use_limit ?? 1
    }
  })
}
// Reload Datatable, SnackBar Message, Alert, Offcanvas Close
const reset_datatable_close_offcanvas = (res) => {
  IS_SUBMITED.value = false
  if (res.status) {
    window.successSnackbar(res.message)
    renderedDataTable.ajax.reload(null, false)
    bootstrap.Offcanvas.getInstance('#form-offcanvas').hide()
    setFormData(defaultData())
  } else {
    window.errorSnackbar(res.message)
    errorMessages.value = res.all_message
  }
}

// Validations
const validationSchema = yup.object({
  name: yup.string().required(window.i18n.global.t('admin_validation.name_required')),
  services: yup
    .array()
    .of(
      yup.number().oneOf(
        [0, ...props.services.map(s => s.id)], 
        'Selected service is invalid'
      )
    )
    .min(1, 'At least one service must be selected')
    .required('Services are required'),
  start_date_time: yup.string().required('start date time is a required field'),
  end_date_time: yup.string().required('End date is required').test('date-range', 'End date cannot be before start date', function(value) { return !this.parent.start_date_time || value >= this.parent.start_date_time }),
  description: yup.string().required('Description is a required field'),
  minimum_booking_amount: yup.number().required().min(0),
  use_limit: yup.number().integer().required('Use limit is required').min(0, 'Use limit cannot be negative').typeError('Use limit must be a valid number'),
  coupon_code: yup.string().when('coupon_type', {
    is: 'custom',
    then: () =>
      yup
        .string()
        .required('Coupon code is a required field')
        .test('unique', 'Coupon code must be unique', async function (value) {
          if (ISREADONLY.value) {
            return true
          }
          const isUnique = await storeRequest({ url: UNIQUE_CHECK, body: value, type: 'file' })
          if (!isUnique.isUnique) {
            return this.createError({ path: 'coupon_code', message: 'Coupon code must be unique' })
          }
          return true
        })
  }),
  number_of_coupon: yup.string().when('coupon_type', {
    is: 'bulk',
    then: (schema) => schema.required('Number of coupon is required').matches(/^[1-9]\d*$/, 'Number of coupon must be greater than or equal to 1')
  }),
  discount_amount: yup.string().when('discount_type', {
    is: 'fixed',
    then: () => yup.number().typeError('Discount amount must be a number').min(1, 'Discount amount must be greater than or equal to 1')
  }),
  discount_percentage: yup.string().when('discount_type', {
    is: 'percent',
    then: () =>
      yup
        .number()
        .required(window.i18n.global.t('admin_validation.value_required'))
        .test('is-less-than-100', 'Percent Value must be less than 100', (value) => {
          const numValue = parseFloat(value)
          return !isNaN(numValue) && numValue <= 100
        })
  })
})


const { handleSubmit, errors, resetForm } = useForm({
  validationSchema
})

const { value: name } = useField('name')
const { value: services } = useField('services')
const { value: description } = useField('description')
const { value: start_date_time } = useField('start_date_time')
const { value: end_date_time } = useField('end_date_time')
const { value: feature_image } = useField('feature_image')
const { value: status } = useField('status')
const { value: discount_amount } = useField('discount_amount')
const { value: discount_percentage } = useField('discount_percentage')
const { value: discount_type } = useField('discount_type')
const { value: coupon_type } = useField('coupon_type')
const { value: number_of_coupon } = useField('number_of_coupon')
const { value: coupon_code } = useField('coupon_code')
const { value: use_limit } = useField('use_limit')
const { value: minimum_booking_amount } = useField('minimum_booking_amount')

const errorMessages = ref({})

const typeOptions = [
  { label: 'Percent', value: 'percent' },
  { label: 'Fixed', value: 'fixed' }
]

const couponOptions = [
  { label: 'Custom', value: 'custom' },
  { label: 'bulk', value: 'bulk' }
]

onMounted(() => {
  setFormData(defaultData())
})

const serviceOptions = computed(() => {
  if (!props.services?.length) return [{ label: 'كل الحجوزات (بدون المنتجات والهدايا)', value: 0 }]

  const defaultOption = { label: 'كل الحجوزات (بدون المنتجات والهدايا)', value: 0 }

  const otherOptions = props.services.map(service => ({
    label: service.name || `Service ${service.id}`,
    value: service.id
  }))

  return [defaultOption, ...otherOptions]
})



// Select all services function
const selectAllServices = () => {
  const allServiceIds = [0]
  services.value = [...allServiceIds]
  console.log('Selected all services:', services.value)
}

// Clear all services function  
const clearAllServices = () => {
  services.value = []
  console.log('Cleared all services')
}



const ISREADONLY = ref(false)
// Form Submit
const IS_SUBMITED = ref(false)

const formSubmit = handleSubmit((values) => {
  if (IS_SUBMITED.value) return false
  IS_SUBMITED.value = true

  const cleanValues = {
    ...values,
    services: JSON.stringify(Array.isArray(values.services) ? values.services : []),
    description: values.description || '',
    name: values.name || '',
    custom_fields_data: JSON.stringify(values.custom_fields_data || {})
  }

  if (currentId.value > 0) {
    updateRequest({ url: UPDATE_URL, id: currentId.value, body: cleanValues, type: 'file' })
      .then((res) => reset_datatable_close_offcanvas(res))
      .catch((error) => {
        IS_SUBMITED.value = false
        console.error('Update error:', error)
        window.errorSnackbar(window.i18n.global.t("An error occurred while updating the promotion"))
      })
  } else {
    storeRequest({ url: STORE_URL, body: cleanValues, type: 'file' })
      .then((res) => reset_datatable_close_offcanvas(res))
      .catch((error) => {
        IS_SUBMITED.value = false
        console.error('Store error:', error)
        window.errorSnackbar(window.i18n.global.t("An error occurred while creating the promotion"))
      })
  }
})
</script>
