<template>
  <form @submit="formSubmit">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
      <FormHeader :currentId="currentId" :editTitle="editTitle" :createTitle="createTitle"></FormHeader>
      <div class="offcanvas-body">
        <div class="form-group">
          <div class="text-center">
            <img :src="ImageViewer || defaultImage" alt="feature-image" class="img-fluid mb-2 avatar-140 avatar-rounded" />
            <div v-if="validationMessage" class="text-danger mb-2">{{ validationMessage }}</div>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <input type="file" ref="profileInputRef" class="form-control d-none" id="feature_image" name="feature_image" @change="fileUpload" accept=".jpeg, .jpg, .png, .gif" />
              <label class="btn btn-info" for="feature_image">{{ $t('messages.upload') }}</label>
              <input type="button" class="btn btn-danger" name="remove" :value="$t('messages.remove')" @click="removeLogo()" v-if="ImageViewer" />
            </div>
          </div>
        </div>
        <InputField
          :is-required="true"
          :label="$t('service.lbl_name') + ' ' + $t('settings.translate.ar')"
          :placeholder="$t('service.placeholder_name')"
          v-model="nameAr"
          :error-message="nameArError"
          :error-messages="errorMessages['name'] && errorMessages['name']['ar']"
        />

        <InputField
          :is-required="true"
          :label="$t('service.lbl_name') + ' ' + $t('settings.translate.en')"
          :placeholder="$t('service.placeholder_name')"
          v-model="nameEn"
          :error-message="nameEnError"
          :error-messages="errorMessages['name'] && errorMessages['name']['en']"
        />
        <InputField class="col-md-12" type="text" :is-required="true" :label="$t('service.lbl_duration_min')"  :placeholder="$t('service.service_duration')" v-model="duration_min" :error-message="errors['duration_min']" :error-messages="errorMessages['duration_min']"></InputField>
        <InputField class="col-md-12" type="text" :is-required="true" :label="`${$t('service.lbl_default_price')} (${CURRENCY_SYMBOL})`"  :placeholder="$t('service.enter_price')" v-model="default_price" :error-message="errors['default_price']" :error-messages="errorMessages['default_price']"></InputField>


        <div class="form-group">
          <label class="form-label" for="category_id">{{ $t('service.lbl_category') }} <span class="text-danger">*</span> </label>
          <Multiselect v-model="category_id" :placeholder="$t('service.select_category')" :value="category_id" v-bind="categories" id="category_id" @select="changeCategory"></Multiselect>
          <span v-if="errorMessages['category_id']">
            <ul class="text-danger">
              <li v-for="err in errorMessages['category_id']" :key="err">{{ err }}</li>
            </ul>
          </span>
          <span class="text-danger">{{ errors.category_id }}</span>
        </div>

        <div class="form-group" v-if="subCategories.options.length > 0">
          <label class="form-label" for="sub_category_id">{{ $t('service.lbl_sub_category') }} </label>
          <Multiselect v-model="sub_category_id" :value="sub_category_id" :placeholder="$t('service.select_subcategory')" v-bind="subCategories" id="sub_category_id"></Multiselect>
          <span v-if="errorMessages['sub_category_id']">
            <ul class="text-danger">
              <li v-for="err in errorMessages['sub_category_id']" :key="err">{{ err }}</li>
            </ul>
          </span>
          <span class="text-danger">{{ errors.sub_category_id }}</span>
        </div>

        <div v-for="field in customefield" :key="field.id">
          <FormElement v-model="custom_fields_data" :name="field.name" :label="field.label" :type="field.type" :required="field.required" :options="field.value" :field_id="field.id"></FormElement>
        </div>

        <div class="form-group col-md-12">
              <label class="form-label" for="description_ar">{{$t('service.lbl_description_ar')}}</label>
              <textarea class="form-control" :placeholder="$t('service.description')" v-model="descriptionAr" id="description_ar"></textarea>
              <span v-if="errorMessages['description.ar']">
                <ul class="text-danger">
                  <li v-for="err in errorMessages['description.ar']" :key="err">{{ err }}</li>
                </ul>
              </span>
              <span class="text-danger">{{ errors['description.ar'] }}</span>
            </div>
        
        <div class="form-group col-md-12">
              <label class="form-label" for="description_en">{{$t('service.lbl_description_en')}}</label>
              <textarea class="form-control" :placeholder="$t('service.description')" v-model="descriptionEn"  id="description_en"></textarea>
              <span v-if="errorMessages['description.en']">
                <ul class="text-danger">
                  <li v-for="err in errorMessages['description.en']" :key="err">{{ err }}</li>
                </ul>
              </span>
            <span class="text-danger">{{ errors['description.en'] }}</span>
        </div>

        <div class="form-group d-none">
          <div class="d-flex justify-content-between align-items-center">
            <label class="form-label">
              {{ $t('service.lbl_visible_home') }}
            </label>
            <div class="form-check form-switch">
              <input
                class="form-check-input"
                type="checkbox"
                v-model="is_visible"
                :true-value="1"
                :false-value="0"
              />
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <div class="d-flex justify-content-between align-items-center">
            <label class="form-label" for="category-status">{{ $t('service.lbl_status') }}</label>
            <div class="form-check form-switch">
              <input class="form-check-input" :value="status" :checked="status" name="status" id="category-status" type="checkbox" v-model="status" />
            </div>
          </div>
        </div>

      </div>
    <FormFooter :IS_SUBMITED="IS_SUBMITED"></FormFooter>
    </div>
  </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { EDIT_URL, STORE_URL, UPDATE_URL, CATEGORY_LIST,SERVICES_UNIQUE_CHECK } from '../constant/service'
