<script setup lang="ts">
import { useLanguage } from '~/composables/useLanguage'

const route = useRoute()
const { t } = useLanguage()

const entityType = computed(() => route.params.entityType as string)
</script>

<template>
  <div>
    <div class="page-header">
      <h1>{{ entityType }}</h1>
      <NuxtLink :to="`/${entityType}/create`" class="btn btn-primary">
        {{ t('create_new') }}
      </NuxtLink>
    </div>

    <SchemaList :entity-type="entityType" />
  </div>
</template>
