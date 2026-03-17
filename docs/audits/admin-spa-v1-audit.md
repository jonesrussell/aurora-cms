# Admin SPA Audit Report -- v1.0 Release Readiness

**Date:** 2026-03-12
**Scope:** `packages/admin/` -- Nuxt 3 + Vue 3 + TypeScript admin SPA
**Verdict:** Solid foundation with good patterns; several gaps must be addressed before v1.0.

---

## 1. Build System & Config

### nuxt.config.ts
- **Proxy setup:** Dev proxy and route rules both forward `/api` to `localhost:8081`. Dual config (nitro.devProxy + routeRules) is redundant -- one should suffice.
- **Runtime config:** Clean use of `NUXT_PUBLIC_*` env vars for `enableRealtime`, `appName`, `docsUrl`.
- **Missing:** No explicit CSS framework (Tailwind, UnoCSS). All styles are hand-written CSS with CSS custom properties. This is intentional but limits consistency at scale.
- **Missing:** No Nuxt modules configured (no `@nuxtjs/i18n`, `@pinia/nuxt`, etc.). i18n is hand-rolled.
- **Missing:** No error page (`error.vue`) for unmatched routes or server errors.

### tsconfig.json
- Minimal -- extends `.nuxt/tsconfig.json`. Acceptable for Nuxt.

### package.json
- Dependencies are lean: only `nuxt`, `vue`, `vue-router` in production.
- Dev deps include `@nuxt/test-utils`, `@playwright/test`, `@vue/test-utils`, `vitest`, `happy-dom`.
- **No linting tools:** No `eslint`, `@nuxt/eslint-config`, or `prettier`. No `lint` script.
- **No Pinia:** State management is all local refs. Acceptable for current scope but will not scale.

### vitest.config.ts
- Uses `@nuxt/test-utils/config` with nuxt environment. Coverage via `v8`.
- Properly excludes `e2e/` and `node_modules`.

### playwright.config.ts
- Single browser target (Chromium only). Consider adding Firefox for v1.0.
- Properly configured webServer auto-start.

---

## 2. Pages & Routing

### Dashboard (`pages/index.vue`)
- Fetches `/api/entity-types` and renders card grid. Good error/loading states.
- Onboarding detection via `node_type` count -- clever but fragile (fails silently if `node_type` entity type does not exist).
- Two separate `onMounted` hooks -- could race; no coordination between them.
- `IngestSummaryWidget` always renders (self-hides on 404). Good pattern.

### Entity List (`pages/[entityType]/index.vue`)
- Schema-driven list via `SchemaList` component.
- Entity type lifecycle controls (enable/disable) with confirmation modal.
- Modal has `role="dialog"` and `aria-modal="true"` -- good accessibility.
- **Issue:** `entityType` is computed but `useSchema(entityType.value)` is called with a non-reactive value (the `.value` at call time). If the route param changes without a full page navigation, the schema will be stale. This is a latent bug in Nuxt's file-based routing where `[entityType]` changes.

### Entity Create (`pages/[entityType]/create.vue`)
- Delegates to `SchemaForm`. Good error/success messaging.
- Navigates to edit page on success with 500ms delay -- the delay is arbitrary and could feel sluggish.
- **Missing:** No form validation feedback beyond API errors.

### Entity Edit (`pages/[entityType]/[id].vue`)
- Same pattern as create, delegates to `SchemaForm` with `entityId`.
- Success message auto-clears after 3s. Good UX.
- **Missing:** No unsaved changes warning (beforeRouteLeave guard).
- **Missing:** No delete action from the edit page.

---

## 3. Components

### Layout

**AdminShell.vue**
- Skip link, ARIA roles (banner, navigation, main), sr-only utility -- strong accessibility foundation.
- Responsive sidebar with overlay on mobile (768px breakpoint).
- Locale switcher in topbar. Sidebar closes on route change.
- Global styles defined here (btn, field, table, pagination) -- acts as a design system. This works but couples all styling to one component.
- **Missing:** No dark mode support.
- **Missing:** No user/account indicator or logout button.

**NavBuilder.vue**
- Fetches entity types and groups them via `groupEntityTypes()`.
- Error state shown if fetch fails.
- Uses `router-link-active` class for active state.
- **Issue:** Fetches `/api/entity-types` independently from the dashboard page -- results in duplicate API calls on initial load.

### Schema Components

**SchemaField.vue**
- Widget dispatch via `resolveComponent()` map. Covers: text, email, url, textarea, richtext, number, boolean, select, datetime, entity_autocomplete, hidden, machine_name, password, image, file.
- Falls back to TextInput for unknown widgets. Good defensive behavior.
- Respects `x-access-restricted` for disabled state.

**SchemaForm.vue**
- Handles create (POST) and edit (GET + PATCH) modes.
- Initializes defaults from schema (`default` property, boolean convention).
- Provides `schemaFormData` and `schemaFormEditMode` via Vue `provide()` for child widgets (used by MachineNameInput).
- Loading/error/load-error states all handled.
- **Issue:** No client-side validation. Relies entirely on server-side 422 responses.
- **Issue:** No dirty tracking -- submits all fields even if unchanged.

