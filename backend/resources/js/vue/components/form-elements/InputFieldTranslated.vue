<template>
  <div class="form-group">
    <label class="form-label">{{ label }} <span v-if="isRequired" class="text-danger">*</span></label>
    <ul class="nav nav-tabs mb-2">
      <li class="nav-item" v-for="lang in languages" :key="lang">
        <button
          class="nav-link"
          :class="{ active: currentLang === lang }"
          @click.prevent="currentLang = lang"
        >
          {{ lang.toUpperCase() }}
        </button>
      </li>
    </ul>

    <input
      v-for="lang in languages"
      v-show="currentLang === lang"
      :key="lang"
      type="text"
      class="form-control"
      :placeholder="`${placeholder} (${lang.toUpperCase()})`"
      :value="modelValue[lang] || ''"
      @input="updateTranslation(lang, $event.target.value)"
    />

    <span class="text-danger">{{ errorMessage }}</span>
    <ul class="m-0">
      <li class="text-danger" v-for="msg in errorMessages" :key="msg">{{ msg }}</li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  label: { type: String, default: '' },
  languages: { type: Array, default: () => ['ar', 'en'] },
  placeholder: { type: String, default: '' },
  isRequired: { type: Boolean, default: false },
  errorMessage: { type: String, default: '' },
  errorMessages: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:modelValue'])

const currentLang = ref(props.languages[0])

const updateTranslation = (lang, value) => {
  const updated = { ...props.modelValue, [lang]: value }
  emit('update:modelValue', updated)
}
</script>
