<?php

declare(strict_types=1);

namespace Waaseyaa\Plugin\Tests\Unit\Extension;

use PHPUnit\Framework\TestCase;
use Waaseyaa\Plugin\Attribute\WaaseyaaPlugin;
use Waaseyaa\Plugin\DefaultPluginManager;
use Waaseyaa\Plugin\Discovery\AttributeDiscovery;
use Waaseyaa\Plugin\Extension\KnowledgeToolingExtensionRunner;

final class KnowledgeToolingExtensionRunnerTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = dirname(__DIR__, 2) . '/Fixtures';
    }

    public function testRunnerBuildsFromPluginManagerAndAppliesContexts(): void
    {
        $discovery = new AttributeDiscovery(
            directories: [$this->fixturesDir],
            attributeClass: WaaseyaaPlugin::class,
        );
        $manager = new DefaultPluginManager($discovery);

        // Inject deterministic configuration through plugin manager factory path.
        $extension = $manager->createInstance('knowledge_tooling_example', [
            'workflow_tag' => 'editorial-gate',
            'relationship_type' => 'influences',
            'discovery_hint' => 'graph-anchor',
        ]);
        $runner = new KnowledgeToolingExtensionRunner([
            'knowledge_tooling_example' => $extension,
        ]);

        $workflowContext = $runner->applyWorkflowContext([
            'workflow_tags' => ['existing'],
        ]);
        $this->assertSame(['knowledge_tooling_example'], $workflowContext['extension_trace']);
        $this->assertSame(['editorial-gate', 'existing'], $workflowContext['workflow_tags']);

        $traversalContext = $runner->applyTraversalContext([
            'relationship_types' => ['related'],
        ]);
        $this->assertSame(['influences', 'related'], $traversalContext['relationship_types']);

        $discoveryContext = $runner->applyDiscoveryContext([
            'hints' => ['semantic'],
        ]);
        $this->assertSame(['graph-anchor', 'semantic'], $discoveryContext['hints']);
    }

    public function testRunnerFactoryFiltersNonExtensionPlugins(): void
    {
        $discovery = new AttributeDiscovery(
            directories: [$this->fixturesDir],
            attributeClass: WaaseyaaPlugin::class,
        );
        $manager = new DefaultPluginManager($discovery);
        $runner = KnowledgeToolingExtensionRunner::fromPluginManager($manager);

        $descriptors = $runner->describeExtensions();
        $this->assertCount(1, $descriptors);
        $this->assertSame('knowledge_tooling_example', $descriptors[0]['plugin_id']);
        $this->assertSame('Knowledge Tooling Example', $descriptors[0]['label']);
    }
}

