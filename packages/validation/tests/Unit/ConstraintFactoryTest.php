<?php

declare(strict_types=1);

namespace Waaseyaa\Validation\Tests\Unit;

use Waaseyaa\Validation\Constraint\AllowedValues;
use Waaseyaa\Validation\Constraint\EntityExists;
use Waaseyaa\Validation\Constraint\NotEmpty;
use Waaseyaa\Validation\Constraint\SafeMarkup;
use Waaseyaa\Validation\Constraint\UniqueField;
use Waaseyaa\Validation\ConstraintFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;

final class ConstraintFactoryTest extends TestCase
{
    public function testRequired(): void
    {
        $constraint = ConstraintFactory::required();
        $this->assertInstanceOf(NotEmpty::class, $constraint);
        $this->assertSame('This value must not be empty.', $constraint->message);
    }

    public function testRequiredWithCustomMessage(): void
    {
        $constraint = ConstraintFactory::required('Field is required.');
        $this->assertInstanceOf(NotEmpty::class, $constraint);
        $this->assertSame('Field is required.', $constraint->message);
    }

    public function testMaxLength(): void
    {
        $constraint = ConstraintFactory::maxLength(255);
        $this->assertInstanceOf(Length::class, $constraint);
        $this->assertSame(255, $constraint->max);
    }

    public function testMaxLengthWithCustomMessage(): void
    {
        $constraint = ConstraintFactory::maxLength(100, 'Too long!');
        $this->assertInstanceOf(Length::class, $constraint);
        $this->assertSame(100, $constraint->max);
        $this->assertSame('Too long!', $constraint->maxMessage);
    }

    public function testAllowedValues(): void
    {
        $constraint = ConstraintFactory::allowedValues(['a', 'b', 'c']);
        $this->assertInstanceOf(AllowedValues::class, $constraint);
        $this->assertSame(['a', 'b', 'c'], $constraint->values);
        $this->assertTrue($constraint->strict);
    }

    public function testAllowedValuesNonStrict(): void
    {
        $constraint = ConstraintFactory::allowedValues([1, 2], strict: false);
        $this->assertInstanceOf(AllowedValues::class, $constraint);
        $this->assertFalse($constraint->strict);
    }

    public function testUnique(): void
    {
        $checker = fn (mixed $value): bool => false;
        $constraint = ConstraintFactory::unique('node', 'title', $checker);

        $this->assertInstanceOf(UniqueField::class, $constraint);
        $this->assertSame('node', $constraint->entityTypeId);
        $this->assertSame('title', $constraint->fieldName);
        $this->assertSame($checker, $constraint->existsChecker);
    }

    public function testSafeMarkup(): void
    {
        $constraint = ConstraintFactory::safeMarkup();
        $this->assertInstanceOf(SafeMarkup::class, $constraint);
        $this->assertSame(SafeMarkup::DEFAULT_ALLOWED_TAGS, $constraint->allowedTags);
    }

    public function testSafeMarkupWithCustomTags(): void
    {
        $constraint = ConstraintFactory::safeMarkup(['p', 'br', 'a']);
        $this->assertInstanceOf(SafeMarkup::class, $constraint);
        $this->assertSame(['p', 'br', 'a'], $constraint->allowedTags);
    }

    public function testEntityExists(): void
    {
        $checker = fn (mixed $id): bool => true;
        $constraint = ConstraintFactory::entityExists('node', $checker);

        $this->assertInstanceOf(EntityExists::class, $constraint);
        $this->assertSame('node', $constraint->entityTypeId);
        $this->assertSame($checker, $constraint->existsChecker);
    }

    public function testEntityExistsWithCustomMessage(): void
    {
        $checker = fn (mixed $id): bool => true;
        $constraint = ConstraintFactory::entityExists('user', $checker, 'User not found.');

        $this->assertInstanceOf(EntityExists::class, $constraint);
        $this->assertSame('User not found.', $constraint->message);
    }
}