import { useField, useForm } from 'vee-validate'
import InputField from '@/vue/components/form-elements/InputField.vue'

import { useModuleId, useRequest, useOnOffcanvasHide } from '@/helpers/hooks/useCrudOpration'
import * as yup from 'yup'
import { buildMultiSelectObject } from '@/helpers/utilities'
import { readFile } from '@/helpers/utilities'
import FormHeader from '@/vue/components/form-elements/FormHeader.vue'
import FormFooter from '@/vue/components/form-elements/FormFooter.vue'
import FormElement from '@/helpers/custom-field/FormElement.vue'

// props
const props=defineProps({
  createTitle: { type: String, default: '' },
  editTitle: { type: String, default: '' },
  customefield: { type: Array, default: () => [] },
  defaultImage: { type: String, default: 'https://dummyimage.com/600x300/cfcfcf/000000.png' }
})
const CURRENCY_SYMBOL = ref(window.defaultCurrencySymbol)
const { getRequest, storeRequest, updateRequest, listingRequest } = useRequest()

// Edit Form Or Create Form
const currentId = useModuleId(() => {
  subCategories.value.options = []
  if (currentId.value > 0) {
    getRequest({ url: EDIT_URL, id: currentId.value }).then((res) => {
      if (res.status) {
        setFormData(res.data)
        changeCategory(category_id.value)
        if (res.data.sub_category_id.value != 0) {
          sub_category_id.value = res.data.sub_category_id
        }
      }
    })
  } else {
    setFormData(defaultData())
  }
})

// File Upload Function
const ImageViewer = ref(null)
const profileInputRef = ref(null)
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

/*
 * Form Data & Validation & Handeling
 */
// Default FORM DATA
const defaultData = () => {
  ImageViewer.value = props.defaultImage
  errorMessages.value = {}
  return {
    id: null,
    name: {ar: '',en: ''},
     description: { ar: '', en: '' },
    duration_min: '',
    default_price: '',
    status: 1,
    is_visible: 0,
    category_id: '',
    sub_category_id: '',
    feature_image: null,
    custom_fields_data: {}
  }
}

