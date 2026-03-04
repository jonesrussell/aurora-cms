<?php

declare(strict_types=1);

namespace Waaseyaa\Plugin\Tests\Fixtures;

use Waaseyaa\Plugin\Attribute\WaaseyaaPlugin;
use Waaseyaa\Plugin\Extension\KnowledgeToolingExtensionInterface;
use Waaseyaa\Plugin\PluginBase;

#[WaaseyaaPlugin(
    id: 'knowledge_tooling_example',
    label: 'Knowledge Tooling Example',
    description: 'Reference extension plugin for workflow/traversal/discovery context alterations',
)]
final class KnowledgeToolingExamplePlugin extends PluginBase implements KnowledgeToolingExtensionInterface
{
    public function alterWorkflowContext(array $context): array
    {
        $context['extension_trace'] = is_array($context['extension_trace'] ?? null) ? $context['extension_trace'] : [];
        $context['extension_trace'][] = $this->getPluginId();

        $tag = is_string($this->configuration['workflow_tag'] ?? null)
            ? trim($this->configuration['workflow_tag'])
            : '';
        if ($tag !== '') {
            $tags = is_array($context['workflow_tags'] ?? null) ? $context['workflow_tags'] : [];
            $tags[] = $tag;
            $tags = array_values(array_unique(array_map(static fn(mixed $value): string => (string) $value, $tags)));
            sort($tags);
            $context['workflow_tags'] = $tags;
        }

        return $context;
    }

    public function alterTraversalContext(array $context): array
    {
        $relationshipType = is_string($this->configuration['relationship_type'] ?? null)
            ? trim(strtolower($this->configuration['relationship_type']))
            : '';
        if ($relationshipType !== '') {
            $types = is_array($context['relationship_types'] ?? null) ? $context['relationship_types'] : [];
            $types[] = $relationshipType;
            $types = array_values(array_unique(array_map(
                static fn(mixed $value): string => strtolower(trim((string) $value)),
                $types,
            )));
            sort($types);
            $context['relationship_types'] = $types;
        }

        return $context;
    }

    public function alterDiscoveryContext(array $context): array
    {
        $hint = is_string($this->configuration['discovery_hint'] ?? null)
            ? trim($this->configuration['discovery_hint'])
            : '';
        if ($hint !== '') {
            $hints = is_array($context['hints'] ?? null) ? $context['hints'] : [];
            $hints[] = $hint;
            $hints = array_values(array_unique(array_map(static fn(mixed $value): string => (string) $value, $hints)));
            sort($hints);
            $context['hints'] = $hints;
        }

        return $context;
    }
}

