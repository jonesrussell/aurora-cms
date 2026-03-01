<script setup lang="ts">
import type { SchemaProperty } from '~/composables/useSchema'

const props = defineProps<{
  modelValue: string | number
  label?: string
  description?: string
  required?: boolean
  disabled?: boolean
  schema?: SchemaProperty
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const options = computed(() => {
  const enumValues = props.schema?.enum ?? []
  const labels = props.schema?.['x-enum-labels'] ?? {}
  return enumValues.map((val: string) => ({
    value: val,
    label: labels[val] ?? val,
  }))
})
</script>

<template>
  <div class="field">
    <label v-if="label" class="field-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    <select
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      class="field-input"
      @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="" disabled>-- Select --</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <p v-if="description" class="field-description">{{ description }}</p>
  </div>
</template>
