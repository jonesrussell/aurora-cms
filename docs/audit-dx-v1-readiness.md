# Developer Experience Audit — v1.0 Release Readiness

**Date:** 2026-03-12
**Auditor:** Claude Opus 4.6
**Scope:** README, skeleton project, tooling, environment config, installation path, package docs

---

## Critical

### C1. No LICENSE file
The repository has no `LICENSE` file. Open-source or proprietary, a license is mandatory before any public release. Composer packages without a license are unusable by most organizations.

### C2. 37 of 38 packages have no README
Only `packages/admin` has a README. The remaining 37 packages — including foundational ones like `entity`, `access`, `api`, `foundation`, `user` — have zero documentation. A developer trying to understand or use any individual package has no entry point.

### C3. README port mismatch
`README.md` Installation section tells users to `curl` on port **8000**, but `composer dev` starts the built-in server on port **8081**. This will confuse every new developer on first try.

---

## High

### H1. No CONTRIBUTING.md
No contribution guidelines exist. For a v1.0 release, contributors need to know coding standards, PR process, and testing expectations. The CLAUDE.md has this info but it targets AI agents, not human developers.

### H2. No CHANGELOG
No `CHANGELOG.md` exists. Users and integrators need a record of breaking changes, especially for a framework with 38 packages.

### H3. No Docker/container story
No Dockerfile, docker-compose, or container configuration exists. The only dev server option is PHP's built-in server. For a CMS framework targeting developers, a containerized setup (or at least documented system dependencies like SQLite extensions) would significantly lower the onboarding barrier.

### H4. `composer dev` uses background process with fragile cleanup
The `composer dev` script backgrounds the PHP server with `&` and relies on `kill $!` when npm exits. If npm crashes or the user Ctrl+C's, the PHP process may become orphaned. Consider `symfony/process` or a proper process manager.

### H5. No `composer test` script
The composer scripts define `cs-check`, `cs-fix`, `phpstan`, and `dev`, but there is no `test` shortcut. Developers must know to run `./vendor/bin/phpunit` directly. A `composer test` alias is a standard DX expectation.

### H6. Skeleton has no .env.example
The root project has `.env.example` but the skeleton project (what `create-project` installs) does not. New project creators won't know which environment variables are available without reading config/waaseyaa.php.

---

## Medium

### M1. Skeleton `src/` is entirely empty (.gitkeep stubs only)
The skeleton provides directory stubs (Access/, Controller/, Domain/, Entity/, etc.) but zero example code — no example entity, no example controller, no example service provider. A developer gets a working homepage but no guidance on writing their first custom entity type. The README says "see `packages/node` as a reference" but that package itself has no README.

### M2. Skeleton README lacks database setup instructions
The skeleton README shows `php -S localhost:8080 -t public` but doesn't mention that SQLite is auto-created, or how to run schema installation. The `bin/waaseyaa` CLI is referenced but no `schema:install` or `migrate` command is documented.

### M3. PHPStan at level 5
Level 5 is reasonable for development but may want to be raised before v1.0 to catch more type safety issues. The baseline file approach is good practice.

### M4. No pre-commit hooks or Husky equivalent
CS Fixer and PHPStan are available as composer scripts and in CI, but there are no git hooks to catch issues before push.

### M5. EditorConfig references Drupal
The `.editorconfig` file contains the comment "Drupal editor configuration normalization" — a copy-paste artifact that should be updated to reference Waaseyaa.

### M6. Skeleton homepage links to `/admin` and `/api` but doesn't explain prerequisites
The skeleton's `home.html.twig` has cards linking to `/admin` (requires Nuxt dev server on port 3000) and `/api` — but the skeleton README doesn't mention that the admin SPA requires separate npm setup.

---

## Low

### L1. No Makefile or Taskfile at root
The Go projects in the workspace use Taskfiles, but the PHP monorepo has only composer scripts. A Makefile or Taskfile could provide a unified command surface (`make test`, `make lint`, `make dev`).

### L2. `bin/check-milestones` is agent-oriented, not developer-oriented
The bin scripts (`check-milestones`, `check-ingestion-defaults`, `check-no-secrets`) are governance tooling for AI agents. Human developers would benefit from a `bin/setup` or `bin/doctor` script that validates their local environment.

### L3. Issue templates are comprehensive but verbose
Seven issue templates exist (feature, breaking-change, release-authorization, tag-quarantine, RFC, plus config.yml). The release/versioning templates are thorough but may overwhelm contributors unfamiliar with the governance model.

### L4. Skeleton page.html.twig is minimal
The generic page template only outputs `{{ path }}` — no layout, no CSS, no navigation. Compare to home.html.twig which has full styling. New developers may be confused by the disparity.

---

## Documentation Gaps

| Area | Status | Notes |
|------|--------|-------|
| Root README.md | Exists, adequate | Port mismatch (C3), no PHP extension requirements listed |
| VERSIONING.md | Exists, thorough | Well-structured quarantine process |
| AGENTS.md | Exists | Agent-specific, appropriately brief |
| CLAUDE.md | Exists, comprehensive | Excellent for AI agents; not a substitute for human docs |
| docs/architecture/app-structure.md | Exists | Good convention doc |
| docs/migration-defaults.md | Exists | Pre-v1 migration guide, clear |
| PR template | Exists | Minimal but functional |
| Issue templates | 7 templates | Comprehensive |
| release-approvals/README.md | Exists | Clear approval workflow |
| CONTRIBUTING.md | **Missing** | No contribution guide |
| CHANGELOG.md | **Missing** | No changelog |
| LICENSE | **Missing** | No license file |
| .env.example (skeleton) | **Missing** | Root has one, skeleton doesn't |
| Package READMEs | **37/38 missing** | Only `admin` has a README |
| Skeleton README | Exists | Lacks database setup, admin SPA prereqs |
| API documentation | **Missing** | No OpenAPI spec or API docs beyond curl examples |
| Architecture diagram | In README | Text-based, adequate for now |

---

## Summary

The project has strong internal tooling (CI workflows, PHPStan, CS Fixer, governance scripts, codified context for AI agents) but significant gaps in human-facing documentation. The three critical items — missing LICENSE, near-total absence of package READMEs, and the port mismatch — should be resolved before any v1.0 tag. The skeleton provides a working starting point but needs better guidance for the "what next" developer journey.
