<?php

declare(strict_types=1);

namespace Aurora\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a value is within a set of allowed values.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class AllowedValues extends Constraint
{
    public string $message = 'The value "{{ value }}" is not a valid choice. Accepted values are: {{ choices }}.';

    /**
     * @param array<int, mixed> $values The list of allowed values.
     * @param bool $strict Whether to use strict type comparison.
     * @param string|null $message Custom violation message.
     * @param string[]|string|null $groups Validation groups.
     * @param mixed|null $payload Payload for external use.
     */
    public function __construct(
        public readonly array $values = [],
        public readonly bool $strict = true,
        ?string $message = null,
        array|string|null $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
