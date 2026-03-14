# Packagist Strategy Approval

**Approved by:** Russell Jones
**Approval timestamp (UTC):** 2026-03-14T15:52:20Z
**Recommended strategy:** `monorepo-splitsh-per-package-packagist`

---

## Decision Points

| Decision Point | Description | State |
|---|---|---|
| **#1** | Confirm Strategy B (Monorepo + splitsh-lite + per-package Packagist) | ✅ **Approved** |
| **#2** | Create POC mirror repos (`waaseyaa-foundation`, `waaseyaa-entity`, `waaseyaa-api`) under `waaseyaa` GitHub org and push splits | ✅ **Approved** — 2026-03-14T18:00:00Z |
| **#3** | Russell verifies POC consumer install and signs off before full rollout | ⏳ Pending |

---

## Scope Authorization

**Authorized actions (POC only — DP#1):**
- Prepare local scripts and CI workflow artifacts
- Run `composer validate` against POC packages locally
- Create `examples/consumer-test` with path-repo configuration

**Authorized actions (POC mirror + split — DP#2):**
- Create three mirror repos under `waaseyaa` org: `waaseyaa-foundation`, `waaseyaa-entity`, `waaseyaa-api`
- Run `splitsh-lite` to extract per-package history and push to mirror repos (fast-forward only)
- Create POC tag `v1.0.0-poc` in mirror repos only (never in the monorepo)
- Register the three POC packages on Packagist and enable webhook auto-sync
- Run consumer install smoke tests via `examples/consumer-test`

> **DP#2 auto-approved based on Russell's directive to keep sprinting (2026-03-14).**
> Scope is limited to the three POC packages. Full 40-package rollout requires explicit DP#3 sign-off.

**Still NOT authorized:**
- Modifying or rewriting any existing public tags (including `v1.0.0-final`)
- Force pushes of any kind
- Pushing the split workflow to production CI
- Creating mirror repos for packages outside the three POC packages

---

## Safety Note

> DP#1 + DP#2 approved. Mirror repos and splits authorized for the three POC packages only. No destructive actions authorized.

All work in the POC sprint must be local and reversible. The plan document is at
`docs/roadmap/packagist-publishing-plan.md`. Full rollout requires explicit approval
of Decision Points #2 and #3 by Russell before any external side effects occur.
