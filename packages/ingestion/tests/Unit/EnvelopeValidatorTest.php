<?php

declare(strict_types=1);

namespace Waaseyaa\Ingestion\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Ingestion\EnvelopeValidator;
use Waaseyaa\Ingestion\ValidationResult;

#[CoversClass(EnvelopeValidator::class)]
final class EnvelopeValidatorTest extends TestCase
{
    private EnvelopeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new class extends EnvelopeValidator {
            protected function supportedVersions(): array
            {
                return ['1.0'];
            }

            protected function supportedEntityTypes(): array
            {
                return ['article', 'note'];
            }

            protected function validateEntityData(string $entityType, array $data): array
            {
                if ($entityType === 'article' && empty($data['title'])) {
                    return ['Article requires title.'];
                }
                return [];
            }
        };
    }

    #[Test]
    public function validEnvelopePasses(): void
    {
        $result = $this->validator->validate($this->envelope());
        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function missingRequiredFieldFails(): void
    {
        $envelope = $this->envelope();
        unset($envelope['version']);
        $result = $this->validator->validate($envelope);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('version', $result->errors[0]);
    }

    #[Test]
    public function unsupportedVersionFails(): void
    {
        $envelope = $this->envelope(['version' => '99.0']);
        $result = $this->validator->validate($envelope);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Unsupported version', $result->errors[0]);
    }

    #[Test]
    public function unsupportedEntityTypeFails(): void
    {
        $envelope = $this->envelope(['entity_type' => 'widget']);
        $result = $this->validator->validate($envelope);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Unsupported entity type', $result->errors[0]);
    }

    #[Test]
    public function nonArrayDataFails(): void
    {
        $envelope = $this->envelope(['data' => 'not-an-array']);
        $result = $this->validator->validate($envelope);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Data field must be an array', $result->errors[0]);
    }

    #[Test]
    public function entitySpecificValidationRuns(): void
    {
        $envelope = $this->envelope(['data' => ['body' => 'no title']]);
        $result = $this->validator->validate($envelope);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('Article requires title', $result->errors[0]);
    }

    #[Test]
    public function entityTypeWithNoExtraRulesPasses(): void
    {
        $envelope = $this->envelope(['entity_type' => 'note', 'data' => ['text' => 'hello']]);
        $result = $this->validator->validate($envelope);
        $this->assertTrue($result->isValid());
    }

    /** @param array<string, mixed> $overrides */
    private function envelope(array $overrides = []): array
    {
        return array_merge([
            'payload_id' => 'test-001',
            'version' => '1.0',
            'source' => 'test',
            'snapshot_type' => 'full',
            'timestamp' => '2026-01-01T00:00:00Z',
            'entity_type' => 'article',
            'source_url' => 'https://example.com/article/1',
            'data' => ['title' => 'Test Article'],
        ], $overrides);
    }
}