**SchemaList.vue**
- Pagination (offset/limit), sorting, delete with confirmation.
- Column selection via `x-list-display` with fallback to first 6 fields.
- SSE real-time refresh via `useRealtime` (auto-refreshes on `entity.saved`/`entity.deleted`).
- `aria-live="polite"` region for screen reader pagination updates.
- Delete button has accessible label: `t('delete') + ': ' + getEntityLabel(entity)`.
- **Issue:** Delete uses `confirm()` (browser native) -- not consistent with the modal pattern used elsewhere.
- **Issue:** No bulk operations (select multiple, bulk delete).
- **Issue:** No filtering/search on the list view.

### Widgets (11 total)

| Widget | Status | Notes |
|---|---|---|
| TextInput | Good | Supports text/email/url via schema, maxlength |
| TextArea | Good | Simple, rows=5 default |
| NumberInput | Good | min/max from schema, NaN protection |
| Toggle | Good | Checkbox with label |
| Select | Good | Enum + x-enum-labels support |
| DateTimeInput | Good | ISO 8601 timezone stripping for datetime-local |
| RichText | Fair | contenteditable div with HTML sanitizer. No toolbar. Sanitizer is DOM-based -- adequate but not XSS-proof for all edge cases. |
| EntityAutocomplete | Good | Debounced search, keyboard navigation (ArrowUp/Down/Enter/Escape), ARIA combobox pattern, clear button |
| MachineNameInput | Good | Auto-generates from source field, locks in edit mode, regex pattern validation |
| FileUpload | Good | XHR with progress, image preview with object URL cleanup, handles upload errors |
| HiddenField | Good | Renders nothing -- correct behavior |

**Missing widgets:** No color picker, no tag/multi-value input, no map/geo widget, no JSON editor.

### Other Components

**OnboardingPrompt.vue**
- Clean, well-structured first-run experience.
- External link has `target="_blank" rel="noreferrer"` -- correct.

**IngestSummaryWidget.vue**
- Fetches up to 1000 ingest_log entries and counts by status client-side.
- **Issue:** Fetching 1000 records to count statuses is inefficient. Should use a server-side aggregation endpoint.
- Gracefully hides on 404 (ingest_log entity type not registered).

---

## 4. Composables

### useEntity.ts
- Clean JSON:API client with `list`, `get`, `create`, `update`, `remove`, `search`.
- Proper `Content-Type: application/vnd.api+json` headers on mutations.
- Search uses `STARTS_WITH` filter with minimum 2-char threshold.
- **Missing:** No request cancellation (AbortController) for concurrent searches.
- **Missing:** No retry logic or error normalization.

### useSchema.ts
- Module-level `Map` cache -- persists across component instances (intended behavior).
- `sortedProperties()` correctly handles system readOnly vs x-access-restricted distinction.
- `invalidate()` available but never called from any component.
- **Missing:** No cache TTL or cache-busting mechanism.

### useNavGroups.ts
- Pure function `groupEntityTypes()` with deterministic ordering.
- 7 known groups with fallback "other" group.
- Handles all 12 registered entity types.
- Well-tested (6 tests).

### useLanguage.ts
- Hand-rolled i18n with two locales (en, fr).
- Locale persisted to localStorage.
- Simple `{token}` interpolation.
- `entityLabel()` with API fallback -- good pattern for dynamic entity types.
- **Missing:** No pluralization support.
- **Missing:** No date/number formatting (Intl API).

### useRealtime.ts
- SSE via EventSource with exponential backoff (3s base, 30s cap, 10 max retries).
- Correctly handles CONNECTING vs CLOSED states (avoids reconnect loops).
- Message buffer capped at 100 entries.
- SSE endpoint `/api/broadcast` noted as "not yet implemented" -- **blocker if realtime is a v1.0 feature**.
- `onUnmounted` cleanup -- proper lifecycle management.
- Disabled by default in dev (php -S single-process constraint).

---

## 5. Test Coverage

### Unit Tests (14 files, ~55 tests per CLAUDE.md)

| Area | File | Tests | Assessment |
|---|---|---|---|
| useEntity | useEntity.test.ts | 7 | All CRUD operations + search + error propagation |
| useSchema | useSchema.test.ts | 7 | Sorted properties, caching, invalidation, errors |
| useNavGroups | useNavGroups.test.ts | 6 | Grouping logic, edge cases, all 12 types |
| useLanguage | useLanguage.test.ts | 5 | Locale switching, fallback, interpolation |
| useRealtime | useRealtime.test.ts | 4 | SSE events, reconnect, autoConnect |
| AdminShell | AdminShell.test.ts | 1 | Locale switcher only |
| NavBuilder | NavBuilder.test.ts | 4 | Render, sections, error state |
| SchemaField | SchemaField.test.ts | 7 | Widget dispatch for all types |
| SchemaForm | SchemaForm.test.ts | 6 | Loading/error, create/edit, defaults |
| Toggle | Toggle.test.ts | 4 | Render, state, emit, disabled |
| TextInput | TextInput.test.ts | (exists) | Not read -- assumed similar |
| Select | Select.test.ts | (exists) | Not read -- assumed similar |
| FileUpload | FileUpload.test.ts | (exists) | Not read -- assumed similar |
| Dashboard page | dashboard.test.ts | 1 | Onboarding prompt |

