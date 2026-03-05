<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\CLI\Ingestion\AuthoringAssistBuilder;

#[CoversClass(AuthoringAssistBuilder::class)]
final class AuthoringAssistBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_deterministic_suggestions_with_frozen_shape_and_ordering(): void
    {
        $builder = new AuthoringAssistBuilder();
        $payload = $builder->build(
            envelope: [
                'batch_id' => 'batch_assist',
                'items' => [
                    ['source_uri' => 'item://river_teaching', 'ingested_at' => 1735689600, 'parser_version' => null],
                    ['source_uri' => 'item://water_story', 'ingested_at' => 1735689601, 'parser_version' => null],
                ],
            ],
            nodes: [
                'river_teaching' => ['title' => 'River Teaching', 'workflow_state' => 'published', 'status' => 1],
                'water_story' => ['title' => 'Water Story', 'workflow_state' => 'draft', 'status' => 0],
            ],
            relationships: [
                [
                    'key' => 'river_teaching_to_water_story_related_to_inferred',
                    'from' => 'river_teaching',
                    'to' => 'water_story',
                    'relationship_type' => 'related_to',
                    'inference_source' => 'text_overlap_v1',
                    'inference_confidence' => 0.42,
                ],
            ],
            validationDiagnostics: [
                [
                    'code' => 'validation.semantic.insufficient_publishable_tokens',
                    'location' => '/nodes/river_teaching/body',
                ],
            ],
            refreshSummary: [
                'needs_refresh' => true,
                'primary_category' => 'relationship_change',
            ],
        );

        $this->assertSame(2, $payload['summary']['suggestion_count']);
        $this->assertGreaterThan(0.0, $payload['summary']['average_confidence']);
        $this->assertSame([], $payload['diagnostics']);

        $first = $payload['suggestions'][0];
        $this->assertSame(
            ['suggestion_id', 'title', 'body', 'confidence', 'source_item_index', 'tags', 'provenance', 'explainability'],
            array_keys($first),
        );
        $this->assertSame(
            ['primary_cue', 'supporting_cues', 'inference_edges_used', 'validation_signals'],
            array_keys($first['explainability']),
        );
    }

    #[Test]
    public function it_emits_assist_diagnostic_for_unmapped_source_items(): void
    {
        $builder = new AuthoringAssistBuilder();
        $payload = $builder->build(
            envelope: [
                'batch_id' => 'batch_assist',
                'items' => [
                    ['source_uri' => 'item://missing', 'ingested_at' => 1735689600, 'parser_version' => null],
                ],
            ],
            nodes: [],
            relationships: [],
            validationDiagnostics: [],
            refreshSummary: ['needs_refresh' => false, 'primary_category' => null],
        );

        $this->assertSame(0, $payload['summary']['suggestion_count']);
        $this->assertSame('assist.unmapped_source_item', $payload['diagnostics'][0]['code']);
    }
}
