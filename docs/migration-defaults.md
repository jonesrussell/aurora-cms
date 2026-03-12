# Migration Guide: Default Content Types

## Overview

Waaseyaa ships with `core.note` as the default content type. Existing tenants
that pre-date this default may have no enabled content types. The
`migrate:defaults` CLI command detects and fixes these tenants.

## Pre-v1 Notice

This migration is best-effort during the pre-v1 phase. Post-v1.0, a documented
migration path will be required for all breaking changes per the versioning
policy.

## Detecting Affected Tenants

Run a dry-run to see which tenants have no enabled content types:

    bin/waaseyaa migrate:defaults --dry-run

The command auto-discovers tenants from the lifecycle status file and entity
audit log. To target specific tenants:

    bin/waaseyaa migrate:defaults --tenant=acme --tenant=beta --dry-run

## Running the Migration

Enable `core.note` (or another type) for all affected tenants:

    bin/waaseyaa migrate:defaults --enable=note --yes

Interactive mode (omit `--yes`) prompts per-tenant with a choice of registered
types including a "skip" option.

## Rollback

If a migration was applied in error, roll it back:

    bin/waaseyaa migrate:defaults --rollback --yes

This re-disables any types that were enabled by a previous `migrate:defaults`
run. The migration log at `storage/framework/migrate-defaults.jsonl` tracks
all actions.

To rollback specific tenants only:

    bin/waaseyaa migrate:defaults --tenant=acme --rollback --yes

## Per-Tenant Feature Flags

Tenants can individually disable or re-enable content types:

    bin/waaseyaa type:disable note --tenant=acme
    bin/waaseyaa type:enable note --tenant=acme

A guardrail prevents disabling the last enabled type unless `--force` is used.
Every toggle records an audit entry in `storage/framework/entity-type-audit.jsonl`.

## Audit Log

View all lifecycle audit entries:

    bin/waaseyaa audit:log

All `type:disable`, `type:enable`, and `migrate:defaults` actions are logged
with actor ID, timestamp, and optional tenant ID.
