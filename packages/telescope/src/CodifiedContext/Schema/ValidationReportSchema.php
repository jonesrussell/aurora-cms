<?php

declare(strict_types=1);

namespace Waaseyaa\Telescope\CodifiedContext\Schema;

final class ValidationReportSchema
{
    /**
     * Validate a validation report data array against required keys and types.
     *
     * @param array<string, mixed> $data
     */
    public static function validate(array $data): bool
    {
        if (!isset($data['session_id']) || !is_string($data['session_id'])) {
            return false;
        }

        if (!array_key_exists('drift_score', $data) || !is_int($data['drift_score'])) {
            return false;
        }

        if (!array_key_exists('issues', $data) || !is_array($data['issues'])) {
            return false;
        }

        if (!isset($data['recommendation']) || !is_string($data['recommendation'])) {
            return false;
        }

        if (!isset($data['validated_at']) || !is_string($data['validated_at'])) {
            return false;
        }

        return true;
    }
}
