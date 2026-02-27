<?php

declare(strict_types=1);

namespace Aurora\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a value is not empty.
 *
 * Unlike Symfony's NotBlank, this also rejects empty arrays and null values.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class NotEmpty extends Constraint
{
    public string $message = 'This value must not be empty.';

    /**
     * @param string|null $message Custom violation message.
     * @param string[]|string|null $groups Validation groups.
     * @param mixed|null $payload Payload for external use.
     */
    public function __construct(
        ?string $message = null,
        array|string|null $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
