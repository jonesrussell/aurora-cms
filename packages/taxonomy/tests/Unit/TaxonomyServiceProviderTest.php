<?php

declare(strict_types=1);

namespace Waaseyaa\Taxonomy\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Taxonomy\TaxonomyServiceProvider;
use Waaseyaa\Taxonomy\Term;
use Waaseyaa\Taxonomy\Vocabulary;

#[CoversClass(TaxonomyServiceProvider::class)]
final class TaxonomyServiceProviderTest extends TestCase
{
    #[Test]
    public function registers_term_and_vocabulary(): void
    {
        $provider = new TaxonomyServiceProvider();
        $provider->register();

        $entityTypes = $provider->getEntityTypes();

        $this->assertCount(2, $entityTypes);
        $this->assertSame('taxonomy_term', $entityTypes[0]->id());
        $this->assertSame(Term::class, $entityTypes[0]->getClass());
        $this->assertSame('taxonomy_vocabulary', $entityTypes[1]->id());
        $this->assertSame(Vocabulary::class, $entityTypes[1]->getClass());
    }

    #[Test]
    public function term_has_field_definitions(): void
    {
        $provider = new TaxonomyServiceProvider();
        $provider->register();

        $fields = $provider->getEntityTypes()[0]->getFieldDefinitions();

        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('weight', $fields);
        $this->assertArrayHasKey('parent_id', $fields);
        $this->assertArrayHasKey('status', $fields);
    }

    #[Test]
    public function vocabulary_has_field_definitions(): void
    {
        $provider = new TaxonomyServiceProvider();
        $provider->register();

        $fields = $provider->getEntityTypes()[1]->getFieldDefinitions();

        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('weight', $fields);
    }
}
