# SSR/Twig System Audit -- v1.0 Release Readiness

**Date:** 2026-03-12
**Scope:** `packages/ssr/` (src, templates, tests), `skeleton/templates/`, HttpKernel integration

---

## Critical

### C1. `HtmlFormatter` passes raw HTML without sanitization
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Formatter/HtmlFormatter.php` L13-16
- **Issue:** `format()` returns `(string) ($value ?? '')` with zero sanitization. Combined with `entity.html.twig` L4 using `{{ field.formatted|raw }}`, this is a stored-XSS vector. Any `text_long` field value flows straight to HTML output.
- **Impact:** Any user-submitted or ingested long-text field can inject arbitrary JavaScript.
- **Recommendation:** Integrate an HTML purifier (e.g., `HTMLPurifier` or `symfony/html-sanitizer`) before returning.

### C2. `configFactory` is always `null` at runtime -- `config()` Twig function is dead code
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/ThemeServiceProvider.php` L64-68
- **Issue:** The code sets `$configFactory = null`, checks `interface_exists(ConfigFactoryInterface::class)` but never resolves or injects an actual instance. The `if` block is empty. Therefore `{{ config("site.name") }}` always returns `''` in production.
- **Impact:** Any template relying on `config()` silently gets empty strings. Site name, logo URL, analytics IDs, etc. cannot be sourced from config.

### C3. No 500 or 403 error templates exist
- **Files missing:**
  - `packages/ssr/templates/500.html.twig` -- does not exist
  - `packages/ssr/templates/403.html.twig` -- does not exist
  - `skeleton/templates/500.html.twig` -- does not exist
  - `skeleton/templates/403.html.twig` -- does not exist
- **Impact:** `TwigErrorPageRenderer::render()` (L22) checks `$this->twig->getLoader()->exists($template)` and returns `null` when the template is missing. For 500 errors, users see raw JSON:API error payloads. For 403 (forbidden entity access), `SsrPageHandler` L139-144 returns a 404 instead of a proper 403 -- users denied access see "Not Found" which is a security information leak (confirms resource existence by distinguishing 404 from forbidden).

---

## High

### H1. Twig template caching is disabled in production
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/ThemeServiceProvider.php` L51
- **Issue:** `'cache' => false` is hardcoded. Twig compiles templates to PHP on every request. For production SSR with non-trivial templates, this is a significant performance penalty.
- **Recommendation:** Make cache configurable via `$config['ssr']['twig_cache']` with a sensible default (e.g., `sys_get_temp_dir() . '/waaseyaa_twig'`).

### H2. `home.html.twig` does not extend `base.html.twig` -- 435 lines of inline CSS duplicated
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/templates/home.html.twig` (435 lines)
- **File:** `/home/jones/dev/waaseyaa/skeleton/templates/home.html.twig` (418 lines, near-identical copy)
- **Issue:** Both home templates are standalone HTML documents with ~300 lines of inline `<style>`. They do NOT extend `layouts/base.html.twig`. The `page.html.twig` and `404.html.twig` templates DO extend it. This means the home page has no `header`/`footer` blocks, no `meta` block, no `scripts` block, and diverges structurally from all other pages.
- **Secondary issue:** `skeleton/templates/home.html.twig` is a near-duplicate of `packages/ssr/templates/home.html.twig` with minor content differences (missing `card-hint` divs). DRY violation.

### H3. `entity.html.twig` does not extend `base.html.twig` -- renders a bare `<article>` fragment
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/templates/entity.html.twig` (7 lines)
- **Issue:** This template renders only `<article>...</article>` with no `<!doctype>`, no `<html>`, no `<head>`. When served as a full page via `RenderController::renderEntity()`, the browser receives an HTML fragment, not a valid document. No title, no charset, no viewport meta.
- **Impact:** Entity pages have no page chrome (header, footer, styles, scripts).

### H4. Forbidden entities return 404 instead of 403
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/SsrPageHandler.php` L139-144
- **Issue:** When `EditorialVisibilityResolver::canRender()` returns forbidden, the handler calls `renderNotFound()` which returns 404. This conflates access control with resource existence. A proper 403 response with a "Forbidden" template should be returned.