### E2E Tests (4 specs)

| Spec | Tests | Assessment |
|---|---|---|
| dashboard.spec.ts | 4 | Card rendering, navigation, onboarding |
| navigation.spec.ts | 3 | Dashboard link, section headings, entity labels |
| entity-form.spec.ts | 3 | Form fields, disabled fields, submit |
| type-lifecycle.spec.ts | 1 | Disable last type with warning |

### Coverage Gaps
- **No tests for:** SchemaList (pagination, sorting, delete, SSE refresh)
- **No tests for:** IngestSummaryWidget
- **No tests for:** OnboardingPrompt (only tested indirectly via dashboard page test)
- **No tests for:** RichText sanitizer
- **No tests for:** DateTimeInput timezone handling
- **No tests for:** MachineNameInput auto-generation
- **No tests for:** EntityAutocomplete (debounce, keyboard nav, selection)
- **No tests for:** NumberInput
- **No tests for:** Entity list page ([entityType]/index.vue)
- **No tests for:** Entity edit page ([entityType]/[id].vue)
- **No E2E for:** Edit flow, delete from list, pagination, sorting

---

## 6. Missing Screens/Flows for CMS v1.0

### Critical (expected for any CMS admin)
1. **User management page** -- No dedicated users list/create/edit. Currently relies on generic entity CRUD for `user` type, but lacks password change, role assignment, account status toggle.
2. **Login/logout** -- No authentication UI. No login page, no session management, no logout button.
3. **Error page** -- No `error.vue` for 404s or server errors.
4. **Media library** -- File upload widget exists, but no dedicated media browser/gallery view for selecting existing media.

### Important (expected for CMS usability)
5. **Config/settings page** -- No system configuration UI (site name, default locale, etc.).
6. **Taxonomy management** -- Relies on generic entity CRUD; no tree/hierarchy view for terms.
7. **Menu builder** -- No drag-and-drop menu tree editor; relies on flat entity list.
8. **Bulk operations** -- No multi-select, bulk delete, bulk publish/unpublish.
9. **Content search/filter** -- List view has no text search or field-based filtering.
10. **Revision history** -- `x-revisionable` exists in schema but no revision diff/revert UI.

### Nice-to-Have (post-v1.0)
11. **Workflow management UI** -- Visual workflow state editor.
12. **Dashboard widgets** -- Only IngestSummaryWidget exists. No content stats, recent activity, quick actions.
13. **Dark mode** -- CSS vars are set up but no toggle.
14. **Keyboard shortcuts** -- None beyond standard browser behavior.
15. **Breadcrumbs** -- No breadcrumb trail.

---

## 7. i18n Assessment

### Coverage
- **en.json:** 98 keys -- covers all currently used strings.
- **fr.json:** 98 keys -- 1:1 parity with en.json. All keys translated.
- Both files are well-structured with logical grouping (UI chrome, errors, onboarding, nav groups, entity types, fields).

### Issues
- **No unused keys detected** -- all keys are referenced by components or composables.
- **No missing keys detected** -- all `t()` calls in templates have corresponding en.json entries.
- **`field_*` keys** (field_title, field_machine_name, etc.) are defined but appear unused in the codebase -- they exist for future field label i18n but are not yet wired to SchemaField (which uses `x-label` from the schema instead).
- **Only 2 locales** (en, fr). No mechanism to add more without code changes to `useLanguage.ts`.
- **No RTL support** for potential Arabic/Hebrew locales.

---

## 8. Summary of Findings

### Strengths
- Schema-driven architecture is well-executed -- one set of components handles all entity types.
- Accessibility is above average: skip link, ARIA roles, combobox pattern, sr-only, aria-live.
- Error handling is consistent across all API calls (loading/error/empty states).
- i18n has full en/fr parity.
- Test infrastructure is solid (Vitest + Playwright properly configured).
- Real-time updates via SSE with proper reconnection logic.
- Responsive layout works for mobile.

### Blockers for v1.0
1. No authentication/login UI.
2. No error page (error.vue).
3. No linting tooling (eslint).
4. SSE endpoint `/api/broadcast` not implemented on the backend.
5. IngestSummaryWidget fetches 1000 records for client-side counting.

### High Priority Issues
6. `useSchema()` called with non-reactive param -- stale schema on in-page route changes.
7. No client-side form validation.
8. No unsaved changes warning on navigation.
9. SchemaList has no test coverage.
10. No media browser -- only upload, no selection of existing media.
11. No user management beyond generic entity CRUD (no password change, no role UI).

### Low Priority Issues
12. Duplicate `/api/entity-types` fetches (NavBuilder + dashboard).
13. No dark mode toggle.
14. RichText widget has no toolbar.
15. `field_*` i18n keys defined but unused.
16. Redundant proxy config in nuxt.config.ts (devProxy + routeRules).
