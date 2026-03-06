<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Tests\Unit\Ingestion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\CLI\Ingestion\SourcePriorityMergeResolver;

#[CoversClass(SourcePriorityMergeResolver::class)]
final class SourcePriorityMergeResolverTest extends TestCase
{
    #[Test]
    public function it_merges_rows_by_canonical_id_using_ownership_priority(): void
    {
        $resolver = new SourcePriorityMergeResolver();
        $result = $resolver->merge([
            [
                'canonical_id' => 'canon-a',
                'source_id' => 'third-party',
                'source_uri' => 'https://example.com/item',
                'ownership' => 'third_party',
                'title' => 'Third Party Title',
                'body' => 'Body A',
            ],
            [
                'canonical_id' => 'canon-a',
                'source_id' => 'first-party',
                'source_uri' => 'https://example.com/item',
                'ownership' => 'first_party',
                'title' => 'First Party Title',
                'body' => 'Body B',
            ],
        ]);

        $this->assertCount(1, $result['merged']);
        $this->assertSame('first-party', $result['merged'][0]['source_id']);
        $this->assertSame('First Party Title', $result['merged'][0]['title']);
        $this->assertCount(2, $result['diagnostics']);
        $this->assertSame('merge.field_conflict', $result['diagnostics'][0]['code']);
    }

    #[Test]
    public function it_is_stable_for_reordered_input(): void
    {
        $resolver = new SourcePriorityMergeResolver();
        $input = [
            [
                'canonical_id' => 'canon-a',
                'source_id' => 'a',
                'source_uri' => 'https://example.com/a',
                'ownership' => 'federated',
                'title' => 'A',
            ],
            [
                'canonical_id' => 'canon-b',
                'source_id' => 'b',
                'source_uri' => 'https://example.com/b',
                'ownership' => 'third_party',
                'title' => 'B',
            ],
        ];

        $first = $resolver->merge($input);
        $second = $resolver->merge(array_reverse($input));

        $this->assertSame($first, $second);
    }
}
