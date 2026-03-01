<script setup lang="ts">
import type { SchemaProperty } from '~/composables/useSchema'

const props = defineProps<{
  name: string
  modelValue: any
  schema: SchemaProperty
}>()

const emit = defineEmits<{ 'update:modelValue': [value: any] }>()

const label = computed(() => props.schema['x-label'] ?? props.name)
const description = computed(() => props.schema['x-description'] ?? props.schema.description)
const required = computed(() => props.schema['x-required'] ?? false)

const widgetMap: Record<string, string> = {
  text: 'WidgetsTextInput',
  email: 'WidgetsTextInput',
  url: 'WidgetsTextInput',
  textarea: 'WidgetsTextArea',
  richtext: 'WidgetsRichText',
  number: 'WidgetsNumberInput',
  boolean: 'WidgetsToggle',
  select: 'WidgetsSelect',
  datetime: 'WidgetsDateTimeInput',
  entity_autocomplete: 'WidgetsEntityAutocomplete',
  hidden: 'WidgetsHiddenField',
  password: 'WidgetsTextInput',
  image: 'WidgetsTextInput',
  file: 'WidgetsTextInput',
}

const widgetComponent = computed(() => {
  const widget = props.schema['x-widget'] ?? 'text'
  return widgetMap[widget] ?? 'WidgetsTextInput'
})
</script>

<template>
  <component
    :is="widgetComponent"
    :model-value="modelValue"
    :label="label"
    :description="description"
    :required="required"
    :schema="schema"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
