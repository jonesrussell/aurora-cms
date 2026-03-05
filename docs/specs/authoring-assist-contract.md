# Authoring Assist Contract (v1.5)

## Scope
- Issue: `#164`
- Surface: deterministic AI-assisted authoring suggestions composed from validated ingestion context.
- Goal: provide explainable editorial suggestions with strict, stable contract boundaries.

## Activation
- `ingest:run --authoring-assist`

## Inputs
- Normalized envelope (post schema pass)
- Validation diagnostics (`validation.*`)
- Relationship set (including inferred edges from `#166`)
- Refresh summary (`refresh_summary`)

No raw/unvalidated envelope input is accepted.

## Output
- `assist.suggestions` (deterministically ordered list)
- `assist.summary`:
  - `suggestion_count`
  - `average_confidence`
- `assist.diagnostics` (deterministic `assist.*` diagnostics)

## Suggestion Object (Frozen v1.5)
Required fields in exact order:
1. `suggestion_id`
2. `title`
3. `body`
4. `confidence`
5. `source_item_index`

Optional additive fields in exact order:
6. `tags`
7. `provenance`
8. `explainability`

## Explainability Block (Frozen v1.5)
Fields in exact order:
1. `primary_cue`
2. `supporting_cues`
3. `inference_edges_used`
4. `validation_signals`

## Ordering Rules
- Suggestions:
  1. `confidence` desc
  2. `source_item_index` asc
  3. `suggestion_id` asc
- `supporting_cues`: cue strength desc, tie-break lexicographic
- `inference_edges_used`: lexicographic
- `validation_signals`: ascending code order

## Diagnostics
- `assist.unmapped_source_item` for source rows that cannot map to nodes.

## Stability Rules
- Field names and ordering are frozen for v1.5.
- Additions must be optional/additive only.
- MCP/public contract boundaries remain unchanged by this surface.
