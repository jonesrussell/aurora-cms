<?php

declare(strict_types=1);

namespace Waaseyaa\Ingestion\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Ingestion\ValidationResult;

#[CoversClass(ValidationResult::class)]
final class ValidationResultTest extends TestCase
{
    #[Test]
    public function emptyErrorsIsValid(): void
    {
        $result = new ValidationResult();
        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->errors);
    }

    #[Test]
    public function withErrorsIsInvalid(): void
    {
        $result = new ValidationResult(['Missing field: name']);
        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->errors);
    }
}
