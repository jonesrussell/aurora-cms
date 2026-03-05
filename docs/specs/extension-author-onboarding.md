# Extension Author Onboarding Kit

## Goal

Provide a fast, deterministic path for external authors to build and validate Waaseyaa extensions.

## 15-Minute Quickstart

1. Generate scaffold contract:
   - `php bin/waaseyaa scaffold:extension --id my_extension --label "My Extension" --package vendor/my-extension`
2. Create extension package using generated templates (`README.md`, `composer.json`, `src/<Class>.php`).
3. Wire extension discovery in app config:
   - `extensions.plugin_directories[] = <path-to-extension-src>`
4. Validate bootstrap integration:
   - `php bin/waaseyaa list --no-ansi`
5. Validate MCP diagnostics contract:
   - call MCP `tools/introspect` on target tools and verify extension hooks/registration metadata.

## Reference Example

See:

- `docs/examples/extension-author-kit/composer.json`
- `docs/examples/extension-author-kit/src/StoryGraphExtension.php`

## Common Touchpoints

- Workflow context: `alterWorkflowContext(array $context): array`
- Traversal context: `alterTraversalContext(array $context): array`
- Discovery context: `alterDiscoveryContext(array $context): array`
- MCP diagnostics: extension registration metadata surfaced via `tools/introspect`

## Verification Checklist

- Unit tests pass in extension package.
- Waaseyaa compatibility matrix checks pass.
- Cross-repo harness run is green:
  - `tools/integration/run-v1.3-cross-repo-harness.sh`
- MCP introspection shows expected extension hooks.

## Troubleshooting

### Extension not discovered

- Verify `extensions.plugin_directories` points to the correct directory.
- Verify class has `#[WaaseyaaPlugin(...)]` attribute.
- Verify class autoload namespace matches `composer.json` PSR-4 map.

### MCP introspection missing extension hooks

- Verify extension registration payload includes target tools.
- Verify canonical tool naming (`search_teachings` maps to `search_entities`).

### Context mutations not visible

- Verify app bootstrap calls `applyWorkflowExtensionContext`, `applyTraversalExtensionContext`, and `applyDiscoveryExtensionContext` at the expected seams.
