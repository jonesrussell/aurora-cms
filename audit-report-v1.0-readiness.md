# Waaseyaa v1.0 Backend Architecture Audit Report

**Date:** 2026-03-12
**Scope:** 40 packages (38 + 3 metapackages), kernel bootstrapping, middleware pipeline, content model, multi-tenant, AI integration, test coverage

---

## Critical

### C1. PHPUnit Fatal Error in Phase16 Test — Incompatible `create()` Override
**File:** `/home/jones/dev/waaseyaa/tests/Integration/Phase16/NoteApiIntegrationTest.php` L218
**Root cause:** `NoteInMemoryStorage::create(array $values = []): Note` narrows the return type of the parent `InMemoryEntityStorage::create(array $values = []): EntityInterface` (at `/home/jones/dev/waaseyaa/packages/api/tests/Fixtures/InMemoryEntityStorage.php` L26). PHP 8.3 enforces covariant return types on inherited methods, but the method also overrides storage behavior (maintaining a separate `$notes` array plus `$nextId` counter) that duplicates and shadows the parent's `$entities` array. The `save()`, `load()`, `loadMultiple()`, `delete()`, and `getQuery()` methods all operate on `$this->notes`, not the parent's `$this->entities`, meaning the parent's `EntityStorageInterface` contract is effectively re-implemented rather than extended.

**Fix direction:** Either (a) change return type to `EntityInterface` and add `@return Note` in phpdoc, or (b) don't extend `InMemoryEntityStorage` at all — implement `EntityStorageInterface` directly, since no parent behavior is reused.

