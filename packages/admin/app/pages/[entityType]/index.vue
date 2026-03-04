<script setup lang="ts">
import { useLanguage } from '~/composables/useLanguage'
import { useSchema } from '~/composables/useSchema'

const route = useRoute()
const { t } = useLanguage()

const entityType = computed(() => route.params.entityType as string)
const { schema, fetch: fetchSchema } = useSchema(entityType.value)
onMounted(() => fetchSchema())
const entityLabel = computed(() => schema.value?.title ?? entityType.value)
</script>

<template>
  <div>
    <div class="page-header">
      <h1>{{ entityLabel }}</h1>
      <NuxtLink :to="`/${entityType}/create`" class="btn btn-primary">
        {{ t('create_new') }}
      </NuxtLink>
    </div>

    <SchemaList :entity-type="entityType" />
  </div>
</template>
