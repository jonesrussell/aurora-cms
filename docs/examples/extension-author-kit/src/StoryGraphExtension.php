<?php

declare(strict_types=1);

namespace Vendor\StoryGraphExtension;

use Waaseyaa\Plugin\Attribute\WaaseyaaPlugin;
use Waaseyaa\Plugin\Extension\KnowledgeToolingExtensionInterface;
use Waaseyaa\Plugin\PluginBase;

#[WaaseyaaPlugin(
    id: 'story_graph_extension',
    label: 'Story Graph Extension',
    description: 'Example extension author onboarding module',
)]
final class StoryGraphExtension extends PluginBase implements KnowledgeToolingExtensionInterface
{
    public function alterWorkflowContext(array $context): array
    {
        $context['workflow_tags'] = array_values(array_unique(array_merge($context['workflow_tags'] ?? [], ['story-graph'])));
        sort($context['workflow_tags']);
        return $context;
    }

    public function alterTraversalContext(array $context): array
    {
        $context['relationship_types'] = array_values(array_unique(array_merge($context['relationship_types'] ?? [], ['narrates'])));
        sort($context['relationship_types']);
        return $context;
    }

    public function alterDiscoveryContext(array $context): array
    {
        $context['hints'] = array_values(array_unique(array_merge($context['hints'] ?? [], ['story-anchor'])));
        sort($context['hints']);
        return $context;
    }
}
