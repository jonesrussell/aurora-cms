<script setup lang="ts">
import { useLanguage } from '~/composables/useLanguage'

const { t } = useLanguage()

interface EntityTypeInfo {
  id: string
  label: string
}

const entityTypes = ref<EntityTypeInfo[]>([])

onMounted(async () => {
  try {
    const response = await $fetch<{ data: EntityTypeInfo[] }>('/api/entity-types')
    entityTypes.value = response.data
  } catch {
    // Fallback: navigation will be empty.
  }
})
</script>

<template>
  <nav class="nav">
    <NuxtLink to="/" class="nav-item">
      {{ t('dashboard') }}
    </NuxtLink>
    <div class="nav-section">{{ t('content') }}</div>
    <NuxtLink
      v-for="et in entityTypes"
      :key="et.id"
      :to="`/${et.id}`"
      class="nav-item"
    >
      {{ et.label }}
    </NuxtLink>
  </nav>
</template>

<style scoped>
.nav { display: flex; flex-direction: column; }
.nav-section {
  padding: 12px 16px 4px;
  font-size: 11px;
  font-weight: 600;
  color: var(--color-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.nav-item {
  padding: 8px 16px;
  color: var(--color-text);
  text-decoration: none;
  font-size: 14px;
}
.nav-item:hover { background: var(--color-bg); }
.nav-item.router-link-active { color: var(--color-primary); font-weight: 500; }
</style>