//  Reset Form
const setFormData = (data) => {
  // معالجة الاسم
  let parsedName = { ar: '', en: '' }
  try {
    if (typeof data.name === 'string') {
      parsedName = JSON.parse(data.name)
    } else if (typeof data.name === 'object' && data.name !== null) {
      parsedName = {
        ar: data.name.ar ?? '',
        en: data.name.en ?? ''
      }
    }
  } catch (e) {
    console.warn('Invalid JSON name field:', data.name)
  }

  let parsedDescription = { ar: '', en: '' }
  try {
    if (typeof data.description === 'string') {
      parsedDescription = JSON.parse(data.description)
    } else if (typeof data.description === 'object' && data.description !== null) {
      parsedDescription = {
        ar: data.description.ar ?? '',
        en: data.description.en ?? ''
      }
    }
  } catch (e) {
    console.warn('Invalid JSON description field:', data.description)
  }

  // إدخال البيانات في الـ form
  resetForm({
    values: {
      id: data.id,
      name: parsedName,
      description: parsedDescription,
      duration_min: data.duration_min,
      default_price: data.default_price,
      is_visible: data.is_visible ?? 0,
      status: data.status,
      category_id: data.category_id,
      sub_category_id: data.sub_category_id,
      feature_image: data.feature_image,
      custom_fields_data: data.custom_field_data
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
    removeImage({ imageViewerBS64: ImageViewer, changeFile: feature_image })
  } else {
    window.errorSnackbar(res.message)
    errorMessages.value = res.all_message
  }
}

const numberRegex = /^\d+$/
// Validations
const validationSchema = yup.object({
  name: yup.object({
    ar: yup.string().required('Arabic Name is a required field')
      .test('unique-ar', 'Arabic name must be unique', async function (value) {
        const serviceId = id.value;

        const isUnique = await storeRequest({
          url: SERVICES_UNIQUE_CHECK,
          body: { service: value, service_id: serviceId, lang: 'ar' },
          type: 'file'
        });

        if (!isUnique.isUnique) {
          return this.createError({ path: 'name.ar', message: 'Arabic name must be unique' });
        }

        return true;
      }),

    en: yup.string().required('English Name is a required field')
      .test('unique-en', 'English name must be unique', async function (value) {
        const serviceId = id.value;

        const isUnique = await storeRequest({
          url: SERVICES_UNIQUE_CHECK,
          body: { service: value, service_id: serviceId, lang: 'en' },
          type: 'file'
        });

        if (!isUnique.isUnique) {
          return this.createError({ path: 'name.en', message: 'English name must be unique' });
        }

        return true;
      }),
  }),

  duration_min: yup.string()
    .required('Service Duration (Mins) is a required field')
    .matches(/^\d+$/, 'Only numbers are allowed'),

  default_price: yup.number()
    .typeError('Default Price must be a number')
    .required('Default Price is a required field')
    .min(0, 'Default Price cannot be negative'),

  category_id: yup.string()
    .required('Category is a required field')
    .matches(/^\d+$/, 'Only numbers are allowed'),
});



const { handleSubmit, errors, resetForm } = useForm({
  validationSchema
})
const { value: id } = useField('id')

// const { value: name } = useField('name')
const { value: nameAr, errorMessage: nameArError } = useField('name.ar')
const { value: nameEn, errorMessage: nameEnError } = useField('name.en')
const { value: descriptionAr, errorMessage: descriptionArError } = useField('description.ar')
const { value: descriptionEn, errorMessage: descriptionEnError } = useField('description.en')
const { value: duration_min } = useField('duration_min')
const { value: default_price } = useField('default_price')
const { value: is_visible } = useField('is_visible')
const { value: status } = useField('status')
const { value: category_id } = useField('category_id')
const { value: sub_category_id } = useField('sub_category_id')
const { value: feature_image } = useField('feature_image')
const { value: custom_fields_data } = useField('custom_fields_data')

const errorMessages = ref({})
const validationMessage = ref('');

const categories = ref({
  searchable: true,
  createOption: true,
  options: []
})

const subCategories = ref({
  searchable: true,
  createOption: true,
  options: []
})

const getCategoryList = () => {
  listingRequest({ url: CATEGORY_LIST, data: { id: 0 } }).then((res) => (categories.value.options = buildMultiSelectObject(res, { value: 'id', label: 'name' })))
}

const changeCategory = (value) => {
  sub_category_id.value = null
  listingRequest({ url: CATEGORY_LIST, data: { id: value } }).then((res) => (subCategories.value.options = buildMultiSelectObject(res, { value: 'id', label: 'name' })))
}

onMounted(() => {
  getCategoryList()
  setFormData(defaultData())
})

// Form Submit
const IS_SUBMITED = ref(false)
  const formSubmit = handleSubmit((values) => {
  if(IS_SUBMITED.value) return false
    values.name = JSON.stringify(values.name); 
    values.description = JSON.stringify(values.description)
    IS_SUBMITED.value = true; // Disable the save button and show loading spinner

  values.custom_fields_data = JSON.stringify(values.custom_fields_data);

  if (currentId.value > 0) {
    updateRequest({ url: UPDATE_URL, id: currentId.value, body: values, type: 'file' })
      .then((res) => reset_datatable_close_offcanvas(res))
  } else {
    storeRequest({ url: STORE_URL, body: values, type: 'file' })
      .then((res) => reset_datatable_close_offcanvas(res))
  } 
});

</script>
