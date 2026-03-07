# Release Approvals

This directory holds the owner authorization artifacts required by CI before a `v1.0` tag is allowed.

## How It Works

The file `v1.0.approved` must exist in this directory before CI will allow any tag matching `v1.0*`
to propagate through the monorepo split pipeline or the release gate.

## Creating the Approval Artifact (v1.0 only)

Only @jonesrussell may authorize a v1.0 release. The process:

1. Russell opens a PR that creates `release-approvals/v1.0.approved` with this content:
   ```
   Authorized by: @jonesrussell
   Date: YYYY-MM-DD
   Commit: <sha>
   Notes: <release reason>
   ```
2. The PR is merged by Russell.
3. CI is now unblocked for `v1.0*` tags.
4. Record the authorization in `VERSIONING.md` Audit Log.

## What Happens Without This File

If a `v1.0*` tag is pushed and this file does not exist:
- `release-gate.yml` fails with `UNAUTHORIZED_V1_TAG`
- `split.yml` aborts before touching any remote
- A `release-quarantine` issue should be opened

See `VERSIONING.md` for the full policy.