### C2. TenantMiddleware Not Wired into HttpKernel Pipeline
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Kernel/HttpKernel.php` L135-146
**Issue:** The `HttpPipeline` in `HttpKernel::handle()` adds `BearerAuthMiddleware`, `SessionMiddleware`, `CsrfMiddleware`, and `AuthorizationMiddleware` — but `TenantMiddleware` is never instantiated or added. The `TenantContext`, `TenantResolverInterface`, and `TenantMiddleware` classes exist in `packages/foundation/src/Tenant/` but are dead code from the kernel's perspective. Any entity with a `tenant_id` field (e.g., Note) has no automatic tenant scoping at the request level.

**Impact:** In a multi-tenant deployment, all tenants would see each other's data. The `Note` entity stores `tenant_id` but nothing enforces it.

### C3. No Storage-Level Tenant Filtering
**File:** `/home/jones/dev/waaseyaa/packages/entity-storage/src/Connection/ConnectionResolverInterface.php`
**Issue:** `ConnectionResolverInterface` documents a "multi-tenancy seam" but only `SingleConnectionResolver` exists. There is no `TenantAwareConnectionResolver` or query-level `WHERE tenant_id = ?` filtering. Tenant isolation is entirely absent from the storage layer.

---

## High

### H1. Middleware Pipeline Has No Attribute-Based Priority — Uses Manual Ordering
**Files:** `packages/user/src/Middleware/*.php`, `packages/access/src/Middleware/AuthorizationMiddleware.php`
**Issue:** None of the middleware classes use `#[AsMiddleware(priority: N)]` attributes despite the foundation providing `AsMiddleware` attribute support and `PackageManifestCompiler` supporting attribute scanning. Instead, middleware ordering is hardcoded in `HttpKernel::handle()` via sequential `withMiddleware()` calls (L135-146). This means:
- The order is: BearerAuth -> Session -> CSRF -> Authorization
- This is correct functionally, but the attribute-based auto-discovery documented in CLAUDE.md ("Add `#[AsMiddleware(priority: N)]` attribute") is unused.
- Adding new middleware requires editing `HttpKernel.php` directly.

### H2. `graphql` Package Has Zero Source Files and Zero Tests
**Package:** `packages/graphql/`
**Issue:** Marked as "(stub)" in CLAUDE.md. Has `composer.json` but no `src/` content. If shipping v1.0 with this package in the metapackage dependency tree, it adds dead weight and could confuse users.

### H3. HttpKernel::handle() Returns `never` but Has No Global Exception Handler
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Kernel/HttpKernel.php` L52
**Issue:** The `handle(): never` method wraps routing and auth in try-catch, but there's no top-level try-catch around the entire method. If `boot()` throws (e.g., database connection failure, missing config), the exception propagates to `public/index.php` as an unhandled fatal error with a PHP stack trace visible to users. `ConsoleKernel::handle()` (L79-89) correctly wraps `boot()` in try-catch, but `HttpKernel` does not.

### H4. `menu` and `path` Packages Missing Access Policies
**Packages:** `packages/menu/`, `packages/path/`
**Issue:** Neither `menu` nor `path` has `*AccessPolicy*` or `*Access*` files (only service providers and entity classes). Without access policies, these entity types fall through to the default deny-by-default behavior in `EntityAccessHandler`, making them completely inaccessible via the API unless `_public` route options are used.

### H5. `relationship` Package Missing Access Policy and Service Provider
**Package:** `packages/relationship/`
**Issue:** The relationship package has entity classes and discovery/traversal services but no `AccessPolicy` and no `ServiceProvider`. Without a service provider, relationship entity types are not auto-registered during kernel boot. They would need manual registration in `config/entity-types.php`.

---

## Medium

### M1. `index.php` Front Controller Has No Boot Error Handling
**File:** `/home/jones/dev/waaseyaa/public/index.php` L27-28
**Issue:** The front controller is only 28 lines and calls `$kernel->handle()` without any try-catch. If `HttpKernel::boot()` fails (database file missing, config parse error), users see a raw PHP fatal error. Compare with `ConsoleKernel` which wraps boot in try-catch and outputs to STDERR cleanly.

### M2. Service Provider `setKernelContext()` Coupling
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Kernel/AbstractKernel.php` L137
**Issue:** `setKernelContext($projectRoot, $config, $formatters)` passes raw config array and formatters into every provider. This creates tight coupling between kernel internals and providers. A registry/container pattern would be more maintainable for v1.0.

### M3. `CacheConfigResolver` Created After `boot()` in HttpKernel
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Kernel/HttpKernel.php` L55
**Issue:** `CacheConfigResolver` is created in `handle()` after `boot()` completes. If any boot step needs cache config resolution, it won't have access. Not a bug today but a latent design issue.

### M4. AI Pipeline Package Has Contracts but No Real Implementations
**Packages:** `packages/ai-schema/`, `packages/ai-agent/`, `packages/ai-pipeline/`, `packages/ai-vector/`
**Issue:** These packages have interfaces, value objects, executors, and dispatchers but rely on external embedding providers (`EmbeddingProviderFactory::fromConfig()`). The factory and provider implementations should be validated against real API integrations (OpenAI, etc.) before v1.0 if AI features are advertised.

### M5. `cms`, `core`, `full` Metapackages Have No Tests
**Packages:** `packages/cms/`, `packages/core/`, `packages/full/`
**Issue:** Metapackages have `composer.json` only. No smoke tests validate that their dependency trees resolve correctly or that the combined package set boots without conflict.

### M6. Version String Hardcoded as `0.1.0`
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Kernel/ConsoleKernel.php` L177
**Issue:** `AboutCommand` receives `'version' => '0.1.0'` hardcoded. For v1.0 release, this needs to be updated and ideally sourced from a single canonical location.

---

## Low

### L1. Packages with Unit Tests but Low Coverage
The following packages have tests but relatively few:
- `relationship`: 2 test files (discovery + traversal only; no entity/access tests)
- `state`: 2 test files
- `search`: 3 test files
- `i18n`: 4 test files

### L2. `NullTenantResolver` Exists but Is Never Used
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Tenant/NullTenantResolver.php`
**Issue:** A null-object resolver exists for single-tenant mode, but since `TenantMiddleware` is never wired (see C2), even this is dead code.

### L3. CORS Handler Allows Dev Localhost Ports Conditionally
**File:** `/home/jones/dev/waaseyaa/packages/foundation/src/Kernel/HttpKernel.php` L200
**Issue:** `allowDevLocalhostPorts` is gated by `isDevelopmentMode()` which checks `APP_ENV`. This is correct, but there's no explicit production guard — if `APP_ENV` is unset, `isDevelopmentMode()` returns false (safe default). Documented for awareness.

### L4. `admin` Package (Nuxt SPA) Not Covered in This Audit
The admin SPA at `packages/admin/` has its own test suite (Vitest + Playwright) which is out of scope for this backend audit.

### L5. Integration Tests Only Go Up to Phase16
**Directory:** `/home/jones/dev/waaseyaa/tests/Integration/`
**Note:** Integration test phases appear to track implementation milestones. Current coverage is through Phase16. All content type packages introduced after Phase16 may lack integration test coverage.

---

## Summary

| Severity | Count | Key Themes |
|----------|-------|------------|
| Critical | 3 | Test suite crash, tenant isolation completely absent |
| High | 5 | No attribute-based middleware, missing access policies, no boot error handling in HTTP |
| Medium | 6 | Front controller fragility, AI completeness, version string |
| Low | 5 | Minor coverage gaps, dead code |

**Top 3 v1.0 Blockers:**
1. Fix the `NoteInMemoryStorage::create()` return type incompatibility to unblock the test suite (C1)
2. Either implement tenant isolation end-to-end (storage filtering + middleware wiring) or explicitly document single-tenant-only for v1.0 and remove `tenant_id` from Note (C2, C3)
3. Add access policies for `menu`, `path`, and `relationship` packages, or those entity types will be inaccessible via API (H4, H5)
