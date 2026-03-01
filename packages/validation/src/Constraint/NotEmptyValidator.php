<?php

declare(strict_types=1);

namespace Waaseyaa\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a value is not empty.
 *
 * Rejects null, empty strings, whitespace-only strings, and empty arrays.
 */
final class NotEmptyValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotEmpty) {
            throw new UnexpectedTypeException($constraint, NotEmpty::class);
        }

        if ($value === null) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        if (\is_string($value) && trim($value) === '') {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        if (\is_array($value) && $value === []) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
