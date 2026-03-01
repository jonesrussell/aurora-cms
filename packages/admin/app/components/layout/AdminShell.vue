<script setup lang="ts">
import { useLanguage } from '~/composables/useLanguage'

const { t } = useLanguage()
</script>

<template>
  <div class="admin-shell">
    <header class="topbar">
      <NuxtLink to="/" class="topbar-brand">{{ t('app_name') }}</NuxtLink>
    </header>

    <div class="admin-body">
      <aside class="sidebar">
        <LayoutNavBuilder />
      </aside>
      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style>
:root {
  --sidebar-width: 220px;
  --topbar-height: 48px;
  --color-bg: #f5f5f5;
  --color-surface: #fff;
  --color-primary: #2563eb;
  --color-primary-hover: #1d4ed8;
  --color-danger: #dc2626;
  --color-text: #1f2937;
  --color-muted: #6b7280;
  --color-border: #e5e7eb;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: var(--color-text);
  background: var(--color-bg);
}

.admin-shell {
  min-height: 100vh;
}

.topbar {
  height: var(--topbar-height);
  background: var(--color-primary);
  color: #fff;
  display: flex;
  align-items: center;
  padding: 0 16px;
}

.topbar-brand {
  color: #fff;
  text-decoration: none;
  font-weight: 600;
  font-size: 16px;
}

.admin-body {
  display: flex;
  min-height: calc(100vh - var(--topbar-height));
}

.sidebar {
  width: var(--sidebar-width);
  background: var(--color-surface);
  border-right: 1px solid var(--color-border);
  padding: 16px 0;
}

.content {
  flex: 1;
  padding: 24px;
}

/* Shared utility styles */
.btn {
  display: inline-block;
  padding: 8px 16px;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  background: var(--color-surface);
  color: var(--color-text);
  cursor: pointer;
  font-size: 14px;
  text-decoration: none;
}
.btn:hover { background: var(--color-bg); }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
.btn-primary:hover { background: var(--color-primary-hover); }
.btn-danger { color: var(--color-danger); border-color: var(--color-danger); }
.btn-sm { padding: 4px 10px; font-size: 12px; }

.field { margin-bottom: 16px; }
.field-label { display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px; }
.field-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  font-size: 14px;
  font-family: inherit;
}
.field-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15); }
.field-textarea { resize: vertical; min-height: 100px; }
.field-richtext { min-height: 120px; padding: 8px 12px; border: 1px solid var(--color-border); border-radius: 4px; }
.field-description { margin-top: 4px; font-size: 12px; color: var(--color-muted); }
.required { color: var(--color-danger); }

.entity-table { width: 100%; border-collapse: collapse; background: var(--color-surface); border-radius: 4px; }
.entity-table th, .entity-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--color-border); }
.entity-table th { font-weight: 600; font-size: 13px; color: var(--color-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.entity-table th.sortable { cursor: pointer; }
.entity-table .empty { text-align: center; color: var(--color-muted); padding: 40px; }
.entity-table .actions { white-space: nowrap; }
.entity-table .actions > * { margin-right: 4px; }

.pagination { display: flex; align-items: center; gap: 12px; margin-top: 16px; font-size: 14px; color: var(--color-muted); }

.form-actions { margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--color-border); }

.loading { padding: 40px; text-align: center; color: var(--color-muted); }
.error { padding: 12px 16px; background: #fef2f2; color: var(--color-danger); border-radius: 4px; margin-bottom: 16px; }
.success { padding: 12px 16px; background: #f0fdf4; color: #16a34a; border-radius: 4px; margin-bottom: 16px; }

.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-header h1 { font-size: 24px; font-weight: 600; }
</style>
