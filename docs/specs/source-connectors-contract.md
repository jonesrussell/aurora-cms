# Source Connector Contract (v1.6)

## Scope
- Issue: `#174`
- Surface: dataset/api/file/crawl connectors that consume normalized adapter payloads and feed the ingestion engine with deterministic provenance.
- Goal: specify connector inputs, diagnostics, ordering, and fixture expectations so each transport emits consistent payloads ready for dedupe/merge.

## Inputs
- Each connector receives:
  1. Normalized provenance block (see `docs/specs/source-adapter-contract.md`)
  2. Optional `metadata.adapter_extra`
  3. Transport-specific payload (batch of rows, API response, file stream chunk, crawl document)

Connectors must not mutate the normalized provenance block or re-normalize URIs.

## Connector behavior
For every transport, connectors must:
1. Validate transport payload shape deterministically.
2. Attach transport-specific diagnostics under `adapter.*` or `connector.*` namespaces.
3. Emit deterministic ordering of connector rows (sorted by `source_id`).
4. Preserve provenance block ordering per adapter spec.
5. Surface provider hints via `metadata.adapter_extra`.

## Diagnostic namespace
- Connector diagnostics must use the `connector.*` namespace with deterministic codes:
  - `connector.missing_required_field`
  - `connector.invalid_payload_schema`
  - `connector.rate_limit_hint`
  - plus transport-specific codes (e.g., `connector.api.timeout`)
- Diagnostics include `location`, `item_index`, and scalar `context` fields (no nested objects).

## Transport templates
- **dataset**: JSON array of rows; connectors fail fast on missing keys, emit `connector.missing_required_field`.
- **api**: structured JSON response; connectors strip metadata, normalize list order by `source_id`, emit `connector.api.timeout` when HTTP timeout occurs.
- **file**: newline-delimited JSON; connectors sort lines deterministically by `source_id` and handle missing newline gracefully.
- **crawl**: HTML/text batch; connectors extract canonical metadata, hash to normalized provenance, and emit `connector.crawl.missing_link` when canonical link absent.

## Fixtures
- Provide per-transport fixtures under `tests/fixtures/connectors/<transport>.json` encoding normalized provenance + payload to anchor tests.
- Tests assert:
  - canonical provenance block remains unchanged
  - diagnostics emitted deterministically when expected
  - connector ordering by `source_id`

## Determinism
- Connectors must keep the same output order and diagnostics irrespective of input ordering (sort by `source_id`).
- Any metadata emitted must be lexicographically ordered.
- No randomness, timestamps, or environment-specific values may leak into connector output.

Once this spec is stable, implementation becomes mechanical: parse transport payload, validate shape, attach diagnostics, and feed the deterministic provenance block downstream.
