<?php

declare(strict_types=1);

namespace Waaseyaa\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a field value is unique across entities of the same type.
 *
 * For v0.1.0, uses a callback-based approach: the caller provides a callable
 * that receives the value and returns true if the value is already taken.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class UniqueField extends Constraint
{
    public string $message = 'The value "{{ value }}" is already in use for {{ entity_type }}.{{ field }}.';

    /**
     * @param string $entityTypeId The entity type machine name.
     * @param string $fieldName The field machine name.
     * @param callable(mixed): bool $existsChecker Returns true if value already exists.
     * @param string|null $message Custom violation message.
     * @param string[]|string|null $groups Validation groups.
     * @param mixed|null $payload Payload for external use.
     */
    public function __construct(
        public readonly string $entityTypeId = '',
        public readonly string $fieldName = '',
        /** @var callable(mixed): bool */
        public readonly mixed $existsChecker = null,
        ?string $message = null,
        array|string|null $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