### H5. `RenderCache::invalidateEntity()` silently no-ops without `TagAwareCacheInterface`
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/RenderCache.php` L58-69
- **Issue:** If the cache backend does not implement `TagAwareCacheInterface`, `invalidateEntity()` returns silently without invalidating anything. There is no logging and no fallback (e.g., clearing the entire cache bin). Stale rendered pages could persist indefinitely after entity updates.

### H6. `SsrPageHandler` creates new instances of `RenderController`, `EntityRenderer`, `PathAliasResolver`, and `EditorialVisibilityResolver` on every request
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/SsrPageHandler.php` L89, 102, 110, 114, 130, 137, 151, 197
- **Issue:** No dependency injection for these collaborators. Each `handleRenderPage()` call constructs multiple objects. This prevents testing with stubs and adds overhead.

---

## Medium

### M1. No Twig `url()` or `path()` function -- templates cannot generate internal links
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Twig/WaaseyaaExtension.php` L34-41
- **Issue:** Only 3 Twig functions are registered: `asset()`, `config()`, `env()`. There is no `url()`, `path()`, or `entity_url()` function. Templates must hardcode URLs (e.g., `<a href="/admin">`). The `entity.html.twig` template has no way to link to related entities by route.

### M2. No Twig filters registered
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Twig/WaaseyaaExtension.php`
- **Issue:** `getFilters()` is not overridden. Common CMS filters are missing: `truncate`, `date_format`, `strip_tags`, `markdown`, `trans` (i18n).

### M3. No `<meta>` OG tags, canonical URL, or description in base layout or entity template
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/templates/layouts/base.html.twig` L7
- **Issue:** The `{% block meta %}` is empty by default. No child template populates OG tags. Entity pages (which don't extend base anyway -- see H3) have zero metadata. For SEO and social sharing, `og:title`, `og:description`, `og:image`, `og:url`, and `<link rel="canonical">` are essential.

### M4. `AsFormatter` attribute is defined but never used for auto-discovery
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Attribute/AsFormatter.php`
- **Issue:** All 6 formatters have `#[AsFormatter(fieldType: '...')]` attributes, and `PackageManifestCompiler` scans for them. However, `FieldFormatterRegistry` constructor takes a hardcoded map (L28-37) and only uses the manifest array as overrides. The attribute is decorative -- removing it would change nothing in the current code path.

### M5. `skeleton/templates/404.html.twig` does not extend base layout
- **File:** `/home/jones/dev/waaseyaa/skeleton/templates/404.html.twig` (13 lines)
- **Issue:** Standalone minimal HTML. No viewport meta, no styles, no header/footer. Inconsistent with `packages/ssr/templates/404.html.twig` which extends `layouts/base.html.twig`.

### M6. No pagination, breadcrumb, or listing support in templates or rendering pipeline
- **Issue:** `EntityRenderer::render()` handles a single entity. There is no `renderList()` or collection rendering. No pagination component. No breadcrumb trail. These are standard CMS features for entity listing pages, taxonomy term pages, and search results.

### M7. `RenderController::renderPath()` hardcodes title as `'Waaseyaa'`
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/RenderController.php` L29
- **Issue:** `$context = ['title' => 'Waaseyaa', 'path' => $normalizedPath]`. The site name cannot be customized per-instance. This should come from config.

### M8. `RenderController::renderEntity()` returns 500 with bare `<h1>` when no template matches
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/RenderController.php` L88
- **Issue:** `return new SsrResponse(content: '<h1>Render template missing</h1>', statusCode: 500)` -- no HTML document structure, no error logging, no template suggestion list for debugging.

### M9. No RSS feed or sitemap generation
- **Issue:** No RSS/Atom feed template or controller. No XML sitemap generation. Both are standard for content-driven CMS sites and expected for v1.0.

---

## Low

### L1. `ImageFormatter` does not support `width`, `height`, `loading="lazy"`, or `srcset`
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Formatter/ImageFormatter.php`
- **Issue:** Only `src`, `alt`, and `image_style` (as CSS class) are supported. Modern image rendering needs `width`/`height` (CLS prevention), `loading="lazy"`, and responsive `srcset`/`sizes`.

### L2. `DateFormatter` does not support timezone configuration
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Formatter/DateFormatter.php` L14-30
- **Issue:** Timestamp-based dates use UTC implicitly (via `@` prefix). No `timezone` setting key. Dates display in server timezone for string inputs.

### L3. `EntityReferenceFormatter` uses a hardcoded URL pattern `/entity/{id}`
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/Formatter/EntityReferenceFormatter.php` L25
- **Issue:** Default `url_pattern` is `/entity/{id}`. This doesn't match path alias resolution or entity-type-specific routes (e.g., `/node/{id}`, `/user/{id}`). The formatter has no access to the entity type manager to resolve real paths.

### L4. `ViewMode` has factory methods for only 3 modes: `full`, `teaser`, `embed`
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/ViewMode.php`
- **Issue:** Minor -- custom view modes work via `new ViewMode('custom')`. But a `search_result` or `rss` mode would be typical additions.

