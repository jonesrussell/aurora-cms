<?php

declare(strict_types=1);

namespace Waaseyaa\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a referenced entity exists.
 *
 * For v0.1.0, uses a callback-based approach: the caller provides a callable
 * that receives the entity ID and returns true if the entity exists.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class EntityExists extends Constraint
{
    public string $message = 'The referenced {{ entity_type }} with ID "{{ value }}" does not exist.';

    /**
     * @param string $entityTypeId The entity type being referenced.
     * @param callable(mixed): bool $existsChecker Returns true if entity exists.
     * @param string|null $message Custom violation message.
     * @param string[]|string|null $groups Validation groups.
     * @param mixed|null $payload Payload for external use.
     */
    public function __construct(
        public readonly string $entityTypeId = '',
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
