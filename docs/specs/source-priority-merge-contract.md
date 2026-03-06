# Source-Priority Merge Contract (v1.6)

## Scope
- Issue: `#176`
- Surface: deterministic merge of cross-source rows already bound to a `canonical_id`.
- Goal: resolve field conflicts using explicit source ownership priority with auditable diagnostics.

## Merge Policy
- Row grouping key: `canonical_id`.
- Winner selection order:
  1. `first_party`
  2. `federated`
  3. `third_party`
- Tie-break: lexicographic `source_id`.

## Field Selection
- Reserved provenance fields are excluded from field conflict selection.
- Non-reserved fields are processed in lexicographic order.
- First available value from winner-ordered members is selected.

## Diagnostics
- `merge.field_conflict` emitted when two or more distinct values are present for a field within a canonical group.
- Context:
  - `canonical_id`
  - `field`
  - `winner_source_id`
  - `member_source_ids` (sorted)

## Determinism
- Canonical groups are sorted lexicographically.
- Group members are sorted by fixed ownership priority + `source_id`.
- Field evaluation order is lexicographic.
- Merged output is replay-safe for any input ordering.