### L5. `ComponentRegistry` has no integration with `PackageManifestCompiler`
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/ComponentRegistry.php`
- **Issue:** Components must be registered manually via `register()` or `registerClass()`. No auto-discovery from the manifest. This is workable but inconsistent with the formatter discovery pattern.

### L6. Duplicate `RendererTestEntity` / `RenderControllerEntity` classes in tests
- **Files:** `/home/jones/dev/waaseyaa/packages/ssr/tests/Unit/EntityRendererTest.php` L105-154 and `/home/jones/dev/waaseyaa/packages/ssr/tests/Unit/RenderControllerTest.php` L258-276
- **Issue:** Two nearly identical `EntityInterface` test implementations. Should be extracted to a shared test fixture.

### L7. `ThemeInterface` is minimal -- no metadata (label, description, version, parent theme)
- **File:** `/home/jones/dev/waaseyaa/packages/ssr/src/ThemeInterface.php`
- **Issue:** Only `id()` and `templateDirectories()`. No `label()`, `description()`, `version()`, `parent()` for theme inheritance, `regions()` for layout regions, or `libraries()` for CSS/JS assets.

### L8. Test coverage gaps
- **Missing test files for:** individual formatters (`DateFormatter`, `BooleanFormatter`, `ImageFormatter`, `EntityReferenceFormatter`, `HtmlFormatter`, `PlainTextFormatter`). The `FieldFormatterRegistryTest` exists but individual formatter behavior tests are absent.
- **Missing tests for:** `SsrPageHandler::handleRenderPage()` end-to-end (only helper methods are tested), `SsrPageHandler::dispatchAppController()`, `SsrPageHandler::resolveControllerInstance()`, `ArrayViewModeConfig`, `ComponentMetadata`.
- **Existing coverage:** 19 test files covering `SsrController`, `ComponentMetadata`, `ComponentRegistry`, `Component` attribute, `ComponentRenderer`, `SsrResponse`, `FieldFormatterRegistry`, `SsrServiceProvider`, `ViewMode`, `ThemeServiceProvider`, `RenderCache`, `EntityRenderer`, `RenderController`, `TwigErrorPageRenderer`, `SsrPageHandler` (partial), `WaaseyaaExtension`, `BaseLayout`, `InteractsWithRenderer`.

---

## Rendering Pipeline Trace

```
HTTP Request
  -> HttpKernel::handle()
    -> boot(), create SsrPageHandler
    -> Middleware pipeline (SessionMiddleware, AuthorizationMiddleware, ...)
    -> Route matching via Symfony Router
    -> ControllerDispatcher::dispatch()
      -> 'render.page' controller:
           SsrPageHandler::handleRenderPage($path, $account, $request)
             -> Language negotiation (URL prefix + Accept-Language)
             -> Path alias resolution (PathAliasResolver)
             -> Entity loading (EntityTypeManager::getStorage()->load())
             -> Editorial visibility check (EditorialVisibilityResolver)
             -> Render cache check (RenderCache::get())
             -> RenderController::renderEntity()
               -> EntityRenderer::render() => template var bag
               -> Twig template suggestion chain (e.g., node.article.full.html.twig)
               -> SsrResponse (HTML string + status + headers)
             -> Render cache set (RenderCache::set())
             -> Surrogate-Key headers for CDN purging
             -> ResponseSender::html()
      -> 'Class::method' controller:
           SsrPageHandler::dispatchAppController()
             -> Reflection-based DI for controller constructor
             -> Controller method receives ($params, $query, $account, $request)
             -> SsrResponse or HttpResponse
```

**Pipeline integrity:** The chain from `HttpKernel` through `ControllerDispatcher` to `SsrPageHandler` to `RenderController` to Twig is complete and functional. No broken links identified.

**Key gap:** The pipeline handles single-entity rendering well but has no support for collection/listing pages, search result pages, or custom route rendering beyond single-segment path matching.

---

## Summary

| Severity | Count | Key themes |
|----------|-------|------------|
| Critical | 3 | XSS via HtmlFormatter, dead config() function, missing error templates |
| High | 6 | No Twig cache, home/entity templates not extending base, 403-as-404, cache invalidation no-op |
| Medium | 9 | No URL/path Twig functions, no OG tags, no pagination/breadcrumbs, no RSS/sitemap |
| Low | 8 | Formatter limitations, test coverage gaps, theme interface minimal |
