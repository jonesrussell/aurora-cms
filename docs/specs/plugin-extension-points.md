# Plugin Extension Points

## Purpose

This spec defines stable plugin extension points for workflow, traversal, and discovery tooling integrations.

## Stable Contract

Primary interface:

- `Waaseyaa\Plugin\Extension\KnowledgeToolingExtensionInterface`

Required methods:

- `alterWorkflowContext(array $context): array`
- `alterTraversalContext(array $context): array`
- `alterDiscoveryContext(array $context): array`

Contract requirements:

- Input and output must be associative arrays.
- Implementations must be deterministic for identical input/configuration.
- Implementations must preserve unknown keys unless intentionally removed.

## Runner Contract

Runner class:

- `Waaseyaa\Plugin\Extension\KnowledgeToolingExtensionRunner`

Behavior:

- plugin IDs are sorted before execution to ensure deterministic ordering,
- `fromPluginManager()` instantiates all discovered plugins and filters to `KnowledgeToolingExtensionInterface`,
- context is passed through each extension in sequence.

Runner surfaces:

- `applyWorkflowContext()`
- `applyTraversalContext()`
- `applyDiscoveryContext()`
- `describeExtensions()`

## Reference Example Module

Reference plugin:

- `Waaseyaa\Plugin\Tests\Fixtures\KnowledgeToolingExamplePlugin`

Demonstrates:

- workflow trace tagging,
- traversal relationship-type augmentation,
- discovery hint augmentation,
- deterministic normalization/sorting behavior.

## Compatibility Notes

- These extension points are additive and do not alter existing `PluginManagerInterface` contracts.
- Existing plugins that do not implement `KnowledgeToolingExtensionInterface` remain fully compatible.
