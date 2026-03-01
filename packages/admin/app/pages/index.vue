<script setup lang="ts">
import { useLanguage } from '~/composables/useLanguage'

const { t } = useLanguage()

interface EntityTypeInfo {
  id: string
  label: string
  keys: Record<string, string>
}

const entityTypes = ref<EntityTypeInfo[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await $fetch<{ data: EntityTypeInfo[] }>('/api/entity-types')
    entityTypes.value = response.data
  } catch {
    // Dashboard loads with empty state.
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="page-header">
      <h1>{{ t('dashboard') }}</h1>
    </div>

    <div v-if="loading" class="loading">{{ t('loading') }}</div>

    <div v-else class="card-grid">
      <NuxtLink
        v-for="et in entityTypes"
        :key="et.id"
        :to="`/${et.id}`"
        class="card"
      >
        <h2 class="card-title">{{ et.label }}</h2>
        <p class="card-sub">{{ et.id }}</p>
      </NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
}
.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 20px;
  text-decoration: none;
  color: var(--color-text);
  transition: border-color 0.15s;
}
.card:hover { border-color: var(--color-primary); }
.card-title { font-size: 18px; margin-bottom: 4px; }
.card-sub { font-size: 13px; color: var(--color-muted); }
</style>
